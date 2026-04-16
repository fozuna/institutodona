<?php
require_once __DIR__ . '/../../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();
$sql = file_get_contents(__DIR__ . '/../import_all_tables.sql');
if ($sql === false) {
    throw new RuntimeException('Nao foi possivel ler import_all_tables.sql');
}

$pdo->exec($sql);
echo "BASELINE_OK\n";
