<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Core\PdfSupport;
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

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível no ambiente atual.\n";
    exit(0);
}

try {
    Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

(new MigrationRunner())->applyAll();

$clientes = new ClienteModel();
$suffix = uniqid('dashpdf', true);
$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Dashboard PDF ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($clienteId > 0, 'Criou cliente para teste de dashboard/pdf');

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'route' => 'dashboard/pdf',
    'month_start' => '2026-01',
    'month_end' => '2026-01',
    'clientes' => [$clienteId],
];

header_remove();
ob_start();
(new DashboardController())->pdf();
$pdf = (string)ob_get_clean();

assert_true(substr($pdf, 0, 4) === '%PDF', 'dashboard/pdf retorna PDF');
assert_true(strlen($pdf) > 1000, 'dashboard/pdf retorna um PDF com tamanho mínimo');

$pageCount = 0;
if (preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdf, $m)) {
    $pageCount = count($m[0] ?? []);
}
assert_true($pageCount <= 1, 'dashboard/pdf deve gerar 1 página em cenário padrão (atual=' . $pageCount . ')');

echo "Dashboard PDF smoke tests passed.\n";
