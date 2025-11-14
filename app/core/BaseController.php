<?php
namespace App\Core;

class BaseController
{
    protected function render(string $view, array $params = []): void
    {
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
}