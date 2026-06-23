<?php
require_once __DIR__ . '/../../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

function dcs_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :table_name'
    );
    $stmt->execute(['table_name' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function dcs_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table_name
           AND column_name = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    return (int)$stmt->fetchColumn() > 0;
}

function dcs_insert_link(PDO $pdo, int $departamentoId, int $clienteId): void
{
    if ($departamentoId <= 0 || $clienteId <= 0) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id)
         VALUES (:departamento_id, :cliente_id)'
    );
    $stmt->execute([
        'departamento_id' => $departamentoId,
        'cliente_id' => $clienteId,
    ]);
}

if (!dcs_table_exists($pdo, 'departamentos') || !dcs_table_exists($pdo, 'clientes')) {
    echo "MIGRATION_OK\n";
    return;
}

try {
    if (!dcs_column_exists($pdo, 'departamentos', 'compartilhar_todas_filiais')) {
        $pdo->exec(
            'ALTER TABLE departamentos
             ADD COLUMN compartilhar_todas_filiais TINYINT(1) NOT NULL DEFAULT 0 AFTER cliente_id'
        );
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS departamento_clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            departamento_id INT NOT NULL,
            cliente_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_departamento_cliente (departamento_id, cliente_id),
            KEY idx_departamento_clientes_cliente (cliente_id),
            CONSTRAINT fk_departamento_clientes_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_departamento_clientes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $stmt = $pdo->query('SELECT id, cliente_id FROM departamentos ORDER BY id');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        dcs_insert_link($pdo, (int)($row['id'] ?? 0), (int)($row['cliente_id'] ?? 0));
    }

    if (dcs_table_exists($pdo, 'catalogo_grupo_sync_logs')) {
        $stmt = $pdo->query(
            "SELECT action, entity_type, source_id, target_id, cliente_origem_id
             FROM catalogo_grupo_sync_logs
             WHERE entity_type = 'departamento'
               AND action IN ('reparent_to_group', 'merge_duplicate')
             ORDER BY id"
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $departamentoId = (int)($row['target_id'] ?? 0);
            if ($departamentoId <= 0) {
                $departamentoId = (int)($row['source_id'] ?? 0);
            }
            $clienteOrigemId = (int)($row['cliente_origem_id'] ?? 0);
            dcs_insert_link($pdo, $departamentoId, $clienteOrigemId);
        }
    }

    echo "MIGRATION_OK\n";
} catch (\Throwable $e) {
    throw $e;
}
