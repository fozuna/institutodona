CREATE TABLE IF NOT EXISTS manual_portal_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  expira_em DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_manual_portal_empresa (empresa_id),
  CONSTRAINT fk_manual_portal_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
