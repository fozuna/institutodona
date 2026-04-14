<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Controllers\AvaliacoesController;
use App\Controllers\AvaliacaoPublicaController;
use App\Database\Database;

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$avaliacaoId = (int)$pdo->query('SELECT id FROM avaliacoes ORDER BY id DESC LIMIT 1')->fetchColumn();
if ($avaliacaoId <= 0) {
    echo 'NO_AVALIACAO';
    exit(0);
}

$controller = new AvaliacoesController();
$csrf = Security::csrfToken();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf' => $csrf,
    'avaliacao_id' => $avaliacaoId,
];
ob_start();
$controller->apiGeneratePublicLink();
$json = ob_get_clean();
$data = json_decode($json, true);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['id' => $avaliacaoId];
ob_start();
$controller->relatorioPdf();
$pdf = ob_get_clean();

$validateController = new AvaliacaoPublicaController();
$_GET = ['resource' => 'validate', 'slug' => (string)($data['data']['slug'] ?? '')];
ob_start();
$validateController->validateApi();
$validateJson = ob_get_clean();
$validateData = json_decode($validateJson, true);

echo json_encode([
    'api_success' => (bool)($data['success'] ?? false),
    'api_standalone' => (int)($data['data']['avaliacao_id'] ?? -1) === 0,
    'public_id_present' => !empty($data['data']['public_id']),
    'public_slug_present' => !empty($data['data']['slug']),
    'public_url' => $data['data']['public_url'] ?? null,
    'permanent' => $data['data']['permanent'] ?? null,
    'validate_available' => $validateData['data']['available'] ?? null,
    'pdf_header' => substr((string)$pdf, 0, 4),
    'pdf_size' => strlen((string)$pdf),
], JSON_UNESCAPED_UNICODE);
