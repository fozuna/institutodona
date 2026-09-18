SELECT 'tabela_acao_planos' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_acao_planos';

SELECT 'tabela_acao_treinamentos' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_acao_treinamentos';

SELECT 'tabela_necessidades' AS item, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pessoas_necessidades_treinamento';

SELECT 'uq_plano_por_acao' AS item, COUNT(*) AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pessoas_acao_planos' AND index_name = 'uq_pessoas_acao_plano_acao';

SELECT 'fk_plano_task' AS item, COUNT(*) AS ok FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'pessoas_acao_planos' AND constraint_name = 'fk_pessoas_acao_planos_task';

SELECT 'uq_acao_treinamento' AS item, COUNT(*) AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pessoas_acao_treinamentos' AND index_name = 'uq_pessoas_acao_treinamento';

SELECT 'fk_necessidade_treinamento' AS item, COUNT(*) AS ok FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'pessoas_necessidades_treinamento' AND constraint_name = 'fk_pessoas_necessidades_treinamento';
