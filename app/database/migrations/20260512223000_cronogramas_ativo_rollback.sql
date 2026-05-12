SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
        AND INDEX_NAME = 'idx_cronogramas_cliente_ano_ativo'
    ),
    'DROP INDEX idx_cronogramas_cliente_ano_ativo ON cronogramas',
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
    'ALTER TABLE cronogramas DROP COLUMN ativo',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

