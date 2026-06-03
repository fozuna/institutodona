-- 20260602222714_auto_schema_update_verify.sql
SET @schema_name = DATABASE();

SET @exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'treinamento_colaboradores'
    AND COLUMN_NAME = 'status_detalhe'
);

SET @sql = (SELECT IF(@exists = 1, 'SELECT 1', 'SELECT 1/0'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
