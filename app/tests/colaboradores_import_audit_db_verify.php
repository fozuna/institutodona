<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$logPath = __DIR__ . '/../../storage/logs/audit.log';
if (!is_file($logPath)) {
    failFast('audit.log não encontrado em storage/logs.');
}

$lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$lines) {
    failFast('audit.log vazio.');
}

$lastImport = null;
for ($i = count($lines) - 1; $i >= 0; $i--) {
    $raw = trim((string)$lines[$i]);
    if ($raw === '') continue;
    $row = json_decode($raw, true);
    if (!is_array($row)) continue;
    if (($row['action'] ?? '') === 'import' && ($row['entity'] ?? '') === 'colaboradores') {
        $lastImport = $row;
        break;
    }
}

if (!$lastImport) {
    failFast('Nenhum evento de importação encontrado no audit.log.');
}

$payload = is_array($lastImport['payload'] ?? null) ? $lastImport['payload'] : [];
$arquivo = (string)($payload['arquivo'] ?? '');
$inserted = (int)($payload['inserted'] ?? 0);
$clienteIds = array_values(array_unique(array_filter(array_map('intval', (array)($payload['cliente_ids'] ?? [])))));

ok('Última importação encontrada no audit.log');
echo 'Arquivo: ' . $arquivo . PHP_EOL;
echo 'Inseridos (log): ' . $inserted . PHP_EOL;
echo 'Cliente IDs (log): ' . (empty($clienteIds) ? '(nenhum)' : implode(',', $clienteIds)) . PHP_EOL;

$pdo = Database::getConnection();

$whereCliente = '';
$params = [];
if (!empty($clienteIds)) {
    $holders = [];
    foreach ($clienteIds as $idx => $cid) {
        $k = 'c' . $idx;
        $holders[] = ':' . $k;
        $params[$k] = $cid;
    }
    $whereCliente = ' AND col.cliente_id IN (' . implode(',', $holders) . ')';
}

$sql = "SELECT col.id, col.nome, col.email, col.documento, col.data_nascimento, col.celular, col.cliente_id
        FROM colaboradores col
        WHERE 1=1 $whereCliente
        ORDER BY col.id DESC
        LIMIT 20";

echo "\nSQL executado:\n" . $sql . "\n";
echo "Parâmetros: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n\n";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];
echo "Top 20 colaboradores mais recentes (escopo do cliente do import, se disponível):\n";
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

if ($inserted > 0 && empty($rows)) {
    echo "\nAVISO: o log indica inserção, mas a consulta não retornou linhas (verificar filtro de cliente_id ou permissões).\n";
}

echo "\nAll colaboradores import audit/db verify checks completed.\n";

