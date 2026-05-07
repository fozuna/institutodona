-- 20260507131521_auto_schema_update_verify.sql
-- Verificacao no-op: confirma apenas disponibilidade basica do schema.

SELECT 1 AS db_online;
SELECT COUNT(*) AS tabelas_encontradas
FROM information_schema.tables
WHERE table_schema = DATABASE();
