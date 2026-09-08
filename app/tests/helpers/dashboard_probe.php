<?php
// Executa DashboardController::index() num processo separado, para validar o
// bloqueio cross-tenant (404 oculto) sem sofrer o exit() incondicional de
// BaseController::respondNotFound() - mesmo padrao de subprocesso usado em
// cronograma_evento_probe.php / atas_probe.php.
//
// Uso: php dashboard_probe.php <role> <userId> <allowedClientIdsCsv> <clienteGet>
// Stdout: ---PROBE_RESULT--- seguido de uma linha JSON com {status}.

namespace {
    require_once __DIR__ . '/../../autoload.php';

    use App\Controllers\DashboardController;

    session_start();

    register_shutdown_function(function () {
        echo "\n---PROBE_RESULT---\n";
        echo json_encode(['status' => http_response_code()], JSON_UNESCAPED_UNICODE) . "\n";
    });

    $role = (string)($argv[1] ?? 'cliente_admin');
    $userId = (int)($argv[2] ?? 501);
    $allowedCsv = (string)($argv[3] ?? '');
    $allowed = $allowedCsv !== '' ? array_map('intval', explode(',', $allowedCsv)) : [];
    $clienteGet = (string)($argv[4] ?? '');

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION['user'] = [
        'id' => $userId,
        'nome' => 'Probe Dashboard',
        'email' => 'probe.dashboard@test.local',
        'tipo_acesso' => $role,
        'allowed_client_ids' => $allowed,
    ];
    $_GET = ['route' => 'dashboard/index'];
    if ($clienteGet !== '') {
        $_GET['cliente'] = $clienteGet;
    }

    (new DashboardController())->index();
}
