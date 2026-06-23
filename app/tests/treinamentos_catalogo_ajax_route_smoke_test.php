<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;

function assert_true($condition, $message)
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

(new MigrationRunner())->applyAll();

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();

$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$suffix = uniqid('catalogo_ajax_', true);
$matrizId = $clientes->create([
    'nome_empresa' => 'Matriz Catalogo Ajax ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Teste',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$filialId = $clientes->create([
    'nome_empresa' => 'Filial Catalogo Ajax ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
$filialBId = $clientes->create([
    'nome_empresa' => 'Filial B Catalogo Ajax ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
assert_true($matrizId > 0 && $filialId > 0 && $filialBId > 0, 'Criou matriz e filiais para testar a rota AJAX do catálogo');

$departamentoCompartilhadoId = $departamentos->create([
    'nome' => 'Departamento Ajax Compartilhado ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId, $filialId],
]);
$departamentoFilialAId = $departamentos->create([
    'nome' => 'Departamento Ajax Filial A ' . $suffix,
    'cliente_id' => $filialId,
    'cliente_ids' => [$filialId],
]);
$departamentoFilialBId = $departamentos->create([
    'nome' => 'Departamento Ajax Filial B ' . $suffix,
    'cliente_id' => $filialBId,
    'cliente_ids' => [$filialBId],
]);
assert_true(
    $departamentoCompartilhadoId > 0 && $departamentoFilialAId > 0 && $departamentoFilialBId > 0,
    'Criou departamentos compartilhados e exclusivos para as filiais'
);

$callRoute = static function (int $clienteId): array {
    $_GET = [
        'route' => 'treinamentos/catalogoOptionsAjax',
        'cliente_id' => $clienteId,
    ];
    $_SERVER['REQUEST_URI'] = '/index.php?route=treinamentos/catalogoOptionsAjax&cliente_id=' . $clienteId;

    ob_start();
    require __DIR__ . '/../../public_html/index.php';
    $output = trim((string)ob_get_clean());

    $payload = json_decode($output, true);
    assert_true(is_array($payload), 'Rota AJAX retorna JSON válido');
    assert_true(!empty($payload['ok']), 'Rota AJAX responde com sucesso');

    return $payload;
};

$payloadFilialA = $callRoute($filialId);
$payloadFilialB = $callRoute($filialBId);

$departamentoIdsFilialA = array_map(
    static fn(array $row): int => (int)($row['id'] ?? 0),
    (array)($payloadFilialA['catalogo']['departamentos'] ?? [])
);
$departamentoIdsFilialB = array_map(
    static fn(array $row): int => (int)($row['id'] ?? 0),
    (array)($payloadFilialB['catalogo']['departamentos'] ?? [])
);
assert_true(in_array($departamentoCompartilhadoId, $departamentoIdsFilialA, true), 'Filial A recebe departamento compartilhado da matriz');
assert_true(in_array($departamentoFilialAId, $departamentoIdsFilialA, true), 'Filial A recebe seu departamento exclusivo');
assert_true(!in_array($departamentoFilialBId, $departamentoIdsFilialA, true), 'Filial A não recebe departamento exclusivo da filial B');
assert_true(!in_array($departamentoCompartilhadoId, $departamentoIdsFilialB, true), 'Filial B não recebe departamento nao compartilhado com ela');
assert_true(!in_array($departamentoFilialAId, $departamentoIdsFilialB, true), 'Filial B não recebe departamento exclusivo da filial A');
assert_true(in_array($departamentoFilialBId, $departamentoIdsFilialB, true), 'Filial B recebe seu departamento exclusivo');

echo "OK: Criou matriz e filiais para testar a rota AJAX do catálogo\n";
echo "OK: Criou departamentos compartilhados e exclusivos para as filiais\n";
echo "OK: Rota AJAX retorna JSON válido\n";
echo "OK: Rota AJAX responde com sucesso\n";
echo "OK: Filial A recebe departamento compartilhado da matriz\n";
echo "OK: Filial A recebe seu departamento exclusivo\n";
echo "OK: Filial A não recebe departamento exclusivo da filial B\n";
echo "OK: Filial B não recebe departamento nao compartilhado com ela\n";
echo "OK: Filial B não recebe departamento exclusivo da filial A\n";
echo "OK: Filial B recebe seu departamento exclusivo\n";
echo "treinamentos_catalogo_ajax_route_smoke_test passed.\n";
