<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Database\MigrationRunner;
use App\Models\TreinamentoModel;

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

Auth::login([
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
    'allowed_client_ids' => [],
]);

try {
    Database::getConnection();
} catch (\Throwable $e) {
    failFast('Sem conexão com o banco no ambiente atual.');
}

try {
    (new MigrationRunner())->applyAll();
} catch (\Throwable $e) {
}

$apply = true;
if (isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--dry-run') $apply = false;
    }
}

$model = new TreinamentoModel();
$report = $model->reconcileStatuses($apply);

$pdo = Database::getConnection();
$cacheKey = 'treinamentos_status_reconcile_report_' . date('YmdHis') . '_' . random_int(100, 999);
$payloadJson = json_encode($report, JSON_UNESCAPED_UNICODE);
$pdo->prepare("INSERT INTO treinamento_export_cache (cache_key, payload_json, expires_at)
    VALUES (:k,:p,:e)")
    ->execute([
        'k' => $cacheKey,
        'p' => $payloadJson ?: '{}',
        'e' => date('Y-m-d H:i:s', time() + (7 * 24 * 3600)),
    ]);

$alteracoes = $report['alteracoes'] ?? [];
$preview = array_slice(is_array($alteracoes) ? $alteracoes : [], 0, 50);

echo json_encode([
    'apply' => (bool)($report['apply'] ?? false),
    'total_lidos' => (int)($report['total_lidos'] ?? 0),
    'total_inconsistencias' => (int)($report['total_inconsistencias'] ?? 0),
    'cache_key' => $cacheKey,
    'preview_alteracoes' => $preview,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
