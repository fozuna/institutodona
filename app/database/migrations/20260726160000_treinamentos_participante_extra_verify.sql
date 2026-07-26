SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE, COLUMN_DEFAULT
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'treinamento_colaboradores'
  AND column_name = 'origem';

SELECT origem, COUNT(*) AS total
FROM treinamento_colaboradores
GROUP BY origem;
