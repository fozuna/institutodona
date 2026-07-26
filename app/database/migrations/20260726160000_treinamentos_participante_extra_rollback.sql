SET @has_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'treinamento_colaboradores'
    AND column_name = 'origem'
);
SET @sql := IF(@has_col > 0,
  'ALTER TABLE treinamento_colaboradores DROP COLUMN origem',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
