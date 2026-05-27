SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'funcoes'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE funcoes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
