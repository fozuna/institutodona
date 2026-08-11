<?php
namespace App\Database;

use PDO;
use RuntimeException;

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationDir;

    public function __construct(?PDO $pdo = null, ?string $migrationDir = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->migrationDir = $migrationDir ?? __DIR__ . '/migrations';
    }

    public function ensureRepository(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(190) PRIMARY KEY,
            checksum CHAR(64) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function allMigrations(): array
    {
        $files = glob($this->migrationDir . '/*') ?: [];
        $migrations = [];
        foreach ($files as $file) {
            $basename = basename($file);
            if (!preg_match('/^\d+.*(\.php|_apply\.sql)$/', $basename)) {
                continue;
            }
            if (str_ends_with($basename, '_verify.sql') || str_ends_with($basename, '_rollback.sql')) {
                continue;
            }
            $migrations[] = [
                'version' => $basename,
                'path' => $file,
                'checksum' => hash_file('sha256', $file) ?: '',
                'type' => str_ends_with($basename, '.php') ? 'php' : 'sql',
            ];
        }
        usort($migrations, static fn(array $a, array $b): int => strcmp($a['version'], $b['version']));
        return $migrations;
    }

    public function appliedMigrations(): array
    {
        $this->ensureRepository();
        $stmt = $this->pdo->query('SELECT version, checksum, applied_at FROM schema_migrations ORDER BY version');
        $applied = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $applied[$row['version']] = $row;
        }
        return $applied;
    }

    public function pendingMigrations(): array
    {
        $applied = $this->appliedMigrations();
        $pending = [];
        foreach ($this->allMigrations() as $migration) {
            if (!isset($applied[$migration['version']])) {
                $pending[] = $migration;
                continue;
            }
            if (($applied[$migration['version']]['checksum'] ?? '') !== $migration['checksum']) {
                throw new RuntimeException('Checksum divergente para migration ' . $migration['version']);
            }
        }
        return $pending;
    }

    public function checksumMismatches(): array
    {
        $this->ensureRepository();
        $applied = $this->appliedMigrations();
        $mismatches = [];
        foreach ($this->allMigrations() as $migration) {
            $current = (string)($migration['checksum'] ?? '');
            $stored = (string)($applied[$migration['version']]['checksum'] ?? '');
            if ($stored !== '' && $stored !== $current) {
                $mismatches[] = [
                    'version' => $migration['version'],
                    'stored_checksum' => $stored,
                    'current_checksum' => $current,
                ];
            }
        }
        return $mismatches;
    }

    public function repairChecksumMismatches(): array
    {
        $this->ensureRepository();
        $mismatches = $this->checksumMismatches();
        if (empty($mismatches)) {
            return [];
        }
        $stmt = $this->pdo->prepare('UPDATE schema_migrations SET checksum = :c WHERE version = :v');
        $fixed = [];
        foreach ($mismatches as $m) {
            $stmt->execute([
                'v' => $m['version'],
                'c' => $m['current_checksum'],
            ]);
            $fixed[] = $m['version'];
        }
        return $fixed;
    }

    public function applyAll(): array
    {
        $this->ensureRepository();
        $appliedNow = [];
        foreach ($this->pendingMigrations() as $migration) {
            $this->applyOne($migration);
            $appliedNow[] = $migration['version'];
        }
        return $appliedNow;
    }

    public function status(): array
    {
        $all = $this->allMigrations();
        $applied = $this->appliedMigrations();
        $pending = $this->pendingMigrations();
        return [
            'all' => array_map(static fn(array $m): string => $m['version'], $all),
            'applied' => array_keys($applied),
            'pending' => array_map(static fn(array $m): string => $m['version'], $pending),
        ];
    }

    public function currentFingerprint(): string
    {
        $parts = [];
        foreach ($this->allMigrations() as $migration) {
            $parts[] = $migration['version'] . ':' . $migration['checksum'];
        }
        return hash('sha256', implode('|', $parts));
    }

    private function applyOne(array $migration): void
    {
        try {
            if ($migration['type'] === 'sql') {
                $this->execSqlFile($migration['path'], $migration['version']);
            } else {
                require $migration['path'];
            }
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (:version, :checksum)');
            $stmt->execute([
                'version' => $migration['version'],
                'checksum' => $migration['checksum'],
            ]);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function execSqlFile(string $path, string $version): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Nao foi possivel ler ' . $version);
        }

        foreach (self::parseStatements($sql) as $statement) {
            $this->execStatement($statement);
        }
    }

    /**
     * Divide o conteudo de uma migration .sql em statements individuais.
     * Metodo puro (sem I/O, sem PDO) para permitir teste isolado do parsing.
     */
    public static function parseStatements(string $sql): array
    {
        $sql = self::stripUtf8Bom($sql);

        $delimiter = ';';
        $buffer = '';
        $statements = [];
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
                $statements[] = $statement;
            }
        }

        $remainder = trim($buffer);
        if ($remainder !== '') {
            $statements[] = $remainder;
        }

        return $statements;
    }

    /**
     * Remove um BOM UTF-8 (EF BB BF) no inicio do conteudo, se presente.
     * trim() nao remove esses bytes por padrao, entao um arquivo salvo com
     * BOM faz a primeira linha (ex.: um comentario "--") deixar de ser
     * reconhecida, quebrando o parsing do statement seguinte.
     */
    private static function stripUtf8Bom(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        return $content;
    }

    private function execStatement(string $statement): void
    {
        $prefix = strtoupper(strtok(ltrim($statement), " \t\r\n(") ?: '');
        if (in_array($prefix, ['SELECT', 'SHOW', 'CALL', 'EXECUTE'], true)) {
            $query = $this->pdo->query($statement);
            if ($query !== false) {
                $query->fetchAll();
                $query->closeCursor();
            }
            return;
        }
        $this->pdo->exec($statement);
    }
}
