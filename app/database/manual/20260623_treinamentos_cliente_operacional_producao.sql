-- =========================================================
-- Correção de treinamentos para persistir a unidade operacional
-- selecionada e compatibilizar o cadastro com departamentos
-- compartilhados entre matriz e filiais.
--
-- Execute este script APOS:
-- 1) 20260620_producao_migrations_pendentes_sem_usuarios_platform_access.sql
-- 2) 20260623_departamentos_compartilhamento_seletivo_producao.sql
-- =========================================================

SET @schema_name = DATABASE();

-- 1. Adiciona a coluna cliente_id em treinamentos, se ainda nao existir
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'cliente_id'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN cliente_id INT NULL AFTER carga_horaria'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Preenche registros legados com a empresa de origem do departamento
UPDATE treinamentos t
JOIN departamentos d ON d.id = t.departamento_id
SET t.cliente_id = d.cliente_id
WHERE t.cliente_id IS NULL;

-- 3. Garante indice para performance
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND INDEX_NAME = 'idx_treinamentos_cliente'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD INDEX idx_treinamentos_cliente (cliente_id)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Garante a FK para integridade referencial
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND CONSTRAINT_NAME = 'fk_treinamentos_cliente'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Validacoes recomendadas
-- SELECT id, nome, cliente_id, departamento_id FROM treinamentos ORDER BY id DESC;
-- SELECT COUNT(*) FROM treinamentos WHERE cliente_id IS NULL;
