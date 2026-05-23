-- 20260523132515_auto_schema_update_apply.sql
-- Migration no-op: alteracoes desta entrega sao de logica no cronograma (fallback de collation),
-- sem necessidade de DDL adicional em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
