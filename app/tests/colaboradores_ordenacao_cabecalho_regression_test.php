<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\ColaboradoresController;
use App\Core\AccessControl;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 15b: ordenação por cabeçalho clicável na listagem de Colaboradores
 * (Nome, E-mail, Unidade, Função, Setor, Departamento). "Classificação" foi
 * interpretado como ordenar a listagem clicando no cabeçalho - nenhum campo
 * novo foi criado. ColaboradoresController::index()/filterAjax() não dão
 * exit() em nenhum caminho, então os cenários rodam direto no mesmo processo.
 */

function roleUser(string $role, ?int $idCliente = null, array $allowed = []): array
{
    return ['id' => 1, 'nome' => ucfirst($role), 'email' => $role . '@test.local', 'tipo_acesso' => $role, 'id_cliente' => $idCliente, 'allowed_client_ids' => $allowed];
}

$pdo = Database::getConnection();
$suffix = 'colabsort_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colabIds = [];

$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};

try {
    // ===================== PARTE 1: whitelist e fallback seguro (sem tocar DB) =====================
    if (ColaboradorModel::normalizeSortColumn('id; DROP TABLE colaboradores;--') !== null) {
        failFast('Cenário 10: tentativa de injeção em "sort" deveria cair fora da whitelist (null)');
    }
    if (ColaboradorModel::normalizeSortColumn('coisa-invalida') !== null) {
        failFast('Cenário 8: coluna inválida deveria cair fora da whitelist (null = manter ordenação padrão)');
    }
    if (ColaboradorModel::normalizeSortColumn('nome') !== 'nome') {
        failFast('Whitelist deveria aceitar "nome"');
    }
    if (ColaboradorModel::normalizeSortDirection('DROP') !== 'asc') {
        failFast('Cenário 9: direção inválida deveria cair no fallback "asc"');
    }
    if (ColaboradorModel::normalizeSortDirection('DESC') !== 'desc') {
        failFast('Direção "DESC" (maiúscula) deveria normalizar para "desc"');
    }
    $expectedSortable = ['nome', 'email', 'unidade', 'funcao', 'setor', 'departamento'];
    if (array_diff($expectedSortable, ColaboradorModel::sortableColumns()) !== [] || array_diff(ColaboradorModel::sortableColumns(), $expectedSortable) !== []) {
        failFast('Colunas ordenáveis deveriam ser exatamente: ' . implode(',', $expectedSortable));
    }
    ok('Cenários 8/9/10: whitelist de ordenação - coluna inválida/injeção e direção inválida caem em fallback seguro; nenhum valor cru chega à query');

    // ===================== PARTE 2: fixtures =====================
    $_SESSION['user'] = roleUser('instituto');
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute(['n' => 'Cliente Colab Sort ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $clienteXId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteXId;

    $insCli->execute(['n' => 'Cliente Colab Sort Y ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $clienteYId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteYId;

    $depModel = new DepartamentoModel();
    $setorModel = new SetorModel();
    $funcaoModel = new FuncaoModel();
    $colabModel = new ColaboradorModel();

    $makeCombo = static function (string $label, int $clienteId, string $colabNome, string $email) use ($depModel, $setorModel, $funcaoModel, $colabModel, $suffix, &$depIds, &$setorIds, &$funcaoIds, &$colabIds): int {
        $depId = $depModel->create(['nome' => 'Dep ' . $label . ' ' . $suffix, 'cliente_id' => $clienteId]);
        $depIds[] = $depId;
        $setorId = $setorModel->create(['nome' => 'Setor ' . $label . ' ' . $suffix, 'departamento_id' => $depId]);
        $setorIds[] = $setorId;
        $funcaoId = $funcaoModel->create(['nome' => 'Funcao ' . $label . ' ' . $suffix, 'setor_id' => $setorId]);
        $funcaoIds[] = $funcaoId;
        $colabId = $colabModel->create([
            'nome' => $colabNome . ' ' . $suffix,
            'email' => $email . '.' . $suffix . '@test.local',
            'funcao_id' => $funcaoId,
            'lider' => 'não',
            'cliente_id' => $clienteId,
            'ativo' => 1,
        ]);
        $colabIds[] = $colabId;
        return $colabId;
    };

    // Deps/Setores/Funcoes nomeados Alfa/Mike/Zulu (ordem alfabética conhecida), com nomes de
    // colaborador que NÃO seguem a mesma ordem alfabética dos deps - assim dá para provar que
    // cada chave de ordenação (nome vs. departamento/setor/funcao) produz sequências diferentes,
    // e não é coincidência da ordem padrão hierárquica.
    $idBravo = $makeCombo('Alfa', $clienteXId, 'Colab Bravo', 'bravo');
    $idAlpha = $makeCombo('Mike', $clienteXId, 'Colab Alpha', 'alpha');
    $idCharlie = $makeCombo('Zulu', $clienteXId, 'Colab Charlie', 'charlie');
    ok('Fixtures criadas: 3 colaboradores no Cliente X (dep/setor/função Alfa/Mike/Zulu, nomes Bravo/Alpha/Charlie), 1 cliente Y isolado');

    $byNome = static function (array $rows): array {
        return array_map(static fn(array $r): string => preg_replace('/ \S+$/', '', (string)$r['nome']), $rows);
    };

    // ===================== CENÁRIO 1: ordenação padrão permanece igual à atual =====================
    $default = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, []);
    if ($byNome($default) !== ['Colab Bravo', 'Colab Alpha', 'Colab Charlie']) {
        failFast('Cenário 1: ordenação padrão (hierárquica dep/setor/função/nome) mudou. Obtido: ' . implode(',', $byNome($default)));
    }
    ok('Cenário 1: ordenação padrão (sem "sort") permanece a mesma de antes - hierárquica por departamento/setor/função/nome');

    // ===================== CENÁRIOS 2/3: Nome ASC/DESC =====================
    $nomeAsc = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], 'nome', 'asc');
    if ($byNome($nomeAsc) !== ['Colab Alpha', 'Colab Bravo', 'Colab Charlie']) {
        failFast('Cenário 2: Nome ASC incorreto. Obtido: ' . implode(',', $byNome($nomeAsc)));
    }
    ok('Cenário 2: Nome ASC correto');
    $nomeDesc = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], 'nome', 'desc');
    if ($byNome($nomeDesc) !== ['Colab Charlie', 'Colab Bravo', 'Colab Alpha']) {
        failFast('Cenário 3: Nome DESC incorreto. Obtido: ' . implode(',', $byNome($nomeDesc)));
    }
    ok('Cenário 3: Nome DESC correto');

    // ===================== CENÁRIOS 4/5/6: Departamento/Setor/Função ASC/DESC =====================
    foreach (['departamento', 'setor', 'funcao'] as $col) {
        $asc = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], $col, 'asc');
        if ($byNome($asc) !== ['Colab Bravo', 'Colab Alpha', 'Colab Charlie']) {
            failFast("Cenário 4/5/6: $col ASC incorreto. Obtido: " . implode(',', $byNome($asc)));
        }
        $desc = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], $col, 'desc');
        if ($byNome($desc) !== ['Colab Charlie', 'Colab Alpha', 'Colab Bravo']) {
            failFast("Cenário 4/5/6: $col DESC incorreto. Obtido: " . implode(',', $byNome($desc)));
        }
    }
    ok('Cenários 4/5/6: Departamento, Setor e Função ASC/DESC corretos (Alfa → Mike → Zulu e o inverso)');

    // E-mail e Unidade: whitelist funcional (colunas seguras já selecionadas na query).
    $emailAsc = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], 'email', 'asc');
    if ($byNome($emailAsc) !== ['Colab Alpha', 'Colab Bravo', 'Colab Charlie']) {
        failFast('E-mail ASC incorreto. Obtido: ' . implode(',', $byNome($emailAsc)));
    }
    $unidadeRows = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, [], 'unidade', 'asc');
    if (count($unidadeRows) !== 3) {
        failFast('Ordenação por Unidade não deveria alterar a quantidade de registros retornados');
    }
    ok('E-mail e Unidade: colunas ordenáveis adicionais funcionam sem erro (mesma whitelist)');

    // ===================== CENÁRIO 8/9 (controller): sort/dir inválidos via GET não quebram =====================
    $controller = new ColaboradoresController();
    $_GET = ['route' => 'colaboradores/index', 'cliente' => (string)$clienteXId, 'sort' => "1' OR '1'='1", 'dir' => 'lixo'];
    ob_start();
    $controller->index();
    $htmlInvalidSort = (string)ob_get_clean();
    if (strpos($htmlInvalidSort, '<table') === false) {
        failFast('Cenário 8/9: sort/dir inválidos via GET não podem quebrar a listagem');
    }
    ok('Cenário 8/9 (controller): sort/dir inválidos via GET não quebram a listagem (fallback seguro de ponta a ponta)');

    // ===================== CENÁRIO 11: filtros preservados junto com a ordenação =====================
    $depAlfaId = $depIds[0];
    $filteredSorted = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 10, ['departamento_id' => $depAlfaId], 'nome', 'asc');
    if (count($filteredSorted) !== 1 || $byNome($filteredSorted) !== ['Colab Bravo']) {
        failFast('Cenário 11: filtro por departamento + ordenação por nome deveriam funcionar juntos, sem um anular o outro');
    }
    ok('Cenário 11: filtro (departamento) e ordenação (nome) funcionam combinados, sem um sobrescrever o outro');

    // Ponta-a-ponta via filterAjax(): filtros + sort na querystring, preservados na resposta.
    $_GET = ['route' => 'colaboradores/filterAjax', 'cliente' => (string)$clienteXId, 'sort' => 'nome', 'dir' => 'asc'];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    if (!is_array($payload) || empty($payload['ok'])) {
        failFast('filterAjax() deveria responder ok=true');
    }
    if (($payload['filters']['sort'] ?? '') !== 'nome' || ($payload['filters']['dir'] ?? '') !== 'asc') {
        failFast('Cenário 11 (ajax): resposta de filterAjax() deveria ecoar sort/dir aplicados');
    }
    if (strpos((string)$payload['rows_html'], 'Colab Alpha') === false || strpos((string)$payload['rows_html'], 'Colab Charlie') === false) {
        failFast('Cenário 11 (ajax): rows_html deveria conter os colaboradores ordenados');
    }
    ok('Cenário 11 (ajax): filterAjax() aplica e ecoa sort/dir junto com os demais filtros');

    // ===================== CENÁRIO 12: paginação preserva ordenação =====================
    $page1 = $colabModel->paginatedByClientesWithFilters([$clienteXId], 1, 2, [], 'nome', 'asc');
    $page2 = $colabModel->paginatedByClientesWithFilters([$clienteXId], 2, 2, [], 'nome', 'asc');
    if ($byNome($page1) !== ['Colab Alpha', 'Colab Bravo'] || $byNome($page2) !== ['Colab Charlie']) {
        failFast('Cenário 12: paginação com ordenação por nome ASC deveria manter a sequência entre páginas (Alpha,Bravo | Charlie). Obtido: ' . implode(',', $byNome($page1)) . ' | ' . implode(',', $byNome($page2)));
    }
    ok('Cenário 12: paginação preserva a ordenação escolhida entre páginas (sem repetição nem embaralhamento)');

    // ===================== CENÁRIOS 13/14: tenant/RBAC =====================
    $idColabY = $colabModel->create([
        'nome' => 'Colab Cliente Y ' . $suffix,
        'email' => 'y.' . $suffix . '@test.local',
        'funcao_id' => $funcaoIds[0],
        'lider' => 'não',
        'cliente_id' => $clienteYId,
        'ativo' => 1,
    ]);
    // create() acima roda como Instituto (sem tenant restriction); trocamos a sessão só para o teste de leitura.
    $_SESSION['user'] = roleUser('cliente_admin', $clienteXId, [$clienteXId]);
    $asAdminSorted = $colabModel->paginatedByClientesWithFilters([$clienteXId, $clienteYId], 1, 10, [], 'nome', 'desc');
    foreach ($asAdminSorted as $row) {
        if ((int)$row['cliente_id'] === $clienteYId) {
            failFast('Cenário 13: Cliente Admin restrito ao Cliente X não pode enxergar colaborador do Cliente Y, mesmo pedindo ordenação por nome');
        }
    }
    if (count($asAdminSorted) !== 3) {
        failFast('Cenário 13 (controle): Cliente Admin deveria continuar enxergando os 3 colaboradores do próprio tenant');
    }
    ok('Cenário 13: Cliente Admin permanece restrito ao próprio tenant mesmo pedindo ordenação (tenantInCondition preservado)');

    $_SESSION['user'] = roleUser('instituto');
    $asInstitutoSorted = $colabModel->paginatedByClientesWithFilters([$clienteXId, $clienteYId], 1, 10, [], 'nome', 'desc');
    $foundY = false;
    foreach ($asInstitutoSorted as $row) {
        if ((int)$row['cliente_id'] === $clienteYId) { $foundY = true; }
    }
    if (!$foundY) {
        failFast('Cenário 14: Instituto deveria continuar enxergando colaboradores de qualquer empresa, inclusive ordenando');
    }
    if (!AccessControl::canAccessRoute('colaboradores/index', 'GET', roleUser('instituto'))
        || !AccessControl::canAccessRoute('colaboradores/index', 'GET', roleUser('cliente_admin'))) {
        failFast('Cenário 14: RBAC de acesso a colaboradores/index não pode ter sido alterado para Instituto/Cliente Admin');
    }
    ok('Cenário 14: Instituto preserva o escopo total; RBAC de acesso à rota não foi alterado');

    // ===================== CENÁRIO 15: sem regressão em cadastro/edição =====================
    $_GET = ['route' => 'colaboradores/create', 'cliente' => (string)$clienteXId];
    ob_start();
    $controller->create();
    $createHtml = (string)ob_get_clean();
    if (strpos($createHtml, 'name="nome"') === false) {
        failFast('Cenário 15: formulário de cadastro de colaborador parou de renderizar');
    }
    $_GET = ['route' => 'colaboradores/edit', 'id' => (string)$idBravo, 'cliente' => (string)$clienteXId];
    ob_start();
    $controller->edit();
    $editHtml = (string)ob_get_clean();
    if (strpos($editHtml, 'name="nome"') === false) {
        failFast('Cenário 15: formulário de edição de colaborador parou de renderizar');
    }
    ok('Cenário 15: cadastro e edição de colaboradores continuam funcionando sem regressão');

    $colabIds[] = $idColabY;
    echo "colaboradores_ordenacao_cabecalho_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($colabIds)) {
        $pdo->exec('DELETE FROM colaboradores WHERE id IN (' . implode(',', array_map('intval', array_filter($colabIds))) . ')');
    }
    if (!empty($funcaoIds)) {
        $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', array_filter($funcaoIds))) . ')');
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', array_filter($setorIds))) . ')');
    }
    if (!empty($depIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', array_filter($depIds))) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', array_filter($clienteIds))) . ')');
    }
    unset($_SESSION['user']);
}
