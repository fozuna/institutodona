SELECT 1 FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'treinamentos_agenda'
  AND column_name = 'data_fim'
LIMIT 1;
