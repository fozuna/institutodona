<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Core\Security;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

function loginAs(string $tipo, int $userId, ?int $idCliente): void
{
    Auth::login([
        'id' => $userId,
        'nome' => ucfirst($tipo) . ' Teste',
        'email' => $tipo . '.' . $userId . '@test.local',
        'tipo_acesso' => $tipo,
        'id_cliente' => $idCliente,
    ]);
}

/**
 * Executa AuditoriasController::reabrir() num processo separado (ver
 * helpers/auditoria_reabrir_probe.php) - os caminhos de sucesso/erro usam
 * BaseController::redirect(), que termina com exit(), inviável de chamar
 * repetidamente no processo deste teste.
 */
function runReabrirProbe(string $method, string $role, int $userId, ?int $idCliente, int $auditoriaId, string $motivo, bool $withCsrf = true): array
{
    $probe = __DIR__ . '/helpers/auditoria_reabrir_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg($idCliente !== null ? (string)$idCliente : '') . ' '
        . escapeshellarg((string)$auditoriaId) . ' '
        . escapeshellarg($motivo) . ' '
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
$suffix = 'audrb2_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];

try {
    // ===================== FIXTURES =====================
    $cnpjA = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $cnpjB = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Reabertura A ' . $suffix, 'c' => $cnpjA, 'ct' => 'contato']);
    $clienteA = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteA;
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Reabertura B ' . $suffix, 'c' => $cnpjB, 'ct' => 'contato']);
    $clienteB = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteB;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')->execute(['n' => 'Dep A ' . $suffix, 'cid' => $clienteA]);
    $depA = (int)$pdo->lastInsertId();
    $depIds[] = $depA;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depA, 'c' => $clienteA]);

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor A ' . $suffix, 'did' => $depA]);
    $setorA = (int)$pdo->lastInsertId();
    $setorIds[] = $setorA;

    loginAs('instituto', 4001, null);
    $model = new AuditoriaModel();

    $criarAuditoriaFinalizada = static function () use ($model, $clienteA, $setorA, $suffix): int {
        $id = $model->create([
            'cliente_id' => $clienteA,
            'setor_id' => $setorA,
            'nome_auditoria' => 'Auditoria RBAC ' . $suffix . ' ' . uniqid(),
            'data_auditoria' => date('Y-m-d'),
            'questoes' => [[
                'responsavel_nome' => 'Resp',
                'pergunta' => 'Pergunta padrão para teste de reabertura de auditoria',
                'referencia_esperada' => 'POP',
                'processos' => [],
            ]],
        ], 4001);
        $qs = $model->questoesByAuditoria($id);
        $model->finalizarAuditoria($id, [[
            'questao_id' => (int)$qs[0]['id'],
            'conformidade' => 'conforme',
            'observacoes' => '',
        ]], 4001);
        return $id;
    };

    // ===================== TESTES DE ESTADO (MODEL) =====================

    $auditoriaAgendada = $model->create([
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Agendada ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta ainda não respondida para o teste', 'referencia_esperada' => 'POP', 'processos' => []]],
    ], 4001);
    $auditoriaIds[] = $auditoriaAgendada;
    $r1 = $model->reabrirAuditoria($auditoriaAgendada, 4001, 'motivo');
    if ($r1 !== false || $model->getLastError() !== 'invalid_status') {
        failFast('Não deveria permitir reabrir auditoria "Agendada" (esperado invalid_status)');
    }
    ok('Bloqueia reabertura de auditoria "Agendada" (estado inválido)');

    $r2 = $model->reabrirAuditoria(999999999, 4001, 'motivo');
    if ($r2 !== false || $model->getLastError() !== 'not_found') {
        failFast('Não deveria permitir reabrir auditoria inexistente (esperado not_found)');
    }
    ok('Bloqueia reabertura de auditoria inexistente');

    // ===================== TESTES DE HISTÓRICO =====================

    $auditoriaHist = $criarAuditoriaFinalizada();
    $auditoriaIds[] = $auditoriaHist;
    $histAntes = (int)$pdo->query('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = ' . $auditoriaHist)->fetchColumn();
    if ($histAntes < 1) { failFast('Finalização deveria ter registrado snapshot em auditoria_historico'); }
    $snapFinal = $pdo->query('SELECT dados_anteriores FROM auditoria_historico WHERE auditoria_id = ' . $auditoriaHist . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
    $decodedFinal = json_decode((string)$snapFinal, true);
    if (($decodedFinal['evento']['tipo'] ?? '') !== 'finalizacao') {
        failFast('Snapshot da finalização deveria conter evento.tipo = "finalizacao"');
    }
    ok('Finalização registra snapshot de histórico com evento.tipo = "finalizacao"');

    $motivoTeste = 'Auditoria finalizada por engano antes da conclusão das respostas.';
    $okReabrir = $model->reabrirAuditoria($auditoriaHist, 4001, $motivoTeste);
    if (!$okReabrir) { failFast('Reabertura para teste de histórico deveria ter sucesso'); }
    $histDepois = (int)$pdo->query('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = ' . $auditoriaHist)->fetchColumn();
    if ($histDepois !== $histAntes + 1) { failFast('Reabertura deveria adicionar exatamente 1 novo registro de histórico'); }
    $snapReabertura = $pdo->query('SELECT dados_anteriores, usuario_id FROM auditoria_historico WHERE auditoria_id = ' . $auditoriaHist . ' ORDER BY id DESC LIMIT 1')->fetch();
    $decodedReabertura = json_decode((string)$snapReabertura['dados_anteriores'], true);
    $evento = $decodedReabertura['evento'] ?? [];
    if (($evento['tipo'] ?? '') !== 'reabertura') { failFast('Snapshot da reabertura deveria conter evento.tipo = "reabertura"'); }
    if (($evento['status_anterior'] ?? '') !== 'Realizada' || ($evento['status_novo'] ?? '') !== 'Em Auditoria') {
        failFast('Snapshot da reabertura deveria registrar status_anterior=Realizada e status_novo=Em Auditoria');
    }
    if (($evento['motivo'] ?? '') !== $motivoTeste) { failFast('Snapshot da reabertura deveria registrar o motivo informado'); }
    if ((int)$snapReabertura['usuario_id'] !== 4001) { failFast('Snapshot da reabertura deveria registrar o usuário responsável'); }
    ok('Reabertura registra snapshot de histórico com auditoria, status anterior/novo, usuário, motivo e snapshot anterior completo');

    // Finaliza de novo: deve registrar nova finalização, preservando os registros antigos.
    $qsHist = $model->questoesByAuditoria($auditoriaHist);
    $model->finalizarAuditoria($auditoriaHist, [[
        'questao_id' => (int)$qsHist[0]['id'],
        'conformidade' => 'conforme',
        'observacoes' => 'Reavaliado',
    ]], 4001);
    $histFinal = (int)$pdo->query('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = ' . $auditoriaHist)->fetchColumn();
    if ($histFinal !== $histAntes + 2) { failFast('Segunda finalização deveria adicionar mais 1 registro, preservando os anteriores'); }
    ok('Segunda finalização preserva o histórico anterior (finalização → reabertura → nova finalização, 3 eventos)');

    // ===================== RBAC (Auth::canReopenAuditoria) =====================

    loginAs('instituto', 4001, null);
    if (!Auth::canReopenAuditoria($clienteA)) { failFast('Instituto deveria poder reabrir qualquer auditoria'); }
    ok('RBAC: Instituto pode reabrir');

    loginAs('cliente_admin', 4002, $clienteA);
    if (!Auth::canReopenAuditoria($clienteA)) { failFast('Cliente Admin deveria poder reabrir auditoria da própria empresa'); }
    ok('RBAC: Cliente Admin pode reabrir auditoria da própria empresa');

    if (Auth::canReopenAuditoria($clienteB)) { failFast('Cliente Admin NÃO deveria poder reabrir auditoria de outra empresa'); }
    ok('RBAC: Cliente Admin NÃO pode reabrir auditoria de outra empresa (tenant)');

    loginAs('cliente', 4003, $clienteA);
    if (Auth::canReopenAuditoria($clienteA)) { failFast('Cliente comum NÃO deveria poder reabrir, mesmo na própria empresa'); }
    ok('RBAC: Cliente comum bloqueado (não reutiliza a permissão genérica de write)');

    loginAs('reader', 4004, $clienteA);
    if (Auth::canReopenAuditoria($clienteA)) { failFast('Reader NÃO deveria poder reabrir'); }
    ok('RBAC: Reader bloqueado');

    loginAs('consultor', 4005, null);
    if (Auth::canReopenAuditoria($clienteA)) { failFast('Consultor NÃO deveria poder reabrir nesta versão'); }
    ok('RBAC: Consultor bloqueado (nesta versão)');

    loginAs('instituto', 4001, null);

    // ===================== FLUXO COMPLETO DO CONTROLLER (via subprocesso) =====================

    $auditoriaCtrl = $criarAuditoriaFinalizada();
    $auditoriaIds[] = $auditoriaCtrl;

    // GET não pode reabrir.
    $rGet = runReabrirProbe('GET', 'instituto', 4001, null, $auditoriaCtrl, $motivoTeste);
    if ($rGet['status'] !== 400) { failFast('GET em auditorias/reabrir deveria retornar 400 (obtido ' . var_export($rGet['status'], true) . ')'); }
    if (($model->find($auditoriaCtrl)['status'] ?? '') !== 'Realizada') { failFast('GET não deveria ter alterado o status'); }
    ok('Controller: GET não reabre (400), status inalterado');

    // POST sem CSRF.
    $rNoCsrf = runReabrirProbe('POST', 'instituto', 4001, null, $auditoriaCtrl, 'sem csrf', false);
    if ($rNoCsrf['status'] !== 400) { failFast('POST sem CSRF deveria retornar 400 (obtido ' . var_export($rNoCsrf['status'], true) . ')'); }
    ok('Controller: POST sem CSRF válido é rejeitado (400)');

    // POST sem motivo.
    $rSemMotivo = runReabrirProbe('POST', 'instituto', 4001, null, $auditoriaCtrl, '');
    if (strpos($rSemMotivo['location'], 'auditorias/show') === false) { failFast('POST sem motivo deveria redirecionar de volta para a auditoria (location=' . $rSemMotivo['location'] . ')'); }
    if (($model->find($auditoriaCtrl)['status'] ?? '') !== 'Realizada') { failFast('Motivo vazio não deveria ter reaberto a auditoria'); }
    ok('Controller: motivo obrigatório é validado, sem alterar o estado quando ausente');

    // Cliente comum não pode (bloqueado pela permissão dedicada, não pela genérica de write).
    $rClienteComum = runReabrirProbe('POST', 'cliente', 4003, $clienteA, $auditoriaCtrl, 'tentativa indevida');
    if ($rClienteComum['status'] !== 403) { failFast('Cliente comum deveria receber 403 ao tentar reabrir (obtido ' . var_export($rClienteComum['status'], true) . ')'); }
    if (($model->find($auditoriaCtrl)['status'] ?? '') !== 'Realizada') { failFast('Tentativa de cliente comum não deveria ter alterado o status'); }
    ok('Controller: Cliente comum bloqueado com 403, mesmo autenticado e com CSRF válido');

    // Cliente Admin de outra empresa não pode (tenant scoping de find() já barra: "não encontrada").
    $rCrossTenant = runReabrirProbe('POST', 'cliente_admin', 4006, $clienteB, $auditoriaCtrl, 'tentativa cross-tenant');
    if (($model->find($auditoriaCtrl)['status'] ?? '') !== 'Realizada') { failFast('Cliente Admin de outra empresa não deveria conseguir reabrir (vazamento cross-tenant)'); }
    ok('Controller: Cliente Admin de outra empresa é bloqueado (isolamento de tenant, status=' . var_export($rCrossTenant['status'], true) . ')');

    // Sucesso: Instituto reabre de fato.
    $rSucesso = runReabrirProbe('POST', 'instituto', 4001, null, $auditoriaCtrl, $motivoTeste);
    if (strpos($rSucesso['location'], 'auditorias/show&id=' . $auditoriaCtrl) === false) {
        failFast('Reabertura bem-sucedida deveria redirecionar para auditorias/show da própria auditoria: ' . $rSucesso['location']);
    }
    $checkOk = $model->find($auditoriaCtrl);
    if (($checkOk['status'] ?? '') !== 'Em Auditoria') { failFast('Status deveria ser "Em Auditoria" após reabertura bem-sucedida via controller'); }
    ok('Controller: Instituto reabre com sucesso via POST, redireciona para a própria auditoria');

    // ===================== CONCORRÊNCIA / DUPLO POST =====================

    $rDuploCtrl = runReabrirProbe('POST', 'instituto', 4001, null, $auditoriaCtrl, 'segunda tentativa (duplo clique)');
    if (strpos($rDuploCtrl['location'], 'auditorias/show') === false) { failFast('Segunda tentativa deveria redirecionar de volta (falha segura)'); }
    $checkDuplo = $model->find($auditoriaCtrl);
    if (($checkDuplo['status'] ?? '') !== 'Em Auditoria') { failFast('Segunda tentativa não deveria alterar o status (já não está mais Realizada)'); }
    ok('Concorrência: tentar reabrir duas vezes via controller falha de forma segura na segunda vez');

    // Reabrir diretamente no model também deve falhar de forma limpa na segunda tentativa.
    $rDup = $model->reabrirAuditoria($auditoriaCtrl, 4001, 'terceira tentativa direta no model');
    if ($rDup !== false || $model->getLastError() !== 'invalid_status') {
        failFast('reabrirAuditoria() direto no model deveria falhar com invalid_status na segunda tentativa');
    }
    ok('Model: reabrirAuditoria() é seguro contra chamada repetida (idempotência de falha, sem estornar setor_metricas de novo)');

    echo "Auditoria reabertura (RBAC/histórico/controller/concorrência) regression tests passed.\n";
} catch (\Throwable $e) {
    failFast('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
} finally {
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
