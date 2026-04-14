<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacaoPublicaController;
use App\Models\AvaliacaoPublicaModel;

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$publicModel = new AvaliacaoPublicaModel();
$public = $publicModel->createStandaloneLink();
$slug = (string)($public['slug'] ?? '');

unset($_SESSION['user']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/avaliar/' . $slug;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_GET = ['slug' => $slug];
$_POST = [];

$controller = new AvaliacaoPublicaController();
ob_start();
$controller->handle();
$html = (string)ob_get_clean();

$_GET = ['resource' => 'validate', 'slug' => $slug];
ob_start();
$controller->validateApi();
$json = (string)ob_get_clean();
$data = json_decode($json, true);

echo json_encode([
    'renders_public_page_without_auth' => str_contains($html, 'Dados iniciais'),
    'does_not_render_login' => !str_contains($html, 'Entrar'),
    'public_api_works_without_auth' => (bool)($data['success'] ?? false),
    'public_api_valid_flag' => $data['data']['valid'] ?? null,
    'public_slug_present' => !empty($data['data']['slug']),
], JSON_UNESCAPED_UNICODE);
