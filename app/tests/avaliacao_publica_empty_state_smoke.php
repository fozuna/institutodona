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

$_POST = [
    'csrf' => Security::csrfToken(),
    'avaliacao_id' => '',
];

$controller = new AvaliacoesControllerTestDouble();
$controller->gerarLinkCliente();

$afterCount = (int)$pdo->query('SELECT COUNT(*) FROM avaliacoes')->fetchColumn();
$generated = $_SESSION['generated_public_link'] ?? [];
$newAvaliacaoId = (int)($generated['avaliacao_id'] ?? 0);
$newAvaliacao = null;
if ($newAvaliacaoId > 0) {
    $stmt = $pdo->prepare('SELECT empresa_nome, origem_cadastro FROM avaliacoes WHERE id = :id');
    $stmt->execute(['id' => $newAvaliacaoId]);
    $newAvaliacao = $stmt->fetch();
}

echo json_encode([
    'created_new_avaliacao' => $afterCount === ($beforeCount + 1),
    'generated_link_present' => !empty($generated['url']),
    'redirected_to_index' => $controller->redirectUrl === 'index.php?route=avaliacoes/index',
    'placeholder_empresa' => $newAvaliacao['empresa_nome'] ?? null,
    'placeholder_origem' => $newAvaliacao['origem_cadastro'] ?? null,
], JSON_UNESCAPED_UNICODE);
