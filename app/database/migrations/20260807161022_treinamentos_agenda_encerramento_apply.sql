SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos_agenda'
        AND COLUMN_NAME = 'encerrada_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos_agenda ADD COLUMN encerrada_em DATETIME NULL AFTER data_fim'
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
        AND TABLE_NAME = 'treinamentos_agenda'
        AND COLUMN_NAME = 'encerrada_por'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos_agenda ADD COLUMN encerrada_por INT NULL AFTER encerrada_em'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.statistics
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos_agenda'
        AND INDEX_NAME = 'idx_treinamentos_agenda_encerrada_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos_agenda ADD INDEX idx_treinamentos_agenda_encerrada_em (encerrada_em)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
