-- 20260602222714_auto_schema_update_apply.sql
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_colaboradores'
        AND COLUMN_NAME = 'status_detalhe'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_colaboradores ADD COLUMN status_detalhe VARCHAR(30) NULL AFTER status'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
