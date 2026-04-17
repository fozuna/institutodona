SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(255) NOT NULL,
  CNPJ VARCHAR(18) NOT NULL,
  contato VARCHAR(255) NULL,
  logo_path VARCHAR(255) NULL,
  dominio_publico VARCHAR(255) NULL,
  is_matriz TINYINT(1) NOT NULL DEFAULT 1,
  matriz_id INT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  acesso_restrito TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_clientes_matriz_id (matriz_id),
  CONSTRAINT fk_clientes_matriz FOREIGN KEY (matriz_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pilares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metodologias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_pilar INT NOT NULL,
  item_pilar VARCHAR(255) NOT NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'tarefa',
  arquivo_path VARCHAR(255) NULL,
  observacoes TEXT NULL,
  cliente_id INT NULL,
  CONSTRAINT fk_met_pilar FOREIGN KEY (id_pilar) REFERENCES pilares(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  tipo_acesso ENUM('instituto','cliente','cliente_admin','reader','consultor') NOT NULL DEFAULT 'cliente',
  id_cliente INT NULL,
  INDEX idx_usuarios_id_cliente (id_cliente),
  CONSTRAINT fk_usr_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  cliente_id INT NOT NULL,
  UNIQUE KEY dep_unique (cliente_id, nome),
  CONSTRAINT fk_dep_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS manual_portal_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  expira_em DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_manual_portal_empresa (empresa_id),
  CONSTRAINT fk_manual_portal_empresa FOREIGN KEY (empresa_id) REFERENCES clientes(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS aplicacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  id_metodologia INT NOT NULL,
  status ENUM('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado',
  consultor_id INT NULL,
  data_prevista DATE NULL,
  data_conclusao DATE NULL,
  funcao_id INT NULL,
  INDEX idx_aplicacoes_id_cliente (id_cliente),
  CONSTRAINT fk_apl_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_apl_metodologia FOREIGN KEY (id_metodologia) REFERENCES metodologias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  faturamento_faixa_id INT NULL,
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
  INDEX idx_avaliacoes_cliente_id (cliente_id),
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
  faturamento_faixa_id INT NULL,
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

CREATE TABLE IF NOT EXISTS faturamento_faixas (
  id INT PRIMARY KEY,
  descricao VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO faturamento_faixas (id, descricao) VALUES
  (1, 'Até R$ 100.000,00'),
  (2, 'De R$ 100.001,00 a R$ 250.000,00'),
  (3, 'De R$ 250.001,00 a R$ 500.000,00'),
  (4, 'De R$ 500.001,00 a R$ 750.000,00'),
  (5, 'De R$ 750.001,00 a R$ 1.000.000,00'),
  (6, 'Acima de R$ 1.000.000,00')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

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
  INDEX idx_pdca_tasks_id_cliente (id_cliente),
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
  INDEX idx_auditorias_cliente (cliente_id),
  INDEX idx_auditorias_setor (setor_id),
  INDEX idx_auditorias_nome (nome_auditoria),
  INDEX idx_auditorias_responsavel (responsavel_id),
  INDEX idx_auditorias_status_data (status, data_auditoria),
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
  INDEX idx_auditoria_relatorios_auditoria (auditoria_id),
  CONSTRAINT fk_auditoria_relatorios_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_questoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  responsavel_nome VARCHAR(180) NOT NULL,
  pergunta TEXT NOT NULL,
  referencia_esperada TEXT NOT NULL,
  processos_json TEXT NULL,
  ordem INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_auditoria_questoes_auditoria (auditoria_id),
  CONSTRAINT fk_auditoria_questoes_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  questao_id INT NOT NULL,
  conformidade ENUM('pendente','conforme','nao_conforme') NOT NULL DEFAULT 'pendente',
  observacoes TEXT NULL,
  auto_saved_at DATETIME NULL,
  finalized_at DATETIME NULL,
  updated_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_auditoria_questao (auditoria_id, questao_id),
  INDEX idx_auditoria_avaliacoes_auditoria (auditoria_id),
  CONSTRAINT fk_auditoria_avaliacoes_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
  CONSTRAINT fk_auditoria_avaliacoes_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria_historico (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  dados_anteriores JSON NOT NULL,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_aud_hist_auditoria (auditoria_id),
  CONSTRAINT fk_aud_hist_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS usuario_empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  cliente_id INT NOT NULL,
  origem ENUM('direto','herdado') NOT NULL DEFAULT 'direto',
  permitido TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_cliente (usuario_id, cliente_id),
  INDEX idx_usuario_empresas_usuario (usuario_id),
  INDEX idx_usuario_empresas_cliente (cliente_id),
  CONSTRAINT fk_usuario_empresas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_empresas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS indicadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  nome VARCHAR(180) NOT NULL,
  unidade VARCHAR(32) NULL,
  referencia DATE NULL,
  meta DECIMAL(14,2) NOT NULL DEFAULT 0,
  realizado DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_indicadores_cliente_id (cliente_id),
  CONSTRAINT fk_ind_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS planoacao_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_type ENUM('task','action') NOT NULL,
  item_id INT NOT NULL,
  user_id INT NULL,
  action_type ENUM('create','update','delete') NOT NULL,
  changes_json TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aplicacao_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aplicacao_id INT NOT NULL,
  user_email VARCHAR(255) NULL,
  user_nome VARCHAR(255) NULL,
  summary TEXT NOT NULL,
  payload_json TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aplicacao_arquivos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aplicacao_id INT NOT NULL,
  cliente_id INT NOT NULL,
  nome_original VARCHAR(255) NOT NULL,
  arquivo_path VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  tamanho INT NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente)
VALUES ('Fabio Ozuna', 'admin@agencialester.com.br', '$2y$10$QXSPXutrnoeknf4oS/Aj7e8dUhCLfgYvo0BCeGMlAVRrUJiNTr5ka', 'instituto', NULL)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  senha_hash = VALUES(senha_hash),
  tipo_acesso = VALUES(tipo_acesso),
  id_cliente = VALUES(id_cliente);

SET FOREIGN_KEY_CHECKS = 1;
