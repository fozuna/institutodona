SELECT
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tarefas') AS tarefas_table,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'reunioes') AS reunioes_table,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'coachings') AS coachings_table,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'processos') AS processos_table;

SELECT
    column_name,
    data_type,
    column_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'cronograma_eventos'
  AND column_name = 'status';

SELECT COUNT(*) AS cronograma_status_invalidos
FROM cronograma_eventos
WHERE status NOT IN ('Planejado','Pendente','Andamento','Adiado','Finalizado');

