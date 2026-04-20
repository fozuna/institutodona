SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'evento_pai_id'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN evento_pai_id INT NULL AFTER id_cronograma'
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'periodicidade'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN periodicidade VARCHAR(20) NOT NULL DEFAULT ''unico'' AFTER data'
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND INDEX_NAME = 'idx_cronograma_eventos_pai'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronograma_eventos_pai ON cronograma_eventos (evento_pai_id)'
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND INDEX_NAME = 'idx_cronograma_eventos_data'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronograma_eventos_data ON cronograma_eventos (id_cronograma, data)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
