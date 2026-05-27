SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cronograma_evento_anexos'
  ),
  'OK_TABLE_EXISTS',
  'ERR_TABLE_MISSING'
) AS verify_table;

SELECT COUNT(*) AS verify_columns
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'cronograma_evento_anexos'
  AND COLUMN_NAME IN ('id','evento_id','path','original_name','mime','size','sha256','created_at','deleted_at');
