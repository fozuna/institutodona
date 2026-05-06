-- Verificacao pos-migration para evitar HTTP 500 em indicadores/index.
-- Execute em staging e producao apos apply.

-- 1) Confirmar estrutura essencial de indicadores.
SELECT 'table_indicadores' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'indicadores';

SELECT 'coluna_indicador' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'indicador';

SELECT 'coluna_deleted_at' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'deleted_at';

SELECT 'table_unidades_medida' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'unidades_medida';

SELECT 'table_indicador_responsavel' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel';

SELECT 'table_indicador_eventos' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos';

-- 2) Conferir constraints e indices criticos.
SELECT CONSTRAINT_NAME, TABLE_NAME
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
  AND TABLE_NAME IN ('indicadores', 'indicador_responsavel', 'indicador_eventos')
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_idx
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND TABLE_NAME IN ('indicadores', 'indicador_responsavel', 'indicador_eventos')
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- 3) Smoke query da tela indicadores/index (sem filtros).
SELECT
  i.id,
  COALESCE(i.indicador, i.nome) AS indicador_nome,
  i.cliente_id
FROM indicadores i
ORDER BY i.id DESC
LIMIT 20;

