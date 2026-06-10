SET @schema_name = DATABASE();

SELECT
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'responsavel_principal') AS has_responsavel_principal,
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'responsaveis_secundarios') AS has_responsaveis_secundarios,
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'comentarios') AS has_comentarios,
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'notas_internas') AS has_notas_internas;
