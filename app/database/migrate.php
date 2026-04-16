<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

$runner = new MigrationRunner();
$applied = $runner->applyAll();

echo json_encode([
    'applied' => $applied,
    'pending' => $runner->status()['pending'],
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
