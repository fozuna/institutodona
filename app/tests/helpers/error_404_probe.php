<?php
require_once __DIR__ . '/../../autoload.php';

/**
 * Probe genérico para cenários que terminam em exit() (denyAccess/renderNotFound),
 * rodado em subprocesso para não encerrar o processo de teste principal.
 *
 * Uso: php error_404_probe.php <mode> <route> <role> <ajax:0|1> <clienteId> <foreignClienteParam>
 * mode: 'unknown' (rota inexistente) | 'denied' (RBAC nega) | 'valid' (rota válida, regressão) | 'tenant' (tenant nega via ?cliente=)
 */

use App\Controllers\DashboardController;
use App\Controllers\ErrorController;
use App\Controllers\UsuariosController;

register_shutdown_function(function () {
    echo "\n---STATUS:" . http_response_code() . "---\n";
});

session_start();

$mode = $argv[1] ?? 'unknown';
$route = $argv[2] ?? '';
$role = $argv[3] ?? 'cliente';
$ajax = ($argv[4] ?? '0') === '1';
$clienteId = (int)($argv[5] ?? 1);
$foreignCliente = (int)($argv[6] ?? 0);

$_GET['route'] = $route;
if ($foreignCliente > 0) {
    $_GET['cliente'] = (string)$foreignCliente;
}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=' . $route . ($foreignCliente > 0 ? '&cliente=' . $foreignCliente : '');
if ($ajax) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

if ($role !== 'anon') {
    // id=999999999 evita que Auth::refreshScope() encontre um usuário real no
    // banco de dev e sobrescreva o tipo_acesso simulado aqui.
    $_SESSION['user'] = [
        'id' => 999999999,
        'nome' => 'Probe',
        'email' => 'probe.404@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => $clienteId,
        'allowed_client_ids' => [$clienteId],
    ];
}

if ($mode === 'unknown') {
    (new ErrorController())->notFound();
} elseif ($mode === 'denied') {
    // Usuários "cliente"/"reader" não têm o módulo admin/client_admin liberado
    // para rotas como usuarios/index - dispara authorizeRoute() -> denyAccess().
    (new UsuariosController())->index();
} elseif ($mode === 'valid' || $mode === 'tenant') {
    // dashboard/index não exige módulo especial; usado para isolar a checagem
    // de tenant (?cliente=) feita em authorizeRoute() independente de RBAC de módulo.
    (new DashboardController())->index();
}
