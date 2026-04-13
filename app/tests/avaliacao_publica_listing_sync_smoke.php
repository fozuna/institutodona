<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacoesController;
use App\Core\Security;
use App\Database\Database;
use App\Models\AvaliacaoModel;
use App\Models\AvaliacaoPublicaModel;

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

$token = (string)($data['data']['token'] ?? '');
if ($token === '') {
    echo 'NO_TOKEN';
    exit(0);
}

$publicModel = new AvaliacaoPublicaModel();
$publicModel->startByToken($token, [
    'nome' => 'Lead Listagem',
    'empresa' => 'Empresa Standalone',
    'whatsapp' => '11999999999',
    'email' => 'lead.listagem@example.com',
    'numero_funcionarios' => 30,
    'numero_lideres' => 4,
    'faturamento_anual' => 300000,
    'tomador_decisao' => 1,
]);
$publicModel->concludeByToken($token, [
    'respostas_json' => json_encode([
        'financeiro' => [1],
        'mercado' => [1, 2],
        'pessoas' => [1],
        'processo' => [1, 2],
    ]),
    'nota_financeiro' => 1,
    'nota_mercado' => 2,
    'nota_pessoas' => 1,
    'nota_processo' => 2,
    'realidade_financeiro' => 14,
    'realidade_mercado' => 29,
    'realidade_pessoas' => 14,
    'realidade_processo' => 29,
]);

$materializedId = $publicModel->materializeStandaloneToAvaliacao($token);
$avaliacaoModel = new AvaliacaoModel();
$items = $avaliacaoModel->all();
$matched = null;
foreach ($items as $item) {
    if ((string)($item['publico_token'] ?? '') === $token) {
        $matched = $item;
        break;
    }
}

echo json_encode([
    'token_generated' => $token !== '',
    'materialized_id' => $materializedId,
    'listed_in_avaliacoes_index' => !empty($matched),
    'listed_empresa' => $matched['empresa_nome'] ?? null,
    'listed_nome' => $matched['nome'] ?? null,
    'listed_status' => $matched['publico_status'] ?? null,
], JSON_UNESCAPED_UNICODE);
