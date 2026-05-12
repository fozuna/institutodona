<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

try {
    $runner = new MigrationRunner();
    $mismatches = $runner->checksumMismatches();
    echo json_encode([
        'status' => $runner->status(),
        'checksum_mismatches' => $mismatches,
        'has_checksum_mismatch' => !empty($mismatches),
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (\Throwable $e) {
    $msg = $e->getMessage();
    $isChecksum = str_contains($msg, 'Checksum divergente para migration');
    echo json_encode([
        'error' => $isChecksum ? 'migration_checksum_mismatch' : 'db_connection_failed',
        'message' => $isChecksum ? $msg : 'Erro ao conectar ao banco de dados.',
        'hint' => $isChecksum ? 'Execute: php app/database/migrate.php --repair-checksums' : null,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
