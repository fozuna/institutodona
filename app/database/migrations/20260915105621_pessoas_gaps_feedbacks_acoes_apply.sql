-- Pilar de Pessoas, Sprint 02: Resultado -> GAP -> Feedback -> Ação de
-- Melhoria. Reaproveita clientes/colaboradores/pessoas_avaliacoes/
-- pessoas_avaliacao_respostas já existentes (Sprint 01) - nenhuma
-- alteração no schema da Sprint 01, só 3 tabelas novas.

CREATE TABLE IF NOT EXISTS pessoas_gaps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  avaliacao_id INT NULL,
  resposta_id INT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NULL,
  origem VARCHAR(20) NOT NULL DEFAULT 'manual',
  prioridade VARCHAR(20) NOT NULL DEFAULT 'media',
  status VARCHAR(20) NOT NULL DEFAULT 'aberto',
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolvido_em DATETIME NULL,
  -- Um único GAP por resposta (evita registro duplicado por acidente); NULL
  -- (GAP manual, sem resposta associada) fica de fora da unicidade - o
  -- MySQL não considera múltiplos NULL como duplicados numa UNIQUE KEY.
  UNIQUE KEY uq_pessoas_gap_resposta (resposta_id),
  INDEX idx_pessoas_gaps_empresa_colaborador (empresa_id, colaborador_id),
  INDEX idx_pessoas_gaps_status (status),
  INDEX idx_pessoas_gaps_avaliacao (avaliacao_id),
  CONSTRAINT fk_pessoas_gaps_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_gaps_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_gaps_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES pessoas_avaliacoes(id) ON DELETE SET NULL,
  CONSTRAINT fk_pessoas_gaps_resposta FOREIGN KEY (resposta_id) REFERENCES pessoas_avaliacao_respostas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_feedbacks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  avaliacao_id INT NULL,
  gap_id INT NULL,
  tipo VARCHAR(20) NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NOT NULL,
  data_feedback DATE NOT NULL,
  registrado_por INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_feedbacks_empresa_colaborador (empresa_id, colaborador_id),
  INDEX idx_pessoas_feedbacks_tipo (tipo),
  INDEX idx_pessoas_feedbacks_avaliacao (avaliacao_id),
  INDEX idx_pessoas_feedbacks_gap (gap_id),
  CONSTRAINT fk_pessoas_feedbacks_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_feedbacks_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_feedbacks_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES pessoas_avaliacoes(id) ON DELETE SET NULL,
  CONSTRAINT fk_pessoas_feedbacks_gap FOREIGN KEY (gap_id) REFERENCES pessoas_gaps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_acoes_melhoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  gap_id INT NULL,
  avaliacao_id INT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NULL,
  responsavel_usuario_id INT NULL,
  data_inicio DATE NULL,
  prazo DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pendente',
  conclusao VARCHAR(2000) NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  concluido_em DATETIME NULL,
  INDEX idx_pessoas_acoes_empresa_colaborador (empresa_id, colaborador_id),
  INDEX idx_pessoas_acoes_gap (gap_id),
  INDEX idx_pessoas_acoes_status (status),
  CONSTRAINT fk_pessoas_acoes_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acoes_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acoes_gap FOREIGN KEY (gap_id) REFERENCES pessoas_gaps(id) ON DELETE SET NULL,
  CONSTRAINT fk_pessoas_acoes_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES pessoas_avaliacoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
