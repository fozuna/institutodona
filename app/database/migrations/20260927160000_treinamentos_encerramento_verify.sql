SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'treinamentos'
  AND column_name IN ('encerrado_em', 'encerrado_por', 'encerramento_justificativa')
ORDER BY column_name;

SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'treinamentos'
      AND index_name = 'idx_treinamentos_encerrado_em'
  ),
  'OK_INDEX_EXISTS',
  'ERR_INDEX_MISSING'
) AS verify_encerrado_em_index;
