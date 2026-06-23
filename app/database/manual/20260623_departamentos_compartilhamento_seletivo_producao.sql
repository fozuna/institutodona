-- =========================================================
-- Compartilhamento seletivo de departamentos entre matriz e filiais
-- Execute este script APOS:
-- 20260620_producao_migrations_pendentes_sem_usuarios_platform_access.sql
-- =========================================================

SET @schema_name = DATABASE();

-- 1. Adiciona o flag de compartilhamento global no grupo
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'departamentos'
        AND COLUMN_NAME = 'compartilhar_todas_filiais'
    ),
    'SELECT 1',
    'ALTER TABLE departamentos ADD COLUMN compartilhar_todas_filiais TINYINT(1) NOT NULL DEFAULT 0 AFTER cliente_id'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Cria a tabela relacional de visibilidade por empresa
CREATE TABLE IF NOT EXISTS departamento_clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  departamento_id INT NOT NULL,
  cliente_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_departamento_cliente (departamento_id, cliente_id),
  KEY idx_departamento_clientes_cliente (cliente_id),
  CONSTRAINT fk_departamento_clientes_departamento
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_departamento_clientes_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Preserva a disponibilidade atual para a empresa originalmente vinculada
INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id)
SELECT d.id, d.cliente_id
FROM departamentos d
WHERE d.cliente_id IS NOT NULL
  AND d.cliente_id > 0;

-- 4. Se houver historico da consolidacao anterior, reaproveita os vinculos de origem
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'catalogo_grupo_sync_logs'
    ),
    'INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id)
     SELECT
       CASE
         WHEN COALESCE(target_id, 0) > 0 THEN target_id
         ELSE source_id
       END AS departamento_id,
       cliente_origem_id
     FROM catalogo_grupo_sync_logs
     WHERE entity_type = ''departamento''
       AND action IN (''reparent_to_group'', ''merge_duplicate'')
       AND cliente_origem_id IS NOT NULL
       AND cliente_origem_id > 0',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Validacoes recomendadas
-- SELECT * FROM departamento_clientes ORDER BY departamento_id, cliente_id;
-- SELECT id, nome, cliente_id, compartilhar_todas_filiais FROM departamentos ORDER BY id;
