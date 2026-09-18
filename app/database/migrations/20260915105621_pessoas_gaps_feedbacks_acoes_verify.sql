SELECT 'tabela_gaps' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_gaps';

SELECT 'tabela_feedbacks' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks';

SELECT 'tabela_acoes' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_acoes_melhoria';

SELECT 'uq_gap_resposta' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'pessoas_gaps' AND index_name = 'uq_pessoas_gap_resposta';

SELECT 'fk_gaps_resposta' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_gaps' AND constraint_name = 'fk_pessoas_gaps_resposta';

SELECT 'fk_feedbacks_gap' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_feedbacks' AND constraint_name = 'fk_pessoas_feedbacks_gap';

SELECT 'fk_acoes_gap' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_acoes_melhoria' AND constraint_name = 'fk_pessoas_acoes_gap';
