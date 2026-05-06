<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;

if ($argc < 2) {
    fwrite(STDERR, "Uso: php app/database/unmark_migration.php <version>\n");
    exit(1);
}

$version = trim((string)$argv[1]);
if ($version === '') {
    fwrite(STDERR, "Versao da migration invalida.\n");
    exit(1);
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('DELETE FROM schema_migrations WHERE version = :version');
$stmt->execute(['version' => $version]);

echo json_encode([
    'ok' => true,
    'version' => $version,
    'deleted_rows' => $stmt->rowCount(),
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
