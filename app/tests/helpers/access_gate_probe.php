<?php
require_once __DIR__ . '/../../autoload.php';

use App\Core\BaseController;

session_start();

$route = $argv[1] ?? 'dashboard/index';
$method = $argv[2] ?? 'GET';
$role = $argv[3] ?? 'reader';

$_GET['route'] = $route;
$_SERVER['REQUEST_METHOD'] = strtoupper($method);
$_SESSION['user'] = [
    'id' => 9999,
    'nome' => 'Probe',
    'email' => 'probe@test.local',
    'tipo_acesso' => $role,
    'id_cliente' => 1,
    'allowed_client_ids' => [1],
];

class AccessGateProbeController extends BaseController
{
    public function run(): void
    {
        $this->requireLogin();
        echo 'ALLOWED';
    }
}

(new AccessGateProbeController())->run();
