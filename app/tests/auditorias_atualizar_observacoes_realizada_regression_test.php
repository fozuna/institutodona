<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AuditoriasController;
use App\Core\Security;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

class AuditoriasControllerRedirectProbe extends AuditoriasController
{
    public ?string $lastRedirect = null;

    protected function redirect(string $url): void
    {
        $this->lastRedirect = $url;
    }
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$auditorias = new AuditoriaModel();

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['aud_a1' => 0, 'aud_a2' => 0, 'aud_b1' => 0, 'setor_a' => 0, 'setor_b' => 0, 'dep_a' => 0, 'dep_b' => 0, 'cliente_a' => 0, 'cliente_b' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        foreach (['aud_a1', 'aud_a2', 'aud_b1'] as $k) {
            if (!empty($cleanup[$k])) {
                $pdo->prepare('DELETE FROM auditoria_avaliacoes_log WHERE auditoria_id = :id')->execute(['id' => $cleanup[$k]]);
                $pdo->prepare('DELETE FROM auditoria_historico WHERE auditoria_id = :id')->execute(['id' => $cleanup[$k]]);
                $pdo->prepare('DELETE FROM auditoria_avaliacoes WHERE auditoria_id = :id')->execute(['id' => $cleanup[$k]]);
                $pdo->prepare('DELETE FROM auditoria_questoes WHERE auditoria_id = :id')->execute(['id' => $cleanup[$k]]);
                $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $cleanup[$k]]);
            }
        }
        if (!empty($cleanup['setor_a'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_a']]); }
        if (!empty($cleanup['setor_b'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_b']]); }
        if (!empty($cleanup['dep_a'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['dep_a']]); }
        if (!empty($cleanup['dep_b'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['dep_b']]); }
        if (!empty($cleanup['cliente_a'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_a']]); }
        if (!empty($cleanup['cliente_b'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_b']]); }
    } catch (\Throwable $e) {
    }
});

$clienteA = $clientes->create(['nome_empresa' => 'Cliente ObsFix A ' . $suffix, 'CNPJ' => '11.222.111/0001-' . substr($suffix, 0, 2), 'contato' => 'x']);
$clienteB = $clientes->create(['nome_empresa' => 'Cliente ObsFix B ' . $suffix, 'CNPJ' => '22.333.222/0001-' . substr($suffix, 0, 2), 'contato' => 'x']);
if ($clienteA <= 0 || $clienteB <= 0) failFast('Falha ao criar clientes de teste');
$cleanup['cliente_a'] = $clienteA;
$cleanup['cliente_b'] = $clienteB;

$depA = $departamentos->create(['nome' => 'Dep ObsFix A ' . $suffix, 'cliente_id' => $clienteA, 'cliente_ids' => [$clienteA]]);
$setorA = $setores->create(['nome' => 'Setor ObsFix A ' . $suffix, 'departamento_id' => $depA]);
$depB = $departamentos->create(['nome' => 'Dep ObsFix B ' . $suffix, 'cliente_id' => $clienteB, 'cliente_ids' => [$clienteB]]);
$setorB = $setores->create(['nome' => 'Setor ObsFix B ' . $suffix, 'departamento_id' => $depB]);
$cleanup['dep_a'] = $depA;
$cleanup['setor_a'] = $setorA;
$cleanup['dep_b'] = $depB;
$cleanup['setor_b'] = $setorB;
ok('Criou clientes, departamentos e setores para o teste');

$audA1 = $auditorias->create([
    'cliente_id' => $clienteA, 'setor_id' => $setorA, 'nome_auditoria' => 'Auditoria ObsFix A1 ' . $suffix, 'data_auditoria' => '2026-07-01',
    'questoes' => [
        ['pergunta' => 'Q1', 'referencia_esperada' => 'R1'],
        ['pergunta' => 'Q2', 'referencia_esperada' => 'R2'],
    ],
], 1);
$audB1 = $auditorias->create([
    'cliente_id' => $clienteB, 'setor_id' => $setorB, 'nome_auditoria' => 'Auditoria ObsFix B1 ' . $suffix, 'data_auditoria' => '2026-07-02',
    'questoes' => [
        ['pergunta' => 'Q1', 'referencia_esperada' => 'R1'],
    ],
], 1);
if ($audA1 <= 0 || $audB1 <= 0) failFast('Falha ao criar auditorias de teste');
$cleanup['aud_a1'] = $audA1;
$cleanup['aud_b1'] = $audB1;
ok('Criou auditorias de teste (cliente A com 2 questões, cliente B com 1 questão)');

$questoesA = $auditorias->questoesByAuditoria($audA1);
if (count($questoesA) !== 2) failFast('Auditoria A1 não tem as 2 questões esperadas');
$qA1 = (int)$questoesA[0]['id'];
$qA2 = (int)$questoesA[1]['id'];

$questoesB = $auditorias->questoesByAuditoria($audB1);
if (count($questoesB) !== 1) failFast('Auditoria B1 não tem a questão esperada');
$qB1 = (int)$questoesB[0]['id'];

$finA = $auditorias->finalizarAuditoria($audA1, [
    ['questao_id' => $qA1, 'conformidade' => 'conforme', 'observacoes' => 'Observação inicial Q1'],
    ['questao_id' => $qA2, 'conformidade' => 'conforme', 'observacoes' => 'Observação inicial Q2'],
], 1);
$finB = $auditorias->finalizarAuditoria($audB1, [
    ['questao_id' => $qB1, 'conformidade' => 'conforme', 'observacoes' => 'Observação inicial B'],
], 1);
if (!$finA || !$finB) failFast('Falha ao finalizar auditorias de teste (status Realizada)');
ok('Finalizou as duas auditorias (status "Realizada")');

// Helper para ler estado atual sem depender de cache.
$countLog = function (int $auditoriaId) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM auditoria_avaliacoes_log WHERE auditoria_id = :id');
    $stmt->execute(['id' => $auditoriaId]);
    return (int)$stmt->fetchColumn();
};
$countHist = function (int $auditoriaId) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = :id');
    $stmt->execute(['id' => $auditoriaId]);
    return (int)$stmt->fetchColumn();
};
$lockVersion = function (int $auditoriaId) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT lock_version FROM auditorias WHERE id = :id');
    $stmt->execute(['id' => $auditoriaId]);
    return (int)$stmt->fetchColumn();
};

// 1) Edição válida: muda só Q1 -> persiste, gera log e histórico, incrementa lock_version.
$lockBefore = $lockVersion($audA1);
$logBefore = $countLog($audA1);
$histBefore = $countHist($audA1);
try {
    $result = $auditorias->updateObservacoesRealizada($audA1, [$qA1 => 'Observação revisada Q1', $qA2 => 'Observação inicial Q2'], 1);
} catch (\Throwable $e) {
    failFast('updateObservacoesRealizada lançou exceção em edição válida: ' . get_class($e) . ': ' . $e->getMessage());
}
if (!$result['ok'] || $result['updated'] !== 1) failFast('Edição válida não retornou ok/updated=1: ' . json_encode($result));
$respostas = $auditorias->respostasByAuditoria($audA1);
if (trim((string)$respostas[$qA1]['observacoes']) !== 'Observação revisada Q1') failFast('Novo valor de observação não foi persistido');
if (trim((string)$respostas[$qA2]['observacoes']) !== 'Observação inicial Q2') failFast('Questão não alterada foi modificada indevidamente');
if ($countLog($audA1) !== $logBefore + 1) failFast('Log de alteração não foi criado (ou criado em excesso) para a edição válida');
if ($countHist($audA1) !== $histBefore + 1) failFast('Histórico não foi criado para a edição válida');
if ($lockVersion($audA1) !== $lockBefore + 1) failFast('lock_version não foi incrementado na edição válida');
ok('Edição válida de auditoria finalizada persiste, registra log/histórico e incrementa lock_version');

// 2) Old/new corretos no log.
$stmtLog = $pdo->prepare('SELECT old_observacoes, new_observacoes FROM auditoria_avaliacoes_log WHERE auditoria_id = :id AND questao_id = :qid ORDER BY id DESC LIMIT 1');
$stmtLog->execute(['id' => $audA1, 'qid' => $qA1]);
$logRow = $stmtLog->fetch();
if (!$logRow || $logRow['old_observacoes'] !== 'Observação inicial Q1' || $logRow['new_observacoes'] !== 'Observação revisada Q1') {
    failFast('Log não registrou valores antigo/novo corretamente: ' . json_encode($logRow));
}
ok('Log registra valores antigo e novo corretamente');

// 3) Submissão sem mudança real (no-op) -> updated=0, sem novo log/histórico/lock bump.
$lockBefore2 = $lockVersion($audA1);
$logBefore2 = $countLog($audA1);
$histBefore2 = $countHist($audA1);
$resultNoop = $auditorias->updateObservacoesRealizada($audA1, [$qA1 => 'Observação revisada Q1', $qA2 => 'Observação inicial Q2'], 1);
if (!$resultNoop['ok'] || $resultNoop['updated'] !== 0) failFast('Submissão sem mudança não retornou ok/updated=0: ' . json_encode($resultNoop));
if ($countLog($audA1) !== $logBefore2) failFast('Submissão sem mudança criou entrada de log fantasma');
if ($countHist($audA1) !== $histBefore2) failFast('Submissão sem mudança criou histórico fantasma');
if ($lockVersion($audA1) !== $lockBefore2) failFast('Submissão sem mudança incrementou lock_version indevidamente');
ok('Submissão sem alteração real não gera log, histórico nem incremento de lock_version');

// 4) Auditoria inexistente -> ok=false, not_found, sem exceção.
$resultMissing = $auditorias->updateObservacoesRealizada(999999999, [$qA1 => 'x'], 1);
if ($resultMissing['ok'] !== false) failFast('Auditoria inexistente deveria retornar ok=false');
if ($auditorias->getLastError() !== 'not_found') failFast('Auditoria inexistente deveria reportar lastError=not_found, obteve: ' . $auditorias->getLastError());
ok('Tentativa em auditoria inexistente retorna ok=false/not_found sem exceção');

// 5) Auditoria não finalizada (ex.: recém-criada, status "Agendada") -> rejeitada pela mesma via.
$audAberta = $auditorias->create(['cliente_id' => $clienteA, 'setor_id' => $setorA, 'nome_auditoria' => 'Auditoria ObsFix Aberta ' . $suffix, 'data_auditoria' => '2026-07-03', 'questoes' => [['pergunta' => 'Q1', 'referencia_esperada' => 'R1']]], 1);
$cleanup['aud_a2'] = $audAberta;
$questoesAberta = $auditorias->questoesByAuditoria($audAberta);
$resultAberta = $auditorias->updateObservacoesRealizada($audAberta, [(int)$questoesAberta[0]['id'] => 'x'], 1);
if ($resultAberta['ok'] !== false || $auditorias->getLastError() !== 'not_found') failFast('Auditoria não finalizada deveria ser rejeitada com not_found');
ok('Auditoria ainda não finalizada é rejeitada por esta via (fluxo específico de observações pós-conclusão)');

// 6) Conflito de concorrência: prev_lock_version incorreto -> rollback, sem alteração parcial.
$lockBefore3 = $lockVersion($audA1);
$logBefore3 = $countLog($audA1);
$resultConflict = $auditorias->updateObservacoesRealizada($audA1, [$qA1 => 'Tentativa com conflito'], 1, null, $lockBefore3 + 99);
if ($resultConflict['ok'] !== false) failFast('Conflito de concorrência deveria retornar ok=false');
if ($auditorias->getLastError() !== 'concurrency_conflict') failFast('Conflito de concorrência deveria reportar lastError=concurrency_conflict, obteve: ' . $auditorias->getLastError());
if ($lockVersion($audA1) !== $lockBefore3) failFast('lock_version mudou apesar do conflito de concorrência');
if ($countLog($audA1) !== $logBefore3) failFast('Log foi criado apesar do conflito de concorrência (rollback incompleto)');
$respostasAposConflito = $auditorias->respostasByAuditoria($audA1);
if (trim((string)$respostasAposConflito[$qA1]['observacoes']) !== 'Observação revisada Q1') failFast('Valor foi alterado apesar do conflito de concorrência');
ok('Conflito de concorrência causa rollback completo, sem alteração parcial de dados/log/lock_version');

// 7) Isolamento multiempresa: usuário restrito ao cliente B não pode editar auditoria do cliente A.
$_SESSION['user'] = [
    'id' => 999999997,
    'nome' => 'Cliente Admin B (fake)',
    'email' => 'clienteb.fake@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [$clienteB],
];
$logBefore4 = $countLog($audA1);
$resultCrossTenant = $auditorias->updateObservacoesRealizada($audA1, [$qA1 => 'Tentativa cross-tenant'], 999999997);
if ($resultCrossTenant['ok'] !== false) failFast('Usuário sem acesso ao cliente A conseguiu editar auditoria do cliente A');
// find() já aplica o filtro de tenant na própria query (tenantInCondition), então para um
// usuário restrito a auditoria de outro cliente nem é encontrada — reporta not_found, o que é
// inclusive mais seguro que expor "scope" (evita confirmar a existência do registro).
if ($auditorias->getLastError() !== 'not_found') failFast('Tentativa cross-tenant deveria reportar lastError=not_found (find() já filtra por tenant), obteve: ' . $auditorias->getLastError());
if ($countLog($audA1) !== $logBefore4) failFast('Tentativa cross-tenant gerou log indevidamente');
$respostasAposCrossTenant = $auditorias->respostasByAuditoria($audA1);
if (trim((string)$respostasAposCrossTenant[$qA1]['observacoes']) !== 'Observação revisada Q1') failFast('Valor foi alterado apesar do isolamento multiempresa');
ok('Isolamento multiempresa impede edição de auditoria de outro cliente (lastError=not_found via find() com escopo, sem efeitos colaterais)');

// 8) Mesmo usuário restrito PODE editar auditoria do próprio cliente (B).
$resultOwnTenant = $auditorias->updateObservacoesRealizada($audB1, [$qB1 => 'Observação revisada B'], 999999997);
if (!$resultOwnTenant['ok'] || $resultOwnTenant['updated'] !== 1) failFast('Usuário restrito não conseguiu editar auditoria do próprio cliente: ' . json_encode($resultOwnTenant));
ok('Usuário restrito consegue editar auditoria finalizada do seu próprio cliente');

// Restaura sessão instituto para os testes de controller.
$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

// 9) Fluxo completo via controller (sintoma original: acesso ilegal a propriedade protegida $db).
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'id' => (string)$audA1,
    'csrf' => Security::csrfToken(),
    'observacoes_json' => json_encode([
        ['questao_id' => $qA1, 'observacoes' => 'Observação via controller'],
        ['questao_id' => $qA2, 'observacoes' => 'Observação inicial Q2'],
    ], JSON_UNESCAPED_UNICODE),
];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
ob_start();
try {
    (new AuditoriasControllerRedirectProbe())->atualizarObservacoes();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Controller atualizarObservacoes() lançou exceção (bug original): ' . get_class($e) . ': ' . $e->getMessage());
}
ob_end_clean();
if (empty($_SESSION['flash_success'])) failFast('Controller não sinalizou sucesso após edição real via fluxo completo: ' . json_encode($_SESSION));
$respostasPosController = $auditorias->respostasByAuditoria($audA1);
if (trim((string)$respostasPosController[$qA1]['observacoes']) !== 'Observação via controller') failFast('Alteração via controller não foi persistida no banco');
ok('Fluxo completo via controller (POST real) persiste a alteração sem lançar erro de propriedade protegida');

// 10) Submissão sem mudança via controller -> flash_error informativo, sem sucesso falso.
$_POST['observacoes_json'] = json_encode([
    ['questao_id' => $qA1, 'observacoes' => 'Observação via controller'],
    ['questao_id' => $qA2, 'observacoes' => 'Observação inicial Q2'],
], JSON_UNESCAPED_UNICODE);
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
ob_start();
try {
    (new AuditoriasControllerRedirectProbe())->atualizarObservacoes();
} catch (\Throwable $e) {
    ob_end_clean();
    failFast('Controller lançou exceção em submissão sem mudança: ' . get_class($e) . ': ' . $e->getMessage());
}
ob_end_clean();
if (!empty($_SESSION['flash_success'])) failFast('Controller sinalizou sucesso falso para submissão sem alteração real');
if (empty($_SESSION['flash_error'])) failFast('Controller deveria sinalizar mensagem informativa quando nada mudou');
ok('Controller não exibe mensagem de sucesso falsa quando nenhuma observação foi realmente alterada');

$_POST = [];
ob_end_flush();
echo "TODOS OS TESTES PASSARAM\n";
