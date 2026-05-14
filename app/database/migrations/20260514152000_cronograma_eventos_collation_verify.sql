SET @schema_name = DATABASE();

SET @expected = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLLATIONS WHERE COLLATION_NAME = 'utf8mb4_uca1400_ai_ci'),
    'utf8mb4_uca1400_ai_ci',
    'utf8mb4_unicode_ci'
  )
);

SELECT
  @expected AS expected_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'topico' LIMIT 1) AS topico_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'unidade' LIMIT 1) AS unidade_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'atividade' LIMIT 1) AS atividade_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'responsavel' LIMIT 1) AS responsavel_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'modelo' LIMIT 1) AS modelo_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'status' LIMIT 1) AS status_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'periodicidade' LIMIT 1) AS periodicidade_collation,
  (SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'tipo_evento' LIMIT 1) AS tipo_evento_collation;

