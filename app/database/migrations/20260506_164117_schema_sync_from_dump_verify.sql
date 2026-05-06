-- Verificacao pos-migracao para sincronizacao de schema (dump -> dev)
-- Execute no ambiente alvo apos aplicar 20260506_164117_schema_sync_from_dump_apply.sql

-- 1) Tabelas obrigatorias
SELECT 'tables_missing' AS check_name, COUNT(*) AS missing_count
FROM (
    SELECT 'indicador_eventos' AS t
    UNION ALL SELECT 'indicador_responsavel'
    UNION ALL SELECT 'unidades_medida'
) req
LEFT JOIN information_schema.tables it
  ON it.table_schema = DATABASE() AND it.table_name = req.t
WHERE it.table_name IS NULL;

-- 2) Colunas criticas
SELECT 'columns_missing' AS check_name, COUNT(*) AS missing_count
FROM (
    SELECT 'cronograma_eventos' AS t, 'evento_pai_id' AS c
    UNION ALL SELECT 'cronograma_eventos', 'periodicidade'
    UNION ALL SELECT 'indicadores', 'indicador'
    UNION ALL SELECT 'indicadores', 'unidade_medida_id'
    UNION ALL SELECT 'indicadores', 'valor_minimo'
    UNION ALL SELECT 'indicadores', 'valor_maximo'
    UNION ALL SELECT 'avaliacoes', 'id_cliente'
    UNION ALL SELECT 'colaboradores', 'ativo'
    UNION ALL SELECT 'setores', 'ativo'
) req
LEFT JOIN information_schema.columns ic
  ON ic.table_schema = DATABASE()
 AND ic.table_name = req.t
 AND ic.column_name = req.c
WHERE ic.column_name IS NULL;

-- 3) Chaves estrangeiras criticas
SELECT 'fks_missing' AS check_name, COUNT(*) AS missing_count
FROM (
    SELECT 'fk_indicador_eventos_cliente' AS fk
    UNION ALL SELECT 'fk_indicador_eventos_indicador'
    UNION ALL SELECT 'fk_indicador_responsavel_colaborador'
    UNION ALL SELECT 'fk_indicador_responsavel_indicador'
    UNION ALL SELECT 'fk_indicadores_cliente'
    UNION ALL SELECT 'fk_indicadores_departamento'
    UNION ALL SELECT 'fk_indicadores_setor'
    UNION ALL SELECT 'fk_indicadores_unidade_medida'
) req
LEFT JOIN information_schema.referential_constraints rc
  ON rc.constraint_schema = DATABASE()
 AND rc.constraint_name = req.fk
WHERE rc.constraint_name IS NULL;

-- 4) Indices criticos (performance de consultas principais)
SELECT 'indexes_missing' AS check_name, COUNT(*) AS missing_count
FROM (
    SELECT 'indicadores' AS t, 'idx_indicadores_cliente' AS i
    UNION ALL SELECT 'indicadores', 'idx_indicadores_deleted_at'
    UNION ALL SELECT 'indicador_eventos', 'idx_indicador_eventos_cliente_data'
    UNION ALL SELECT 'indicador_eventos', 'idx_indicador_eventos_status'
    UNION ALL SELECT 'cronograma_eventos', 'idx_cronograma_eventos_data'
    UNION ALL SELECT 'cronograma_eventos', 'idx_cronograma_eventos_pai'
) req
LEFT JOIN information_schema.statistics st
  ON st.table_schema = DATABASE()
 AND st.table_name = req.t
 AND st.index_name = req.i
WHERE st.index_name IS NULL;

-- 5) Sanidade de consultas criticas (EXPLAIN)
EXPLAIN SELECT i.id, i.indicador, i.valor
FROM indicadores i
WHERE i.deleted_at IS NULL
ORDER BY i.id DESC
LIMIT 50;

EXPLAIN SELECT ie.id, ie.data_evento, ie.status_meta
FROM indicador_eventos ie
WHERE ie.deleted_at IS NULL
  AND ie.cliente_id = 1
ORDER BY ie.data_evento DESC
LIMIT 50;
