<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

$runner = new MigrationRunner();
$repair = in_array('--repair-checksums', $argv ?? [], true);
$repaired = $repair ? $runner->repairChecksumMismatches() : [];
$applied = $runner->applyAll();

echo json_encode([
    'applied' => $applied,
    'pending' => $runner->status()['pending'],
    'repair_checksums' => $repair,
    'repaired_versions' => $repaired,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
