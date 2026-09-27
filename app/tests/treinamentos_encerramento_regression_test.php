<?php
/**
 * Encerramento manual de treinamento (independente da cobertura de 100%).
 *
 * Regras cobertas:
 *  - Instituto e Cliente Admin encerram; justificativa opcional.
 *  - Turmas futuras nao iniciadas e sem presenca/certificado sao excluidas;
 *    turmas passadas ou com presenca registrada sao preservadas.
 *  - Quem nao concluiu passa a "nao participou" (pendente/interrompido).
 *  - Listagem mostra "Encerrado"; dashboard separa encerrados e cobertura media.
 *  - Encerrado bloqueia novas turmas.
 *  - Somente o Instituto reabre; reabrir recalcula os status.
 */
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$suffix = 'encerr_' . substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'colaborador_ids' => [], 'setor_ids' => [], 'funcao_ids' => [], 'departamento_ids' => [],
    'treinamento_id' => 0, 'cliente_ids' => [],
];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['treinamento_id'])) {
            $id = $cleanup['treinamento_id'];
            $pdo->prepare('DELETE FROM treinamento_auditoria_logs WHERE treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE tp FROM treinamento_participantes tp JOIN treinamentos_agenda ta ON ta.id = tp.agenda_id WHERE ta.treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM treinamentos_agenda WHERE treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM treinamento_colaboradores WHERE treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM treinamento_setores WHERE treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM treinamento_funcoes WHERE treinamento_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['colaborador_ids'] as $id) { $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['funcao_ids'] as $id) { $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$treinamentoModel = new TreinamentoModel();
$agendaModel = new TreinamentoAgendaModel();

$clienteId = (new ClienteModel())->create(['nome_empresa' => 'Cliente Encerramento ' . $suffix, 'CNPJ' => '66.444.1' . substr($suffix, 7, 2) . '/0001-66', 'contato' => 'Teste']);
if ($clienteId <= 0) failFast('Falha ao criar cliente de teste');
$cleanup['cliente_ids'] = [$clienteId];
$depId = (new DepartamentoModel())->create(['nome' => 'Dep Encerr ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorId = (new SetorModel())->create(['nome' => 'Setor Encerr ' . $suffix, 'departamento_id' => $depId]);
$funcaoId = (new FuncaoModel())->create(['nome' => 'Funcao Encerr ' . $suffix, 'setor_id' => $setorId]);
if (!$depId || !$setorId || !$funcaoId) failFast('Falha ao criar estrutura de departamento/setor/função');
$cleanup['departamento_ids'] = [$depId];
$cleanup['setor_ids'] = [$setorId];
$cleanup['funcao_ids'] = [$funcaoId];

$stmt = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n, :e, :f, :c)');
$colab = [];
foreach (['presente', 'ausente', 'nao_agendado'] as $k) {
    $stmt->execute(['n' => 'Colab ' . $k . ' ' . $suffix, 'e' => $k . '.' . $suffix . '@test.local', 'f' => $funcaoId, 'c' => $clienteId]);
    $colab[$k] = (int)$pdo->lastInsertId();
}
$cleanup['colaborador_ids'] = array_values($colab);

$treinamentoId = $treinamentoModel->create([
    'nome' => 'Treinamento Encerramento ' . $suffix,
    'objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '4',
    'cliente_id' => $clienteId, 'departamento_id' => $depId, 'periodicidade' => 'avulso', 'fornecedor' => 'Fornecedor',
    'setor_ids' => [$setorId], 'funcao_ids' => [$funcaoId],
]);
if ($treinamentoId <= 0) failFast('Falha ao criar treinamento de teste');
$cleanup['treinamento_id'] = $treinamentoId;
$treinamentoModel->syncColaboradores($treinamentoId, array_values($colab));
ok('Criou treinamento com 3 colaboradores vinculados');

$novaAgenda = static function (string $inicio, string $fim) use ($agendaModel, $treinamentoId, $clienteId): int {
    $id = $agendaModel->create([
        'treinamento_id' => $treinamentoId,
        'data' => date('Y-m-d H:i:s', strtotime($inicio)),
        'data_fim' => date('Y-m-d H:i:s', strtotime($fim)),
        'unidade_id' => $clienteId,
        'instrutor' => 'Instrutor', 'local' => 'Sala', 'observacoes' => '',
    ]);
    if ($id <= 0) failFast('Falha ao criar agenda de teste');
    return $id;
};

// Turma passada: presente com 4h (carga cumprida) e ausente sem presenca.
$agendaPassada = $novaAgenda('-3 days 08:00', '-3 days 12:00');
$agendaModel->syncParticipants($agendaPassada, [$colab['presente'], $colab['ausente']]);
$agendaModel->savePresence($agendaPassada, [$colab['presente'] => 1], [$colab['presente'] => '08:00'], [$colab['presente'] => '12:00'], []);
$agendaModel->closeAgenda($agendaPassada);
// Turma futura sem presenca: deve ser excluida no encerramento.
$agendaFuturaVazia = $novaAgenda('+10 days 08:00', '+10 days 12:00');
$agendaModel->syncParticipants($agendaFuturaVazia, [$colab['nao_agendado']]);
// Turma futura COM presenca registrada: deve ser preservada.
$agendaFuturaComPresenca = $novaAgenda('+12 days 08:00', '+12 days 12:00');
$agendaModel->syncParticipants($agendaFuturaComPresenca, [$colab['ausente']]);
$agendaModel->savePresence($agendaFuturaComPresenca, [$colab['ausente'] => 1], [], [], []);
ok('Criou turma passada encerrada, turma futura vazia e turma futura com presença');

function statusColab(PDO $pdo, int $treinamentoId, int $colaboradorId): array
{
    $stmt = $pdo->prepare('SELECT status, status_detalhe FROM treinamento_colaboradores WHERE treinamento_id = :t AND colaborador_id = :c');
    $stmt->execute(['t' => $treinamentoId, 'c' => $colaboradorId]);
    $row = $stmt->fetch() ?: [];
    return [(string)($row['status'] ?? ''), (string)($row['status_detalhe'] ?? '')];
}
function agendaExiste(PDO $pdo, int $agendaId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM treinamentos_agenda WHERE id = :id');
    $stmt->execute(['id' => $agendaId]);
    return (int)$stmt->fetchColumn() === 1;
}

$treinamentoModel->refreshStatuses($treinamentoId);
[$stNaoAg, $detNaoAg] = statusColab($pdo, $treinamentoId, $colab['nao_agendado']);
if ($detNaoAg === 'interrompido') failFast('Antes do encerramento, colaborador só com turma futura não deveria estar "interrompido"');
if ($treinamentoModel->turmasFuturasExcluiveis($treinamentoId) !== 1) failFast('Deveria haver exatamente 1 turma futura excluível');
$cobAntes = $treinamentoModel->cobertura($treinamentoId);
if ($cobAntes['concluidos'] !== 1 || $cobAntes['total'] !== 3 || $cobAntes['pct'] !== 33) {
    failFast('Cobertura antes do encerramento deveria ser 1/3 (33%), obteve ' . json_encode($cobAntes));
}
ok('Antes de encerrar: cobertura 33%, 1 turma futura excluível, ninguém marcado como não participou indevidamente');

// Controller: Cliente Admin encerra (permissao de gestao do treinamento).
$probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'treinamentos_encerramento_probe.php';
$runProbe = static function (string $acao, string $perfil) use ($probe, $treinamentoId, $clienteId): void {
    @exec('php ' . escapeshellarg($probe) . ' ' . escapeshellarg($acao) . ' ' . escapeshellarg($perfil) . ' ' . escapeshellarg((string)$treinamentoId) . ' ' . escapeshellarg((string)$clienteId) . ' 2>&1');
};
$runProbe('encerrar', 'cliente_admin');
$item = $treinamentoModel->find($treinamentoId);
if (empty($item['encerrado_em'])) failFast('Cliente Admin deveria conseguir encerrar o treinamento');
if (($item['encerramento_justificativa'] ?? null) !== 'Probe') failFast('Justificativa deveria ter sido gravada');
ok('Cliente Admin encerra o treinamento com justificativa');

if (agendaExiste($pdo, $agendaFuturaVazia)) failFast('Turma futura sem presença deveria ter sido excluída');
if (!agendaExiste($pdo, $agendaFuturaComPresenca)) failFast('Turma futura com presença registrada NÃO deveria ser excluída');
if (!agendaExiste($pdo, $agendaPassada)) failFast('Turma passada NÃO deveria ser excluída');
ok('Exclui só a turma futura sem presença; preserva turma passada e turma com presença');

[$stP] = statusColab($pdo, $treinamentoId, $colab['presente']);
[$stA, $detA] = statusColab($pdo, $treinamentoId, $colab['ausente']);
[$stN, $detN] = statusColab($pdo, $treinamentoId, $colab['nao_agendado']);
if ($stP !== 'concluido') failFast('Colaborador que concluiu deveria continuar concluído, obteve ' . $stP);
if ($stA !== 'pendente' || $detA !== 'interrompido') failFast("Ausente deveria ser pendente/interrompido, obteve $stA/$detA");
if ($stN !== 'pendente' || $detN !== 'interrompido') failFast("Não agendado deveria ser pendente/interrompido, obteve $stN/$detN");
ok('Quem não concluiu passa a contar como "não participou"; quem concluiu não muda');

$logStmt = $pdo->prepare("SELECT detalhes_json FROM treinamento_auditoria_logs WHERE treinamento_id = :id AND acao = 'treinamento_encerrado' ORDER BY id DESC LIMIT 1");
$logStmt->execute(['id' => $treinamentoId]);
$log = json_decode((string)$logStmt->fetchColumn(), true);
if (!is_array($log) || count($log['turmas_excluidas'] ?? []) !== 1 || (int)($log['turmas_excluidas'][0]['agenda_id'] ?? 0) !== $agendaFuturaVazia) {
    failFast('Log de auditoria do encerramento deveria registrar a turma excluída');
}
if ((int)($log['cobertura']['pct'] ?? -1) !== 33) failFast('Log de auditoria deveria registrar a cobertura final (33%)');
ok('Auditoria registra cobertura final e turmas excluídas');

$again = $treinamentoModel->encerrar($treinamentoId, 'de novo');
if ($again['ok'] !== false) failFast('Encerrar um treinamento já encerrado deveria falhar');
$itemAgain = $treinamentoModel->find($treinamentoId);
if ($itemAgain['encerrado_em'] !== $item['encerrado_em'] || $itemAgain['encerramento_justificativa'] !== 'Probe') {
    failFast('Encerrar de novo não deveria alterar os dados do encerramento');
}
ok('Encerrar de novo é recusado sem alterar nada');

$rows = $treinamentoModel->paginateIndex(['cliente_id' => $clienteId], 1, 25);
$row = null;
foreach ($rows as $r) { if ((int)$r['id'] === $treinamentoId) { $row = $r; } }
if (!$row || ($row['status_resumo'] ?? '') !== 'Encerrado' || (int)($row['progresso_pct'] ?? -1) !== 33) {
    failFast('Listagem deveria mostrar status "Encerrado" com cobertura 33%, obteve ' . json_encode([$row['status_resumo'] ?? null, $row['progresso_pct'] ?? null]));
}
ok('Listagem mostra "Encerrado" mantendo a cobertura de 33%');

$dash = $treinamentoModel->dashboard(['cliente_id' => $clienteId]);
if ((int)($dash['resumo']['treinamentos_encerrados'] ?? 0) !== 1 || (int)($dash['resumo']['treinamentos_distintos'] ?? 0) !== 1) {
    failFast('Dashboard deveria contar 1 encerrado de 1 treinamento, obteve ' . json_encode($dash['resumo']));
}
if (abs((float)($dash['resumo']['cobertura_media'] ?? 0) - 33.3) > 0.05) {
    failFast('Dashboard deveria ter cobertura média 33,3%, obteve ' . json_encode($dash['resumo']['cobertura_media'] ?? null));
}
ok('Dashboard separa encerrados (1 de 1) e cobertura média (33,3%)');

$agendasAntes = count($agendaModel->listByTreinamento($treinamentoId));
$runProbe('store_agenda', 'instituto');
if (count($agendaModel->listByTreinamento($treinamentoId)) !== $agendasAntes) failFast('Não deveria ser possível agendar turma em treinamento encerrado');
ok('Treinamento encerrado bloqueia novas turmas');

$runProbe('reabrir', 'cliente_admin');
if (empty($treinamentoModel->find($treinamentoId)['encerrado_em'])) failFast('Cliente Admin NÃO deveria conseguir reabrir');
ok('Cliente Admin não consegue reabrir');

$runProbe('reabrir', 'instituto');
if (!empty($treinamentoModel->find($treinamentoId)['encerrado_em'])) failFast('Instituto deveria conseguir reabrir');
[$stN2, $detN2] = statusColab($pdo, $treinamentoId, $colab['nao_agendado']);
if ($detN2 === 'interrompido') failFast('Após reabrir, colaborador nunca agendado deveria voltar a pendente (não "interrompido")');
[$stP2] = statusColab($pdo, $treinamentoId, $colab['presente']);
if ($stP2 !== 'concluido') failFast('Após reabrir, quem concluiu deveria continuar concluído');
if (agendaExiste($pdo, $agendaFuturaVazia)) failFast('Turma excluída no encerramento não deveria voltar ao reabrir');
$logStmt = $pdo->prepare("SELECT COUNT(*) FROM treinamento_auditoria_logs WHERE treinamento_id = :id AND acao = 'treinamento_reaberto'");
$logStmt->execute(['id' => $treinamentoId]);
if ((int)$logStmt->fetchColumn() !== 1) failFast('Deveria existir log de auditoria da reabertura');
ok('Instituto reabre: status recalculados, turma excluída não volta, reabertura auditada');

// Card "Treinamentos" do dashboard conta treinamentos distintos, mesmo com
// turmas de instrutores diferentes (participacao tem 1 linha por instrutor).
$agendaOutroInstrutor = $agendaModel->create([
    'treinamento_id' => $treinamentoId,
    'data' => date('Y-m-d H:i:s', strtotime('-2 days 14:00')),
    'data_fim' => date('Y-m-d H:i:s', strtotime('-2 days 18:00')),
    'unidade_id' => $clienteId,
    'instrutor' => 'Outro Instrutor', 'local' => 'Sala', 'observacoes' => '',
]);
$agendaModel->syncParticipants($agendaOutroInstrutor, [$colab['nao_agendado']]);
$dash2 = $treinamentoModel->dashboard(['cliente_id' => $clienteId]);
if (count($dash2['participacao_treinamento'] ?? []) !== 2) failFast('Pré-condição: participação deveria ter 2 linhas (1 por instrutor)');
if ((int)($dash2['resumo']['treinamentos_monitorados'] ?? 0) !== 1) {
    failFast('Card "Treinamentos" deveria contar 1 treinamento distinto, obteve ' . (int)($dash2['resumo']['treinamentos_monitorados'] ?? 0));
}
ok('Dashboard conta treinamentos distintos mesmo com 2 instrutores');

echo "Treinamentos encerramento regression tests passed.\n";
