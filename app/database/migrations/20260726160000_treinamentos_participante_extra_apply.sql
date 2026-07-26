SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_colaboradores'
        AND COLUMN_NAME = 'origem'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_colaboradores ADD COLUMN origem ENUM(''publico_alvo'',''extra'') NOT NULL DEFAULT ''publico_alvo'' AFTER status_detalhe'
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
        AND TABLE_NAME = 'treinamento_colaboradores'
        AND INDEX_NAME = 'idx_treinamento_colaboradores_origem'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_colaboradores ADD INDEX idx_treinamento_colaboradores_origem (origem)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
