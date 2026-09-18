SELECT 'tabela_pdis' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdis';

SELECT 'tabela_objetivos' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdi_objetivos';

SELECT 'tabela_pdi_gaps' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdi_gaps';

SELECT 'tabela_objetivo_acoes' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdi_objetivo_acoes';

SELECT 'coluna_gerada_pdi_ativo' AS item, COUNT(*) AS ok FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdis' AND column_name = 'ativo_colaborador_key' AND extra LIKE '%VIRTUAL GENERATED%';

SELECT 'uq_pdi_ativo_por_colaborador' AS item, COUNT(*) AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdis' AND index_name = 'uq_pessoas_pdi_ativo_colaborador' AND non_unique = 0;

SELECT 'uq_pdi_gap' AS item, COUNT(*) AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdi_gaps' AND index_name = 'uq_pessoas_pdi_gap';

SELECT 'uq_objetivo_acao' AS item, COUNT(*) AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pessoas_pdi_objetivo_acoes' AND index_name = 'uq_pessoas_pdi_objetivo_acao';
