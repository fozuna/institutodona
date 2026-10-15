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

SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'cliente_status_logs'
      AND constraint_name = 'fk_cliente_status_logs_cliente'
      AND constraint_type = 'FOREIGN KEY'
  ),
  'OK_FK_EXISTS',
  'ERR_FK_MISSING'
) AS verify_cliente_status_logs_fk;
