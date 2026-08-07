SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'treinamentos_agenda'
  AND column_name IN ('encerrada_em', 'encerrada_por')
ORDER BY column_name;

SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'treinamentos_agenda'
      AND index_name = 'idx_treinamentos_agenda_encerrada_em'
  ),
  'OK_INDEX_EXISTS',
  'ERR_INDEX_MISSING'
) AS verify_encerrada_em_index;
