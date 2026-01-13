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
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (($_SESSION['user']['tipo_acesso'] ?? null) !== $role) {
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }
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
