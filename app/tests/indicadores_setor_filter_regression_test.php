<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

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
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['indicador_ids' => [], 'setor_ids' => [], 'departamento_ids' => [], 'unidade_id' => 0, 'cliente_ids' => []];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        foreach ($cleanup['indicador_ids'] as $id) { $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

function makeCliente(PDO $pdo, string $suffix, string $tag, array &$cleanup): int {
    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
    $stmt->execute(['nome' => "Cliente Setor {$tag} {$suffix}", 'cnpj' => '77.777.7' . $tag . '/0001-77', 'contato' => 'Test']);
    $id = (int)$pdo->lastInsertId();
    $cleanup['cliente_ids'][] = $id;
    return $id;
}

function makeSetor(PDO $pdo, string $suffix, string $tag, int $clienteId, array &$cleanup): array {
    $stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
    $stmt->execute(['nome' => "Dep {$tag} {$suffix}", 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $cleanup['departamento_ids'][] = $depId;

    $stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
    $stmt->execute(['nome' => "Setor {$tag} {$suffix}", 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $cleanup['setor_ids'][] = $setorId;

    return ['departamento_id' => $depId, 'setor_id' => $setorId];
}

$clienteA = makeCliente($pdo, $suffix, 'A', $cleanup);
$clienteB = makeCliente($pdo, $suffix, 'B', $cleanup);

$setorA1 = makeSetor($pdo, $suffix, 'A1', $clienteA, $cleanup);
$setorA2 = makeSetor($pdo, $suffix, 'A2', $clienteA, $cleanup);
$setorB1 = makeSetor($pdo, $suffix, 'B1', $clienteB, $cleanup);

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade Setor Teste ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$model = new IndicadorModel();
$makeIndicador = function (int $clienteId, array $setor, string $nome) use ($model, $unidadeId, &$cleanup) {
    $payload = [
        'cliente_id' => $clienteId,
        'indicador' => $nome,
        'departamento_id' => $setor['departamento_id'],
        'setor_id' => $setor['setor_id'],
        'responsavel_ids' => [],
        'periodicidade_tipo' => 'mensal',
        'data_inicial' => date('Y-m-01'),
        'data_final' => date('Y-m-t'),
        'valor' => '10',
        'tipo_meta' => 'minimo',
        'unidade_medida_id' => $unidadeId,
        'valor_minimo' => '0',
        'valor_maximo' => '100',
    ];
    $errors = $model->validate($payload);
    if ($errors) failFast('Payload inválido para ' . $nome . ': ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
    $id = $model->create($payload, 1);
    if ($id <= 0) failFast('Falha ao criar indicador ' . $nome);
    $cleanup['indicador_ids'][] = $id;
    return $id;
};

$indA1 = $makeIndicador($clienteA, $setorA1, 'Indicador A1 ' . $suffix);
$indA2 = $makeIndicador($clienteA, $setorA2, 'Indicador A2 ' . $suffix);
$indB1 = $makeIndicador($clienteB, $setorB1, 'Indicador B1 ' . $suffix);
ok('Indicadores criados em setores distintos (dois no cliente A, um no cliente B)');

// 1) Filtro por setor restringe corretamente dentro do mesmo cliente.
$resultA1 = $model->search(['cliente_id' => $clienteA, 'setor_id' => $setorA1['setor_id']]);
$idsA1 = array_map(static fn(array $r): int => (int)$r['id'], $resultA1);
if (!in_array($indA1, $idsA1, true)) failFast('Filtro por setor não incluiu o indicador do setor filtrado');
if (in_array($indA2, $idsA1, true)) failFast('Filtro por setor incluiu indicador de outro setor do mesmo cliente');
ok('Filtro por setor restringe indicadores corretamente dentro do mesmo cliente');

// 2) Isolamento multiempresa: setor de outro cliente não vaza indicadores ao ser combinado com cliente_id de A.
$resultCrossTenant = $model->search(['cliente_id' => $clienteA, 'setor_id' => $setorB1['setor_id']]);
if (!empty($resultCrossTenant)) failFast('Filtro por setor de outro cliente vazou indicadores fora do isolamento multiempresa');
ok('Filtro por setor não vaza dados entre clientes (setor de B não retorna indicadores ao filtrar cliente A)');

// 3) Sem filtro de setor, mantém comportamento atual (todos os indicadores do cliente).
$resultAll = $model->search(['cliente_id' => $clienteA]);
$idsAll = array_map(static fn(array $r): int => (int)$r['id'], $resultAll);
if (!in_array($indA1, $idsAll, true) || !in_array($indA2, $idsAll, true)) {
    failFast('Sem filtro de setor, deveria retornar todos os indicadores do cliente');
}
ok('Sem filtro de setor, retorna todos os indicadores do cliente (compatibilidade preservada)');

// 4) Controller: endpoint apiSetoresByCliente só retorna setores do cliente informado (RBAC/multiempresa).
$_GET = ['route' => 'indicadores/apiSetoresByCliente', 'cliente_id' => (string)$clienteA];
ob_start();
(new IndicadoresController())->apiSetoresByCliente();
$out = (string)ob_get_clean();
$payload = json_decode($out, true);
if (!is_array($payload) || empty($payload['success']) || !is_array($payload['items'])) {
    failFast('apiSetoresByCliente não retornou payload válido: ' . $out);
}
$returnedSetorIds = array_map(static fn(array $s): int => (int)$s['id'], $payload['items']);
if (!in_array($setorA1['setor_id'], $returnedSetorIds, true) || !in_array($setorA2['setor_id'], $returnedSetorIds, true)) {
    failFast('apiSetoresByCliente não retornou os setores esperados do cliente A');
}
if (in_array($setorB1['setor_id'], $returnedSetorIds, true)) {
    failFast('apiSetoresByCliente vazou setor de outro cliente (violação de isolamento multiempresa)');
}
ok('apiSetoresByCliente retorna apenas setores do cliente informado, sem vazar dados de outro cliente');

// 5) Controller index(): filtro de setor combinado com AJAX retorna apenas o indicador esperado.
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
$_GET = ['route' => 'indicadores/index', 'cliente' => (string)$clienteA, 'setor' => (string)$setorA1['setor_id'], 'ajax' => '1'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
ob_start();
(new IndicadoresController())->index();
$html = (string)ob_get_clean();
if (strpos($html, 'Indicador A1 ' . $suffix) === false) {
    failFast('Listagem com filtro de setor não exibiu o indicador esperado');
}
if (strpos($html, 'Indicador A2 ' . $suffix) !== false) {
    failFast('Listagem com filtro de setor exibiu indicador de outro setor');
}
ok('Controller index() aplica o filtro de setor corretamente via AJAX');

echo "Indicadores setor filter regression tests passed.\n";
ob_end_flush();
