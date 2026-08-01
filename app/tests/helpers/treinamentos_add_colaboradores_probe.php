<?php
require_once __DIR__ . '/../../autoload.php';

use App\Controllers\TreinamentosController;
use App\Core\Security;

$treinamentoId = (int)($argv[1] ?? 0);
$colaboradorIds = array_slice($argv, 2);

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

register_shutdown_function(function () {
    echo 'PROBE_RESULT:' . json_encode([
        'flash_success' => $_SESSION['flash_success'] ?? null,
        'flash_error' => $_SESSION['flash_error'] ?? null,
    ]) . "\n";
});

$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['route'] = 'treinamentos/add_colaboradores';
$_POST = [
    'csrf' => $csrf,
    'treinamento_id' => (string)$treinamentoId,
    'colaborador_ids' => $colaboradorIds,
];

(new TreinamentosController())->addColaboradores();
