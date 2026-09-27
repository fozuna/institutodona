-- Encerramento manual de treinamento (independente da cobertura de 100%).
-- encerrado_em/encerrado_por/encerramento_justificativa ficam NULL enquanto o
-- treinamento esta aberto; reabrir limpa os tres (historico fica em
-- treinamento_auditoria_logs). Aditivo e idempotente: nenhum dado existente muda.
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerrado_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN encerrado_em DATETIME NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerrado_por'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN encerrado_por INT NULL AFTER encerrado_em'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'encerramento_justificativa'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN encerramento_justificativa VARCHAR(1000) NULL AFTER encerrado_por'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.statistics
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND INDEX_NAME = 'idx_treinamentos_encerrado_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD INDEX idx_treinamentos_encerrado_em (encerrado_em)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
