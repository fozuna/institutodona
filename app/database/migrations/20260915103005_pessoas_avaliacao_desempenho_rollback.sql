-- Rollback de 20260915103005_pessoas_avaliacao_desempenho_apply.sql
-- Ordem inversa das FKs (filhas antes das pais). Todas as 7 tabelas são
-- novas nesta Sprint - nenhum dado de outra entidade é tocado.
DROP TABLE IF EXISTS pessoas_avaliacao_respostas;
DROP TABLE IF EXISTS pessoas_avaliacoes;
DROP TABLE IF EXISTS pessoas_ciclo_participantes;
DROP TABLE IF EXISTS pessoas_ciclos_avaliacao;
DROP TABLE IF EXISTS pessoas_modelos_perguntas;
DROP TABLE IF EXISTS pessoas_modelos_grupos;
DROP TABLE IF EXISTS pessoas_modelos_avaliacao;
