-- Repara ambientes onde 20260728120000_clientes_status_ativo_apply não
-- chegou a rodar (ex.: deploy defasado): sem esta tabela,
-- ClienteModel::updateWithStatusAudit() falha de forma permanente e
-- universal para qualquer cliente, mesmo com clientes.ativo já existindo
-- (coluna autorreparada por ClienteModel::ensureColumns()). Idempotente -
-- seguro reaplicar mesmo onde a tabela já existe.
CREATE TABLE IF NOT EXISTS cliente_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  old_ativo TINYINT(1) NULL,
  new_ativo TINYINT(1) NOT NULL,
  justificativa TEXT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  changed_by INT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  KEY idx_cliente_status_logs_cliente (cliente_id),
  KEY idx_cliente_status_logs_changed_at (changed_at),
  CONSTRAINT fk_cliente_status_logs_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
