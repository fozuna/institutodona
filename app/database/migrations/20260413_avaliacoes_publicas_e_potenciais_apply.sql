-- ============================================================================
-- Migração: avaliações internas flexíveis + links públicos permanentes
-- Data: 2026-04-13
-- Banco alvo: MySQL 8+
--
-- Objetivos:
-- 1. Permitir avaliações internas sem cliente pré-cadastrado.
-- 2. Registrar rastreabilidade de origem e associação posterior.
-- 3. Criar/ajustar a estrutura de avaliações públicas independentes.
-- 4. Tornar links públicos permanentes (sem expiração automática).
-- 5. Adicionar índices e constraints auxiliares de integridade/performance.
--
-- Observação importante:
-- Em MySQL, comandos DDL (ALTER TABLE / CREATE INDEX / DROP INDEX) fazem
-- commit implícito. Por isso este script usa:
-- - helpers idempotentes para alterações estruturais;
-- - transações explícitas para correções de dados (UPDATE / INSERT).
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_add_column_if_missing $$
CREATE PROCEDURE sp_add_column_if_missing(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table
          AND column_name = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS sp_add_index_if_missing $$
CREATE PROCEDURE sp_add_index_if_missing(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table
          AND index_name = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS sp_add_fk_if_missing $$
CREATE PROCEDURE sp_add_fk_if_missing(
    IN p_table VARCHAR(128),
    IN p_fk VARCHAR(128),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.referential_constraints
        WHERE constraint_schema = DATABASE()
          AND table_name = p_table
          AND constraint_name = p_fk
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_fk, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS sp_drop_check_if_exists $$
CREATE PROCEDURE sp_drop_check_if_exists(
    IN p_table VARCHAR(128),
    IN p_check VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.table_constraints
        WHERE constraint_schema = DATABASE()
          AND table_name = p_table
          AND constraint_name = p_check
          AND constraint_type = 'CHECK'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP CHECK `', p_check, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- ============================================================================
-- 1. Estrutura principal de avaliacoes
-- ============================================================================

CALL sp_add_column_if_missing('avaliacoes', 'origem_cadastro', 'VARCHAR(30) NOT NULL DEFAULT ''cliente_existente'' AFTER tomador_decisao');
CALL sp_add_column_if_missing('avaliacoes', 'created_by_user_id', 'INT NULL AFTER origem_cadastro');
CALL sp_add_column_if_missing('avaliacoes', 'cliente_associado_em', 'DATETIME NULL AFTER created_by_user_id');
CALL sp_add_column_if_missing('avaliacoes', 'realidade_financeiro', 'TINYINT NULL AFTER nota_processo');
CALL sp_add_column_if_missing('avaliacoes', 'realidade_mercado', 'TINYINT NULL AFTER realidade_financeiro');
CALL sp_add_column_if_missing('avaliacoes', 'realidade_pessoas', 'TINYINT NULL AFTER realidade_mercado');
CALL sp_add_column_if_missing('avaliacoes', 'realidade_processo', 'TINYINT NULL AFTER realidade_pessoas');

-- Índices para consultas por cliente, origem e rastreabilidade do criador.
CALL sp_add_index_if_missing('avaliacoes', 'idx_avaliacoes_cliente_id', 'INDEX idx_avaliacoes_cliente_id (cliente_id)');
CALL sp_add_index_if_missing('avaliacoes', 'idx_avaliacoes_created_by_user_id', 'INDEX idx_avaliacoes_created_by_user_id (created_by_user_id)');
CALL sp_add_index_if_missing('avaliacoes', 'idx_avaliacoes_origem_cliente', 'INDEX idx_avaliacoes_origem_cliente (origem_cadastro, cliente_id)');
CALL sp_add_index_if_missing('avaliacoes', 'idx_avaliacoes_cliente_associado_em', 'INDEX idx_avaliacoes_cliente_associado_em (cliente_associado_em)');

-- FK opcional para usuário criador; mantida como SET NULL para preservar histórico.
CALL sp_add_fk_if_missing('avaliacoes', 'fk_avaliacoes_created_by_user', 'FOREIGN KEY (created_by_user_id) REFERENCES usuarios(id) ON DELETE SET NULL');

-- Recria check constraint de origem para manter consistência sem impedir legado.
CALL sp_drop_check_if_exists('avaliacoes', 'chk_avaliacoes_origem_cadastro');
ALTER TABLE avaliacoes
  ADD CONSTRAINT chk_avaliacoes_origem_cadastro
  CHECK (origem_cadastro IN ('cliente_existente', 'potencial_cliente'));

-- ============================================================================
-- 2. Saneamento de dados existentes em avaliacoes
-- ============================================================================

START TRANSACTION;

-- Preenche origem de forma coerente para registros antigos.
UPDATE avaliacoes
SET origem_cadastro = CASE
    WHEN cliente_id IS NULL OR cliente_id = 0 THEN 'potencial_cliente'
    ELSE 'cliente_existente'
END
WHERE origem_cadastro IS NULL
   OR origem_cadastro NOT IN ('cliente_existente', 'potencial_cliente');

-- Se a avaliação nasceu como potencial e posteriormente foi associada, registra
-- data mínima de associação para permitir rastreabilidade em relatórios.
UPDATE avaliacoes
SET cliente_associado_em = COALESCE(cliente_associado_em, created_at)
WHERE cliente_id IS NOT NULL
  AND origem_cadastro = 'potencial_cliente'
  AND cliente_associado_em IS NULL;

-- Normaliza percentuais para o intervalo esperado.
UPDATE avaliacoes
SET realidade_financeiro = GREATEST(0, LEAST(100, COALESCE(realidade_financeiro, 0))),
    realidade_mercado = GREATEST(0, LEAST(100, COALESCE(realidade_mercado, 0))),
    realidade_pessoas = GREATEST(0, LEAST(100, COALESCE(realidade_pessoas, 0))),
    realidade_processo = GREATEST(0, LEAST(100, COALESCE(realidade_processo, 0)))
WHERE realidade_financeiro IS NOT NULL
   OR realidade_mercado IS NOT NULL
   OR realidade_pessoas IS NOT NULL
   OR realidade_processo IS NOT NULL;

COMMIT;

-- ============================================================================
-- 3. Estrutura de avaliacoes_publicas
-- ============================================================================

CREATE TABLE IF NOT EXISTS avaliacoes_publicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  avaliacao_id INT NOT NULL,
  token CHAR(36) NOT NULL,
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
  UNIQUE KEY uq_avaliacoes_publicas_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL sp_add_column_if_missing('avaliacoes_publicas', 'expiracao', 'DATETIME NULL AFTER status');
CALL sp_add_column_if_missing('avaliacoes_publicas', 'data_conclusao', 'DATETIME NULL AFTER data_criacao');

-- Ajustes de tipo para tokens UUID e links permanentes.
ALTER TABLE avaliacoes_publicas MODIFY token CHAR(36) NOT NULL;
ALTER TABLE avaliacoes_publicas MODIFY expiracao DATETIME NULL;

CALL sp_add_index_if_missing('avaliacoes_publicas', 'uq_avaliacoes_publicas_token', 'UNIQUE INDEX uq_avaliacoes_publicas_token (token)');
CALL sp_add_index_if_missing('avaliacoes_publicas', 'idx_avaliacoes_publicas_status_data', 'INDEX idx_avaliacoes_publicas_status_data (status, data_criacao)');
CALL sp_add_index_if_missing('avaliacoes_publicas', 'idx_avaliacoes_publicas_data_conclusao', 'INDEX idx_avaliacoes_publicas_data_conclusao (data_conclusao)');

CALL sp_add_fk_if_missing('avaliacoes_publicas', 'fk_avaliacoes_publicas_avaliacao', 'FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE');

-- Checks de integridade dos percentuais/respostas.
CALL sp_drop_check_if_exists('avaliacoes_publicas', 'chk_avaliacoes_publicas_status');
ALTER TABLE avaliacoes_publicas
  ADD CONSTRAINT chk_avaliacoes_publicas_status
  CHECK (status IN ('pendente', 'iniciada', 'concluida'));

-- ============================================================================
-- 4. Saneamento de dados existentes em avaliacoes_publicas
-- ============================================================================

START TRANSACTION;

-- Garante links permanentes no novo modelo.
UPDATE avaliacoes_publicas
SET expiracao = NULL
WHERE expiracao IS NOT NULL;

-- Preenche empresa a partir da avaliação mãe quando o campo estiver vazio.
UPDATE avaliacoes_publicas ap
INNER JOIN avaliacoes a ON a.id = ap.avaliacao_id
SET ap.empresa = COALESCE(NULLIF(ap.empresa, ''), NULLIF(a.empresa_nome, ''))
WHERE (ap.empresa IS NULL OR ap.empresa = '')
  AND a.empresa_nome IS NOT NULL
  AND a.empresa_nome <> '';

-- Ajusta coerência do status finalizado.
UPDATE avaliacoes_publicas
SET data_conclusao = COALESCE(data_conclusao, data_criacao)
WHERE status = 'concluida'
  AND data_conclusao IS NULL;

-- Normaliza percentuais no intervalo esperado.
UPDATE avaliacoes_publicas
SET realidade_financeiro = GREATEST(0, LEAST(100, COALESCE(realidade_financeiro, 0))),
    realidade_mercado = GREATEST(0, LEAST(100, COALESCE(realidade_mercado, 0))),
    realidade_pessoas = GREATEST(0, LEAST(100, COALESCE(realidade_pessoas, 0))),
    realidade_processo = GREATEST(0, LEAST(100, COALESCE(realidade_processo, 0)))
WHERE realidade_financeiro IS NOT NULL
   OR realidade_mercado IS NOT NULL
   OR realidade_pessoas IS NOT NULL
   OR realidade_processo IS NOT NULL;

COMMIT;

-- ============================================================================
-- 5. Limpeza de helpers temporários
-- ============================================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DROP PROCEDURE IF EXISTS sp_add_index_if_missing;
DROP PROCEDURE IF EXISTS sp_add_fk_if_missing;
DROP PROCEDURE IF EXISTS sp_drop_check_if_exists;

-- Fim da migração principal.
