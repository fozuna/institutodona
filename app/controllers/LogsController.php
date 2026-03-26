<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;

class LogsController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $file = __DIR__ . '/../../storage/logs/audit.log';
        $lines = [];
        if (is_file($file)) {
            $raw = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $lines = array_reverse(array_slice($raw, -500));
            if (!Auth::isInstituto()) {
                $allowed = Auth::allowedClientIds();
                $lines = array_values(array_filter($lines, function (string $line) use ($allowed) {
                    $decoded = json_decode($line, true);
                    if (!is_array($decoded)) {
                        return false;
                    }
                    $meta = $decoded['meta'] ?? [];
                    $clienteId = isset($meta['cliente_id']) ? (int)$meta['cliente_id'] : 0;
                    if ($clienteId <= 0) {
                        return false;
                    }
                    return in_array($clienteId, $allowed, true);
                }));
            }
        }
        $this->render('logs/index', ['lines' => $lines]);
    }
}
