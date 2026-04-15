SELECT
  COLUMN_NAME,
  IS_NULLABLE,
  COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'clientes'
  AND column_name = 'dominio_publico';

SELECT
  index_name,
  non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'clientes'
  AND index_name = 'uq_clientes_dominio_publico'
GROUP BY index_name, non_unique;
