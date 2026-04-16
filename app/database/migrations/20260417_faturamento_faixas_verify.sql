SELECT id, descricao
FROM faturamento_faixas
ORDER BY id;

SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes'
  AND column_name = 'faturamento_faixa_id';

SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes_publicas'
  AND column_name = 'faturamento_faixa_id';

SELECT COUNT(*) AS avaliacoes_migradas
FROM avaliacoes
WHERE faturamento_medio_anual > 0
  AND faturamento_faixa_id IS NOT NULL;

SELECT COUNT(*) AS avaliacoes_publicas_migradas
FROM avaliacoes_publicas
WHERE faturamento_anual > 0
  AND faturamento_faixa_id IS NOT NULL;
