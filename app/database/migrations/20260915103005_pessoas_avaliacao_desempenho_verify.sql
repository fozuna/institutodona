SELECT 'tabela_modelos' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_modelos_avaliacao';

SELECT 'tabela_grupos' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_modelos_grupos';

SELECT 'tabela_perguntas' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_modelos_perguntas';

SELECT 'tabela_ciclos' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_ciclos_avaliacao';

SELECT 'tabela_participantes' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_ciclo_participantes';

SELECT 'tabela_avaliacoes' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_avaliacoes';

SELECT 'tabela_respostas' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'pessoas_avaliacao_respostas';

SELECT 'uq_ciclo_participante' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'pessoas_ciclo_participantes' AND index_name = 'uq_pessoas_ciclo_participante';

SELECT 'uq_avaliacao_ciclo_colaborador' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'pessoas_avaliacoes' AND index_name = 'uq_pessoas_avaliacao_ciclo_colaborador';

SELECT 'fk_grupos_modelo' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_modelos_grupos' AND constraint_name = 'fk_pessoas_grupos_modelo';

SELECT 'fk_perguntas_grupo' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_modelos_perguntas' AND constraint_name = 'fk_pessoas_perguntas_grupo';

SELECT 'fk_ciclos_modelo' AS item, COUNT(*) AS ok
FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND table_name = 'pessoas_ciclos_avaliacao' AND constraint_name = 'fk_pessoas_ciclos_modelo';
