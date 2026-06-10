SET @schema_name = DATABASE();

SELECT
  CASE
    WHEN EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'usuarios'
        AND COLUMN_NAME = 'platform_access'
    ) THEN 'OK'
    ELSE 'MISSING'
  END AS platform_access_column;

SELECT
  COLUMN_TYPE AS platform_access_type,
  COLUMN_DEFAULT AS platform_access_default,
  IS_NULLABLE AS platform_access_nullable
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @schema_name
  AND TABLE_NAME = 'usuarios'
  AND COLUMN_NAME = 'platform_access';
