-- 20260602222714_auto_schema_update_rollback.sql
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_colaboradores'
        AND COLUMN_NAME = 'status_detalhe'
    ),
    'ALTER TABLE treinamento_colaboradores DROP COLUMN status_detalhe',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
