SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'tipo_evento'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN tipo_evento VARCHAR(30) NOT NULL DEFAULT ''Tarefa'' AFTER periodicidade'
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
        AND COLUMN_NAME = 'ata_path'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_path VARCHAR(255) NULL AFTER status'
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
        AND COLUMN_NAME = 'ata_original_name'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_original_name VARCHAR(255) NULL AFTER ata_path'
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
        AND COLUMN_NAME = 'ata_mime'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_mime VARCHAR(100) NULL AFTER ata_original_name'
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
        AND COLUMN_NAME = 'ata_size'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_size INT UNSIGNED NULL AFTER ata_mime'
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
        AND COLUMN_NAME = 'ata_sha256'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_sha256 CHAR(64) NULL AFTER ata_size'
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
        AND INDEX_NAME = 'idx_cronograma_eventos_tipo'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronograma_eventos_tipo ON cronograma_eventos (tipo_evento)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

