-- 20261007183747_auto_schema_update_verify.sql
-- Verificacao no-op: confirma que a tabela/colunas que PessoaFeedbackModel e
-- PessoaOperacionalModel passaram a consultar com mais JOINs ja existiam
-- (nenhuma DDL nova nesta entrega).

SELECT 1 AS db_online;

SELECT COUNT(*) AS pessoas_feedbacks_existe
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks';

SELECT COUNT(*) AS colunas_esperadas
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks'
  AND column_name IN ('empresa_id', 'colaborador_id', 'avaliacao_id', 'gap_id', 'tipo', 'titulo', 'descricao', 'data_feedback', 'registrado_por');
