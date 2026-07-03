-- 20260623220000_indicadores_tipo_meta_apply.sql
-- Adiciona o tipo de meta dos indicadores com default retrocompativel.

SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'tipo_meta'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN tipo_meta VARCHAR(20) NOT NULL DEFAULT ''minimo'' AFTER valor'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE indicadores
SET tipo_meta = 'minimo'
WHERE tipo_meta IS NULL OR tipo_meta = '' OR tipo_meta NOT IN ('minimo', 'maximo');

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_tipo_meta'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_tipo_meta (tipo_meta)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
