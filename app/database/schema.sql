-- Banco: institutodona
-- Criação das tabelas essenciais com chaves e relacionamentos

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(255) NOT NULL,
  CNPJ VARCHAR(18) NOT NULL,
  contato VARCHAR(255),
  logo_path VARCHAR(255) NULL,
  dominio_publico VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pilares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metodologias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_pilar INT NOT NULL,
  item_pilar VARCHAR(255) NOT NULL,
  CONSTRAINT fk_met_pilar FOREIGN KEY (id_pilar) REFERENCES pilares(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aplicacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  id_metodologia INT NOT NULL,
  status ENUM('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado',
  consultor_id INT NULL,
  data_prevista DATE NULL,
  data_conclusao DATE NULL,
  CONSTRAINT fk_apl_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_apl_metodologia FOREIGN KEY (id_metodologia) REFERENCES metodologias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  tipo_acesso ENUM('instituto','cliente','cliente_admin','reader','consultor') NOT NULL DEFAULT 'cliente',
  id_cliente INT NULL,
  CONSTRAINT fk_usr_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cronogramas e eventos (agenda mensal por cliente)
CREATE TABLE IF NOT EXISTS cronogramas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  nome VARCHAR(255) NULL,
  ano INT NOT NULL,
  CONSTRAINT fk_crono_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cronograma_eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cronograma INT NOT NULL,
  data DATE NOT NULL,
  topico VARCHAR(120) NOT NULL,
  unidade VARCHAR(120) NULL,
  atividade VARCHAR(255) NOT NULL,
  responsavel VARCHAR(255) NULL,
  modelo ENUM('Online','Presencial') NULL,
  status ENUM('Planejado','Realizado','Não Realizado') NOT NULL DEFAULT 'Planejado',
  CONSTRAINT fk_crono_ev_crono FOREIGN KEY (id_cronograma) REFERENCES cronogramas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura organizacional
CREATE TABLE IF NOT EXISTS departamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  cliente_id INT NOT NULL,
  UNIQUE KEY dep_unique (cliente_id, nome),
  CONSTRAINT fk_dep_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS setores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  departamento_id INT NOT NULL,
  UNIQUE KEY setor_unique (departamento_id, nome),
  CONSTRAINT fk_setor_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS funcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  setor_id INT NOT NULL,
  UNIQUE KEY func_unique (setor_id, nome),
  CONSTRAINT fk_func_setor FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS colaboradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  email VARCHAR(180) NULL,
  funcao_id INT NOT NULL,
  lider ENUM('não','sim') NOT NULL DEFAULT 'não',
  cliente_id INT NULL,
  CONSTRAINT fk_colab_func FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_colab_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de consultores (perfil e vínculo opcional com usuário)
CREATE TABLE IF NOT EXISTS consultores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  telefone VARCHAR(30) NULL,
  especialidade VARCHAR(255) NULL,
  senioridade ENUM('Junior','Pleno','Senior') NULL,
  cidade VARCHAR(120) NULL,
  estado CHAR(2) NULL,
  observacoes TEXT NULL,
  usuario_id INT NULL,
  CONSTRAINT fk_cons_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Avaliações
CREATE TABLE IF NOT EXISTS avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NULL,
  empresa_nome VARCHAR(255) NULL,
  nome VARCHAR(150) NOT NULL DEFAULT '',
  whatsapp VARCHAR(20) NOT NULL DEFAULT '',
  email VARCHAR(180) NOT NULL DEFAULT '',
  numero_funcionarios INT UNSIGNED NOT NULL DEFAULT 0,
  numero_lideres INT UNSIGNED NOT NULL DEFAULT 0,
  faturamento_medio_anual BIGINT UNSIGNED NOT NULL DEFAULT 0,
  tomador_decisao TINYINT(1) NOT NULL DEFAULT 0,
  origem_cadastro VARCHAR(30) NOT NULL DEFAULT 'cliente_existente',
  created_by_user_id INT NULL,
  cliente_associado_em DATETIME NULL,
  contato VARCHAR(255) NULL,
  respostas_json TEXT NULL,
  nota_financeiro TINYINT NOT NULL DEFAULT 0,
  nota_mercado TINYINT NOT NULL DEFAULT 0,
  nota_pessoas TINYINT NOT NULL DEFAULT 0,
  nota_processo TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_av_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avaliacoes_publicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  avaliacao_id INT NULL,
  token CHAR(36) NOT NULL UNIQUE,
  slug VARCHAR(120) NULL UNIQUE,
  created_by_user_id INT NULL,
  nome VARCHAR(150) NULL,
  empresa VARCHAR(255) NULL,
  whatsapp VARCHAR(20) NULL,
  email VARCHAR(180) NULL,
  numero_funcionarios INT UNSIGNED NULL,
  numero_lideres INT UNSIGNED NULL,
  faturamento_anual BIGINT UNSIGNED NULL,
  tomador_decisao TINYINT(1) NULL,
  respostas_json TEXT NULL,
  nota_financeiro TINYINT NOT NULL DEFAULT 0,
  nota_mercado TINYINT NOT NULL DEFAULT 0,
  nota_pessoas TINYINT NOT NULL DEFAULT 0,
  nota_processo TINYINT NOT NULL DEFAULT 0,
  realidade_financeiro TINYINT NULL,
  realidade_mercado TINYINT NULL,
  realidade_pessoas TINYINT NULL,
  realidade_processo TINYINT NULL,
  status ENUM('pendente','iniciada','concluida') NOT NULL DEFAULT 'pendente',
  expiracao DATETIME NULL,
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_conclusao DATETIME NULL,
  UNIQUE KEY uq_avaliacao_publica_avaliacao (avaliacao_id),
  CONSTRAINT fk_avaliacoes_publicas_avaliacao FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Alterações para bases já existentes
ALTER TABLE aplicacoes
  ADD COLUMN IF NOT EXISTS consultor_id INT NULL,
  ADD COLUMN IF NOT EXISTS data_prevista DATE NULL,
  ADD COLUMN IF NOT EXISTS data_conclusao DATE NULL;

ALTER TABLE usuarios MODIFY COLUMN tipo_acesso ENUM('instituto','cliente','cliente_admin','reader','consultor') NOT NULL DEFAULT 'cliente';

ALTER TABLE consultores
  ADD COLUMN IF NOT EXISTS telefone VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS especialidade VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS senioridade ENUM('Junior','Pleno','Senior') NULL,
  ADD COLUMN IF NOT EXISTS cidade VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS estado CHAR(2) NULL,
  ADD COLUMN IF NOT EXISTS observacoes TEXT NULL;

-- Campos adicionais
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS is_matriz TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS matriz_id INT NULL;

ALTER TABLE metodologias
  ADD COLUMN IF NOT EXISTS tipo VARCHAR(20) NOT NULL DEFAULT 'tarefa',
  ADD COLUMN IF NOT EXISTS arquivo_path VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS observacoes TEXT NULL,
  ADD COLUMN IF NOT EXISTS cliente_id INT NULL;

ALTER TABLE aplicacoes
  ADD COLUMN IF NOT EXISTS funcao_id INT NULL;
CREATE TABLE IF NOT EXISTS aplicacao_funcoes (
  aplicacao_id INT NOT NULL,
  funcao_id INT NOT NULL,
  PRIMARY KEY (aplicacao_id, funcao_id),
  CONSTRAINT fk_apf_apl FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE CASCADE,
  CONSTRAINT fk_apf_func FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aplicacao_colaboradores (
  aplicacao_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  PRIMARY KEY (aplicacao_id, colaborador_id),
  CONSTRAINT fk_apc_apl FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE CASCADE,
  CONSTRAINT fk_apc_col FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE colaboradores
  ADD COLUMN IF NOT EXISTS lider ENUM('não','sim') NOT NULL DEFAULT 'não' AFTER funcao_id;

-- Pilares básicos
INSERT INTO pilares (nome) VALUES
  ('Processos'), ('Gestão'), ('Pessoas'), ('Trilha Capacitação')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL;

-- PDCA core
CREATE TABLE IF NOT EXISTS pdca_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT NULL,
  meta_valor DECIMAL(12,2) NULL,
  meta_unidade VARCHAR(32) NULL,
  prazo DATE NULL,
  responsavel VARCHAR(120) NULL,
  fase ENUM('PLAN','DO','CHECK','ACT') NOT NULL DEFAULT 'PLAN',
  status ENUM('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado',
  progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pdca_task_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pdca_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  nome VARCHAR(120) NOT NULL,
  planejado DECIMAL(12,2) NULL,
  realizado DECIMAL(12,2) NULL,
  unidade VARCHAR(32) NULL,
  CONSTRAINT fk_pdca_metric_task FOREIGN KEY (task_id) REFERENCES pdca_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pdca_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  gap DECIMAL(12,2) NULL,
  analise TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pdca_check_task FOREIGN KEY (task_id) REFERENCES pdca_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pdca_actions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  owner VARCHAR(120) NULL,
  due_date DATE NULL,
  status ENUM('Planejado','Em Execução','Concluído') NOT NULL DEFAULT 'Planejado',
  CONSTRAINT fk_pdca_action_task FOREIGN KEY (task_id) REFERENCES pdca_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  setor_id INT NOT NULL,
  responsavel_id INT NULL,
  data_auditoria DATE NOT NULL,
  nome_auditoria VARCHAR(180) NOT NULL DEFAULT '',
  pergunta VARCHAR(500) NOT NULL,
  objetivo TEXT NOT NULL,
  referencia_esperada VARCHAR(255) NOT NULL,
  status ENUM('Agendada','Em Auditoria','Realizada') NOT NULL DEFAULT 'Agendada',
  avaliacao TEXT NULL,
  obs TEXT NULL,
  realizada_at DATETIME NULL,
  created_by INT NULL,
  updated_by INT NULL,
  lock_version INT NOT NULL DEFAULT 1,
  deleted_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_auditorias_nome (nome_auditoria),
  CONSTRAINT fk_auditorias_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_auditorias_setor FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE RESTRICT,
  CONSTRAINT fk_auditorias_responsavel FOREIGN KEY (responsavel_id) REFERENCES colaboradores(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_relatorios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  relatorio_ref VARCHAR(120) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_auditoria_relatorios_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
