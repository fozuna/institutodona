-- Banco: institutodona
-- Criação das tabelas essenciais com chaves e relacionamentos

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(255) NOT NULL,
  CNPJ VARCHAR(18) NOT NULL,
  contato VARCHAR(255),
  logo_path VARCHAR(255) NULL
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
  status ENUM('A Fazer','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'A Fazer',
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
  tipo_acesso ENUM('instituto','cliente','consultor') NOT NULL DEFAULT 'cliente',
  id_cliente INT NULL,
  CONSTRAINT fk_usr_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
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

-- Alterações para bases já existentes
ALTER TABLE aplicacoes
  ADD COLUMN IF NOT EXISTS consultor_id INT NULL,
  ADD COLUMN IF NOT EXISTS data_prevista DATE NULL,
  ADD COLUMN IF NOT EXISTS data_conclusao DATE NULL;

ALTER TABLE usuarios MODIFY COLUMN tipo_acesso ENUM('instituto','cliente','consultor') NOT NULL DEFAULT 'cliente';

ALTER TABLE consultores
  ADD COLUMN IF NOT EXISTS telefone VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS especialidade VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS senioridade ENUM('Junior','Pleno','Senior') NULL,
  ADD COLUMN IF NOT EXISTS cidade VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS estado CHAR(2) NULL,
  ADD COLUMN IF NOT EXISTS observacoes TEXT NULL;

-- Pilares básicos
INSERT INTO pilares (nome) VALUES
  ('Processos'), ('Gestão'), ('Pessoas'), ('Trilha Capacitação')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL;
