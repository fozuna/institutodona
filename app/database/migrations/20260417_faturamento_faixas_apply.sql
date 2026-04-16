CREATE TABLE IF NOT EXISTS faturamento_faixas (
  id INT PRIMARY KEY,
  descricao VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO faturamento_faixas (id, descricao) VALUES
  (1, 'Até R$ 100.000,00'),
  (2, 'De R$ 100.001,00 a R$ 250.000,00'),
  (3, 'De R$ 250.001,00 a R$ 500.000,00'),
  (4, 'De R$ 500.001,00 a R$ 750.000,00'),
  (5, 'De R$ 750.001,00 a R$ 1.000.000,00'),
  (6, 'Acima de R$ 1.000.000,00')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

SET @has_av_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes'
    AND column_name = 'faturamento_faixa_id'
);
SET @sql := IF(@has_av_col = 0,
  'ALTER TABLE avaliacoes ADD COLUMN faturamento_faixa_id INT NULL AFTER faturamento_medio_anual',
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
SET @sql := IF(@has_pub_col = 0,
  'ALTER TABLE avaliacoes_publicas ADD COLUMN faturamento_faixa_id INT NULL AFTER faturamento_anual',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE avaliacoes
SET faturamento_faixa_id = CASE
  WHEN faturamento_medio_anual <= 100000 THEN 1
  WHEN faturamento_medio_anual <= 250000 THEN 2
  WHEN faturamento_medio_anual <= 500000 THEN 3
  WHEN faturamento_medio_anual <= 750000 THEN 4
  WHEN faturamento_medio_anual <= 1000000 THEN 5
  WHEN faturamento_medio_anual > 1000000 THEN 6
  ELSE faturamento_faixa_id
END
WHERE faturamento_faixa_id IS NULL
  AND faturamento_medio_anual IS NOT NULL
  AND faturamento_medio_anual > 0;

SET @has_av_pub := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes_publicas'
);
SET @sql := IF(@has_av_pub > 0, '
UPDATE avaliacoes_publicas
SET faturamento_faixa_id = CASE
  WHEN faturamento_anual <= 100000 THEN 1
  WHEN faturamento_anual <= 250000 THEN 2
  WHEN faturamento_anual <= 500000 THEN 3
  WHEN faturamento_anual <= 750000 THEN 4
  WHEN faturamento_anual <= 1000000 THEN 5
  WHEN faturamento_anual > 1000000 THEN 6
  ELSE faturamento_faixa_id
END
WHERE faturamento_faixa_id IS NULL
  AND faturamento_anual IS NOT NULL
  AND faturamento_anual > 0
', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
