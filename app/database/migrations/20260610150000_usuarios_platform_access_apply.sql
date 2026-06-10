SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'usuarios'
    ),
    'SELECT 1',
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
        AND TABLE_NAME = 'usuarios'
        AND COLUMN_NAME = 'platform_access'
    ),
    'SELECT 1',
    'ALTER TABLE usuarios ADD COLUMN platform_access ENUM(''WEB'',''PWA'',''WEB_PWA'') NOT NULL DEFAULT ''WEB_PWA'' AFTER id_cliente'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE usuarios
SET platform_access = 'WEB_PWA'
WHERE platform_access IS NULL OR platform_access = '';
