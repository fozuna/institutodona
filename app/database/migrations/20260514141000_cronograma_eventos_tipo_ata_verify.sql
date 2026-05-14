SET @schema_name = DATABASE();

SELECT
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'tipo_evento') AS has_tipo_evento,
  (SELECT COUNT(*)
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND COLUMN_NAME = 'ata_path') AS has_ata_path,
  (SELECT COUNT(*)
   FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name
     AND TABLE_NAME = 'cronograma_eventos'
     AND INDEX_NAME = 'idx_cronograma_eventos_tipo') AS has_idx_tipo;

