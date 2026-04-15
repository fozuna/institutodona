-- Endpoint publico fixo em /public/avaliacoes.php
-- Resolve contexto por dominio usando clientes.dominio_publico

SET @has_dominio_publico := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'clientes'
    AND column_name = 'dominio_publico'
);
SET @sql := IF(@has_dominio_publico = 0,
  'ALTER TABLE clientes ADD COLUMN dominio_publico VARCHAR(255) NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_dominio_index := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'clientes'
    AND index_name = 'uq_clientes_dominio_publico'
);
SET @sql := IF(@has_dominio_index = 0,
  'CREATE UNIQUE INDEX uq_clientes_dominio_publico ON clientes (dominio_publico)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
