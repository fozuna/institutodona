CREATE TABLE IF NOT EXISTS auditoria_responsaveis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_auditoria_responsavel (auditoria_id, colaborador_id),
  INDEX idx_auditoria_responsaveis_auditoria (auditoria_id),
  INDEX idx_auditoria_responsaveis_colaborador (colaborador_id),
  CONSTRAINT fk_auditoria_responsaveis_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
  CONSTRAINT fk_auditoria_responsaveis_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_questao_responsaveis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  questao_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_auditoria_questao_responsavel (questao_id, colaborador_id),
  INDEX idx_auditoria_questao_responsaveis_questao (questao_id),
  INDEX idx_auditoria_questao_responsaveis_colaborador (colaborador_id),
  CONSTRAINT fk_auditoria_questao_responsaveis_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE,
  CONSTRAINT fk_auditoria_questao_responsaveis_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
