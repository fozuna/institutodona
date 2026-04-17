CREATE TABLE IF NOT EXISTS auditoria_historico (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  dados_anteriores JSON NOT NULL,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_aud_hist_auditoria (auditoria_id),
  CONSTRAINT fk_aud_hist_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
