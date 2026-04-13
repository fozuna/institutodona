<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacaoPublicaController;

unset($_SESSION['user']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/public_html/public/avaliacao/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$_SERVER['SCRIPT_NAME'] = '/public_html/public/avaliacao/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_GET = ['token' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'];
$_POST = [];

$controller = new AvaliacaoPublicaController();
ob_start();
$controller->handle();
$html = (string)ob_get_clean();

$_GET = ['resource' => 'validate', 'token' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'];
ob_start();
$controller->validateApi();
$json = (string)ob_get_clean();
$data = json_decode($json, true);

echo json_encode([
    'renders_public_page_without_auth' => str_contains($html, 'Link inválido ou expirado'),
    'does_not_render_login' => !str_contains($html, 'Entrar'),
    'public_api_works_without_auth' => (bool)($data['success'] ?? false),
    'public_api_valid_flag' => $data['data']['valid'] ?? null,
], JSON_UNESCAPED_UNICODE);
