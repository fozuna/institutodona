SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'notas_internas'
    ),
    'ALTER TABLE cronograma_eventos DROP COLUMN notas_internas',
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'comentarios'
    ),
    'ALTER TABLE cronograma_eventos DROP COLUMN comentarios',
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'responsaveis_secundarios'
    ),
    'ALTER TABLE cronograma_eventos DROP COLUMN responsaveis_secundarios',
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
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'responsavel_principal'
    ),
    'ALTER TABLE cronograma_eventos DROP COLUMN responsavel_principal',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
