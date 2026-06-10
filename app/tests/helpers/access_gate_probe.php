<?php
require_once __DIR__ . '/../../autoload.php';

use App\Core\BaseController;

session_start();

$route = $argv[1] ?? 'dashboard/index';
$method = $argv[2] ?? 'GET';
$role = $argv[3] ?? 'reader';
$allowedClientIds = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', (string)($argv[4] ?? '1')) ?: [])));
$queryString = (string)($argv[5] ?? '');

$_GET['route'] = $route;
if ($queryString !== '') {
    parse_str($queryString, $extraQuery);
    if (is_array($extraQuery)) {
        foreach ($extraQuery as $key => $value) {
            $_GET[$key] = $value;
        }
    }
}
$_SERVER['REQUEST_METHOD'] = strtoupper($method);
$_SESSION['user'] = [
    'id' => 9999,
    'nome' => 'Probe',
    'email' => 'probe@test.local',
    'tipo_acesso' => $role,
    'id_cliente' => $allowedClientIds[0] ?? 1,
    'allowed_client_ids' => !empty($allowedClientIds) ? $allowedClientIds : [1],
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
