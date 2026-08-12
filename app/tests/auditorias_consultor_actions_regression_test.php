<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Sprint B, Achado B: valida via subprocesso (controller real, HTTP-like)
 * que o Consultor executa o fluxo operacional normal de Auditorias mas
 * permanece bloqueado de exclusão e de qualquer edição pós-finalização
 * (que não seja pelos fluxos dedicados A/B, já cobertos em outro teste).
 */

function runConsultorActionProbe(string $method, string $role, int $userId, ?int $idCliente, string $action, int $auditoriaId, array $extraPost = [], bool $withCsrf = true): array
{
    $probe = __DIR__ . '/helpers/auditorias_consultor_action_probe.php';
    $postB64 = base64_encode(json_encode($extraPost, JSON_UNESCAPED_UNICODE));
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg($idCliente !== null ? (string)$idCliente : '') . ' '
        . escapeshellarg($action) . ' '
        . escapeshellarg((string)$auditoriaId) . ' '
        . escapeshellarg($postB64) . ' '
        . escapeshellarg($withCsrf ? '1' : '0');
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $marker = '---PROBE_RESULT---';
    $pos = strpos($raw, $marker);
    $body = $pos !== false ? substr($raw, 0, $pos) : $raw;
    $resultLine = $pos !== false ? trim(substr($raw, $pos + strlen($marker))) : '';
    $decoded = json_decode($resultLine, true);
    return [
        'body' => $body,
        'status' => is_array($decoded) ? ($decoded['status'] ?? null) : null,
        'location' => is_array($decoded) ? ($decoded['location'] ?? '') : '',
    ];
}

$pdo = Database::getConnection();
$suffix = 'audconsact_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];

try {
    $cnpj = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Consultor Acoes ' . $suffix, 'c' => $cnpj, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')->execute(['n' => 'Dep ' . $suffix, 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $depIds[] = $depId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depId, 'c' => $clienteId]);
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor ' . $suffix, 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    Auth::login(['id' => 9998, 'nome' => 'Instituto Setup Acoes', 'email' => 'setup.acoes@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $model = new AuditoriaModel();

    // Auditoria Agendada (para testar acoes operacionais permitidas: auditar/finalizar).
    $auditoriaAbertaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Aberta Consultor ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []]],
    ], 9998);
    if ($auditoriaAbertaId <= 0) { failFast('Falha ao criar auditoria aberta de teste'); }
    $auditoriaIds[] = $auditoriaAbertaId;

    // Auditoria Realizada (para testar bloqueios pós-finalização e exclusão).
    $auditoriaRealizadaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Realizada Consultor ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []]],
    ], 9998);
    if ($auditoriaRealizadaId <= 0) { failFast('Falha ao criar auditoria para finalizar'); }
    $auditoriaIds[] = $auditoriaRealizadaId;
    $questoes = $model->questoesByAuditoria($auditoriaRealizadaId);
    $okFin = $model->finalizarAuditoria($auditoriaRealizadaId, [
        ['questao_id' => (int)$questoes[0]['id'], 'conformidade' => 'conforme', 'observacoes' => ''],
    ], 9998);
    if (!$okFin) { failFast('Falha ao finalizar auditoria de teste'); }

    // ===================== AÇÕES PERMITIDAS AO CONSULTOR =====================
    $rAuditar = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'auditar', $auditoriaAbertaId);
    if ((int)$rAuditar['status'] === 403) { failFast('Consultor deveria poder iniciar execução (auditar) de auditoria aberta do próprio cliente. body=' . $rAuditar['body']); }
    ok('Consultor consegue iniciar execução (auditar) de auditoria aberta do seu cliente');

    $itemAposAuditar = $model->find($auditoriaAbertaId);
    if (($itemAposAuditar['status'] ?? '') !== 'Em Auditoria') { failFast('Status deveria ter avançado para "Em Auditoria" após auditar()'); }
    ok('5) Consultor consegue avançar o fluxo de resposta da auditoria (Agendada -> Em Auditoria)');

    $rFinalizar = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'finalizar', $auditoriaAbertaId, [
        'avaliacoes_json' => json_encode([['questao_id' => (int)$questoes[0]['id'] ?? 0, 'conformidade' => 'conforme', 'observacoes' => '']]),
    ]);
    // finalizar() usa os dados persistidos via auto-save; validamos apenas que o
    // Consultor NÃO é bloqueado por RBAC (403) - o resultado funcional específico
    // de finalizar() já é coberto pelos testes de Fluxo A/B e de finalização.
    if ((int)$rFinalizar['status'] === 403) { failFast('Consultor deveria poder finalizar auditoria do próprio cliente. body=' . $rFinalizar['body']); }
    ok('6) Consultor não é bloqueado por RBAC ao finalizar auditoria do próprio cliente');

    // ===================== BLOQUEIOS ESPECÍFICOS DO CONSULTOR =====================
    $rDelete = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'delete', $auditoriaRealizadaId);
    if ((int)$rDelete['status'] !== 403 || stripos($rDelete['body'], 'Acesso negado') === false) {
        failFast('Consultor deveria ser bloqueado (403, "Acesso negado") ao tentar excluir auditoria. status=' . (string)$rDelete['status'] . ' body=' . $rDelete['body']);
    }
    ok('11 (exclusão)) Consultor bloqueado ao tentar excluir auditoria');

    $rUpdateRealizada = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'update', $auditoriaRealizadaId, [
        'save_mode' => 'partial',
        'cliente_id' => (string)$clienteId,
        'setor_id' => (string)$setorId,
    ]);
    if ((int)$rUpdateRealizada['status'] !== 403 || stripos($rUpdateRealizada['body'], 'Acesso negado') === false) {
        failFast('Consultor deveria ser bloqueado (403) ao editar auditoria Realizada via update(). status=' . (string)$rUpdateRealizada['status'] . ' body=' . $rUpdateRealizada['body']);
    }
    ok('11 (correção pós-finalização)) Consultor bloqueado ao editar auditoria já Realizada via update()');

    $rEditarRealizada = runConsultorActionProbe('GET', 'consultor', 6001, $clienteId, 'editarRealizada', $auditoriaRealizadaId);
    if ((int)$rEditarRealizada['status'] !== 403) {
        failFast('Consultor deveria ser bloqueado (403) ao acessar editarRealizada(). status=' . (string)$rEditarRealizada['status'] . ' body=' . $rEditarRealizada['body']);
    }
    ok('Consultor bloqueado ao acessar a tela dedicada de edição pós-finalização (editarRealizada)');

    $rAtualizarObs = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'atualizarObservacoes', $auditoriaRealizadaId, [
        'observacoes_json' => json_encode([]),
    ]);
    if ((int)$rAtualizarObs['status'] !== 403) {
        failFast('Consultor deveria ser bloqueado (403) ao chamar atualizarObservacoes(). status=' . (string)$rAtualizarObs['status'] . ' body=' . $rAtualizarObs['body']);
    }
    ok('Consultor bloqueado ao tentar atualizar observações de auditoria Realizada');

    // Auditoria ainda ABERTA (não Realizada) continua editável normalmente pelo
    // Consultor - usa uma auditoria própria, pois $auditoriaAbertaId já foi
    // finalizada no passo anterior (verificação de permissão de finalizar()).
    $auditoriaAindaAbertaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Ainda Aberta Consultor ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []]],
    ], 9998);
    if ($auditoriaAindaAbertaId <= 0) { failFast('Falha ao criar auditoria ainda aberta de teste'); }
    $auditoriaIds[] = $auditoriaAindaAbertaId;
    $rUpdateAberta = runConsultorActionProbe('POST', 'consultor', 6001, $clienteId, 'update', $auditoriaAindaAbertaId, [
        'save_mode' => 'partial',
        'cliente_id' => (string)$clienteId,
        'setor_id' => (string)$setorId,
    ]);
    if ((int)$rUpdateAberta['status'] === 403) {
        failFast('Consultor deveria poder editar auditoria ainda não Realizada. status=' . (string)$rUpdateAberta['status'] . ' body=' . $rUpdateAberta['body']);
    }
    ok('Editar aberta) Consultor NÃO é bloqueado ao editar auditoria ainda não Realizada (só pós-finalização é restrito)');

    // ===================== REGRESSÃO: outros perfis continuam podendo excluir =====================
    $auditoriaParaExcluirId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Para Excluir ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []]],
    ], 9998);
    if ($auditoriaParaExcluirId <= 0) { failFast('Falha ao criar auditoria para teste de regressão de exclusão'); }
    $auditoriaIds[] = $auditoriaParaExcluirId;

    $rDeleteInstituto = runConsultorActionProbe('POST', 'instituto', 9998, null, 'delete', $auditoriaParaExcluirId);
    if ((int)$rDeleteInstituto['status'] === 403) {
        failFast('Instituto NÃO deveria ser bloqueado ao excluir auditoria (regressão). status=' . (string)$rDeleteInstituto['status'] . ' body=' . $rDeleteInstituto['body']);
    }
    ok('Regressão) Instituto continua podendo excluir auditorias normalmente');

    echo "Auditorias Consultor ações (Achado B) regression tests passed.\n";
} catch (\Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    Auth::login(['id' => 9998, 'nome' => 'Instituto Cleanup Acoes', 'email' => 'cleanup.acoes@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_historico WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_map('intval', $setorIds));
        $pdo->exec("DELETE FROM setor_metricas WHERE setor_id IN ($in)");
        $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
    }
    if (!empty($depIds)) {
        $in = implode(',', array_map('intval', $depIds));
        $pdo->exec("DELETE FROM departamento_clientes WHERE departamento_id IN ($in)");
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
