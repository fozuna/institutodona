CREATE TABLE IF NOT EXISTS colaborador_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  colaborador_id INT NOT NULL,
  cliente_id INT NULL,
  old_ativo TINYINT(1) NULL,
  new_ativo TINYINT(1) NOT NULL,
  justificativa TEXT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  changed_by INT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  KEY idx_colab_status_logs_colaborador (colaborador_id),
  KEY idx_colab_status_logs_cliente (cliente_id),
  KEY idx_colab_status_logs_changed_at (changed_at),
  CONSTRAINT fk_colab_status_logs_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

