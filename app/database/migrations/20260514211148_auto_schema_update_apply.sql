-- 20260514211148_auto_schema_update_apply.sql
-- Migration no-op: ajustes desta entrega sao de UI/validacao/consulta do modulo Indicadores,
-- sem necessidade de DDL adicional em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
