<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Database\Database;
use App\Database\MigrationRunner;
use App\Models\ClienteModel;

function assert_true($condition, $message) {
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

try {
    Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

(new MigrationRunner())->applyAll();

$clientes = new ClienteModel();
$suffix = uniqid('dash', true);
$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Dashboard ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($clienteId > 0, 'Criou cliente para teste de dashboard');

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'route' => 'dashboard/metrics',
    'month_start' => '2026-01',
    'month_end' => '2026-01',
];

ob_start();
(new DashboardController())->metrics();
$out = trim((string)ob_get_clean());
$json = json_decode($out, true);
assert_true(is_array($json) && ($json['ok'] ?? false) === true, 'Endpoint dashboard/metrics retorna ok');
assert_true(isset($json['cronograma'], $json['biblioteca'], $json['planoacao'], $json['auditorias'], $json['indicadores'], $json['treinamentos']), 'Payload contém todas as seções esperadas');

$_GET = [
    'route' => 'dashboard/metrics',
    'month_start' => '2026-02',
    'month_end' => '2026-01',
];
ob_start();
(new DashboardController())->metrics();
$out = trim((string)ob_get_clean());
$json = json_decode($out, true);
assert_true(is_array($json) && ($json['ok'] ?? true) === false, 'Bloqueia filtro com mês final anterior ao inicial');

echo "Dashboard metrics smoke tests passed.\n";
