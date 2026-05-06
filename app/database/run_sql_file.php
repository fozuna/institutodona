<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use PDO;

if ($argc < 2) {
    fwrite(STDERR, "Uso: php app/database/run_sql_file.php <arquivo.sql>\n");
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "Arquivo nao encontrado: {$path}\n");
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    fwrite(STDERR, "Nao foi possivel ler o arquivo: {$path}\n");
    exit(1);
}

$pdo = Database::getConnection();
$delimiter = ';';
$buffer = '';
$lines = preg_split("/\r\n|\n|\r/", $sql) ?: [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--')) {
        continue;
    }
    if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $m)) {
        $delimiter = trim($m[1]);
        continue;
    }

    $buffer .= $line . "\n";
    $end = rtrim($buffer);
    if ($delimiter !== '' && str_ends_with($end, $delimiter)) {
        $statement = rtrim(substr($end, 0, -strlen($delimiter)));
        $buffer = '';
        if ($statement === '') {
            continue;
        }
        execStatement($pdo, $statement);
    }
}

$remainder = trim($buffer);
if ($remainder !== '') {
    execStatement($pdo, $remainder);
}

echo json_encode(['ok' => true, 'file' => $path], JSON_UNESCAPED_UNICODE) . PHP_EOL;

function execStatement(PDO $pdo, string $statement): void
{
    $prefix = strtoupper(strtok(ltrim($statement), " \t\r\n(") ?: '');
    if (in_array($prefix, ['SELECT', 'SHOW', 'CALL', 'EXECUTE'], true)) {
        $query = $pdo->query($statement);
        if ($query !== false) {
            $query->fetchAll();
            $query->closeCursor();
        }
        return;
    }
    $pdo->exec($statement);
}
