<?php
namespace App\Controllers;

use App\Core\BaseController;

class LogsController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $user = $_SESSION['user'] ?? [];
        if (($user['email'] ?? '') !== 'admin@agencialester.com.br') {
            http_response_code(403);
            echo 'Acesso negado';
            return;
        }
        $file = __DIR__ . '/../../storage/logs/audit.log';
        $lines = [];
        if (is_file($file)) {
            $raw = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $lines = array_reverse(array_slice($raw, -500)); // últimas 500 entradas
        }
        $this->render('logs/index', ['lines' => $lines]);
    }
}
