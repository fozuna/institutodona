-- 20261007183747_auto_schema_update_apply.sql
-- Migration no-op: consolidacao de Feedbacks no Pilar de Pessoas (Sprint 05.1)
-- so adiciona JOINs/colunas calculadas em consultas SELECT (PessoaFeedbackModel,
-- PessoaOperacionalModel) sobre tabelas/colunas ja existentes desde a Sprint 02
-- (pessoas_feedbacks) - nenhuma tabela, coluna ou constraint nova, sem
-- necessidade de DDL.

START TRANSACTION;

SELECT 'NO_SCHEMA_CHANGE' AS status_apply;

COMMIT;
