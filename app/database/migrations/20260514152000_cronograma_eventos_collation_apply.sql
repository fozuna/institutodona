SET @schema_name = DATABASE();

SET @collation = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLLATIONS WHERE COLLATION_NAME = 'utf8mb4_uca1400_ai_ci'),
    'utf8mb4_uca1400_ai_ci',
    'utf8mb4_unicode_ci'
  )
);

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'topico'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN topico VARCHAR(120) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NOT NULL'),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'unidade'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN unidade VARCHAR(120) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NULL'),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'atividade'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN atividade VARCHAR(255) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NOT NULL'),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'responsavel'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN responsavel VARCHAR(255) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NULL'),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'periodicidade'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN periodicidade VARCHAR(20) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NOT NULL DEFAULT ''unico'''),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'status'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN status VARCHAR(20) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NOT NULL DEFAULT ''Planejado'''),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'tipo_evento'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN tipo_evento VARCHAR(30) CHARACTER SET utf8mb4 COLLATE ', @collation, ' NOT NULL DEFAULT ''Tarefa'''),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'cronograma_eventos' AND COLUMN_NAME = 'modelo'),
    CONCAT('ALTER TABLE cronograma_eventos MODIFY COLUMN modelo ENUM(''Online'',''Presencial'') CHARACTER SET utf8mb4 COLLATE ', @collation, ' NULL'),
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

