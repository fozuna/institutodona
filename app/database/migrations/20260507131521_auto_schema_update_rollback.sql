-- 20260507131521_auto_schema_update_rollback.sql
-- Rollback no-op: sem DDL aplicado no arquivo *_apply.sql.

START TRANSACTION;

-- marcador de rollback
SELECT 'NO_SCHEMA_CHANGE' AS status_rollback;

COMMIT;
