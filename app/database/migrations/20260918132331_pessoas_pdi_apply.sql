-- Pilar de Pessoas, Sprint 04: PDI (Plano de Desenvolvimento Individual).
-- Camada organizadora sobre GAPs/Ações já existentes: só relacionamentos
-- estruturais, nada é copiado. Nenhuma tabela anterior é alterada.

-- Regra "no máximo 1 PDI ativo por colaborador" garantida pelo BANCO sem
-- trigger: coluna gerada VIRTUAL (valor = colaborador_id só quando status =
-- 'ativo', senão NULL) com UNIQUE - múltiplos NULL (rascunho/concluído/
-- cancelado) não colidem. VIRTUAL (não STORED) porque o MySQL proíbe FK com CASCADE na coluna-base de uma coluna STORED; índice UNIQUE em coluna virtual é suportado (MySQL 5.7+/8 e MariaDB 10.2+).
CREATE TABLE IF NOT EXISTS pessoas_pdis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NULL,
  data_inicio DATE NULL,
  data_fim_prevista DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
  observacoes_conclusao VARCHAR(2000) NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  concluido_em DATETIME NULL,
  ativo_colaborador_key INT GENERATED ALWAYS AS (IF(status = 'ativo', colaborador_id, NULL)) VIRTUAL,
  UNIQUE KEY uq_pessoas_pdi_ativo_colaborador (ativo_colaborador_key),
  INDEX idx_pessoas_pdis_empresa_colaborador (empresa_id, colaborador_id),
  INDEX idx_pessoas_pdis_empresa_status (empresa_id, status),
  CONSTRAINT fk_pessoas_pdis_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_pdis_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_pdi_objetivos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pdi_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NULL,
  criterio_sucesso VARCHAR(1000) NULL,
  prazo DATE NULL,
  ordem INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'pendente',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  concluido_em DATETIME NULL,
  INDEX idx_pessoas_pdi_objetivos_pdi_status (pdi_id, status),
  INDEX idx_pessoas_pdi_objetivos_status_prazo (status, prazo),
  CONSTRAINT fk_pessoas_pdi_objetivos_pdi FOREIGN KEY (pdi_id) REFERENCES pessoas_pdis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_pdi_gaps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pdi_id INT NOT NULL,
  gap_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_pdi_gap (pdi_id, gap_id),
  INDEX idx_pessoas_pdi_gaps_gap (gap_id),
  CONSTRAINT fk_pessoas_pdi_gaps_pdi FOREIGN KEY (pdi_id) REFERENCES pessoas_pdis(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_pdi_gaps_gap FOREIGN KEY (gap_id) REFERENCES pessoas_gaps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_pdi_objetivo_acoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  objetivo_id INT NOT NULL,
  acao_melhoria_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_pdi_objetivo_acao (objetivo_id, acao_melhoria_id),
  INDEX idx_pessoas_pdi_objetivo_acoes_acao (acao_melhoria_id),
  CONSTRAINT fk_pessoas_pdi_objetivo_acoes_objetivo FOREIGN KEY (objetivo_id) REFERENCES pessoas_pdi_objetivos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_pdi_objetivo_acoes_acao FOREIGN KEY (acao_melhoria_id) REFERENCES pessoas_acoes_melhoria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
