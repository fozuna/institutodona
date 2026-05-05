SET @schema_name = DATABASE();

CREATE TABLE IF NOT EXISTS indicador_eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  indicador_id INT NOT NULL,
  cliente_id INT NOT NULL,
  data_evento DATE NOT NULL,
  periodo_inicio DATE NOT NULL,
  periodo_fim DATE NOT NULL,
  valor_meta DECIMAL(15,4) NOT NULL DEFAULT 0,
  valor_atingido DECIMAL(15,4) NULL DEFAULT NULL,
  percentual_cumprimento DECIMAL(8,2) NULL DEFAULT NULL,
  status_meta VARCHAR(20) NOT NULL DEFAULT 'pendente',
  observacao TEXT NULL,
  lancado_em DATETIME NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_by INT NULL,
  updated_by INT NULL,
  deleted_by INT NULL,
  UNIQUE KEY uq_indicador_eventos_data (indicador_id, data_evento),
  KEY idx_indicador_eventos_cliente_data (cliente_id, data_evento),
  KEY idx_indicador_eventos_status (status_meta),
  KEY idx_indicador_eventos_deleted_at (deleted_at),
  CONSTRAINT fk_indicador_eventos_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id),
  CONSTRAINT fk_indicador_eventos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE indicador_eventos
SET status_meta = CASE
  WHEN valor_atingido IS NULL THEN 'pendente'
  WHEN valor_meta <= 0 AND valor_atingido > 0 THEN 'atingida'
  WHEN valor_meta <= 0 THEN 'pendente'
  WHEN ((valor_atingido / valor_meta) * 100) >= 100 THEN 'atingida'
  WHEN ((valor_atingido / valor_meta) * 100) >= 80 THEN 'parcial'
  ELSE 'nao_atingida'
END,
percentual_cumprimento = CASE
  WHEN valor_atingido IS NULL THEN NULL
  WHEN valor_meta <= 0 THEN NULL
  ELSE ROUND((valor_atingido / valor_meta) * 100, 2)
END
WHERE deleted_at IS NULL;
