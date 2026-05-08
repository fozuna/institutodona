<?php
require_once __DIR__ . '/../autoload.php';

$log = __DIR__ . '/../../storage/logs/audit.log';
if (!is_file($log)) {
    echo "audit.log não encontrado em: {$log}\n";
    exit(1);
}

$needle = 'public_avaliacoes_error';
$lines = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
$hits = [];
foreach (array_reverse($lines) as $line) {
    if (strpos($line, $needle) === false) {
        continue;
    }
    $row = json_decode($line, true);
    if (!is_array($row)) {
        continue;
    }
    $payload = $row['payload'] ?? [];
    $hits[] = [
        'ts' => $row['ts'] ?? '',
        'phase' => $payload['phase'] ?? '',
        'error_id' => $payload['error_id'] ?? '',
        'message' => $payload['message'] ?? '',
        'file' => $payload['file'] ?? '',
        'line' => $payload['line'] ?? '',
        'host' => ($payload['meta']['host'] ?? '') ?: ($payload['host'] ?? ''),
    ];
    if (count($hits) >= 50) {
        break;
    }
}

if (empty($hits)) {
    echo "Nenhum evento {$needle} encontrado.\n";
    exit(0);
}

foreach ($hits as $h) {
    echo ($h['ts'] ?: '-') . ' | phase=' . ($h['phase'] ?: '-') . ' | id=' . ($h['error_id'] ?: '-') . ' | host=' . ($h['host'] ?: '-') . "\n";
    echo '  ' . ($h['message'] ?: '-') . "\n";
    if ($h['file']) {
        echo '  at ' . $h['file'] . ':' . ($h['line'] ?: '-') . "\n";
    }
}

