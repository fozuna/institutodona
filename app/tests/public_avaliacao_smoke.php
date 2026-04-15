<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacaoPublicaController;
use App\Controllers\AvaliacoesController;
use App\Core\Security;
use App\Database\Database;
use App\Services\AvaliacaoPdfService;

class AvaliacaoPublicaControllerTestDouble extends AvaliacaoPublicaController
{
    public string $redirectUrl = '';

    protected function redirect(string $url): void
    {
        $this->redirectUrl = $url;
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

$pdo = Database::getConnection();
$beforeCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();

$controller = new AvaliacoesController();
$_SERVER['REQUEST_METHOD'] = 'POST';
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

$publicController = new AvaliacaoPublicaControllerTestDouble();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['slug' => $slug];
$_POST = [];
ob_start();
$publicController->handle();
$step1Html = (string)ob_get_clean();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'start',
    'identifier' => $slug,
    'nome' => 'Cliente Público',
    'empresa' => 'Empresa Pública',
    'whatsapp' => '11999999999',
    'email' => 'cliente.publico@example.com',
    'numero_funcionarios' => '20',
    'numero_lideres' => '3',
    'faturamento_anual' => '200000',
    'tomador_decisao' => '1',
];
ob_start();
$publicController->handle();
$step2Html = (string)ob_get_clean();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'finish',
    'identifier' => $slug,
    'nome' => 'Cliente Público',
    'empresa' => 'Empresa Pública',
    'whatsapp' => '11999999999',
    'email' => 'cliente.publico@example.com',
    'numero_funcionarios' => '20',
    'numero_lideres' => '3',
    'faturamento_anual' => '200000',
    'tomador_decisao' => '1',
    'financeiro' => [1, 2],
    'mercado' => [1],
    'pessoas' => [],
    'processo' => [1, 2, 3],
];
ob_start();
$publicController->handle();
$finishOutput = (string)ob_get_clean();

$afterCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$latest = $pdo->query('SELECT id, nome, empresa_nome, nota_financeiro, nota_mercado, nota_pessoas, nota_processo FROM avaliacoes ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$service = new AvaliacaoPdfService();
$pdfPath = $service->pdfPath((int)($latest['id'] ?? 0));
parse_str((string)parse_url($publicController->redirectUrl, PHP_URL_QUERY), $redirectQuery);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'slug' => $slug,
    'download' => 'pdf',
    'avaliacao_id' => (int)($redirectQuery['avaliacao_id'] ?? 0),
    'sig' => (string)($redirectQuery['sig'] ?? ''),
];
ob_start();
$publicController->handle();
$publicPdf = (string)ob_get_clean();

echo json_encode([
    'slug' => $slug,
    'public_link_renders_step1' => str_contains($step1Html, 'Dados iniciais'),
    'step1_advances_to_step2' => str_contains($step2Html, 'Questionário da avaliação'),
    'created_new_avaliacao' => $afterCount === ($beforeCount + 1),
    'redirected_after_finish' => str_contains($publicController->redirectUrl, 'submitted=1'),
    'redirect_has_pdf_signature' => !empty($redirectQuery['sig']),
    'finish_output_empty' => $finishOutput === '',
    'pdf_cached' => is_file($pdfPath),
    'public_pdf_header' => substr($publicPdf, 0, 4),
    'latest_nome' => $latest['nome'] ?? null,
    'latest_empresa' => $latest['empresa_nome'] ?? null,
    'latest_total' => (int)($latest['nota_financeiro'] ?? 0) + (int)($latest['nota_mercado'] ?? 0) + (int)($latest['nota_pessoas'] ?? 0) + (int)($latest['nota_processo'] ?? 0),
], JSON_UNESCAPED_UNICODE);
