<?php
require __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;

$runner = new MigrationRunner();
$status = $runner->status();
$result = [
    'all_count' => count($status['all']),
    'applied_count' => count($status['applied']),
    'pending_count' => count($status['pending']),
    'pending_empty' => empty($status['pending']),
    'has_baseline' => in_array('20260325_baseline_from_import_all_tables.php', $status['all'], true),
    'has_faturamento_apply' => in_array('20260417_faturamento_faixas_apply.sql', $status['all'], true),
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);

if (!$result['pending_empty'] || !$result['has_baseline'] || !$result['has_faturamento_apply']) {
    exit(1);
}
