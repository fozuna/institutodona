START TRANSACTION;

DROP TABLE IF EXISTS tarefas;
DROP TABLE IF EXISTS reunioes;
DROP TABLE IF EXISTS coachings;
DROP TABLE IF EXISTS processos;

SET @cronograma_eventos_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'cronograma_eventos'
);
SET @cronograma_status_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'cronograma_eventos'
      AND column_name = 'status'
);

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Realizado' WHERE status = 'Finalizado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Não Realizado' WHERE status = 'Adiado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Planejado' WHERE status IN ('Pendente','Andamento')",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "ALTER TABLE cronograma_eventos MODIFY COLUMN status ENUM('Planejado','Realizado','Não Realizado') NOT NULL DEFAULT 'Planejado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

