-- 20260514204231_auto_schema_update_apply.sql
-- Migration no-op: esta entrega altera filtros/formatacao/consultas do modulo Indicadores,
-- sem necessidade de DDL adicional em banco.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
