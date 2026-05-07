-- 20260507143007_auto_schema_update_rollback.sql
-- Rollback no-op: sem DDL aplicado no arquivo *_apply.sql.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_rollback;

COMMIT;
