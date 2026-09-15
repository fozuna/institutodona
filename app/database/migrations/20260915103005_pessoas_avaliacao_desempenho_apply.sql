-- Pilar de Pessoas, Sprint 01: fundação funcional de Avaliações de Desempenho.
--
-- Reaproveita integralmente as entidades já existentes (clientes/empresas,
-- departamentos, setores, funcoes, colaboradores, usuarios) - nenhuma tabela
-- de Empresa/Departamento/Setor/Função/Colaborador é criada ou duplicada
-- aqui. As 7 tabelas abaixo cobrem só o núcleo novo: Modelo de Avaliação
-- (com Grupos e Perguntas), Ciclo de Avaliação (com Participantes) e
-- Avaliação (com snapshot histórico de perguntas/respostas).

CREATE TABLE IF NOT EXISTS pessoas_modelos_avaliacao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  nome VARCHAR(180) NOT NULL,
  descricao VARCHAR(500) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_modelos_empresa (empresa_id),
  CONSTRAINT fk_pessoas_modelos_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_modelos_grupos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  modelo_id INT NOT NULL,
  nome VARCHAR(180) NOT NULL,
  descricao VARCHAR(500) NULL,
  ordem INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_grupos_modelo (modelo_id),
  CONSTRAINT fk_pessoas_grupos_modelo FOREIGN KEY (modelo_id) REFERENCES pessoas_modelos_avaliacao(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_modelos_perguntas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  pergunta VARCHAR(500) NOT NULL,
  orientacao VARCHAR(500) NULL,
  peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  ordem INT NOT NULL DEFAULT 0,
  obrigatoria TINYINT(1) NOT NULL DEFAULT 1,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_perguntas_grupo (grupo_id),
  CONSTRAINT fk_pessoas_perguntas_grupo FOREIGN KEY (grupo_id) REFERENCES pessoas_modelos_grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ON DELETE RESTRICT (padrão do MySQL sem cláusula ON DELETE) em modelo_id:
-- um Modelo usado por algum Ciclo nunca pode ser removido fisicamente,
-- preservando o histórico - a aplicação usa `ativo` para desativar Modelos.
CREATE TABLE IF NOT EXISTS pessoas_ciclos_avaliacao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  modelo_id INT NOT NULL,
  nome VARCHAR(180) NOT NULL,
  descricao VARCHAR(500) NULL,
  data_inicio DATE NULL,
  data_fim DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_ciclos_empresa (empresa_id),
  INDEX idx_pessoas_ciclos_modelo (modelo_id),
  INDEX idx_pessoas_ciclos_status (status),
  CONSTRAINT fk_pessoas_ciclos_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_ciclos_modelo FOREIGN KEY (modelo_id) REFERENCES pessoas_modelos_avaliacao(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pessoas_ciclo_participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ciclo_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_ciclo_participante (ciclo_id, colaborador_id),
  INDEX idx_pessoas_participantes_colaborador (colaborador_id),
  CONSTRAINT fk_pessoas_participantes_ciclo FOREIGN KEY (ciclo_id) REFERENCES pessoas_ciclos_avaliacao(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_participantes_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- avaliador_usuario_id e resultado ficam NULL propositalmente ate a
-- avaliacao comecar/terminar. UNIQUE(ciclo_id, colaborador_id) reflete a
-- simplificacao desta Sprint (um unico avaliador por participante) sem
-- impedir evolucao futura (360/multiplos avaliadores exigiria apenas
-- relaxar esta constraint numa migration posterior, dado que nenhum outro
-- ponto do desenho depende dela).
CREATE TABLE IF NOT EXISTS pessoas_avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  ciclo_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  avaliador_usuario_id INT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pendente',
  resultado DECIMAL(4,2) NULL,
  iniciado_em DATETIME NULL,
  finalizado_em DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pessoas_avaliacao_ciclo_colaborador (ciclo_id, colaborador_id),
  INDEX idx_pessoas_avaliacoes_empresa (empresa_id),
  INDEX idx_pessoas_avaliacoes_status (status),
  CONSTRAINT fk_pessoas_avaliacoes_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_avaliacoes_ciclo FOREIGN KEY (ciclo_id) REFERENCES pessoas_ciclos_avaliacao(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_avaliacoes_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Snapshot histórico: pergunta_id aponta para a pergunta original só como
-- referência (ON DELETE RESTRICT - a aplicação já impede excluir uma
-- pergunta usada por alguma avaliação, então isso nunca deveria disparar,
-- mas fica como segunda linha de defesa). Os campos *_snapshot são a fonte
-- de verdade para exibir o histórico: editar o modelo amanhã não altera
-- nenhuma avaliação já respondida ontem.
CREATE TABLE IF NOT EXISTS pessoas_avaliacao_respostas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  avaliacao_id INT NOT NULL,
  pergunta_id INT NULL,
  grupo_nome_snapshot VARCHAR(180) NOT NULL,
  grupo_ordem_snapshot INT NOT NULL DEFAULT 0,
  pergunta_snapshot VARCHAR(500) NOT NULL,
  peso_snapshot DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  obrigatoria_snapshot TINYINT(1) NOT NULL DEFAULT 1,
  ordem_snapshot INT NOT NULL DEFAULT 0,
  resposta TINYINT NULL,
  observacao VARCHAR(1000) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pessoas_respostas_avaliacao (avaliacao_id),
  CONSTRAINT fk_pessoas_respostas_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES pessoas_avaliacoes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pessoas_respostas_pergunta FOREIGN KEY (pergunta_id) REFERENCES pessoas_modelos_perguntas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
