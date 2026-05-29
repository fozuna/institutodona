CREATE TABLE IF NOT EXISTS manual_filial_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  manual_id INT NOT NULL,
  filial_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_manual_filial (manual_id, filial_id),
  INDEX idx_mfl_manual (manual_id),
  INDEX idx_mfl_filial (filial_id),
  CONSTRAINT fk_mfl_manual FOREIGN KEY (manual_id) REFERENCES manuais(id) ON DELETE CASCADE,
  CONSTRAINT fk_mfl_filial FOREIGN KEY (filial_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE manual_portal_tokens
  ADD COLUMN scope_ids_json TEXT NULL,
  ADD COLUMN filters_json TEXT NULL;
