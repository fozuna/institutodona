CREATE TABLE IF NOT EXISTS atas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  descricao VARCHAR(500) NULL,
  arquivo VARCHAR(255) NOT NULL,
  tipo_arquivo VARCHAR(10) NOT NULL,
  tamanho INT UNSIGNED NOT NULL DEFAULT 0,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_atas_nome (nome),
  KEY idx_atas_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
