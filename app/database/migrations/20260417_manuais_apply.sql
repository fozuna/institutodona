CREATE TABLE IF NOT EXISTS manuais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  departamento_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  descricao VARCHAR(500) NULL,
  arquivo VARCHAR(255) NOT NULL,
  tipo_arquivo VARCHAR(10) NOT NULL,
  tamanho INT UNSIGNED NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_manuais_empresa (empresa_id),
  INDEX idx_manuais_departamento (departamento_id),
  INDEX idx_manuais_nome (nome),
  CONSTRAINT fk_manuais_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_manuais_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
