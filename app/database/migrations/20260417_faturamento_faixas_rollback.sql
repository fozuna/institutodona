SET @has_av_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes'
    AND column_name = 'faturamento_faixa_id'
);
SET @sql := IF(@has_av_col > 0,
  'ALTER TABLE avaliacoes DROP COLUMN faturamento_faixa_id',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_pub_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes_publicas'
    AND column_name = 'faturamento_faixa_id'
);
SET @sql := IF(@has_pub_col > 0,
  'ALTER TABLE avaliacoes_publicas DROP COLUMN faturamento_faixa_id',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS faturamento_faixas;
