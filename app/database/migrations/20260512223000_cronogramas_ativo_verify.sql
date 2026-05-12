SET @schema_name = DATABASE();

SELECT
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronogramas'
     AND COLUMN_NAME = 'ativo') AS has_ativo,
  (SELECT COUNT(*)
   FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronogramas'
     AND INDEX_NAME = 'idx_cronogramas_cliente_ano_ativo') AS has_idx;

