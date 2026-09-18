-- Pilar de Pessoas, Sprint 03: integração da Ação de Melhoria com Plano de
-- Ação (pdca_tasks) e Treinamentos (treinamentos), mais Necessidade de
-- Treinamento (conceito que não existia no módulo de Treinamentos).
-- Vínculos em tabelas EXPLÍCITAS (sem FK polimórfica): integridade
-- referencial real para cada destino. Nenhuma tabela das Sprints 01/02 nem
-- dos módulos de destino é alterada.

-- No máximo 1 Plano de Ação por Ação de Melhoria (UNIQUE em acao_melhoria_id);
-- UNIQUE em plano_task_id impede o mesmo Plano ser reivindicado por 2 Ações.
-- ON DELETE CASCADE em plano_task_id: excluir o Plano no módulo de Plano de
-- Ação continua funcionando (o vínculo some junto), sem bloquear aquele módulo.
CREATE TABLE IF NOT EXISTS pessoas_acao_planos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  acao_melhoria_id INT NOT NULL,
  plano_task_id INT NOT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_acao_plano_acao (acao_melhoria_id),
  UNIQUE KEY uq_pessoas_acao_plano_task (plano_task_id),
  INDEX idx_pessoas_acao_planos_empresa (empresa_id),
  CONSTRAINT fk_pessoas_acao_planos_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acao_planos_acao FOREIGN KEY (acao_melhoria_id) REFERENCES pessoas_acoes_melhoria(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acao_planos_task FOREIGN KEY (plano_task_id) REFERENCES pdca_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uma Ação pode indicar vários Treinamentos, mas nunca o mesmo duas vezes.
CREATE TABLE IF NOT EXISTS pessoas_acao_treinamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  acao_melhoria_id INT NOT NULL,
  treinamento_id INT NOT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_acao_treinamento (acao_melhoria_id, treinamento_id),
  INDEX idx_pessoas_acao_treinamentos_empresa (empresa_id),
  INDEX idx_pessoas_acao_treinamentos_treinamento (treinamento_id),
  CONSTRAINT fk_pessoas_acao_trein_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acao_trein_acao FOREIGN KEY (acao_melhoria_id) REFERENCES pessoas_acoes_melhoria(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_acao_trein_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demanda de capacitação originada no Pilar de Pessoas (sem treinamento
-- adequado ainda). status: pendente | atendida | cancelada. Ao ser atendida,
-- guarda o treinamento que a atendeu (histórico preservado, nunca apagada).
CREATE TABLE IF NOT EXISTS pessoas_necessidades_treinamento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  acao_melhoria_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao VARCHAR(2000) NULL,
  prioridade VARCHAR(20) NOT NULL DEFAULT 'media',
  status VARCHAR(20) NOT NULL DEFAULT 'pendente',
  treinamento_id INT NULL,
  atendida_em DATETIME NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_necessidades_empresa_colaborador (empresa_id, colaborador_id),
  INDEX idx_pessoas_necessidades_acao (acao_melhoria_id),
  INDEX idx_pessoas_necessidades_status (status),
  CONSTRAINT fk_pessoas_necessidades_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_necessidades_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_necessidades_acao FOREIGN KEY (acao_melhoria_id) REFERENCES pessoas_acoes_melhoria(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_necessidades_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
