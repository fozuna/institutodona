-- =========================================================
-- Indicadores: tipo de meta minimo x teto maximo permitido
-- Execute este script nos ambientes de producao onde as
-- migrations automaticas ainda nao sao aplicadas.
-- =========================================================

SET @schema_name = DATABASE();

-- 1. Adiciona a coluna tipo_meta com default retrocompativel
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'tipo_meta'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN tipo_meta VARCHAR(20) NOT NULL DEFAULT ''minimo'' AFTER valor'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Normaliza todos os registros legados para a regra original
UPDATE indicadores
SET tipo_meta = 'minimo'
WHERE tipo_meta IS NULL OR tipo_meta = '' OR tipo_meta NOT IN ('minimo', 'maximo');

-- 3. Garante indice para filtragem e leitura futura
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_tipo_meta'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_tipo_meta (tipo_meta)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Validacoes recomendadas
-- SELECT id, indicador, valor, tipo_meta FROM indicadores ORDER BY id DESC;
-- SELECT COUNT(*) FROM indicadores WHERE tipo_meta NOT IN ('minimo', 'maximo') OR tipo_meta IS NULL OR tipo_meta = '';
