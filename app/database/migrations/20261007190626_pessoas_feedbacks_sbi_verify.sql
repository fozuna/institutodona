SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'pessoas_feedbacks'
  AND column_name IN ('situacao', 'comportamento', 'impacto', 'orientacao', 'proximo_passo')
ORDER BY column_name;

SELECT IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks'
     AND column_name IN ('situacao', 'comportamento', 'impacto', 'orientacao', 'proximo_passo')) = 5,
  'OK_5_COLUNAS',
  'ERR_COLUNAS_FALTANTES'
) AS verify_colunas_sbi;

SELECT IF(
  (SELECT DATA_TYPE FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks' AND column_name = 'descricao') = 'text',
  'OK_DESCRICAO_TEXT',
  'ERR_DESCRICAO_NAO_ALARGADA'
) AS verify_descricao_text;
