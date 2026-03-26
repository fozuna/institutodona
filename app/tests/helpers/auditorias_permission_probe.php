<?php
require_once __DIR__ . '/../../autoload.php';

use App\Controllers\AuditoriasController;

session_start();

$role = $argv[1] ?? 'cliente';
$action = $argv[2] ?? 'store';

$_SESSION['user'] = [
    'id' => 9901,
    'nome' => 'Probe Auditorias',
    'email' => 'probe.auditorias@test.local',
    'tipo_acesso' => $role,
    'id_cliente' => 1,
    'allowed_client_ids' => [1],
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => 'invalid'];

$controller = new AuditoriasController();
if ($action === 'store') {
    $controller->store();
} elseif ($action === 'update') {
    $controller->update();
} elseif ($action === 'delete') {
    $controller->delete();
} else {
    $controller->auditar();
}
