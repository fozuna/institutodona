<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

try {
    $runner = new MigrationRunner();
    echo json_encode($runner->status(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (\Throwable $e) {
    echo json_encode([
        'error' => 'db_connection_failed',
        'message' => 'Erro ao conectar ao banco de dados.',
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
