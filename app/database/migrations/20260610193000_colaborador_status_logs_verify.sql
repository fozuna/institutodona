SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'colaborador_status_logs'
  ),
  'OK_TABLE_EXISTS',
  'ERR_TABLE_MISSING'
) AS verify_colaborador_status_logs_table;

SELECT column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'colaborador_status_logs'
ORDER BY ordinal_position;

