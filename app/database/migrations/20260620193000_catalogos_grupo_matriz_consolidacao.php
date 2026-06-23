<?php
require_once __DIR__ . '/../../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

function cg_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
    $stmt->execute(['t' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function cg_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
    $stmt->execute(['t' => $table, 'c' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function cg_ensure_log_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS catalogo_grupo_sync_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(60) NOT NULL,
            entity_type VARCHAR(40) NOT NULL,
            source_id INT NULL,
            target_id INT NULL,
            cliente_origem_id INT NULL,
            cliente_grupo_id INT NULL,
            details_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_catalogo_grupo_sync_action (action),
            INDEX idx_catalogo_grupo_sync_entity (entity_type, source_id, target_id),
            INDEX idx_catalogo_grupo_sync_cliente (cliente_origem_id, cliente_grupo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function cg_log(PDO $pdo, string $action, string $entityType, ?int $sourceId, ?int $targetId, ?int $clienteOrigemId, ?int $clienteGrupoId, array $details = []): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO catalogo_grupo_sync_logs
            (action, entity_type, source_id, target_id, cliente_origem_id, cliente_grupo_id, details_json)
         VALUES
            (:action, :entity_type, :source_id, :target_id, :cliente_origem_id, :cliente_grupo_id, :details_json)'
    );
    $stmt->execute([
        'action' => $action,
        'entity_type' => $entityType,
        'source_id' => $sourceId,
        'target_id' => $targetId,
        'cliente_origem_id' => $clienteOrigemId,
        'cliente_grupo_id' => $clienteGrupoId,
        'details_json' => json_encode($details, JSON_UNESCAPED_UNICODE),
    ]);
}

function cg_find_duplicate_department(PDO $pdo, int $clienteId, string $nome, int $excludeId): int
{
    $stmt = $pdo->prepare(
        'SELECT id
         FROM departamentos
         WHERE cliente_id = :cliente_id
           AND id <> :exclude_id
           AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))
         ORDER BY id
         LIMIT 1'
    );
    $stmt->execute([
        'cliente_id' => $clienteId,
        'exclude_id' => $excludeId,
        'nome' => $nome,
    ]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function cg_find_duplicate_sector(PDO $pdo, int $departamentoId, string $nome, int $excludeId): int
{
    $stmt = $pdo->prepare(
        'SELECT id
         FROM setores
         WHERE departamento_id = :departamento_id
           AND id <> :exclude_id
           AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))
         ORDER BY id
         LIMIT 1'
    );
    $stmt->execute([
        'departamento_id' => $departamentoId,
        'exclude_id' => $excludeId,
        'nome' => $nome,
    ]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function cg_find_duplicate_function(PDO $pdo, int $setorId, string $nome, int $excludeId): int
{
    $stmt = $pdo->prepare(
        'SELECT id
         FROM funcoes
         WHERE setor_id = :setor_id
           AND id <> :exclude_id
           AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))
         ORDER BY id
         LIMIT 1'
    );
    $stmt->execute([
        'setor_id' => $setorId,
        'exclude_id' => $excludeId,
        'nome' => $nome,
    ]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function cg_update_simple_reference(PDO $pdo, string $table, string $column, int $sourceId, int $targetId): int
{
    if (!cg_table_exists($pdo, $table) || !cg_column_exists($pdo, $table, $column)) {
        return 0;
    }
    $stmt = $pdo->prepare("UPDATE {$table} SET {$column} = :target WHERE {$column} = :source");
    $stmt->execute([
        'source' => $sourceId,
        'target' => $targetId,
    ]);
    return $stmt->rowCount();
}

function cg_update_join_reference(PDO $pdo, string $table, string $ownerColumn, string $refColumn, int $sourceId, int $targetId): int
{
    if (!cg_table_exists($pdo, $table) || !cg_column_exists($pdo, $table, $ownerColumn) || !cg_column_exists($pdo, $table, $refColumn)) {
        return 0;
    }

    $deleteSql = "DELETE src
                  FROM {$table} src
                  INNER JOIN {$table} tgt
                    ON tgt.{$ownerColumn} = src.{$ownerColumn}
                   AND tgt.{$refColumn} = :target
                  WHERE src.{$refColumn} = :source";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([
        'source' => $sourceId,
        'target' => $targetId,
    ]);

    $updateSql = "UPDATE {$table} SET {$refColumn} = :target WHERE {$refColumn} = :source";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'source' => $sourceId,
        'target' => $targetId,
    ]);
    return $updateStmt->rowCount();
}

function cg_merge_function(PDO $pdo, int $sourceId, int $targetId, int $clienteOrigemId, int $clienteGrupoId): void
{
    $details = [];
    $details['colaboradores'] = cg_update_simple_reference($pdo, 'colaboradores', 'funcao_id', $sourceId, $targetId);
    $details['treinamento_funcoes'] = cg_update_join_reference($pdo, 'treinamento_funcoes', 'treinamento_id', 'funcao_id', $sourceId, $targetId);
    $details['aplicacao_funcoes'] = cg_update_join_reference($pdo, 'aplicacao_funcoes', 'aplicacao_id', 'funcao_id', $sourceId, $targetId);

    $stmt = $pdo->prepare('DELETE FROM funcoes WHERE id = :id');
    $stmt->execute(['id' => $sourceId]);

    cg_log($pdo, 'merge_duplicate', 'funcao', $sourceId, $targetId, $clienteOrigemId, $clienteGrupoId, $details);
}

function cg_merge_sector(PDO $pdo, int $sourceId, int $targetId, int $clienteOrigemId, int $clienteGrupoId): void
{
    $funcStmt = $pdo->prepare('SELECT id, nome FROM funcoes WHERE setor_id = :setor_id ORDER BY id');
    $funcStmt->execute(['setor_id' => $sourceId]);
    foreach ($funcStmt->fetchAll(PDO::FETCH_ASSOC) as $funcao) {
        $funcaoId = (int)($funcao['id'] ?? 0);
        if ($funcaoId <= 0) {
            continue;
        }
        $duplicateFuncId = cg_find_duplicate_function($pdo, $targetId, (string)($funcao['nome'] ?? ''), $funcaoId);
        if ($duplicateFuncId > 0) {
            cg_merge_function($pdo, $funcaoId, $duplicateFuncId, $clienteOrigemId, $clienteGrupoId);
            continue;
        }
        $stmt = $pdo->prepare('UPDATE funcoes SET setor_id = :target WHERE id = :id');
        $stmt->execute([
            'target' => $targetId,
            'id' => $funcaoId,
        ]);
        cg_log($pdo, 'reparent_to_group', 'funcao', $funcaoId, $funcaoId, $clienteOrigemId, $clienteGrupoId, [
            'old_setor_id' => $sourceId,
            'new_setor_id' => $targetId,
        ]);
    }

    $details = [];
    $details['auditorias'] = cg_update_simple_reference($pdo, 'auditorias', 'setor_id', $sourceId, $targetId);
    $details['indicadores'] = cg_update_simple_reference($pdo, 'indicadores', 'setor_id', $sourceId, $targetId);
    $details['treinamento_setores'] = cg_update_join_reference($pdo, 'treinamento_setores', 'treinamento_id', 'setor_id', $sourceId, $targetId);

    $stmt = $pdo->prepare('DELETE FROM setores WHERE id = :id');
    $stmt->execute(['id' => $sourceId]);

    cg_log($pdo, 'merge_duplicate', 'setor', $sourceId, $targetId, $clienteOrigemId, $clienteGrupoId, $details);
}

function cg_merge_department(PDO $pdo, int $sourceId, int $targetId, int $clienteOrigemId, int $clienteGrupoId): void
{
    $sectorStmt = $pdo->prepare('SELECT id, nome FROM setores WHERE departamento_id = :departamento_id ORDER BY id');
    $sectorStmt->execute(['departamento_id' => $sourceId]);
    foreach ($sectorStmt->fetchAll(PDO::FETCH_ASSOC) as $setor) {
        $setorId = (int)($setor['id'] ?? 0);
        if ($setorId <= 0) {
            continue;
        }
        $duplicateSetorId = cg_find_duplicate_sector($pdo, $targetId, (string)($setor['nome'] ?? ''), $setorId);
        if ($duplicateSetorId > 0) {
            cg_merge_sector($pdo, $setorId, $duplicateSetorId, $clienteOrigemId, $clienteGrupoId);
            continue;
        }
        $stmt = $pdo->prepare('UPDATE setores SET departamento_id = :target WHERE id = :id');
        $stmt->execute([
            'target' => $targetId,
            'id' => $setorId,
        ]);
        cg_log($pdo, 'reparent_to_group', 'setor', $setorId, $setorId, $clienteOrigemId, $clienteGrupoId, [
            'old_departamento_id' => $sourceId,
            'new_departamento_id' => $targetId,
        ]);
    }

    $details = [];
    $details['manuais'] = cg_update_simple_reference($pdo, 'manuais', 'departamento_id', $sourceId, $targetId);
    $details['treinamentos'] = cg_update_simple_reference($pdo, 'treinamentos', 'departamento_id', $sourceId, $targetId);
    $details['indicadores'] = cg_update_simple_reference($pdo, 'indicadores', 'departamento_id', $sourceId, $targetId);
    $details['colaboradores'] = cg_update_simple_reference($pdo, 'colaboradores', 'departamento_id', $sourceId, $targetId);

    $stmt = $pdo->prepare('DELETE FROM departamentos WHERE id = :id');
    $stmt->execute(['id' => $sourceId]);

    cg_log($pdo, 'merge_duplicate', 'departamento', $sourceId, $targetId, $clienteOrigemId, $clienteGrupoId, $details);
}

if (!cg_table_exists($pdo, 'departamentos') || !cg_table_exists($pdo, 'clientes')) {
    echo "MIGRATION_OK\n";
    return;
}

cg_ensure_log_table($pdo);

$pdo->beginTransaction();
try {
    $stmt = $pdo->query(
        'SELECT d.id, d.nome, d.cliente_id, c.matriz_id
         FROM departamentos d
         JOIN clientes c ON c.id = d.cliente_id
         WHERE c.matriz_id IS NOT NULL
           AND c.matriz_id > 0
         ORDER BY d.id'
    );
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $processed = 0;

    foreach ($departamentos as $departamento) {
        $departamentoId = (int)($departamento['id'] ?? 0);
        $clienteOrigemId = (int)($departamento['cliente_id'] ?? 0);
        $clienteGrupoId = (int)($departamento['matriz_id'] ?? 0);
        if ($departamentoId <= 0 || $clienteOrigemId <= 0 || $clienteGrupoId <= 0) {
            continue;
        }

        $check = $pdo->prepare('SELECT id, cliente_id FROM departamentos WHERE id = :id LIMIT 1');
        $check->execute(['id' => $departamentoId]);
        $current = $check->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$current) {
            continue;
        }
        if ((int)($current['cliente_id'] ?? 0) === $clienteGrupoId) {
            continue;
        }

        $duplicateDepartamentoId = cg_find_duplicate_department($pdo, $clienteGrupoId, (string)($departamento['nome'] ?? ''), $departamentoId);
        if ($duplicateDepartamentoId > 0) {
            cg_merge_department($pdo, $departamentoId, $duplicateDepartamentoId, $clienteOrigemId, $clienteGrupoId);
            $processed++;
            continue;
        }

        $update = $pdo->prepare('UPDATE departamentos SET cliente_id = :cliente_grupo_id WHERE id = :id');
        $update->execute([
            'cliente_grupo_id' => $clienteGrupoId,
            'id' => $departamentoId,
        ]);
        cg_log($pdo, 'reparent_to_group', 'departamento', $departamentoId, $departamentoId, $clienteOrigemId, $clienteGrupoId, [
            'old_cliente_id' => $clienteOrigemId,
            'new_cliente_id' => $clienteGrupoId,
        ]);
        $processed++;
    }

    cg_log($pdo, 'migration_completed', 'catalogo_grupo', null, null, null, null, [
        'departamentos_processados' => $processed,
        'estrategia' => 'catalogo_global_na_matriz_sem_duplicacao',
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "MIGRATION_OK\n";
