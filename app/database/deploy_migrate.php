<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

$runner = new MigrationRunner();
$repair = in_array('--repair-checksums', $argv ?? [], true);
$repaired = $repair ? $runner->repairChecksumMismatches() : [];
$pendingBefore = $runner->status()['pending'];
$fingerprint = $runner->currentFingerprint();
$applied = $runner->applyAll();
$status = $runner->status();

if (!empty($status['pending'])) {
    throw new RuntimeException('Ainda existem migrations pendentes apos o deploy.');
}

echo json_encode([
    'fingerprint' => $fingerprint,
    'repair_checksums' => $repair,
    'repaired_versions' => $repaired,
    'pending_before' => $pendingBefore,
    'applied' => $applied,
    'pending_after' => $status['pending'],
], JSON_UNESCAPED_UNICODE) . PHP_EOL;
