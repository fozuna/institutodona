SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'tipo_treinamento'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN tipo_treinamento VARCHAR(80) NULL AFTER publico'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'template_certificado'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN template_certificado TEXT NULL AFTER fornecedor'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos'
        AND COLUMN_NAME = 'assinatura_responsavel'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos ADD COLUMN assinatura_responsavel VARCHAR(180) NULL AFTER template_certificado'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND COLUMN_NAME = 'matricula'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD COLUMN matricula VARCHAR(60) NULL AFTER nome'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND COLUMN_NAME = 'cpf'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD COLUMN cpf VARCHAR(20) NULL AFTER matricula'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND COLUMN_NAME = 'data_admissao'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD COLUMN data_admissao DATE NULL AFTER cpf'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND COLUMN_NAME = 'status_atual'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD COLUMN status_atual VARCHAR(40) NOT NULL DEFAULT ''ativo'' AFTER data_admissao'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND INDEX_NAME = 'idx_colaboradores_matricula'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_matricula (matricula)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'colaboradores'
        AND INDEX_NAME = 'idx_colaboradores_status_atual'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_status_atual (status_atual)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'certificado_numero'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN certificado_numero VARCHAR(80) NULL AFTER certificado_emitido'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'certificado_codigo'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN certificado_codigo VARCHAR(120) NULL AFTER certificado_numero'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'certificado_emitido_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN certificado_emitido_em DATETIME NULL AFTER certificado_codigo'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'certificado_arquivo'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN certificado_arquivo VARCHAR(255) NULL AFTER certificado_emitido_em'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'presenca_confirmada_em'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN presenca_confirmada_em DATETIME NULL AFTER certificado_arquivo'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'hora_entrada'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN hora_entrada TIME NULL AFTER presenca_confirmada_em'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'hora_saida'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN hora_saida TIME NULL AFTER hora_entrada'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamento_participantes'
        AND COLUMN_NAME = 'observacao'
    ),
    'SELECT 1',
    'ALTER TABLE treinamento_participantes ADD COLUMN observacao TEXT NULL AFTER hora_saida'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
