-- ============================================================================
-- Verificação pós-migração: avaliações internas + links públicos permanentes
-- Data: 2026-04-13
-- Objetivo: confirmar estrutura, constraints e dados mínimos após aplicação.
-- ============================================================================

-- 1) Estrutura: colunas obrigatórias em avaliacoes
SELECT 'avaliacoes.origim_fields' AS check_name,
       SUM(column_name IN ('origem_cadastro','created_by_user_id','cliente_associado_em')) AS cols_found,
       3 AS cols_expected
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes'
  AND column_name IN ('origem_cadastro','created_by_user_id','cliente_associado_em');

-- 2) Estrutura: colunas obrigatórias em avaliacoes_publicas
SELECT 'avaliacoes_publicas.core_fields' AS check_name,
       SUM(column_name IN ('token','slug','status','expiracao','data_criacao','data_conclusao')) AS cols_found,
       6 AS cols_expected
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes_publicas'
  AND column_name IN ('token','slug','status','expiracao','data_criacao','data_conclusao');

-- 3) Índices críticos
SELECT 'idx_avaliacoes_cliente_id' AS check_name,
       COUNT(*) AS present
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes'
  AND index_name = 'idx_avaliacoes_cliente_id';

SELECT 'uq_avaliacoes_publicas_token' AS check_name,
       COUNT(*) AS present
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes_publicas'
  AND index_name = 'uq_avaliacoes_publicas_token';

SELECT 'uq_avaliacoes_publicas_slug' AS check_name,
       COUNT(*) AS present
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'avaliacoes_publicas'
  AND index_name = 'uq_avaliacoes_publicas_slug';

-- 4) FK principais
SELECT 'fk_avaliacoes_publicas_avaliacao' AS check_name,
       COUNT(*) AS present
FROM information_schema.referential_constraints
WHERE constraint_schema = DATABASE()
  AND table_name = 'avaliacoes_publicas'
  AND constraint_name = 'fk_avaliacoes_publicas_avaliacao';

SELECT 'fk_avaliacoes_created_by_user' AS check_name,
       COUNT(*) AS present
FROM information_schema.referential_constraints
WHERE constraint_schema = DATABASE()
  AND table_name = 'avaliacoes'
  AND constraint_name = 'fk_avaliacoes_created_by_user';

-- 5) Dados: origem_cadastro somente nos valores esperados
SELECT 'avaliacoes.origem_values_invalid' AS check_name,
       COUNT(*) AS invalid_rows
FROM avaliacoes
WHERE origem_cadastro NOT IN ('cliente_existente','potencial_cliente');

-- 6) Dados: links públicos permanentes (sem expiração)
SELECT 'avaliacoes_publicas.permanent_expiracao_not_null' AS check_name,
       COUNT(*) AS rows_with_expiration
FROM avaliacoes_publicas
WHERE expiracao IS NOT NULL;

-- 7) Dados: consistência do status concluido
SELECT 'avaliacoes_publicas.concluida_without_data_conclusao' AS check_name,
       COUNT(*) AS invalid_rows
FROM avaliacoes_publicas
WHERE status = 'concluida'
  AND data_conclusao IS NULL;

-- 8) Amostras para inspeção manual
SELECT id, cliente_id, origem_cadastro, created_by_user_id, cliente_associado_em, created_at
FROM avaliacoes
ORDER BY id DESC
LIMIT 10;

SELECT id, avaliacao_id, token, slug, status, expiracao, data_criacao, data_conclusao
FROM avaliacoes_publicas
ORDER BY id DESC
LIMIT 10;
