-- 20260507131521_auto_schema_update_apply.sql
-- Migration no-op: alteracoes desta entrega sao de aplicacao (UI/branding/navegacao),
-- sem necessidade de DDL adicional em banco.

START TRANSACTION;

-- marcador de execucao
SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
