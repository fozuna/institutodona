CREATE TABLE IF NOT EXISTS cronograma_evento_anexos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  evento_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size INT UNSIGNED NOT NULL,
  sha256 CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_cronograma_evento_anexos_evento (evento_id),
  INDEX idx_cronograma_evento_anexos_evento_deleted (evento_id, deleted_at),
  CONSTRAINT fk_cronograma_evento_anexos_evento
    FOREIGN KEY (evento_id) REFERENCES cronograma_eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
