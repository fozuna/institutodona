<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ClientesController;
use App\Core\Auth;

$controller = new ClientesController();

$run = static function (array $user, int $clienteId) use ($controller): array {
    $_SESSION['user'] = $user;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['id' => $clienteId];
    http_response_code(200);
    ob_start();
    $controller->exportPlanos();
    $output = (string)ob_get_clean();
    return [
        'status' => http_response_code(),
        'output' => $output,
    ];
};

$clienteResult = $run([
    'id' => 10,
    'nome' => 'Cliente Admin',
    'email' => 'cliente@example.com',
    'tipo_acesso' => 'cliente_admin',
    'id_cliente' => 999999,
    'allowed_client_ids' => [999999],
], 999999);
$clientePermission = Auth::canExportPlanosAcao();

$consultorResult = $run([
    'id' => 20,
    'nome' => 'Consultor',
    'email' => 'consultor@example.com',
    'tipo_acesso' => 'consultor',
    'id_cliente' => null,
    'allowed_client_ids' => [999999],
], 999999);

echo json_encode([
    'cliente_role_has_permission' => $clientePermission,
    'cliente_not_blocked_with_403' => $clienteResult['status'] !== 403,
    'cliente_empty_export_returns_400' => $clienteResult['status'] === 400,
    'consultor_gets_403' => $consultorResult['status'] === 403,
    'consultor_message_ok' => str_contains($consultorResult['output'], 'Sem permissao para exportar planos de acao.'),
], JSON_UNESCAPED_UNICODE);

if ($clienteResult['status'] === 403 || $consultorResult['status'] !== 403) {
    exit(1);
}
