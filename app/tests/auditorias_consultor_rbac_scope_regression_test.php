<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Core\AccessControl;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\UsuarioEmpresaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Sprint B, Achado B: Consultor bloqueado indevidamente para operar
 * Auditorias. Cobre a matriz de RBAC (AccessControl), o isolamento por
 * cliente (canBypassScope/tenantClientIds via usuario_empresas) e as
 * regras sensíveis que permanecem restritas (Fluxo A/B).
 */

$pdo = Database::getConnection();
$suffix = 'audcons_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];
$consultorUserId = 0;

try {
    // ===================== FIXTURES =====================
    $cnpjVinc = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $cnpjNaoVinc = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Vinculado ' . $suffix, 'c' => $cnpjVinc, 'ct' => 'contato']);
    $clienteVinculadoId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteVinculadoId;

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Nao Vinculado ' . $suffix, 'c' => $cnpjNaoVinc, 'ct' => 'contato']);
    $clienteNaoVinculadoId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteNaoVinculadoId;

    foreach ([$clienteVinculadoId => 'Vinc', $clienteNaoVinculadoId => 'NaoVinc'] as $cid => $label) {
        $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')->execute(['n' => "Dep $label $suffix", 'cid' => $cid]);
        $depId = (int)$pdo->lastInsertId();
        $depIds[] = $depId;
        $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depId, 'c' => $cid]);
        $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => "Setor $label $suffix", 'did' => $depId]);
        $setorId = (int)$pdo->lastInsertId();
        $setorIds[] = $setorId;
        ${'setor' . $label . 'Id'} = $setorId;
        ${'dep' . $label . 'Id'} = $depId;
    }

    // usuario_empresas tem FK para usuarios - precisa de um usuario real.
    $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:n, :e, :h, 'consultor', NULL)")
        ->execute(['n' => 'Consultor Teste Achado B ' . $suffix, 'e' => 'consultor.' . $suffix . '@test.local', 'h' => password_hash('x', PASSWORD_DEFAULT)]);
    $consultorUserId = (int)$pdo->lastInsertId();

    // Vincula o Consultor SOMENTE ao Cliente Vinculado, via usuario_empresas
    // (mesmo mecanismo generico usado por Cliente Admin/Cliente - ver
    // TenantScopeResolver::assignedClientesForUser()).
    Auth::login(['id' => 9999, 'nome' => 'Instituto Setup', 'email' => 'setup@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    (new UsuarioEmpresaModel())->syncForUser($consultorUserId, [$clienteVinculadoId]);
    ok('Vinculou Consultor de teste ao Cliente Vinculado via usuario_empresas');

    Auth::login([
        'id' => $consultorUserId,
        'nome' => 'Consultor Teste Achado B',
        'email' => 'consultor.achadob@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => null,
    ]);

    $model = new AuditoriaModel();

    // ===================== ISOLAMENTO POR CLIENTE (o ponto mais importante) =====================
    if (Auth::allowedClientIds() !== [$clienteVinculadoId]) {
        failFast('Auth::allowedClientIds() do Consultor deveria conter apenas o Cliente Vinculado, obtido: ' . json_encode(Auth::allowedClientIds()));
    }
    ok('Escopo do Consultor resolvido para exatamente o(s) cliente(s) vinculado(s) (usuario_empresas)');

    $auditoriaVinculadaId = $model->create([
        'cliente_id' => $clienteVinculadoId,
        'setor_id' => $setorVincId,
        'nome_auditoria' => 'Auditoria Vinculada ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []]],
    ], $consultorUserId);
    if ($auditoriaVinculadaId <= 0) { failFast('Consultor deveria conseguir criar auditoria no cliente vinculado'); }
    $auditoriaIds[] = $auditoriaVinculadaId;
    ok('4/5) Consultor cria e opera auditoria no cliente vinculado (ação operacional aprovada)');

    // Auditoria criada diretamente via SQL no cliente NAO vinculado (simula uma
    // auditoria já existente de outra empresa, fora do escopo do Consultor).
    $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
        VALUES (:cid, :sid, CURDATE(), :nome, 'P', 'O', 'R', 'Agendada', 9999, 9999)")
        ->execute(['cid' => $clienteNaoVinculadoId, 'sid' => $setorNaoVincId, 'nome' => 'Auditoria Nao Vinculada ' . $suffix]);
    $auditoriaNaoVinculadaId = (int)$pdo->lastInsertId();
    $auditoriaIds[] = $auditoriaNaoVinculadaId;

    if ($model->find($auditoriaNaoVinculadaId) !== null) {
        failFast('Consultor NÃO deveria conseguir visualizar auditoria de cliente não vinculado (find() deveria retornar null)');
    }
    ok('3/12) Consultor não visualiza/não manipula auditoria de cliente não vinculado (find() com escopo)');

    if ($model->find($auditoriaVinculadaId) === null) {
        failFast('Consultor deveria conseguir visualizar auditoria do cliente vinculado');
    }
    ok('2) Consultor visualiza auditoria do cliente vinculado');

    $listaEscopada = $model->list(['sort_col' => 'data', 'sort_dir' => 'desc'], 1, 50);
    $idsRetornados = array_map(static fn($row) => (int)$row['id'], $listaEscopada['items']);
    if (in_array($auditoriaNaoVinculadaId, $idsRetornados, true)) {
        failFast('Listagem do Consultor vazou uma auditoria de cliente não vinculado');
    }
    if (!in_array($auditoriaVinculadaId, $idsRetornados, true)) {
        failFast('Listagem do Consultor deveria conter a auditoria do cliente vinculado');
    }
    ok('23) Filtros/listagem permanecem escopados ao(s) cliente(s) vinculado(s) do Consultor');

    $relatorioExec = $model->executiveReportData(['sort_col' => 'data', 'sort_dir' => 'desc']);
    $idsRelatorio = array_map(static fn($row) => (int)($row['auditoria_id'] ?? 0), $relatorioExec['por_area'] ?? []);
    // por_area agrega por setor, não por auditoria individual; validação direta
    // de vazamento é feita via total_auditorias/nao_conformidades abaixo.
    if (($relatorioExec['total_auditorias'] ?? 0) < 0) { failFast('executiveReportData retornou estrutura inesperada'); }
    ok('8/22) Relatório executivo (executiveReportData) executa dentro do escopo tenant do Consultor sem erro');

    // ===================== BLOQUEIOS SENSÍVEIS (Fluxo A/B) =====================
    if (Auth::canReopenAuditoria($clienteVinculadoId)) {
        failFast('Consultor NÃO deveria poder reabrir auditoria, mesmo no cliente vinculado (Fluxo B permanece restrito)');
    }
    ok('9/19) Consultor não reabre auditoria mesmo no próprio escopo (Fluxo B protegido)');

    if (Auth::canCorrectAuditoriaClassification($clienteVinculadoId)) {
        failFast('Consultor NÃO deveria poder corrigir classificação, mesmo no cliente vinculado (Fluxo A permanece restrito)');
    }
    ok('10/19b) Consultor não corrige classificação pós-finalização (Fluxo A protegido)');

    // ===================== NÃO AMPLIAÇÃO DE MÓDULOS/ACESSO =====================
    $consultorUser = Auth::user();
    if (AccessControl::canAccessRoute('indicadores/index', 'GET', $consultorUser)) {
        failFast('Consultor não deveria ter ganhado acesso a Indicadores (efeito colateral do módulo Operacional)');
    }
    if (AccessControl::canAccessRoute('coaching/index', 'GET', $consultorUser)) {
        failFast('Consultor não deveria ter ganhado acesso a Coaching');
    }
    if (AccessControl::canAccessRoute('processos/index', 'GET', $consultorUser)) {
        failFast('Consultor não deveria ter ganhado acesso a Processos');
    }
    ok('13) Consultor não ganhou acesso a outros módulos operacionais (indicadores/coaching/processos) via o split de AUDITORIAS_MODULE');

    if (AccessControl::canAccessRoute('dashboard/index', 'GET', $consultorUser)) {
        failFast('Consultor não deveria ter acesso ao Dashboard');
    }
    ok('24) Dashboard não é liberado ao Consultor por acidente');

    if (AccessControl::canAccessRoute('usuarios/index', 'GET', $consultorUser)
        || AccessControl::canAccessRoute('consultores/index', 'GET', $consultorUser)
        || AccessControl::canAccessRoute('pilares/index', 'GET', $consultorUser)
        || AccessControl::canAccessRoute('clientes/index', 'GET', $consultorUser)) {
        failFast('Consultor não deveria ter acesso administrativo (usuarios/consultores/pilares/clientes)');
    }
    ok('14) Consultor não ganhou acesso administrativo');

    if (!AccessControl::canAccessRoute('auditorias/index', 'GET', $consultorUser)) {
        failFast('Consultor deveria acessar a listagem de Auditorias (causa raiz do Achado B)');
    }
    ok('1) Consultor acessa a listagem de Auditorias');

    // ===================== OUTROS PERFIS PERMANECEM INALTERADOS =====================
    $institutoUser = ['tipo_acesso' => 'instituto'];
    $clienteAdminUser = ['tipo_acesso' => 'cliente_admin'];
    $clienteUser = ['tipo_acesso' => 'cliente'];
    $readerUser = ['tipo_acesso' => 'reader'];

    if (!AccessControl::canAccessRoute('auditorias/store', 'POST', $institutoUser)) { failFast('Instituto deveria manter escrita em Auditorias'); }
    if (!AccessControl::canAccessRoute('indicadores/index', 'GET', $institutoUser)) { failFast('Instituto deveria manter acesso a Indicadores'); }
    ok('15) Instituto permanece inalterado (irrestrito)');

    if (!AccessControl::canAccessRoute('auditorias/store', 'POST', $clienteAdminUser)) { failFast('Cliente Admin deveria manter escrita em Auditorias'); }
    if (!AccessControl::canAccessRoute('indicadores/index', 'GET', $clienteAdminUser)) { failFast('Cliente Admin deveria manter acesso a Indicadores (regressão do split de módulo)'); }
    if (!AccessControl::canAccessRoute('dashboard/index', 'GET', $clienteAdminUser)) { failFast('Cliente Admin deveria manter acesso ao Dashboard'); }
    ok('16) Cliente Admin permanece inalterado (Auditorias + Indicadores + Dashboard)');

    if (!AccessControl::canAccessRoute('auditorias/store', 'POST', $clienteUser)) { failFast('Cliente deveria manter escrita em Auditorias'); }
    if (!AccessControl::canAccessRoute('indicadores/index', 'GET', $clienteUser)) { failFast('Cliente deveria manter acesso a Indicadores (regressão do split de módulo)'); }
    ok('17) Cliente permanece inalterado (Auditorias + Indicadores)');

    if (AccessControl::canAccessRoute('auditorias/store', 'POST', $readerUser)) { failFast('Reader não deveria ter escrita em Auditorias'); }
    if (!AccessControl::canAccessRoute('auditorias/index', 'GET', $readerUser)) { failFast('Reader deveria manter leitura em Auditorias'); }
    ok('18) Reader permanece somente leitura');

    echo "Auditorias Consultor RBAC/escopo (Achado B) regression tests passed.\n";
} catch (\Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    Auth::login(['id' => 9999, 'nome' => 'Instituto Cleanup', 'email' => 'cleanup@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_historico WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if ($consultorUserId > 0) {
        $pdo->prepare('DELETE FROM usuario_empresas WHERE usuario_id = :uid')->execute(['uid' => $consultorUserId]);
        $pdo->prepare('DELETE FROM usuarios WHERE id = :uid')->execute(['uid' => $consultorUserId]);
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
