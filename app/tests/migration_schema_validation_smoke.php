<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

$tableCount = static function (string $table) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
    $stmt->execute(['t' => $table]);
    return (int)$stmt->fetchColumn();
};

$columnCount = static function (string $table, string $column) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
    $stmt->execute(['t' => $table, 'c' => $column]);
    return (int)$stmt->fetchColumn();
};

$faixas = (int)$pdo->query('SELECT COUNT(*) FROM faturamento_faixas')->fetchColumn();
$result = [
    'schema_migrations_exists' => $tableCount('schema_migrations') === 1,
    'faturamento_faixas_exists' => $tableCount('faturamento_faixas') === 1,
    'faturamento_faixas_count' => $faixas,
    'avaliacoes_has_faixa_column' => $columnCount('avaliacoes', 'faturamento_faixa_id') === 1,
    'avaliacoes_publicas_has_faixa_column' => $columnCount('avaliacoes_publicas', 'faturamento_faixa_id') === 1,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);

if (!$result['schema_migrations_exists']
    || !$result['faturamento_faixas_exists']
    || $result['faturamento_faixas_count'] !== 6
    || !$result['avaliacoes_has_faixa_column']
    || !$result['avaliacoes_publicas_has_faixa_column']) {
    exit(1);
}
