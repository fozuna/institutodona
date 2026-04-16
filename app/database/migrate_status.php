<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

$runner = new MigrationRunner();
echo json_encode($runner->status(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
