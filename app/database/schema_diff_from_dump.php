<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;

$root = dirname(__DIR__, 2);
$dumpPath = $argv[1] ?? ($root . '/institutodona.sql');
if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump nao encontrado: {$dumpPath}\n");
    exit(2);
}

$pdo = Database::getConnection();
$devSchema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
if ($devSchema === '') {
    fwrite(STDERR, "Nao foi possivel identificar schema atual.\n");
    exit(2);
}

$tmpSchema = 'tmp_schema_cmp_' . date('Ymd_His');
$reportPath = $root . '/storage/logs/schema_diff_report_' . date('Ymd_His') . '.json';
$migrationPath = $root . '/app/database/migrations/' . date('Ymd_His') . '_schema_sync_from_dump_apply.sql';

$stats = [
    'executed_ddl' => 0,
    'skipped_ddl' => 0,
    'errors' => [],
];

try {
    $pdo->exec('CREATE DATABASE `' . $tmpSchema . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $tmpSchema . '`');

    $sql = file_get_contents($dumpPath);
    if ($sql === false) {
        throw new RuntimeException('Nao foi possivel ler o dump.');
    }

    foreach (parseStatements($sql) as $statement) {
        $prefix = strtoupper(strtok(ltrim($statement), " \t\r\n(") ?: '');
        if (!in_array($prefix, ['CREATE', 'ALTER'], true)) {
            continue;
        }
        if (!isRelevantDdl($statement)) {
            continue;
        }
        try {
            $pdo->exec($statement);
            $stats['executed_ddl']++;
        } catch (\Throwable $e) {
            $stats['skipped_ddl']++;
            $stats['errors'][] = [
                'statement' => mb_substr($statement, 0, 240),
                'error' => $e->getMessage(),
            ];
        }
    }

    $dev = snapshotSchema($pdo, $devSchema);
    $dump = snapshotSchema($pdo, $tmpSchema);
    $diff = buildDiff($dev, $dump);
    $migrationSql = buildMigrationSql($pdo, $devSchema, $diff);

    file_put_contents($migrationPath, $migrationSql);

    $report = [
        'generated_at' => date('c'),
        'dev_schema' => $devSchema,
        'dump_path' => $dumpPath,
        'tmp_schema' => $tmpSchema,
        'stats' => $stats,
        'diff_summary' => [
            'missing_tables_in_dump' => count($diff['missing_tables']),
            'missing_columns_in_dump' => count($diff['missing_columns']),
            'missing_indexes_in_dump' => count($diff['missing_indexes']),
            'missing_fks_in_dump' => count($diff['missing_fks']),
        ],
        'diff' => $diff,
        'generated_migration' => $migrationPath,
    ];
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo json_encode([
        'ok' => true,
        'report' => $reportPath,
        'migration' => $migrationPath,
        'summary' => $report['diff_summary'],
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} finally {
    try {
        $pdo->exec('DROP DATABASE IF EXISTS `' . $tmpSchema . '`');
        $pdo->exec('USE `' . $devSchema . '`');
    } catch (\Throwable $e) {
        // noop
    }
}

function parseStatements(string $sql): array
{
    $delimiter = ';';
    $buffer = '';
    $lines = preg_split("/\r\n|\n|\r/", $sql) ?: [];
    $result = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }
        if (preg_match('/^\/\*!/i', $trimmed)) {
            continue;
        }
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $m)) {
            $delimiter = trim($m[1]);
            continue;
        }
        $buffer .= $line . "\n";
        $end = rtrim($buffer);
        if ($delimiter !== '' && str_ends_with($end, $delimiter)) {
            $statement = trim(substr($end, 0, -strlen($delimiter)));
            $buffer = '';
            if ($statement !== '') {
                $result[] = $statement;
            }
        }
    }
    $tail = trim($buffer);
    if ($tail !== '') {
        $result[] = $tail;
    }
    return $result;
}

function isRelevantDdl(string $statement): bool
{
    $s = strtoupper($statement);
    if (str_starts_with($s, 'CREATE TABLE')) {
        return true;
    }
    if (str_starts_with($s, 'ALTER TABLE') && (str_contains($s, 'ADD CONSTRAINT') || str_contains($s, 'ADD KEY') || str_contains($s, 'ADD UNIQUE KEY') || str_contains($s, 'ADD PRIMARY KEY'))) {
        return true;
    }
    if (str_starts_with($s, 'CREATE INDEX') || str_starts_with($s, 'CREATE UNIQUE INDEX')) {
        return true;
    }
    return false;
}

function snapshotSchema(PDO $pdo, string $schema): array
{
    $tables = [];
    $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = :s AND table_type = 'BASE TABLE'");
    $stmt->execute(['s' => $schema]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $tables[(string)$t] = true;
    }

    $columns = [];
    $stmt = $pdo->prepare("SELECT table_name AS table_name, column_name AS column_name FROM information_schema.columns WHERE table_schema = :s");
    $stmt->execute(['s' => $schema]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $columns[$row['table_name'] . '.' . $row['column_name']] = true;
    }

    $indexes = [];
    $stmt = $pdo->prepare("
        SELECT table_name AS table_name, index_name AS index_name, non_unique AS non_unique, index_type AS index_type,
               GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') AS cols
        FROM information_schema.statistics
        WHERE table_schema = :s
        GROUP BY table_name, index_name, non_unique, index_type
    ");
    $stmt->execute(['s' => $schema]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['table_name'] . '|' . $row['index_name'] . '|' . $row['non_unique'] . '|' . $row['index_type'] . '|' . ($row['cols'] ?? '');
        $indexes[$key] = $row;
    }

    $fks = [];
    $stmt = $pdo->prepare("
        SELECT rc.constraint_name AS constraint_name, rc.table_name AS table_name,
               rc.referenced_table_name AS referenced_table_name,
               GROUP_CONCAT(kcu.column_name ORDER BY kcu.ordinal_position SEPARATOR ',') AS cols,
               GROUP_CONCAT(kcu.referenced_column_name ORDER BY kcu.ordinal_position SEPARATOR ',') AS ref_cols
        FROM information_schema.referential_constraints rc
        JOIN information_schema.key_column_usage kcu
          ON kcu.constraint_schema = rc.constraint_schema
         AND kcu.constraint_name = rc.constraint_name
         AND kcu.table_name = rc.table_name
        WHERE rc.constraint_schema = :s
        GROUP BY rc.constraint_name, rc.table_name, rc.referenced_table_name
    ");
    $stmt->execute(['s' => $schema]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['table_name'] . '|' . $row['constraint_name'] . '|' . $row['referenced_table_name'] . '|' . ($row['cols'] ?? '') . '|' . ($row['ref_cols'] ?? '');
        $fks[$key] = $row;
    }

    return [
        'tables' => $tables,
        'columns' => $columns,
        'indexes' => $indexes,
        'fks' => $fks,
    ];
}

function buildDiff(array $dev, array $dump): array
{
    $missingTables = array_values(array_diff(array_keys($dev['tables']), array_keys($dump['tables'])));
    $missingColumns = array_values(array_diff(array_keys($dev['columns']), array_keys($dump['columns'])));
    $missingIndexes = array_values(array_diff(array_keys($dev['indexes']), array_keys($dump['indexes'])));
    $missingFks = array_values(array_diff(array_keys($dev['fks']), array_keys($dump['fks'])));

    sort($missingTables);
    sort($missingColumns);
    sort($missingIndexes);
    sort($missingFks);

    return [
        'missing_tables' => $missingTables,
        'missing_columns' => $missingColumns,
        'missing_indexes' => $missingIndexes,
        'missing_fks' => $missingFks,
    ];
}

function buildMigrationSql(PDO $pdo, string $devSchema, array $diff): string
{
    $lines = [];
    $lines[] = '-- Migration gerada automaticamente por schema_diff_from_dump.php';
    $lines[] = '-- Objetivo: sincronizar dump (producao) com schema de desenvolvimento.';
    $lines[] = 'START TRANSACTION;';
    $lines[] = '';

    foreach ($diff['missing_tables'] as $table) {
        $stmt = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $devSchema) . '`.`' . str_replace('`', '``', $table) . '`');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $create = (string)($row['Create Table'] ?? '');
        if ($create === '') {
            continue;
        }
        $create = preg_replace('/^CREATE TABLE `/i', 'CREATE TABLE IF NOT EXISTS `', $create) ?: $create;
        $lines[] = '-- Tabela ausente: ' . $table;
        $lines[] = $create . ';';
        $lines[] = '';
    }

    $byTable = [];
    foreach ($diff['missing_columns'] as $col) {
        [$table, $column] = explode('.', $col, 2);
        $byTable[$table][] = $column;
    }
    foreach ($byTable as $table => $columns) {
        foreach ($columns as $column) {
            $metaStmt = $pdo->prepare("
                SELECT column_type AS column_type, is_nullable AS is_nullable, column_default AS column_default, extra AS extra, column_comment AS column_comment
                FROM information_schema.columns
                WHERE table_schema = :s AND table_name = :t AND column_name = :c
            ");
            $metaStmt->execute(['s' => $devSchema, 't' => $table, 'c' => $column]);
            $meta = $metaStmt->fetch(PDO::FETCH_ASSOC);
            if (!$meta) {
                continue;
            }
            $def = '`' . $column . '` ' . $meta['column_type'];
            $def .= ((string)$meta['is_nullable'] === 'NO') ? ' NOT NULL' : ' NULL';
            if ($meta['column_default'] !== null) {
                $def .= ' DEFAULT ' . sqlDefaultLiteral((string)$meta['column_default']);
            }
            $extra = strtolower(trim((string)($meta['extra'] ?? '')));
            if (str_contains($extra, 'auto_increment')) {
                $def .= ' AUTO_INCREMENT';
            }
            if (str_contains($extra, 'on update current_timestamp')) {
                $def .= ' ON UPDATE CURRENT_TIMESTAMP';
            }
            if ((string)$meta['column_comment'] !== '') {
                $def .= " COMMENT " . $pdo->quote((string)$meta['column_comment']);
            }
            $lines[] = '-- Coluna ausente: ' . $table . '.' . $column;
            $lines[] = "SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = " . $pdo->quote($table) . " AND column_name = " . $pdo->quote($column) . ");";
            $lines[] = "SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `" . $table . "` ADD COLUMN " . str_replace("'", "''", $def) . "', 'SELECT 1');";
            $lines[] = 'PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;';
            $lines[] = '';
        }
    }

    foreach ($diff['missing_indexes'] as $idxKey) {
        [$table, $indexName, $nonUnique, , $cols] = explode('|', $idxKey, 5);
        if ($indexName === 'PRIMARY' || $cols === '') {
            continue;
        }
        $unique = ((int)$nonUnique === 0) ? 'UNIQUE ' : '';
        $colsSql = '`' . str_replace(',', '`,`', $cols) . '`';
        $lines[] = '-- Indice ausente: ' . $table . '.' . $indexName;
        $lines[] = "SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = " . $pdo->quote($table) . " AND index_name = " . $pdo->quote($indexName) . ");";
        $lines[] = "SET @sql_idx := IF(@exists_idx = 0, 'CREATE " . $unique . "INDEX `" . $indexName . "` ON `" . $table . "` (" . $colsSql . ")', 'SELECT 1');";
        $lines[] = 'PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;';
        $lines[] = '';
    }

    foreach ($diff['missing_fks'] as $fkKey) {
        [$table, $constraint, $refTable, $cols, $refCols] = explode('|', $fkKey, 5);
        if ($cols === '' || $refCols === '') {
            continue;
        }
        $colsSql = '`' . str_replace(',', '`,`', $cols) . '`';
        $refColsSql = '`' . str_replace(',', '`,`', $refCols) . '`';
        $lines[] = '-- FK ausente: ' . $constraint;
        $lines[] = "SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = " . $pdo->quote($constraint) . ");";
        $lines[] = "SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `" . $table . "` ADD CONSTRAINT `" . $constraint . "` FOREIGN KEY (" . $colsSql . ") REFERENCES `" . $refTable . "` (" . $refColsSql . ")', 'SELECT 1');";
        $lines[] = 'PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;';
        $lines[] = '';
    }

    $lines[] = 'COMMIT;';
    $lines[] = '';
    return implode("\n", $lines);
}

function sqlDefaultLiteral(string $default): string
{
    $upper = strtoupper($default);
    if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()'], true)) {
        return $upper;
    }
    if (is_numeric($default)) {
        return $default;
    }
    return "'" . str_replace("'", "''", $default) . "'";
}
