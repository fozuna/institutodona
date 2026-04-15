<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacoesController;
use App\Controllers\PublicAvaliacoesController;
use App\Models\AvaliacaoModel;

class PublicAvaliacoesControllerListingDouble extends PublicAvaliacoesController
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

putenv('PUBLIC_AVALIACOES_DEFAULT_EMPRESA=Empresa Pública Fixa Listagem');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SCRIPT_NAME'] = '/public/avaliacoes.php';

$controller = new AvaliacoesController();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => \App\Core\Security::csrfToken()];
ob_start();
$controller->apiGeneratePublicLink();
$json = (string)ob_get_clean();
$data = json_decode($json, true);

$publicUrl = (string)($data['data']['public_url'] ?? '');
if ($publicUrl === '') {
    echo 'NO_URL';
    exit(0);
}

$publicController = new PublicAvaliacoesControllerListingDouble();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'finish',
    'public_nome' => 'Lead Listagem',
    'public_empresa' => 'Empresa Pública Fixa Listagem',
    'public_whatsapp' => '11999999999',
    'public_email' => 'lead.listagem@example.com',
    'public_numero_funcionarios' => 30,
    'public_numero_lideres' => 4,
    'public_faturamento_anual' => 300000,
    'public_tomador_decisao' => 1,
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
    if ((string)($item['empresa_nome'] ?? '') === 'Empresa Pública Fixa Listagem' && (string)($item['nome'] ?? '') === 'Lead Listagem') {
        $matched = $item;
        break;
    }
}

echo json_encode([
    'static_public_url_present' => $publicUrl !== '',
    'materialized_id' => (int)($matched['id'] ?? 0),
    'listed_in_avaliacoes_index' => !empty($matched),
    'listed_empresa' => $matched['empresa_nome'] ?? null,
    'listed_nome' => $matched['nome'] ?? null,
    'listed_status' => $matched['publico_status'] ?? null,
], JSON_UNESCAPED_UNICODE);
