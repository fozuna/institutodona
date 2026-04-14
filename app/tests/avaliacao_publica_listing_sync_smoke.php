<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacoesController;
use App\Controllers\AvaliacaoPublicaController;
use App\Core\Security;
use App\Models\AvaliacaoModel;

class AvaliacaoPublicaControllerListingDouble extends AvaliacaoPublicaController
{
    protected function redirect(string $url): void
    {
    }
}

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'POST';

$controller = new AvaliacoesController();
$_POST = ['csrf' => Security::csrfToken()];
ob_start();
$controller->apiGeneratePublicLink();
$json = (string)ob_get_clean();
$data = json_decode($json, true);

$slug = (string)($data['data']['slug'] ?? '');
if ($slug === '') {
    echo 'NO_SLUG';
    exit(0);
}

$publicController = new AvaliacaoPublicaControllerListingDouble();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'finish',
    'identifier' => $slug,
    'nome' => 'Lead Listagem',
    'empresa' => 'Empresa Standalone',
    'whatsapp' => '11999999999',
    'email' => 'lead.listagem@example.com',
    'numero_funcionarios' => 30,
    'numero_lideres' => 4,
    'faturamento_anual' => 300000,
    'tomador_decisao' => 1,
    'financeiro' => [1],
    'mercado' => [1, 2],
    'pessoas' => [1],
    'processo' => [1, 2],
];
ob_start();
$publicController->handle();
ob_end_clean();

$avaliacaoModel = new AvaliacaoModel();
$items = $avaliacaoModel->all();
$matched = null;
foreach ($items as $item) {
    if ((string)($item['empresa_nome'] ?? '') === 'Empresa Standalone' && (string)($item['nome'] ?? '') === 'Lead Listagem') {
        $matched = $item;
        break;
    }
}

echo json_encode([
    'slug_generated' => $slug !== '',
    'materialized_id' => (int)($matched['id'] ?? 0),
    'listed_in_avaliacoes_index' => !empty($matched),
    'listed_empresa' => $matched['empresa_nome'] ?? null,
    'listed_nome' => $matched['nome'] ?? null,
    'listed_status' => $matched['publico_status'] ?? null,
], JSON_UNESCAPED_UNICODE);
