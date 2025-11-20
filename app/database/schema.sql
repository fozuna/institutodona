-- Banco: institutodona
-- Criação das tabelas essenciais com chaves e relacionamentos

CREATE DATABASE IF NOT EXISTS `institutodona`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `institutodona`;

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(255) NOT NULL,
  CNPJ VARCHAR(18) NOT NULL,
  contato VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pilares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS metodologias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_pilar INT NOT NULL,
  item_pilar VARCHAR(255) NOT NULL,
  CONSTRAINT fk_met_pilar FOREIGN KEY (id_pilar) REFERENCES pilares(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aplicacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  id_metodologia INT NOT NULL,
  status ENUM('A Fazer','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'A Fazer',
  CONSTRAINT fk_apl_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_apl_metodologia FOREIGN KEY (id_metodologia) REFERENCES metodologias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  tipo_acesso ENUM('instituto','cliente') NOT NULL DEFAULT 'cliente',
  id_cliente INT NULL,
  CONSTRAINT fk_usr_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pilares básicos
INSERT INTO pilares (nome) VALUES
  ('Processos'), ('Gestão'), ('Pessoas'), ('Trilha Capacitação')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

CREATE TABLE IF NOT EXISTS avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NULL,
  financeiro_nota INT NOT NULL,
  mercado_nota INT NOT NULL,
  pessoas_nota INT NOT NULL,
  processo_nota INT NOT NULL,
  financeiro_realidade INT NOT NULL,
  mercado_realidade INT NOT NULL,
  pessoas_realidade INT NOT NULL,
  processo_realidade INT NOT NULL,
  total INT NOT NULL,
  realidade_media INT NOT NULL,
  respostas_json LONGTEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_avaliacao_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;