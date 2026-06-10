SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'responsavel_principal'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN responsavel_principal VARCHAR(255) NULL AFTER responsavel'
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
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN responsaveis_secundarios VARCHAR(255) NULL AFTER responsavel_principal'
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
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN comentarios TEXT NULL AFTER responsaveis_secundarios'
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
        AND COLUMN_NAME = 'notas_internas'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN notas_internas TEXT NULL AFTER comentarios'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cronograma_eventos
SET
  responsavel_principal = NULLIF(TRIM(SUBSTRING_INDEX(responsavel, ',', 1)), ''),
  responsaveis_secundarios = CASE
    WHEN LOCATE(',', responsavel) > 0 THEN NULLIF(TRIM(SUBSTRING(responsavel, LOCATE(',', responsavel) + 1)), '')
    ELSE NULL
  END
WHERE responsavel IS NOT NULL
  AND responsavel <> ''
  AND (responsavel_principal IS NULL OR responsavel_principal = '')
  AND (responsaveis_secundarios IS NULL OR responsaveis_secundarios = '');
