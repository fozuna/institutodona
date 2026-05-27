-- 20260527101012_auto_schema_update_apply.sql
-- Migration no-op: alteracoes desta entrega sao de logica (filtros encadeados / escopo matriz+filiais)
-- e interface, sem necessidade de DDL adicional em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
