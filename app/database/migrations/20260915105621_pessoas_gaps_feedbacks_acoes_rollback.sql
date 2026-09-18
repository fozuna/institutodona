-- Rollback de 20260915105621_pessoas_gaps_feedbacks_acoes_apply.sql
-- Ordem inversa das FKs. Todas as 3 tabelas são novas nesta Sprint -
-- nenhum dado da Sprint 01 ou de outra entidade é tocado.
DROP TABLE IF EXISTS pessoas_acoes_melhoria;
DROP TABLE IF EXISTS pessoas_feedbacks;
DROP TABLE IF EXISTS pessoas_gaps;
