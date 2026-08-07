SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'clientes'
  AND column_name = 'ativo';

SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'cliente_status_logs'
  ),
  'OK_TABLE_EXISTS',
  'ERR_TABLE_MISSING'
) AS verify_cliente_status_logs_table;

SELECT column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'cliente_status_logs'
ORDER BY ordinal_position;
