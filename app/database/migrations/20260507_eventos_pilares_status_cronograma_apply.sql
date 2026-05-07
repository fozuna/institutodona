START TRANSACTION;

CREATE TABLE IF NOT EXISTS tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    prioridade ENUM('baixa','media','alta') NOT NULL DEFAULT 'media',
    status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
    finalizado_em DATETIME NULL,
    finalizado_por_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tarefas_cliente_data (cliente_id, data_inicio),
    INDEX idx_tarefas_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reunioes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    local VARCHAR(180) NULL,
    pauta TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
    realizada_em DATETIME NULL,
    realizada_por_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reunioes_cliente_data (cliente_id, data_inicio),
    INDEX idx_reunioes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coachings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    coach VARCHAR(180) NULL,
    observacoes TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
    finalizado_em DATETIME NULL,
    finalizado_por_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coaching_cliente_data (cliente_id, data_inicio),
    INDEX idx_coaching_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS processos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nome VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    responsavel VARCHAR(180) NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Planejado',
    finalizado_em DATETIME NULL,
    finalizado_por_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_processos_cliente_data (cliente_id, data_inicio),
    INDEX idx_processos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    "ALTER TABLE cronograma_eventos MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Planejado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Finalizado' WHERE status = 'Realizado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Pendente' WHERE status = 'Não Realizado'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@cronograma_eventos_exists > 0 AND @cronograma_status_col_exists > 0,
    "UPDATE cronograma_eventos SET status = 'Planejado' WHERE status NOT IN ('Planejado','Pendente','Andamento','Adiado','Finalizado')",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

