SET @schema_name = DATABASE();

SELECT
  CASE
    WHEN EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_evento_tipos'
    ) THEN 'OK'
    ELSE 'MISSING'
  END AS cronograma_evento_tipos_table;

SELECT
  COUNT(*) AS ativos
FROM cronograma_evento_tipos
WHERE ativo = 1;
