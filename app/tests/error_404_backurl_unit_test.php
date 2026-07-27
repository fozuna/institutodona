<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\BaseController;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

class Notfound404ProbeController extends BaseController
{
    public function callResolveSafeBackUrl(): string
    {
        return $this->resolveSafeBackUrl();
    }

    public function callRequireLoginForRoute(string $route): void
    {
        $_GET['route'] = $route;
        $this->requireLogin();
    }
}

function resetRequestState(): void
{
    $_SESSION = [];
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']);
}

$probe = new Notfound404ProbeController();

// --- Fallbacks sem nenhuma rota anterior disponível ---
resetRequestState();
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=auth/login', 'Sem sessão/usuário, fallback final é o login');

resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Usuário autenticado sem rota anterior usa Dashboard como fallback');

// --- Prioridade 1: última rota válida em sessão ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = '/institutodona/public_html/index.php?route=treinamentos/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === '/institutodona/public_html/index.php?route=treinamentos/index', 'Usa a última rota válida em sessão quando disponível e acessível');

// --- Anti-ciclo: última rota válida igual à URL atual não deve ser reaproveitada ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = '/institutodona/public_html/index.php?route=usuarios/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Última rota válida igual à URL atual é descartada (evita ciclo), cai no fallback');

// --- Última rota válida que o usuário não pode mais acessar (revalidação de RBAC) ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'cliente', 'allowed_client_ids' => [1]];
$_SESSION['last_valid_route'] = '/institutodona/public_html/index.php?route=usuarios/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/create';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Última rota válida que o usuário não pode mais acessar é rejeitada, cai no fallback');

// --- Última rota válida apontando para login/logout é descartada ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = '/institutodona/public_html/index.php?route=auth/logout';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Última rota válida apontando para logout é descartada, cai no fallback');

// --- Prioridade 2: Referer interno e seguro, quando não há última rota válida em sessão ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['HTTP_HOST'] = 'sistema.local';
$_SERVER['HTTP_REFERER'] = 'https://sistema.local/institutodona/public_html/index.php?route=cronograma/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === '/institutodona/public_html/index.php?route=cronograma/index', 'Sem última rota válida, usa Referer interno e seguro');

// --- Referer externo é rejeitado (proteção contra open redirect) ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['HTTP_HOST'] = 'sistema.local';
$_SERVER['HTTP_REFERER'] = 'https://site-malicioso.com/pagina-falsa';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=usuarios/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Referer de domínio externo é rejeitado (open redirect), cai no fallback');

// --- Última rota válida com esquema/host embutido (não deveria ocorrer, mas defensivo) é rejeitada ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = 'https://site-malicioso.com/index.php?route=usuarios/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Valor com esquema/host absoluto em last_valid_route é rejeitado, cai no fallback');

// --- Protocolo "//" (protocol-relative) é rejeitado ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = '//site-malicioso.com/index.php?route=usuarios/index';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'URL "//host" (protocol-relative) é rejeitada, cai no fallback');

// --- javascript:/data: são rejeitados ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SESSION['last_valid_route'] = 'javascript:alert(1)';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/index';
$url = $probe->callResolveSafeBackUrl();
assert_true($url === 'index.php?route=dashboard/index', 'Esquema "javascript:" é rejeitado, cai no fallback');

// --- Rastreamento da última rota válida (trackLastValidRoute via requireLogin) ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/index';
$probe->callRequireLoginForRoute('treinamentos/index');
assert_true(($_SESSION['last_valid_route'] ?? '') === '/institutodona/public_html/index.php?route=treinamentos/index', 'requireLogin() registra a rota válida em sessão após autorizar com sucesso');

// --- Não registra rota de download/export/pdf/ajax como última rota válida ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=manuais/download&id=1';
$probe->callRequireLoginForRoute('manuais/download');
assert_true(!isset($_SESSION['last_valid_route']), 'Rota de download não é registrada como última rota válida');

resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=indicadores/chartspdf';
$probe->callRequireLoginForRoute('indicadores/chartspdf');
assert_true(!isset($_SESSION['last_valid_route']), 'Rota de exportação PDF não é registrada como última rota válida');

// --- Requisição AJAX não é registrada como última rota válida ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/eligible_ajax&id=1';
$probe->callRequireLoginForRoute('treinamentos/eligible_ajax');
assert_true(!isset($_SESSION['last_valid_route']), 'Requisição AJAX (X-Requested-With) não é registrada como última rota válida');

// --- POST não é registrado como última rota válida ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=treinamentos/store';
$probe->callRequireLoginForRoute('treinamentos/store');
assert_true(!isset($_SESSION['last_valid_route']), 'Requisição POST não é registrada como última rota válida');

// --- auth/login não é registrado como última rota válida ---
resetRequestState();
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$_SERVER['REQUEST_URI'] = '/institutodona/public_html/index.php?route=auth/login';
$probe->callRequireLoginForRoute('auth/login');
assert_true(!isset($_SESSION['last_valid_route']), 'Rota auth/login não é registrada como última rota válida');

echo "Error 404 backUrl unit tests passed.\n";
