-- Rollback de 20261007190626_pessoas_feedbacks_sbi_apply.sql
-- Deliberadamente NAO reverte `descricao` de TEXT para VARCHAR(2000): isso
-- truncaria/perderia qualquer texto consolidado maior que 2000 chars ja
-- gravado. O alargamento (TEXT) e' forward-only e seguro; so as colunas
-- aditivas (situacao/comportamento/impacto/orientacao/proximo_passo) sao
-- removidas abaixo.
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'pessoas_feedbacks'
        AND COLUMN_NAME = 'proximo_passo'
    ),
    'ALTER TABLE pessoas_feedbacks DROP COLUMN proximo_passo',
    'SELECT 1'
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
    'ALTER TABLE pessoas_feedbacks DROP COLUMN orientacao',
    'SELECT 1'
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
    'ALTER TABLE pessoas_feedbacks DROP COLUMN impacto',
    'SELECT 1'
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
    'ALTER TABLE pessoas_feedbacks DROP COLUMN comportamento',
    'SELECT 1'
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
        AND COLUMN_NAME = 'situacao'
    ),
    'ALTER TABLE pessoas_feedbacks DROP COLUMN situacao',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
