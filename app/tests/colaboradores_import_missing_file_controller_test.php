<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ColaboradoresController;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['route'] = 'colaboradores/import';
$_POST = ['csrf' => \App\Core\Security::csrfToken()];
$_FILES = [];

ob_start();
$controller = new ColaboradoresController();
$controller->import();
$output = ob_get_clean();

$payload = json_decode((string)$output, true);
if (!is_array($payload) || !empty($payload['ok']) || ($payload['message'] ?? '') !== 'Arquivo obrigatório.') {
    failFast('Resposta inesperada para arquivo ausente: ' . (string)$output);
}

ok('Validação de arquivo obrigatório permanece ativa');
echo "All colaboradores import missing file controller tests passed.\n";

