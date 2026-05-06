-- 20260507_indicadores_treinamentos_consolidado_apply.sql
-- Migration consolidada para Indicadores + Treinamentos (idempotente)
-- Objetivo: permitir execucao segura em dev/staging/producao com validacoes de existencia.

SET @schema_name = DATABASE();

-- ---------------------------------------------------------------------
-- Helpers temporarios para evitar repeticao e garantir idempotencia.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DROP PROCEDURE IF EXISTS sp_add_index_if_missing;
DROP PROCEDURE IF EXISTS sp_add_fk_if_missing;

DELIMITER //
CREATE PROCEDURE sp_add_column_if_missing(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128),
    IN p_ddl TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = p_ddl;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE sp_add_index_if_missing(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128),
    IN p_ddl TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = p_ddl;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE sp_add_fk_if_missing(
    IN p_table VARCHAR(128),
    IN p_fk_name VARCHAR(128),
    IN p_ref_table VARCHAR(128),
    IN p_ddl TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
    ) AND EXISTS (
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_ref_table
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_fk_name
    ) THEN
        SET @sql = p_ddl;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------
-- INDICADORES: tabelas de apoio e estrutura principal
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS unidades_medida (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  simbolo VARCHAR(32) NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  fator_conversao_base DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_unidades_medida_nome_tipo (nome, tipo),
  KEY idx_unidades_medida_ativo (ativo),
  KEY idx_unidades_medida_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Real', 'R$', 'monetaria', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = 'R$' AND tipo = 'monetaria'
);

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Percentual', '%', 'percentual', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = '%' AND tipo = 'percentual'
);

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Unidade inteira', 'un', 'inteiro', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = 'un' AND tipo = 'inteiro'
);

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Litro', 'L', 'volume', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = 'L' AND tipo = 'volume'
);

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Quilograma', 'kg', 'peso', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = 'kg' AND tipo = 'peso'
);

INSERT INTO unidades_medida (nome, simbolo, tipo, fator_conversao_base, ativo)
SELECT 'Hora', 'h', 'tempo', 1, 1
WHERE NOT EXISTS (
  SELECT 1 FROM unidades_medida WHERE simbolo = 'h' AND tipo = 'tempo'
);

CREATE TABLE IF NOT EXISTS indicadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  nome VARCHAR(180) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL sp_add_column_if_missing('departamentos', 'ativo', 'ALTER TABLE departamentos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
CALL sp_add_column_if_missing('setores', 'ativo', 'ALTER TABLE setores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
CALL sp_add_column_if_missing('colaboradores', 'ativo', 'ALTER TABLE colaboradores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');

CALL sp_add_column_if_missing('indicadores', 'indicador', 'ALTER TABLE indicadores ADD COLUMN indicador VARCHAR(255) NULL AFTER cliente_id');
CALL sp_add_column_if_missing('indicadores', 'departamento_id', 'ALTER TABLE indicadores ADD COLUMN departamento_id INT NULL AFTER indicador');
CALL sp_add_column_if_missing('indicadores', 'setor_id', 'ALTER TABLE indicadores ADD COLUMN setor_id INT NULL AFTER departamento_id');
CALL sp_add_column_if_missing('indicadores', 'periodicidade_tipo', 'ALTER TABLE indicadores ADD COLUMN periodicidade_tipo VARCHAR(20) NOT NULL DEFAULT ''mensal'' AFTER setor_id');
CALL sp_add_column_if_missing('indicadores', 'data_inicial', 'ALTER TABLE indicadores ADD COLUMN data_inicial DATE NULL AFTER periodicidade_tipo');
CALL sp_add_column_if_missing('indicadores', 'data_final', 'ALTER TABLE indicadores ADD COLUMN data_final DATE NULL AFTER data_inicial');
CALL sp_add_column_if_missing('indicadores', 'valor', 'ALTER TABLE indicadores ADD COLUMN valor DECIMAL(15,4) NULL AFTER data_final');
CALL sp_add_column_if_missing('indicadores', 'unidade_medida_id', 'ALTER TABLE indicadores ADD COLUMN unidade_medida_id INT NULL AFTER valor');
CALL sp_add_column_if_missing('indicadores', 'valor_minimo', 'ALTER TABLE indicadores ADD COLUMN valor_minimo DECIMAL(15,4) NULL AFTER unidade_medida_id');
CALL sp_add_column_if_missing('indicadores', 'valor_maximo', 'ALTER TABLE indicadores ADD COLUMN valor_maximo DECIMAL(15,4) NULL AFTER valor_minimo');
CALL sp_add_column_if_missing('indicadores', 'updated_at', 'ALTER TABLE indicadores ADD COLUMN updated_at DATETIME NULL DEFAULT NULL AFTER created_at');
CALL sp_add_column_if_missing('indicadores', 'deleted_at', 'ALTER TABLE indicadores ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at');
CALL sp_add_column_if_missing('indicadores', 'created_by', 'ALTER TABLE indicadores ADD COLUMN created_by INT NULL AFTER deleted_at');
CALL sp_add_column_if_missing('indicadores', 'updated_by', 'ALTER TABLE indicadores ADD COLUMN updated_by INT NULL AFTER created_by');
CALL sp_add_column_if_missing('indicadores', 'deleted_by', 'ALTER TABLE indicadores ADD COLUMN deleted_by INT NULL AFTER updated_by');

UPDATE indicadores
SET indicador = COALESCE(NULLIF(indicador, ''), NULLIF(nome, ''))
WHERE (indicador IS NULL OR indicador = '')
  AND nome IS NOT NULL
  AND nome <> '';

UPDATE indicadores
SET periodicidade_tipo = 'mensal'
WHERE periodicidade_tipo IS NULL OR periodicidade_tipo = '';

UPDATE indicadores
SET data_inicial = COALESCE(data_inicial, referencia, CURDATE())
WHERE data_inicial IS NULL;

UPDATE indicadores
SET data_final = COALESCE(data_final, data_inicial, referencia, CURDATE())
WHERE data_final IS NULL;

UPDATE indicadores
SET valor = COALESCE(valor, realizado, meta, 0)
WHERE valor IS NULL;

UPDATE indicadores
SET unidade_medida_id = (
  SELECT id FROM unidades_medida
  WHERE simbolo = 'R$' AND tipo = 'monetaria'
  ORDER BY id
  LIMIT 1
)
WHERE unidade_medida_id IS NULL
  AND EXISTS (SELECT 1 FROM unidades_medida);

CREATE TABLE IF NOT EXISTS indicador_responsavel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  indicador_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_indicador_responsavel (indicador_id, colaborador_id),
  KEY idx_indicador_responsavel_indicador (indicador_id),
  KEY idx_indicador_responsavel_colaborador (colaborador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS indicador_eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  indicador_id INT NOT NULL,
  cliente_id INT NOT NULL,
  data_evento DATE NOT NULL,
  periodo_inicio DATE NOT NULL,
  periodo_fim DATE NOT NULL,
  valor_meta DECIMAL(15,4) NOT NULL DEFAULT 0,
  valor_atingido DECIMAL(15,4) NULL DEFAULT NULL,
  percentual_cumprimento DECIMAL(8,2) NULL DEFAULT NULL,
  status_meta VARCHAR(20) NOT NULL DEFAULT 'pendente',
  observacao TEXT NULL,
  lancado_em DATETIME NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_by INT NULL,
  updated_by INT NULL,
  deleted_by INT NULL,
  UNIQUE KEY uq_indicador_eventos_data (indicador_id, data_evento),
  KEY idx_indicador_eventos_cliente_data (cliente_id, data_evento),
  KEY idx_indicador_eventos_status (status_meta),
  KEY idx_indicador_eventos_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE indicador_eventos
SET status_meta = CASE
  WHEN valor_atingido IS NULL THEN 'pendente'
  WHEN valor_meta <= 0 AND valor_atingido > 0 THEN 'atingida'
  WHEN valor_meta <= 0 THEN 'pendente'
  WHEN ((valor_atingido / valor_meta) * 100) >= 100 THEN 'atingida'
  WHEN ((valor_atingido / valor_meta) * 100) >= 80 THEN 'parcial'
  ELSE 'nao_atingida'
END,
percentual_cumprimento = CASE
  WHEN valor_atingido IS NULL THEN NULL
  WHEN valor_meta <= 0 THEN NULL
  ELSE ROUND((valor_atingido / valor_meta) * 100, 2)
END
WHERE deleted_at IS NULL;

CALL sp_add_index_if_missing('departamentos', 'idx_departamentos_cliente_ativo', 'ALTER TABLE departamentos ADD INDEX idx_departamentos_cliente_ativo (cliente_id, ativo)');
CALL sp_add_index_if_missing('setores', 'idx_setores_departamento_ativo', 'ALTER TABLE setores ADD INDEX idx_setores_departamento_ativo (departamento_id, ativo)');
CALL sp_add_index_if_missing('colaboradores', 'idx_colaboradores_cliente_ativo', 'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_cliente_ativo (cliente_id, ativo)');
CALL sp_add_index_if_missing('indicadores', 'idx_indicadores_cliente', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_cliente (cliente_id)');
CALL sp_add_index_if_missing('indicadores', 'idx_indicadores_departamento', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_departamento (departamento_id)');
CALL sp_add_index_if_missing('indicadores', 'idx_indicadores_setor', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_setor (setor_id)');
CALL sp_add_index_if_missing('indicadores', 'idx_indicadores_unidade_medida', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_unidade_medida (unidade_medida_id)');
CALL sp_add_index_if_missing('indicadores', 'idx_indicadores_deleted_at', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_deleted_at (deleted_at)');

CALL sp_add_fk_if_missing('indicadores', 'fk_indicadores_cliente', 'clientes', 'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)');
CALL sp_add_fk_if_missing('indicadores', 'fk_indicadores_departamento', 'departamentos', 'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id)');
CALL sp_add_fk_if_missing('indicadores', 'fk_indicadores_setor', 'setores', 'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_setor FOREIGN KEY (setor_id) REFERENCES setores(id)');
CALL sp_add_fk_if_missing('indicadores', 'fk_indicadores_unidade_medida', 'unidades_medida', 'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_unidade_medida FOREIGN KEY (unidade_medida_id) REFERENCES unidades_medida(id)');
CALL sp_add_fk_if_missing('indicador_responsavel', 'fk_indicador_responsavel_indicador', 'indicadores', 'ALTER TABLE indicador_responsavel ADD CONSTRAINT fk_indicador_responsavel_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id) ON DELETE CASCADE');
CALL sp_add_fk_if_missing('indicador_responsavel', 'fk_indicador_responsavel_colaborador', 'colaboradores', 'ALTER TABLE indicador_responsavel ADD CONSTRAINT fk_indicador_responsavel_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE');
CALL sp_add_fk_if_missing('indicador_eventos', 'fk_indicador_eventos_indicador', 'indicadores', 'ALTER TABLE indicador_eventos ADD CONSTRAINT fk_indicador_eventos_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id)');
CALL sp_add_fk_if_missing('indicador_eventos', 'fk_indicador_eventos_cliente', 'clientes', 'ALTER TABLE indicador_eventos ADD CONSTRAINT fk_indicador_eventos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)');

-- ---------------------------------------------------------------------
-- TREINAMENTOS: campos novos, auditoria, cache, presenca/certificado
-- ---------------------------------------------------------------------
CALL sp_add_column_if_missing('treinamentos', 'tipo_treinamento', 'ALTER TABLE treinamentos ADD COLUMN tipo_treinamento VARCHAR(80) NULL AFTER publico');
CALL sp_add_column_if_missing('treinamentos', 'template_certificado', 'ALTER TABLE treinamentos ADD COLUMN template_certificado TEXT NULL AFTER fornecedor');
CALL sp_add_column_if_missing('treinamentos', 'assinatura_responsavel', 'ALTER TABLE treinamentos ADD COLUMN assinatura_responsavel VARCHAR(180) NULL AFTER template_certificado');

CALL sp_add_column_if_missing('colaboradores', 'matricula', 'ALTER TABLE colaboradores ADD COLUMN matricula VARCHAR(60) NULL AFTER nome');
CALL sp_add_column_if_missing('colaboradores', 'cpf', 'ALTER TABLE colaboradores ADD COLUMN cpf VARCHAR(20) NULL AFTER matricula');
CALL sp_add_column_if_missing('colaboradores', 'data_admissao', 'ALTER TABLE colaboradores ADD COLUMN data_admissao DATE NULL AFTER cpf');
CALL sp_add_column_if_missing('colaboradores', 'status_atual', 'ALTER TABLE colaboradores ADD COLUMN status_atual VARCHAR(40) NOT NULL DEFAULT ''ativo'' AFTER data_admissao');

CALL sp_add_index_if_missing('colaboradores', 'idx_colaboradores_matricula', 'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_matricula (matricula)');
CALL sp_add_index_if_missing('colaboradores', 'idx_colaboradores_status_atual', 'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_status_atual (status_atual)');

CALL sp_add_column_if_missing('treinamento_participantes', 'certificado_numero', 'ALTER TABLE treinamento_participantes ADD COLUMN certificado_numero VARCHAR(80) NULL AFTER certificado_emitido');
CALL sp_add_column_if_missing('treinamento_participantes', 'certificado_codigo', 'ALTER TABLE treinamento_participantes ADD COLUMN certificado_codigo VARCHAR(120) NULL AFTER certificado_numero');
CALL sp_add_column_if_missing('treinamento_participantes', 'certificado_emitido_em', 'ALTER TABLE treinamento_participantes ADD COLUMN certificado_emitido_em DATETIME NULL AFTER certificado_codigo');
CALL sp_add_column_if_missing('treinamento_participantes', 'certificado_arquivo', 'ALTER TABLE treinamento_participantes ADD COLUMN certificado_arquivo VARCHAR(255) NULL AFTER certificado_emitido_em');
CALL sp_add_column_if_missing('treinamento_participantes', 'presenca_confirmada_em', 'ALTER TABLE treinamento_participantes ADD COLUMN presenca_confirmada_em DATETIME NULL AFTER certificado_arquivo');
CALL sp_add_column_if_missing('treinamento_participantes', 'hora_entrada', 'ALTER TABLE treinamento_participantes ADD COLUMN hora_entrada TIME NULL AFTER presenca_confirmada_em');
CALL sp_add_column_if_missing('treinamento_participantes', 'hora_saida', 'ALTER TABLE treinamento_participantes ADD COLUMN hora_saida TIME NULL AFTER hora_entrada');
CALL sp_add_column_if_missing('treinamento_participantes', 'observacao', 'ALTER TABLE treinamento_participantes ADD COLUMN observacao TEXT NULL AFTER hora_saida');

CREATE TABLE IF NOT EXISTS treinamento_auditoria_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  treinamento_id INT NULL,
  agenda_id INT NULL,
  participante_id INT NULL,
  colaborador_id INT NULL,
  acao VARCHAR(80) NOT NULL,
  detalhes_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by INT NULL,
  KEY idx_treinamento_auditoria_treinamento (treinamento_id),
  KEY idx_treinamento_auditoria_agenda (agenda_id),
  KEY idx_treinamento_auditoria_participante (participante_id),
  KEY idx_treinamento_auditoria_colaborador (colaborador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS treinamento_export_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cache_key VARCHAR(190) NOT NULL,
  payload_json LONGTEXT NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_treinamento_export_cache_key (cache_key),
  KEY idx_treinamento_export_cache_exp (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Limpeza de helpers temporarios.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DROP PROCEDURE IF EXISTS sp_add_index_if_missing;
DROP PROCEDURE IF EXISTS sp_add_fk_if_missing;
