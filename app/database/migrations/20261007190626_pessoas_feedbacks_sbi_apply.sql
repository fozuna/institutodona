-- Pilar de Pessoas - cadastro estruturado de Feedbacks no modelo SBI
-- (Situação, Comportamento, Impacto) + Orientação e Próximo passo.
-- Aditivo e idempotente: todas as colunas são NULL, nenhum dado existente
-- muda. Feedbacks legados continuam com estas colunas vazias e exibidos
-- integralmente a partir do campo `descricao` (preservado como
-- representação canônica consolidada, gerada a partir dos campos
-- estruturados quando eles existem).
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'situacao'
    ),
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks ADD COLUMN situacao VARCHAR(1000) NULL AFTER descricao'
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
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'comportamento'
    ),
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks ADD COLUMN comportamento VARCHAR(1000) NULL AFTER situacao'
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
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'impacto'
    ),
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks ADD COLUMN impacto VARCHAR(1000) NULL AFTER comportamento'
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
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'orientacao'
    ),
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks ADD COLUMN orientacao VARCHAR(1000) NULL AFTER impacto'
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
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'proximo_passo'
    ),
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks ADD COLUMN proximo_passo VARCHAR(1000) NULL AFTER orientacao'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- `descricao` passa a ser a representacao canonica CONSOLIDADA dos 5 campos
-- estruturados (gerada pelo Model, nunca editada direto) - VARCHAR(2000)
-- nao comporta 4-5 campos de ate 1000 chars cada sem truncar. Alarga para
-- TEXT (aditivo, sem perda de dado existente). O rollback NAO reverte esta
-- coluna especifica para nao truncar/perder dado jah gravado como TEXT.
SET @sql = (
  SELECT IF(
    (
      SELECT DATA_TYPE FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'descricao'
    ) = 'text',
    'SELECT 1',
    'ALTER TABLE pessoas_feedbacks MODIFY COLUMN descricao TEXT NOT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
