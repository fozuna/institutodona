<?php
namespace App\Core;

class BaseController
{
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
        $route = $_GET['route'] ?? null;
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
            'route' => $route,
            'cliente_id' => $clienteScoped,
            'scoped_clientes' => Auth::allowedClientIds(),
        ]);
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (($_SESSION['user']['tipo_acesso'] ?? null) !== $role) {
            AuditLogger::log('role_scope_fallback', 'auth', null, [
                'required_role' => $role,
                'actual_role' => $_SESSION['user']['tipo_acesso'] ?? null,
                'route' => $_GET['route'] ?? null,
            ]);
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
