SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'atas'
  ),
  'OK_TABLE_EXISTS',
  'ERR_TABLE_MISSING'
) AS verify_atas_table;

SELECT column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'atas'
ORDER BY ordinal_position;
