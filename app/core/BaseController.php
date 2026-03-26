<?php
namespace App\Core;

class BaseController
{
    private const WRITE_ACTIONS = [
        'store', 'update', 'delete', 'attach', 'upload', 'set_status',
        'upsertmetric', 'addcheck', 'createaction', 'updatetask',
        'updateaction', 'importrun', 'delete_update', 'storefilial',
        'updateaplicacao', 'deleteaplicacao', 'auditar',
        'api_store', 'api_update', 'api_delete', 'api_auditar',
    ];

    protected function render(string $view, array $params = []): void
    {
        $params['pageTitle'] = $params['pageTitle'] ?? $this->autoTitle($view);
        extract($params, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/main.php';
    }

    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?route=auth/login');
            exit;
        }
        Auth::refreshScope();
        $route = (string)($_GET['route'] ?? '');
        $this->enforceScopePermissionByRoute($route);
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
        ]);
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            AuditLogger::log('role_denied', 'auth', null, [
                'required_role' => $role,
                'actual_role' => $_SESSION['user']['tipo_acesso'] ?? null,
                'route' => $_GET['route'] ?? null,
            ]);
            http_response_code(403);
            echo 'Sem permissão para esta ação.';
            exit;
        }
    }

    private function hasRole(string $role): bool
    {
        $actual = (string)($_SESSION['user']['tipo_acesso'] ?? '');
        if ($actual === $role) {
            return true;
        }
        if ($role === 'cliente_admin') {
            return Auth::isClienteAdmin();
        }
        if ($role === 'instituto') {
            return Auth::isInstituto() || Auth::isClienteAdmin();
        }
        return false;
    }

    private function enforceScopePermissionByRoute(string $route): void
    {
        if ($route === '' || Auth::isInstituto()) {
            return;
        }
        $isWriteAction = $this->isWriteRoute($route);
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (Auth::isReader()) {
            if ($method !== 'GET' && $method !== 'HEAD') {
                AuditLogger::log('reader_method_blocked', 'auth', null, ['route' => $route, 'method' => $method]);
                http_response_code(403);
                echo 'Perfil reader possui acesso somente leitura.';
                exit;
            }
            if ($isWriteAction) {
                AuditLogger::log('reader_write_route_blocked', 'auth', null, ['route' => $route, 'method' => $method]);
                http_response_code(403);
                echo 'Perfil reader possui acesso somente leitura.';
                exit;
            }
            return;
        }
        if ($isWriteAction && $method !== 'POST') {
            AuditLogger::log('write_method_blocked', 'auth', null, ['route' => $route, 'method' => $method]);
            http_response_code(405);
            echo 'Método não permitido para esta ação.';
            exit;
        }
        if (Auth::isClienteAdmin() && $isWriteAction) {
            AuditLogger::log('client_admin_write', 'auth', null, [
                'route' => $route,
                'method' => $method,
                'scoped_clientes' => Auth::allowedClientIds(),
            ]);
        }
    }

    private function isWriteRoute(string $route): bool
    {
        $parts = explode('/', strtolower($route));
        $action = $parts[1] ?? '';
        return in_array($action, self::WRITE_ACTIONS, true);
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
