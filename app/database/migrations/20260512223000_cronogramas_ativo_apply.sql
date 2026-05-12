SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
    ),
    'SELECT 1',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE cronogramas ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER ano'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
        AND INDEX_NAME = 'idx_cronogramas_cliente_ano_ativo'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronogramas_cliente_ano_ativo ON cronogramas (id_cliente, ano, ativo)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

