<?php
require_once __DIR__ . '/../../autoload.php';

use App\Controllers\TreinamentosController;

session_start();

$role = $argv[1] ?? 'cliente';
$clienteId = (int)($argv[2] ?? 1);

$_GET['route'] = 'treinamentos/create';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['user'] = [
    'id' => 9999,
    'nome' => 'Probe',
    'email' => 'probe.treinamentos@test.local',
    'tipo_acesso' => $role,
    'id_cliente' => $clienteId,
    'allowed_client_ids' => [$clienteId],
];

(new TreinamentosController())->create();
