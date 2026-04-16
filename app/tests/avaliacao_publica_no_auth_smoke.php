<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\PublicAvaliacoesController;

putenv('PUBLIC_AVALIACOES_DEFAULT_EMPRESA=Empresa Pública Fixa');

unset($_SESSION['user']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/public/avaliacoes.php';
$_SERVER['SCRIPT_NAME'] = '/public/avaliacoes.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_GET = [];
$_POST = [];

$controller = new PublicAvaliacoesController();
ob_start();
$controller->handle();
$html = (string)ob_get_clean();

echo json_encode([
    'renders_public_page_without_auth' => str_contains($html, 'Dados iniciais'),
    'does_not_render_login' => !str_contains($html, 'Entrar'),
    'public_buttons_reset_script_present' => str_contains($html, 'resetPublicActionButtons') && str_contains($html, 'public-action-button'),
    'continue_button_not_disabled_in_html' => preg_match('/data-public-action="continue"[^>]*disabled/i', $html) !== 1,
    'public_form_autocomplete_off' => str_contains($html, 'autocomplete="off"'),
    'public_endpoint_is_static' => str_contains($html, '/public/avaliacoes.php'),
    'empresa_field_editable' => preg_match('/name="public_empresa"[^>]*readonly/i', $html) !== 1,
    'faturamento_is_select' => str_contains($html, 'name="public_faturamento_faixa_id"') && !str_contains($html, 'name="public_faturamento_anual"'),
    'reset_button_visible' => str_contains($html, 'Limpar formulário'),
    'submit_button_visible' => str_contains($html, 'Continuar'),
], JSON_UNESCAPED_UNICODE);
