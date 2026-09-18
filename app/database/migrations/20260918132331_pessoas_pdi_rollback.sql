-- Rollback de 20260918132331_pessoas_pdi_apply.sql (tabelas novas; nada de
-- GAPs/Ações/Plano/Treinamento é tocado).
DROP TABLE IF EXISTS pessoas_pdi_objetivo_acoes;
DROP TABLE IF EXISTS pessoas_pdi_gaps;
DROP TABLE IF EXISTS pessoas_pdi_objetivos;
DROP TABLE IF EXISTS pessoas_pdis;
