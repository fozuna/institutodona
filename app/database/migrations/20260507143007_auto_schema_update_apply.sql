-- 20260507143007_auto_schema_update_apply.sql
-- Migration no-op: correcoes desta entrega sao de logica (download/geracao PDF),
-- sem alteracoes de DDL em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
