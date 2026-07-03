SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_tipo_meta'
    ),
    'ALTER TABLE indicadores DROP INDEX idx_indicadores_tipo_meta',
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'tipo_meta'
    ),
    'ALTER TABLE indicadores DROP COLUMN tipo_meta',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
