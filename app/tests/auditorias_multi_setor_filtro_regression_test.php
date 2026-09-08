<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\AuditoriasController;
use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\UsuarioEmpresaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 02: seletor de Setor na listagem de Auditorias passa a aceitar mais
 * de um setor simultaneamente. Confirmado no diagnóstico: é EXCLUSIVAMENTE
 * filtro de listagem (index.php's `<select name="setor">`) - o cadastro da
 * Auditoria (create.php/edit.php `setor_id`) continua 1:1, intocado. Nenhuma
 * migration, nenhuma mudança de cardinalidade persistida.
 */

function collectFiltersFor(AuditoriasController $controller): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('collectFilters');
    $m->setAccessible(true);
    return $m->invoke($controller);
}

function roleUser(string $role, array $allowed = []): array
{
    return ['id' => 1, 'nome' => ucfirst($role), 'email' => $role . '@test.local', 'tipo_acesso' => $role, 'id_cliente' => null, 'allowed_client_ids' => $allowed];
}

$pdo = Database::getConnection();
$suffix = 'audsetor_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];
$consultorUserId = 0;

$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};

try {
    Auth::login(['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $model = new AuditoriaModel();

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Empresa A Setor ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'x']);
    $empresaAId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Empresa B Setor ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'x']);
    $empresaBId = (int)$pdo->lastInsertId();
    $clienteIds = [$empresaAId, $empresaBId];

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')->execute(['n' => 'Dep A ' . $suffix, 'cid' => $empresaAId]);
    $depAId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')->execute(['n' => 'Dep B ' . $suffix, 'cid' => $empresaBId]);
    $depBId = (int)$pdo->lastInsertId();
    $depIds = [$depAId, $depBId];
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depAId, 'c' => $empresaAId]);
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depBId, 'c' => $empresaBId]);

    $mkSetor = static function (string $nome, int $depId) use ($pdo, &$setorIds): int {
        $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')->execute(['n' => $nome, 'd' => $depId]);
        $id = (int)$pdo->lastInsertId();
        $setorIds[] = $id;
        return $id;
    };
    $setorA1 = $mkSetor('Setor A1 ' . $suffix, $depAId);
    $setorA2 = $mkSetor('Setor A2 ' . $suffix, $depAId);
    $setorA3 = $mkSetor('Setor A3 ' . $suffix, $depAId);
    $setorB1 = $mkSetor('Setor B1 ' . $suffix, $depBId);
    // Criado aqui (antes de qualquer chamada ao Controller) porque
    // AuditoriasController::setoresCached() mantém cache de sessão de 60s por
    // cliente_id - se este setor fosse criado depois da primeira chamada ao
    // Controller nesta mesma execução, ficaria de fora da lista "permitida"
    // usada por collectFilters() para validar setores[].
    $setorPaginacao = $mkSetor('Setor Paginação ' . $suffix, $depAId);
    ok('Fixtures: Empresa A (Setores A1/A2/A3 + Paginação) + Empresa B (Setor B1, fora do escopo de A)');

    $mkAud = static function (int $clienteId, int $setorId, string $nome) use ($model): int {
        $id = $model->create([
            'cliente_id' => $clienteId,
            'setor_id' => $setorId,
            'nome_auditoria' => $nome,
            'data_auditoria' => date('Y-m-d'),
            'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta de conformidade', 'referencia_esperada' => 'POP-1', 'processos' => []]],
        ], 1);
        if ($id <= 0) { failFast("Falha ao criar auditoria de teste ($nome)"); }
        return $id;
    };
    $audA1 = $mkAud($empresaAId, $setorA1, 'Auditoria A1 ' . $suffix);
    $audA2 = $mkAud($empresaAId, $setorA2, 'Auditoria A2 ' . $suffix);
    $audA3 = $mkAud($empresaAId, $setorA3, 'Auditoria A3 ' . $suffix);
    $audB1 = $mkAud($empresaBId, $setorB1, 'Auditoria B1 ' . $suffix);
    $auditoriaIds = [$audA1, $audA2, $audA3, $audB1];
    // Aud A3 finalizada (Realizada), para testar Status + Setor combinados.
    $pdo->prepare("UPDATE auditorias SET status='Realizada', realizada_at=NOW(), semaforo='verde', conformidade_pct=100 WHERE id=:id")->execute(['id' => $audA3]);
    ok('4 auditorias criadas (A1/A2/A3 na Empresa A, B1 na Empresa B); A3 marcada como Realizada');

    $listByIds = static function (array $items): array {
        return array_map(static fn(array $r): int => (int)$r['id'], $items);
    };

    // ===================== CENÁRIO 1: nenhum setor selecionado =====================
    $r1 = $model->list(['cliente' => $empresaAId, 'setores' => []], 1, 50);
    $ids1 = $listByIds($r1['items']);
    if (!in_array($audA1, $ids1, true) || !in_array($audA2, $ids1, true) || !in_array($audA3, $ids1, true)) {
        failFast('Cenário 1: sem setor selecionado deveria trazer todas as auditorias da Empresa A (A1, A2, A3)');
    }
    ok('Cenário 1: nenhum setor selecionado = todos os setores dentro do escopo (Empresa A)');

    // ===================== CENÁRIO 2: um setor =====================
    $r2 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1]], 1, 50);
    $ids2 = $listByIds($r2['items']);
    if ($ids2 !== [$audA1]) {
        failFast('Cenário 2: apenas Setor A1 selecionado deveria trazer somente Auditoria A1. Obtido: ' . implode(',', $ids2));
    }
    ok('Cenário 2: um único setor filtra corretamente');

    // ===================== CENÁRIOS 3/5/6: dois setores =====================
    $r3 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2]], 1, 50);
    $ids3 = $listByIds($r3['items']);
    sort($ids3);
    $expected3 = [$audA1, $audA2];
    sort($expected3);
    if ($ids3 !== $expected3) {
        failFast('Cenário 3: Setor A1 + A2 deveria trazer exatamente Auditoria A1 e A2. Obtido: ' . implode(',', $ids3));
    }
    if (in_array($audA3, $ids3, true)) {
        failFast('Cenário 6: Auditoria A3 (Setor A3, não selecionado) não pode aparecer no filtro A1+A2');
    }
    foreach ($r3['items'] as $row) {
        if (!in_array((int)$row['setor_id'], [$setorA1, $setorA2], true)) {
            failFast('Cenário 5: item retornado com setor_id fora dos setores selecionados');
        }
    }
    ok('Cenário 3/5/6: dois setores = união exata dos dois, sem vazar setor não selecionado, todo item pertence a algum dos setores pedidos');

    // ===================== CENÁRIO 4: três ou mais setores =====================
    $r4 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2, $setorA3]], 1, 50);
    $ids4 = $listByIds($r4['items']);
    sort($ids4);
    $expected4 = [$audA1, $audA2, $audA3];
    sort($expected4);
    if ($ids4 !== $expected4) {
        failFast('Cenário 4: os três setores da Empresa A deveriam trazer as três auditorias. Obtido: ' . implode(',', $ids4));
    }
    if (in_array($audB1, $ids4, true)) {
        failFast('Cenário 4: Auditoria B1 (outra empresa) não pode aparecer');
    }
    ok('Cenário 4: três ou mais setores selecionados funcionam (união completa)');

    // ===================== CENÁRIO 8: combinação com filtro de Status =====================
    $r8 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2, $setorA3], 'status' => 'Realizada'], 1, 50);
    $ids8 = $listByIds($r8['items']);
    if ($ids8 !== [$audA3]) {
        failFast('Cenário 8: Status=Realizada combinado com os três setores deveria trazer somente A3 (a única Realizada). Obtido: ' . implode(',', $ids8));
    }
    ok('Cenário 8/19: combinação com Status funciona; Auditoria finalizada continua acessível pelo filtro multi-setor');

    // ===================== CENÁRIOS 12/13: IDs inexistente/inválido são ignorados com segurança =====================
    $r12 = $model->list(['cliente' => $empresaAId, 'setores' => [999999999]], 1, 50);
    if (!empty($r12['items'])) {
        failFast('Cenário 12: setor inexistente não deveria casar com nenhuma auditoria (nem quebrar a query)');
    }
    ok('Cenário 12: ID de setor inexistente não quebra a listagem, apenas não retorna nada');

    // ===================== CENÁRIO 14: setor de outra empresa não vaza dado dela =====================
    $r14 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorB1]], 1, 50);
    if (!empty($r14['items'])) {
        failFast('Cenário 14: filtrar pelo Setor B1 (de outra empresa) com cliente=A não pode retornar a Auditoria B1');
    }
    $r14b = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorB1]], 1, 50);
    $ids14b = $listByIds($r14b['items']);
    if ($ids14b !== [$audA1] || in_array($audB1, $ids14b, true)) {
        failFast('Cenário 14: misturar Setor A1 (válido) + Setor B1 (outra empresa) deveria retornar só A1, nunca B1');
    }
    ok('Cenário 14: setor de outra empresa nunca vaza dado dela, mesmo combinado com um setor válido (a condição de cliente_id sempre continua ANDada)');

    // ===================== CENÁRIO via Controller: validação/sanitização de collectFilters() =====================
    Auth::login(['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $_GET = ['route' => 'auditorias/index', 'cliente' => (string)$empresaAId, 'setores' => [(string)$setorA1, (string)$setorA2, 'abc', '0', '999999999', (string)$setorB1]];
    $filtersSanitized = collectFiltersFor(new AuditoriasController());
    sort($filtersSanitized['setores']);
    $expectedSanitized = [$setorA1, $setorA2];
    sort($expectedSanitized);
    if ($filtersSanitized['setores'] !== $expectedSanitized) {
        failFast('Cenário 13/14 (Controller): collectFilters() deveria filtrar ID inválido ("abc"), "0", ID inexistente e ID de outra empresa, mantendo só A1/A2. Obtido: ' . json_encode($filtersSanitized['setores']));
    }
    ok('Cenário 13: ID inválido ("abc"/"0") é ignorado com segurança por collectFilters(), junto com inexistente/outra-empresa (validação centralizada no Controller)');

    // ===================== CENÁRIO 7: combinação com filtro de Departamento =====================
    $r7 = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2, $setorA3], 'departamento' => $depAId], 1, 50);
    $ids7 = $listByIds($r7['items']);
    sort($ids7);
    if ($ids7 !== $expected4) {
        failFast('Cenário 7: combinar múltiplos setores com o filtro de Departamento (mesmo departamento) não deveria excluir nada indevidamente');
    }
    ok('Cenário 7: combinação com filtro de Departamento funciona');

    // ===================== CENÁRIOS 9/10/11 (UI): seleção preservada, paginação e "Limpar" =====================
    $_GET = ['route' => 'auditorias/index', 'cliente' => (string)$empresaAId, 'setores' => [(string)$setorA1, (string)$setorA2]];
    ob_start();
    (new AuditoriasController())->index();
    $html = (string)ob_get_clean();
    if (substr_count($html, 'class="setor-multi-checkbox" checked') !== 2) {
        failFast('Cenário 9: os dois setores selecionados deveriam continuar marcados (checked) após aplicar o filtro');
    }
    if (strpos($html, '2 selecionado(s)') === false) {
        failFast('Cenário 9: o rótulo do seletor deveria indicar "2 selecionado(s)"');
    }
    if (strpos($html, 'href="index.php?route=auditorias/index"') === false) {
        failFast('Cenário 11: link "Limpar" deveria apontar para a rota base, sem nenhum filtro (inclusive setores)');
    }
    ok('Cenário 9/11: seleção de múltiplos setores fica visivelmente marcada após filtrar; "Limpar" volta ao estado padrão');

    // Paginação: AuditoriasController::index() sempre aplica um piso de 10 por página
    // (max(10, min(50, ...))), então são necessários mais de 10 registros para gerar
    // uma segunda página de fato. Usa o setor dedicado criado junto das fixtures
    // (não A1/A2/A3), para não interferir nas contagens exatas dos demais cenários.
    for ($i = 0; $i < 12; $i++) {
        $auditoriaIds[] = $mkAud($empresaAId, $setorPaginacao, 'Auditoria Paginação ' . $i . ' ' . $suffix);
    }
    $_GET = ['route' => 'auditorias/index', 'cliente' => (string)$empresaAId, 'setores' => [(string)$setorA1, (string)$setorA2, (string)$setorA3, (string)$setorPaginacao], 'per' => '10'];
    ob_start();
    (new AuditoriasController())->index();
    $htmlPag = (string)ob_get_clean();
    // http_build_query() codifica array indexado como setores[0]=/setores[1]=... (não setores[]=),
    // mas o PHP faz o parse de volta como array normalmente - o que importa é que os 3 valores
    // apareçam em algum link de paginação.
    if (preg_match('/href="index\.php\?[^"]*page=2[^"]*"/', $htmlPag, $m) !== 1) {
        failFast('Cenário 10: não foi encontrado link de paginação para a página 2');
    }
    $page2Link = html_entity_decode($m[0]);
    foreach ([$setorA1, $setorA2, $setorA3, $setorPaginacao] as $sid) {
        if (strpos($page2Link, 'setores%5B') === false || strpos($page2Link, (string)$sid) === false) {
            failFast('Cenário 10: o link da página 2 deveria conter o setor ' . $sid . ' na querystring. Link: ' . $page2Link);
        }
    }
    ok('Cenário 10: seleção de múltiplos setores é preservada nos links de paginação');

    // ===================== CENÁRIO 17/18: Instituto e Cliente Admin preservados =====================
    $rInst = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2, $setorA3]], 1, 50);
    $idsInst = $listByIds($rInst['items']);
    if (!in_array($audA1, $idsInst, true) || !in_array($audA2, $idsInst, true) || !in_array($audA3, $idsInst, true)) {
        failFast('Cenário 17: Instituto deveria continuar enxergando as auditorias filtradas normalmente (A1/A2/A3 presentes)');
    }
    $usuarioEmpresas = new UsuarioEmpresaModel();
    $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:n, :e, :h, 'cliente_admin', :cid)")
        ->execute(['n' => 'Admin Setor Teste ' . $suffix, 'e' => 'admin.setor.' . $suffix . '@test.local', 'h' => password_hash('x', PASSWORD_DEFAULT), 'cid' => $empresaAId]);
    $adminUserId = (int)$pdo->lastInsertId();
    $usuarioEmpresas->syncForUser($adminUserId, [$empresaAId]);
    Auth::login(['id' => $adminUserId, 'nome' => 'Admin Setor Teste', 'email' => 'admin.setor.' . $suffix . '@test.local', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $empresaAId]);
    $rAdmin = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2]], 1, 50);
    $idsAdmin = $listByIds($rAdmin['items']);
    sort($idsAdmin);
    if ($idsAdmin !== $expected3) {
        failFast('Cenário 18: Cliente Admin vinculado à Empresa A deveria ver A1+A2 normalmente. Obtido: ' . implode(',', $idsAdmin));
    }
    $rAdminCross = $model->list(['cliente' => $empresaAId, 'setores' => [$setorB1]], 1, 50);
    if (!empty($rAdminCross['items'])) {
        failFast('Cenário 15/18: Cliente Admin não pode enxergar auditoria de Setor B1 (outra empresa) mesmo manipulando o filtro');
    }
    ok('Cenário 17/18: Instituto preservado (acesso total); Cliente Admin preservado (só a própria empresa, cross-tenant via setor bloqueado)');

    // ===================== CENÁRIO 16: Consultor restrito às empresas vinculadas =====================
    $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente) VALUES (:n, :e, :h, 'consultor', NULL)")
        ->execute(['n' => 'Consultor Setor Teste ' . $suffix, 'e' => 'consultor.setor.' . $suffix . '@test.local', 'h' => password_hash('x', PASSWORD_DEFAULT)]);
    $consultorUserId = (int)$pdo->lastInsertId();
    Auth::login(['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $usuarioEmpresas->syncForUser($consultorUserId, [$empresaAId]);
    Auth::login(['id' => $consultorUserId, 'nome' => 'Consultor Setor Teste', 'email' => 'consultor.setor.' . $suffix . '@test.local', 'tipo_acesso' => 'consultor', 'id_cliente' => null]);
    if (Auth::allowedClientIds() !== [$empresaAId]) {
        failFast('Cenário 16: escopo do Consultor de teste deveria conter só a Empresa A');
    }
    $rCons = $model->list(['cliente' => $empresaAId, 'setores' => [$setorA1, $setorA2, $setorA3]], 1, 50);
    $idsCons = $listByIds($rCons['items']);
    sort($idsCons);
    if ($idsCons !== $expected4) {
        failFast('Cenário 16: Consultor vinculado à Empresa A deveria ver A1/A2/A3 normalmente com múltiplos setores');
    }
    $rConsCross = $model->list(['cliente' => $empresaAId, 'setores' => [$setorB1]], 1, 50);
    if (!empty($rConsCross['items'])) {
        failFast('Cenário 15/16: Consultor não pode enxergar Setor B1 (empresa à qual não está vinculado), mesmo manipulando o filtro');
    }
    // Sem filtro de cliente (todo o escopo do Consultor) tentando setor de B tambem nao pode vazar.
    $rConsCross2 = $model->list(['setores' => [$setorB1]], 1, 50);
    if (!empty($rConsCross2['items'])) {
        failFast('Cenário 15: Consultor sem filtro de cliente explícito, filtrando por Setor B1, ainda não pode ver a Auditoria B1 (tenantInCondition sempre ANDado)');
    }
    ok('Cenário 15/16: Consultor continua restrito exatamente às empresas vinculadas (usuario_empresas) - filtro multi-setor não abre acesso cross-tenant');

    echo "auditorias_multi_setor_filtro_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
} finally {
    Auth::login(['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_responsaveis WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', $setorIds)) . ')');
    }
    if (!empty($depIds)) {
        $pdo->exec('DELETE FROM departamento_clientes WHERE departamento_id IN (' . implode(',', array_map('intval', $depIds)) . ')');
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $depIds)) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM usuario_empresas WHERE cliente_id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
    if (!empty($consultorUserId)) {
        $pdo->exec('DELETE FROM usuario_empresas WHERE usuario_id = ' . (int)$consultorUserId);
        $pdo->exec('DELETE FROM usuarios WHERE id = ' . (int)$consultorUserId);
    }
    if (!empty($adminUserId)) {
        $pdo->exec('DELETE FROM usuario_empresas WHERE usuario_id = ' . (int)$adminUserId);
        $pdo->exec('DELETE FROM usuarios WHERE id = ' . (int)$adminUserId);
    }
    Auth::logout();
}
