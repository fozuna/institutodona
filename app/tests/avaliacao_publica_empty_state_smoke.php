<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\AvaliacoesController;
use App\Core\Security;
use App\Database\Database;

class AvaliacoesControllerTestDouble extends AvaliacoesController
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

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SCRIPT_NAME'] = '/index.php';

$pdo = Database::getConnection();
$beforeCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$beforePublicCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes_publicas')->fetchColumn();

$_POST = [
    'csrf' => Security::csrfToken(),
    'avaliacao_id' => '',
];

$controller = new AvaliacoesControllerTestDouble();
$controller->gerarLinkCliente();

$afterCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$afterPublicCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes_publicas')->fetchColumn();
$generated = $_SESSION['generated_public_link'] ?? [];
$publicId = (int)($generated['public_id'] ?? 0);
$publicRecord = null;
if ($publicId > 0) {
    $stmt = $pdo->prepare('SELECT avaliacao_id, empresa, status FROM avaliacoes_publicas WHERE id = :id');
    $stmt->execute(['id' => $publicId]);
    $publicRecord = $stmt->fetch();
}

echo json_encode([
    'did_not_create_internal_avaliacao' => $afterCount === $beforeCount,
    'created_new_public_link' => $afterPublicCount === ($beforePublicCount + 1),
    'generated_link_present' => !empty($generated['url']),
    'redirected_to_index' => $controller->redirectUrl === 'index.php?route=avaliacoes/index',
    'generated_avaliacao_id' => (int)($generated['avaliacao_id'] ?? 0),
    'public_record_avaliacao_id_is_null' => !isset($publicRecord['avaliacao_id']) || $publicRecord['avaliacao_id'] === null,
    'public_record_empresa_is_null' => !isset($publicRecord['empresa']) || $publicRecord['empresa'] === null,
    'public_record_status' => $publicRecord['status'] ?? null,
], JSON_UNESCAPED_UNICODE);
