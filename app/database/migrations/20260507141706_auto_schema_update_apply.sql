-- 20260507141706_auto_schema_update_apply.sql
-- Migration no-op: correcoes desta entrega sao de camada web (CSP/submit/robustez),
-- sem necessidade de DDL adicional em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
