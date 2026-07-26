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
        foreach ($cleanup['indicador_ids'] as $id) {
            $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

function makeCliente(PDO $pdo, string $suffix, string $tag, array &$cleanup): int {
    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
    $stmt->execute(['nome' => "Cliente ChartsDep {$tag} {$suffix}", 'cnpj' => '66.666.6' . $tag . '/0001-66', 'contato' => 'Test']);
    $id = (int)$pdo->lastInsertId();
    $cleanup['cliente_ids'][] = $id;
    return $id;
}

function makeDepartamentoSetor(PDO $pdo, string $suffix, string $tag, int $clienteId, array &$cleanup): array {
    $stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
    $stmt->execute(['nome' => "Dep ChartsDep {$tag} {$suffix}", 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $cleanup['departamento_ids'][] = $depId;

    $stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
    $stmt->execute(['nome' => "Setor ChartsDep {$tag} {$suffix}", 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $cleanup['setor_ids'][] = $setorId;

    return ['departamento_id' => $depId, 'setor_id' => $setorId];
}

$clienteA = makeCliente($pdo, $suffix, 'A', $cleanup);
$clienteB = makeCliente($pdo, $suffix, 'B', $cleanup);

$depSetorA1 = makeDepartamentoSetor($pdo, $suffix, 'A1', $clienteA, $cleanup);
$depSetorA2 = makeDepartamentoSetor($pdo, $suffix, 'A2', $clienteA, $cleanup);
$depSetorB1 = makeDepartamentoSetor($pdo, $suffix, 'B1', $clienteB, $cleanup);

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade ChartsDep Teste ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$model = new IndicadorModel();
$makeIndicador = function (int $clienteId, array $depSetor, string $nome) use ($model, $unidadeId, &$cleanup) {
    $payload = [
        'cliente_id' => $clienteId,
        'indicador' => $nome,
        'departamento_id' => $depSetor['departamento_id'],
        'setor_id' => $depSetor['setor_id'],
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

$indA1 = $makeIndicador($clienteA, $depSetorA1, 'Indicador ChartsDep A1 ' . $suffix);
$indA2 = $makeIndicador($clienteA, $depSetorA2, 'Indicador ChartsDep A2 ' . $suffix);
$indB1 = $makeIndicador($clienteB, $depSetorB1, 'Indicador ChartsDep B1 ' . $suffix);
ok('Indicadores criados em departamentos distintos (dois no cliente A, um no cliente B)');

// 1) IndicadorModel::search() filtra por departamento dentro do mesmo cliente.
$resultA1 = $model->search(['cliente_id' => $clienteA, 'departamento_id' => $depSetorA1['departamento_id']]);
$idsA1 = array_map(static fn(array $r): int => (int)$r['id'], $resultA1);
if (!in_array($indA1, $idsA1, true)) failFast('Filtro por departamento não incluiu o indicador do departamento filtrado');
if (in_array($indA2, $idsA1, true)) failFast('Filtro por departamento incluiu indicador de outro departamento do mesmo cliente');
ok('Filtro por departamento restringe indicadores corretamente dentro do mesmo cliente');

// 2) Isolamento multiempresa: departamento de outro cliente não vaza indicadores.
$resultCrossTenant = $model->search(['cliente_id' => $clienteA, 'departamento_id' => $depSetorB1['departamento_id']]);
if (!empty($resultCrossTenant)) failFast('Filtro por departamento de outro cliente vazou indicadores fora do isolamento multiempresa');
ok('Filtro por departamento não vaza dados entre clientes');

// 3) apiIndicadoresByCliente: retorna somente indicadores do departamento informado.
$_GET = ['route' => 'indicadores/apiIndicadoresByCliente', 'cliente_id' => (string)$clienteA, 'departamento_id' => (string)$depSetorA1['departamento_id']];
ob_start();
(new IndicadoresController())->apiIndicadoresByCliente();
$payload = json_decode((string)ob_get_clean(), true);
$ids = array_map(static fn(array $r): int => (int)$r['id'], $payload['items'] ?? []);
if (!in_array($indA1, $ids, true) || in_array($indA2, $ids, true)) {
    failFast('apiIndicadoresByCliente não restringiu corretamente pelo departamento informado');
}
ok('apiIndicadoresByCliente restringe indicadores ao departamento informado');

// 4) apiIndicadoresByCliente: departamento de outro cliente é rejeitado com segurança (cai no consolidado do cliente).
$_GET = ['route' => 'indicadores/apiIndicadoresByCliente', 'cliente_id' => (string)$clienteA, 'departamento_id' => (string)$depSetorB1['departamento_id']];
ob_start();
(new IndicadoresController())->apiIndicadoresByCliente();
$payloadRejected = json_decode((string)ob_get_clean(), true);
$idsRejected = array_map(static fn(array $r): int => (int)$r['id'], $payloadRejected['items'] ?? []);
if (!in_array($indA1, $idsRejected, true) || !in_array($indA2, $idsRejected, true)) {
    failFast('Departamento de outro cliente deveria ser ignorado e cair no consolidado do cliente A');
}
ok('apiIndicadoresByCliente rejeita departamento de outro cliente/tenant com segurança, sem quebrar');

// 5) Controller charts(): filtro de departamento restringe a série exibida no gráfico.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'route' => 'indicadores/charts',
    'cliente' => (string)$clienteA,
    'departamento_id' => (string)$depSetorA1['departamento_id'],
    'periodo_inicio' => date('Y-m-01'),
    'periodo_fim' => date('Y-m-t'),
];
ob_start();
(new IndicadoresController())->charts();
$html = (string)ob_get_clean();
if (strpos($html, 'Indicador ChartsDep A1 ' . $suffix) === false) {
    failFast('Gráfico filtrado por departamento A1 não exibiu o indicador esperado');
}
if (strpos($html, 'Indicador ChartsDep A2 ' . $suffix) !== false) {
    failFast('Gráfico filtrado por departamento A1 exibiu indicador de outro departamento');
}
ok('Controller charts() restringe a listagem de indicadores ao departamento filtrado');

// 6) Sem departamento selecionado, mantém o consolidado (compatibilidade preservada).
$_GET = [
    'route' => 'indicadores/charts',
    'cliente' => (string)$clienteA,
    'periodo_inicio' => date('Y-m-01'),
    'periodo_fim' => date('Y-m-t'),
];
ob_start();
(new IndicadoresController())->charts();
$htmlAll = (string)ob_get_clean();
if (strpos($htmlAll, 'Indicador ChartsDep A1 ' . $suffix) === false || strpos($htmlAll, 'Indicador ChartsDep A2 ' . $suffix) === false) {
    failFast('Sem departamento selecionado, o gráfico deveria listar os indicadores dos dois departamentos');
}
ok('Sem departamento selecionado, charts() mantém o consolidado do cliente (compatibilidade preservada)');

echo "Indicadores charts departamento filter regression tests passed.\n";
ob_end_flush();
