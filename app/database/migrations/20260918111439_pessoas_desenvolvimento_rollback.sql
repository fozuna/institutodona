-- Rollback de 20260918111439_pessoas_desenvolvimento_apply.sql (tabelas novas;
-- nenhum dado de Plano de Ação/Treinamentos/Sprints anteriores é tocado).
DROP TABLE IF EXISTS pessoas_necessidades_treinamento;
DROP TABLE IF EXISTS pessoas_acao_treinamentos;
DROP TABLE IF EXISTS pessoas_acao_planos;
