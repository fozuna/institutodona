<?php
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
$suffix = 'closeag_' . substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'colaborador_ids' => [], 'setor_ids' => [], 'funcao_ids' => [], 'departamento_ids' => [],
    'treinamento_id' => 0, 'cliente_ids' => [], 'agenda_id' => 0,
];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['agenda_id'])) {
            $pdo->prepare('DELETE FROM treinamento_auditoria_logs WHERE agenda_id = :id')->execute(['id' => $cleanup['agenda_id']]);
            $pdo->prepare('DELETE FROM treinamento_participantes WHERE agenda_id = :id')->execute(['id' => $cleanup['agenda_id']]);
            $pdo->prepare('DELETE FROM treinamentos_agenda WHERE id = :id')->execute(['id' => $cleanup['agenda_id']]);
        }
        if (!empty($cleanup['treinamento_id'])) {
            $pdo->prepare('DELETE FROM treinamento_colaboradores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_setores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_funcoes WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $cleanup['treinamento_id']]);
        }
        foreach ($cleanup['colaborador_ids'] as $id) { $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['funcao_ids'] as $id) { $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$funcoes = new FuncaoModel();
$treinamentoModel = new TreinamentoModel();
$agendaModel = new TreinamentoAgendaModel();

$clienteId = $clientes->create(['nome_empresa' => 'Cliente CloseAgenda ' . $suffix, 'CNPJ' => '77.333.1' . substr($suffix, 0, 2) . '/0001-77', 'contato' => 'Teste']);
if ($clienteId <= 0) failFast('Falha ao criar cliente de teste');
$cleanup['cliente_ids'] = [$clienteId];

$depId = $departamentos->create(['nome' => 'Dep CloseAgenda ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorId = $setores->create(['nome' => 'Setor CloseAgenda ' . $suffix, 'departamento_id' => $depId]);
$funcaoId = $funcoes->create(['nome' => 'Funcao CloseAgenda ' . $suffix, 'setor_id' => $setorId]);
if (!$depId || !$setorId || !$funcaoId) failFast('Falha ao criar estrutura de departamento/setor/função de teste');
$cleanup['departamento_ids'] = [$depId];
$cleanup['setor_ids'] = [$setorId];
$cleanup['funcao_ids'] = [$funcaoId];

$stmt = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n, :e, :f, :c)');
$stmt->execute(['n' => 'Colaborador Presente ' . $suffix, 'e' => 'presente.' . $suffix . '@test.local', 'f' => $funcaoId, 'c' => $clienteId]);
$colaboradorPresente = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador Ausente ' . $suffix, 'e' => 'ausente.' . $suffix . '@test.local', 'f' => $funcaoId, 'c' => $clienteId]);
$colaboradorAusente = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador Extra ' . $suffix, 'e' => 'extra.' . $suffix . '@test.local', 'f' => $funcaoId, 'c' => $clienteId]);
$colaboradorExtra = (int)$pdo->lastInsertId();
$cleanup['colaborador_ids'] = [$colaboradorPresente, $colaboradorAusente, $colaboradorExtra];
ok('Criou colaboradores Presente, Ausente e Extra');

$treinamentoId = $treinamentoModel->create([
    'nome' => 'Treinamento CloseAgenda ' . $suffix,
    'objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '4',
    'cliente_id' => $clienteId, 'departamento_id' => $depId, 'periodicidade' => 'anual', 'fornecedor' => 'Fornecedor',
    'setor_ids' => [$setorId], 'funcao_ids' => [$funcaoId],
]);
if ($treinamentoId <= 0) failFast('Falha ao criar treinamento de teste');
$cleanup['treinamento_id'] = $treinamentoId;
$treinamentoModel->syncColaboradores($treinamentoId, [$colaboradorPresente, $colaboradorAusente]);
ok('Criou treinamento e vinculou os 2 colaboradores como pendentes');

// Data futura de propósito: a turma NÃO deve ficar "encerrada" implicitamente por data,
// só através do botão/ação explícita de encerramento.
$agendaId = $agendaModel->create([
    'treinamento_id' => $treinamentoId,
    'data' => date('Y-m-d H:i:s', strtotime('+10 days')),
    'unidade_id' => $clienteId,
    'responsavel_id' => null,
    'instrutor' => 'Instrutor Teste',
    'local' => 'Sala Teste',
    'observacoes' => 'Turma de teste',
]);
if ($agendaId <= 0) failFast('Falha ao criar agenda de teste');
$cleanup['agenda_id'] = $agendaId;
$agendaModel->syncParticipants($agendaId, [$colaboradorPresente, $colaboradorAusente]);
$agendaModel->savePresence($agendaId, [$colaboradorPresente => 1], [$colaboradorPresente => '08:00'], [$colaboradorPresente => '12:00'], []);
ok('Criou agenda futura, adicionou os 2 participantes e confirmou presença só do colaborador Presente');

function statusDetalheDoColaborador(PDO $pdo, int $treinamentoId, int $colaboradorId): ?string
{
    $stmt = $pdo->prepare('SELECT status, status_detalhe FROM treinamento_colaboradores WHERE treinamento_id = :t AND colaborador_id = :c');
    $stmt->execute(['t' => $treinamentoId, 'c' => $colaboradorId]);
    $row = $stmt->fetch();
    return $row ? (string)($row['status_detalhe'] ?? '') : null;
}

// 1) Antes de encerrar: agenda aberta, colaborador ausente ainda não está "interrompido"
//    (a data é futura e ninguém encerrou a turma manualmente).
$agendaAntes = $agendaModel->find($agendaId);
if ($agendaAntes === null || !empty($agendaAntes['encerrada_em'])) {
    failFast('Agenda não deveria estar encerrada antes da ação de fechamento');
}
$detalheAntes = statusDetalheDoColaborador($pdo, $treinamentoId, $colaboradorAusente);
if ($detalheAntes === 'interrompido') {
    failFast('Colaborador ausente não deveria estar "interrompido" antes do encerramento manual da turma (agenda ainda é futura e aberta)');
}
ok('Antes de encerrar: agenda aberta e colaborador ausente ainda não está marcado como não participante');

// 2) Encerrar a turma.
$closed = $agendaModel->closeAgenda($agendaId);
if ($closed !== true) failFast('closeAgenda() deveria retornar true ao encerrar uma turma aberta');
$agendaDepois = $agendaModel->find($agendaId);
if (empty($agendaDepois['encerrada_em'])) failFast('encerrada_em deveria estar preenchido após closeAgenda()');
if ((int)($agendaDepois['encerrada_por'] ?? 0) !== 1) failFast('encerrada_por deveria ser o id do usuário da sessão (1)');
ok('closeAgenda() encerra a turma e registra quem encerrou');

$logStmt = $pdo->prepare("SELECT COUNT(*) FROM treinamento_auditoria_logs WHERE agenda_id = :id AND acao = 'turma_encerrada'");
$logStmt->execute(['id' => $agendaId]);
if ((int)$logStmt->fetchColumn() < 1) failFast('Deveria existir um log de auditoria "turma_encerrada" para a agenda');
ok('Registra log de auditoria do encerramento');

// 3) Após encerrar: colaborador ausente vira "interrompido" (não participou); colaborador presente não.
$detalheAusenteDepois = statusDetalheDoColaborador($pdo, $treinamentoId, $colaboradorAusente);
if ($detalheAusenteDepois !== 'interrompido') {
    failFast('Colaborador ausente deveria ficar com status_detalhe "interrompido" após o encerramento manual da turma, obteve: ' . ($detalheAusenteDepois ?? '(nulo)'));
}
$detalhePresenteDepois = statusDetalheDoColaborador($pdo, $treinamentoId, $colaboradorPresente);
if ($detalhePresenteDepois === 'interrompido') {
    failFast('Colaborador que confirmou presença não deveria ficar marcado como "interrompido"');
}
ok('Após encerrar: colaborador ausente é contabilizado como não participante; colaborador presente não é afetado');

// 4) O dashboard de Treinamentos deve refletir o colaborador ausente em "não participaram".
$dashboard = $treinamentoModel->dashboard(['cliente_id' => $clienteId]);
$naoParticiparamIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $dashboard['nao_participaram'] ?? []);
if (!in_array($colaboradorAusente, $naoParticiparamIds, true)) {
    failFast('dashboard()["nao_participaram"] deveria conter o colaborador ausente');
}
if ((int)($dashboard['resumo']['total_nao_participaram'] ?? 0) < 1) {
    failFast('dashboard()["resumo"]["total_nao_participaram"] deveria ser >= 1');
}
ok('Dashboard de Treinamentos reflete o colaborador ausente em "não participaram" e no resumo');

// 5) Idempotência: encerrar de novo não deve alterar nada (proteção WHERE encerrada_em IS NULL).
$closedAgain = $agendaModel->closeAgenda($agendaId);
if ($closedAgain !== false) failFast('closeAgenda() em uma turma já encerrada deveria retornar false');
$agendaFinal = $agendaModel->find($agendaId);
if ($agendaFinal['encerrada_em'] !== $agendaDepois['encerrada_em'] || (int)$agendaFinal['encerrada_por'] !== (int)$agendaDepois['encerrada_por']) {
    failFast('Encerrar uma turma já encerrada não deveria alterar encerrada_em/encerrada_por');
}
ok('Encerrar uma turma já encerrada é uma operação segura e idempotente (não regrava nada)');

// 6) Guarda no controller: não deve ser possível adicionar novos participantes a uma turma encerrada.
// Executado em subprocesso porque addParticipantes() sempre termina em redirect() (header + exit).
$probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'treinamentos_add_participantes_probe.php';
$cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg((string)$agendaId) . ' ' . escapeshellarg((string)$colaboradorExtra);
@exec($cmd . ' 2>&1');
$participantsDepois = $agendaModel->participants($agendaId);
$participantIdsDepois = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $participantsDepois);
if (in_array($colaboradorExtra, $participantIdsDepois, true)) {
    failFast('addParticipantes() não deveria aceitar novos participantes numa turma já encerrada');
}
ok('addParticipantes() rejeita novos participantes numa turma encerrada');

echo "Treinamentos close_agenda regression tests passed.\n";
ob_end_flush();
