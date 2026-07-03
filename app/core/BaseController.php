<?php
namespace App\Core;

class BaseController
{
    private const WRITE_ACTIONS = [
        'store', 'update', 'delete', 'attach', 'upload', 'set_status', 'finalizar',
        'upsertmetric', 'addcheck', 'createaction', 'updatetask',
        'updateaction', 'importrun', 'delete_update', 'storefilial',
        'updateaplicacao', 'deleteaplicacao', 'auditar', 'gerar-link-cliente', 'api_public_link_generate', 'log-link-share', 'associar-cliente',
        'api_store', 'api_update', 'api_delete', 'api_auditar',
        'delete-ajax',
        'import',
        'updatevalorajax',
        'deleteajax',
        'chartspdf',
        'export_selecionados',
        'rh_sync',
        'duplicate',
        'toggleativo',
    ];

    protected function render(string $view, array $params = []): void
    {
        $params['pageTitle'] = $params['pageTitle'] ?? $this->autoTitle($view);
        extract($params, EXTR_SKIP);
        ob_start();
        $defaultViewFile = __DIR__ . '/../views/' . $view . '.php';
        $pwaViewFile = __DIR__ . '/../views/pwa/' . $view . '.php';
        if (is_file($pwaViewFile) && $this->isPwaRequest()) {
            require $pwaViewFile;
        } else {
            require $defaultViewFile;
        }
        $content = ob_get_clean();
        if ($this->isPwaRequest() && is_file(__DIR__ . '/../views/layouts/pwa.php')) {
            require __DIR__ . '/../views/layouts/pwa.php';
            return;
        }
        require __DIR__ . '/../views/layouts/main.php';
    }

    protected function renderPartial(string $view, array $params = []): void
    {
        $params['pageTitle'] = $params['pageTitle'] ?? $this->autoTitle($view);
        extract($params, EXTR_SKIP);
        $defaultViewFile = __DIR__ . '/../views/' . $view . '.php';
        $pwaViewFile = __DIR__ . '/../views/pwa/' . $view . '.php';
        if (is_file($pwaViewFile) && $this->isPwaRequest()) {
            require $pwaViewFile;
            return;
        }
        require $defaultViewFile;
    }

    protected function isPwaRequest(): bool
    {
        $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        if ($path !== '' && strpos($path, '/pwa') === 0) {
            return true;
        }
        $route = (string)($_GET['route'] ?? '');
        return strpos($route, 'pwa/') === 0;
    }

    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
            if ($uri !== '' && strpos($uri, '://') === false) {
                if (!isset($_SESSION['redirect_after_login'])) {
                    $_SESSION['redirect_after_login'] = $uri;
                }
            }
            header('Location: index.php?route=auth/login');
            exit;
        }
        Auth::refreshScope();
        $route = (string)($_GET['route'] ?? '');
        $this->authorizeRoute($route);
        $routeForAudit = $route ?: null;
        $clienteCandidate = null;
        if (isset($_GET['cliente'])) {
            $clienteCandidate = (int)$_GET['cliente'];
        } elseif (isset($_GET['id']) && strpos((string)$route, 'clientes/') === 0) {
            $clienteCandidate = (int)$_GET['id'];
        } elseif (isset($_POST['cliente_id'])) {
            $clienteCandidate = (int)$_POST['cliente_id'];
        } elseif (!empty($_POST['id_clientes']) && is_array($_POST['id_clientes'])) {
            $clienteCandidate = (int)($_POST['id_clientes'][0] ?? 0);
        }
        $clienteScoped = $this->resolveScopedClienteId($clienteCandidate);
        AuditLogger::log('menu_access', 'route', null, [
            'route' => $routeForAudit,
            'cliente_id' => $clienteScoped,
            'scoped_clientes' => Auth::allowedClientIds(),
            'resultado' => 'permitido',
            'platform' => $this->isPwaRequest() ? 'PWA' : 'WEB',
        ]);
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            $this->denyAccess('Você não possui permissão para acessar este recurso.', (string)($_GET['route'] ?? ''), $this->routeClienteCandidate());
        }
    }

    protected function requireClienteAdminAccess(bool $json = false): void
    {
        $this->requireLogin();
        $actual = (string)($_SESSION['user']['tipo_acesso'] ?? '');
        if ($actual === 'instituto' || $actual === 'cliente_admin') {
            return;
        }
        $this->denyAccess(
            AccessControl::clientAdminOnlyMessage(),
            (string)($_GET['route'] ?? ''),
            $this->routeClienteCandidate(),
            $json
        );
    }

    private function hasRole(string $role): bool
    {
        $actual = (string)($_SESSION['user']['tipo_acesso'] ?? '');
        if ($actual === $role) {
            return true;
        }
        if ($role === 'cliente_admin') {
            return $actual === 'cliente_admin';
        }
        if ($role === 'instituto') {
            return Auth::isInstituto();
        }
        return false;
    }

    protected function authorizeRoute(?string $route = null, bool $json = false): void
    {
        $route = (string)($route ?? ($_GET['route'] ?? ''));
        if ($route === '' || Auth::isInstituto()) {
            return;
        }
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $clienteCandidate = $this->routeClienteCandidate();
        if (!AccessControl::canAccessRoute($route, $method)) {
            $this->denyAccess(AccessControl::denyMessage($route, $clienteCandidate), $route, $clienteCandidate, $json);
        }
        if ($clienteCandidate !== null && $clienteCandidate > 0 && !Auth::canAccessCliente($clienteCandidate)) {
            $this->denyAccess(AccessControl::denyMessage($route, $clienteCandidate), $route, $clienteCandidate, $json);
        }
    }

    protected function scopedClienteIds(): array
    {
        return Auth::allowedClientIds();
    }

    protected function canAccessCliente(int $clienteId): bool
    {
        return Auth::canAccessCliente($clienteId);
    }

    protected function resolveScopedClienteId(?int $requestedId): ?int
    {
        if (Auth::isInstituto()) {
            return $requestedId;
        }
        $ids = $this->scopedClienteIds();
        if (empty($ids)) {
            return null;
        }
        if ($requestedId !== null && $requestedId > 0 && in_array($requestedId, $ids, true)) {
            return $requestedId;
        }
        return (int)$ids[0];
    }

    protected function denyAccess(string $message = 'Acesso negado.', ?string $route = null, ?int $clienteId = null, bool $json = false): void
    {
        AuditLogger::log('access_denied', 'auth', null, [
            'route' => $route ?? ($_GET['route'] ?? null),
            'cliente_id' => $clienteId,
            'scoped_clientes' => Auth::allowedClientIds(),
            'perfil' => AccessControl::roleLabel((string)($_SESSION['user']['tipo_acesso'] ?? '')),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'resultado' => 'negado',
            'mensagem' => $message,
            'platform' => $this->isPwaRequest() ? 'PWA' : 'WEB',
        ]);
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['flash_error'] = $message;
        echo $message;
        exit;
    }

    private function routeClienteCandidate(): ?int
    {
        foreach (['cliente', 'cliente_id', 'id_cliente', 'empresa_id'] as $key) {
            if (isset($_GET[$key]) && (int)$_GET[$key] > 0) {
                return (int)$_GET[$key];
            }
            if (isset($_POST[$key]) && (int)$_POST[$key] > 0) {
                return (int)$_POST[$key];
            }
        }
        if (!empty($_POST['id_clientes']) && is_array($_POST['id_clientes'])) {
            $first = (int)($_POST['id_clientes'][0] ?? 0);
            return $first > 0 ? $first : null;
        }
        return null;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function autoTitle(string $view): string
    {
        $parts = explode('/', $view);
        $mod = $parts[0] ?? '';
        $act = $parts[1] ?? 'index';
        $sing = [
            'clientes' => 'Cliente',
            'pilares' => 'Pilar',
            'metodologias' => 'Metodologia',
            'agenda' => 'Agenda',
            'consultores' => 'Consultor',
            'avaliacao' => 'Avaliação',
            'dashboard' => 'Dashboard',
            'auth' => 'Entrar',
        ];
        $plur = [
            'clientes' => 'Clientes',
            'pilares' => 'Pilares',
            'metodologias' => 'Metodologias',
            'departamentos' => 'Departamentos',
            'agenda' => 'Agenda',
            'consultores' => 'Consultores',
            'avaliacao' => 'Avaliação',
            'dashboard' => 'Dashboard',
            'auth' => 'Entrar',
        ];
        $s = $sing[$mod] ?? ucfirst($mod);
        $p = $plur[$mod] ?? ucfirst($mod);
        if ($act === 'index') return $p;
        if ($act === 'create') return 'Novo ' . $s;
        if ($act === 'edit') return 'Editar ' . $s;
        if ($act === 'show') return $s;
        return $p;
    }
}
