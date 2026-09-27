-- Rollback de 20260927160000_treinamentos_encerramento_apply.sql
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.statistics
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND INDEX_NAME = 'idx_treinamentos_encerrado_em'
    ),
    'ALTER TABLE treinamentos DROP INDEX idx_treinamentos_encerrado_em',
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
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerramento_justificativa'
    ),
    'ALTER TABLE treinamentos DROP COLUMN encerramento_justificativa',
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
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerrado_por'
    ),
    'ALTER TABLE treinamentos DROP COLUMN encerrado_por',
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
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerrado_em'
    ),
    'ALTER TABLE treinamentos DROP COLUMN encerrado_em',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
