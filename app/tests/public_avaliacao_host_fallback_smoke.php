<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\PublicAvaliacoesController;

putenv('PUBLIC_AVALIACOES_DEFAULT_EMPRESA');
putenv('PUBLIC_AVALIACOES_DEFAULT_CLIENTE_ID');
putenv('PUBLIC_AVALIACOES_CONTEXT_MAP');

unset($_SESSION['user']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/public/avaliacoes.php';
$_SERVER['SCRIPT_NAME'] = '/public/avaliacoes.php';
$_SERVER['HTTP_HOST'] = 'institutodona.traxter.com.br';
$_SERVER['HTTPS'] = 'off';
$_GET = [];
$_POST = [];

$controller = new PublicAvaliacoesController();
ob_start();
$controller->handle();
$html = (string)ob_get_clean();

echo json_encode([
    'renders_without_domain_mapping' => !str_contains($html, 'Formulario publico indisponivel para este dominio.'),
    'renders_step_1' => str_contains($html, 'Dados iniciais'),
    'empresa_derived_from_host' => str_contains($html, 'Institutodona'),
], JSON_UNESCAPED_UNICODE);
