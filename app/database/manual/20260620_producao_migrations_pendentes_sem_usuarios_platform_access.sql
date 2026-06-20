-- Consolidado de migrations pendentes em producao
-- Baseado na comparacao com o dump de producao fornecido em public/institutodona_dump.sql
-- Exclui intencionalmente a migration 20260610150000_usuarios_platform_access_apply.sql
-- porque ela ja foi aplicada em producao.

SET @schema_name = DATABASE();

-- =========================================================
-- 20260512223000_cronogramas_ativo_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE cronogramas ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER ano'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronogramas'
        AND INDEX_NAME = 'idx_cronogramas_cliente_ano_ativo'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronogramas_cliente_ano_ativo ON cronogramas (id_cliente, ano, ativo)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 20260514141000_cronograma_eventos_tipo_ata_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'tipo_evento'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN tipo_evento VARCHAR(30) NOT NULL DEFAULT ''Tarefa'' AFTER periodicidade'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'ata_path'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_path VARCHAR(255) NULL AFTER status'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'ata_original_name'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_original_name VARCHAR(255) NULL AFTER ata_path'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'ata_mime'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_mime VARCHAR(100) NULL AFTER ata_original_name'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'ata_size'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_size INT UNSIGNED NULL AFTER ata_mime'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'ata_sha256'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN ata_sha256 CHAR(64) NULL AFTER ata_size'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND INDEX_NAME = 'idx_cronograma_eventos_tipo'
    ),
    'SELECT 1',
    'CREATE INDEX idx_cronograma_eventos_tipo ON cronograma_eventos (tipo_evento)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 20260527123000_cronograma_reuniao_anexos_apply.sql
-- =========================================================
CREATE TABLE IF NOT EXISTS cronograma_evento_anexos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  evento_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size INT UNSIGNED NOT NULL,
  sha256 CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_cronograma_evento_anexos_evento (evento_id),
  INDEX idx_cronograma_evento_anexos_evento_deleted (evento_id, deleted_at),
  CONSTRAINT fk_cronograma_evento_anexos_evento
    FOREIGN KEY (evento_id) REFERENCES cronograma_eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 20260527132000_funcoes_ativo_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'funcoes'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE funcoes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 20260528120000_manuais_vinculos_e_portal_filtros_apply.sql
-- =========================================================
CREATE TABLE IF NOT EXISTS manual_filial_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  manual_id INT NOT NULL,
  filial_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_manual_filial (manual_id, filial_id),
  INDEX idx_mfl_manual (manual_id),
  INDEX idx_mfl_filial (filial_id),
  CONSTRAINT fk_mfl_manual FOREIGN KEY (manual_id) REFERENCES manuais(id) ON DELETE CASCADE,
  CONSTRAINT fk_mfl_filial FOREIGN KEY (filial_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'manual_portal_tokens'
        AND COLUMN_NAME = 'scope_ids_json'
    ),
    'SELECT 1',
    'ALTER TABLE manual_portal_tokens ADD COLUMN scope_ids_json TEXT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'manual_portal_tokens'
        AND COLUMN_NAME = 'filters_json'
    ),
    'SELECT 1',
    'ALTER TABLE manual_portal_tokens ADD COLUMN filters_json TEXT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 20260528130000_treinamentos_agenda_data_fim_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos_agenda'
        AND COLUMN_NAME = 'data_fim'
    ),
    'SELECT 1',
    'ALTER TABLE treinamentos_agenda ADD COLUMN data_fim DATETIME NULL AFTER data'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'treinamentos_agenda'
        AND INDEX_NAME = 'idx_treinamentos_agenda_data_fim'
    ),
    'SELECT 1',
    'CREATE INDEX idx_treinamentos_agenda_data_fim ON treinamentos_agenda (data_fim)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 20260610170000_cronograma_evento_tipos_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_evento_tipos'
    ),
    'SELECT 1',
    'CREATE TABLE cronograma_evento_tipos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_cronograma_evento_tipos_nome (nome),
        INDEX idx_cronograma_evento_tipos_ativo (ativo)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO cronograma_evento_tipos (nome, ativo) VALUES
  ('Gestão', 1),
  ('Auditoria', 1),
  ('Treinamento', 1),
  ('Reunião', 1),
  ('Consultoria', 1),
  ('Implementação', 1),
  ('Outros', 1),
  ('Tarefa', 1),
  ('Indicador', 1),
  ('Pessoas', 1),
  ('Processos', 1),
  ('Coaching', 1);

-- =========================================================
-- 20260610174000_cronograma_eventos_participantes_observacoes_apply.sql
-- =========================================================
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'responsavel_principal'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN responsavel_principal VARCHAR(255) NULL AFTER responsavel'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'responsaveis_secundarios'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN responsaveis_secundarios VARCHAR(255) NULL AFTER responsavel_principal'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'comentarios'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN comentarios TEXT NULL AFTER responsaveis_secundarios'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_eventos'
        AND COLUMN_NAME = 'notas_internas'
    ),
    'SELECT 1',
    'ALTER TABLE cronograma_eventos ADD COLUMN notas_internas TEXT NULL AFTER comentarios'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cronograma_eventos
SET
  responsavel_principal = NULLIF(TRIM(SUBSTRING_INDEX(responsavel, ',', 1)), ''),
  responsaveis_secundarios = CASE
    WHEN LOCATE(',', responsavel) > 0 THEN NULLIF(TRIM(SUBSTRING(responsavel, LOCATE(',', responsavel) + 1)), '')
    ELSE NULL
  END
WHERE responsavel IS NOT NULL
  AND responsavel <> ''
  AND (responsavel_principal IS NULL OR responsavel_principal = '')
  AND (responsaveis_secundarios IS NULL OR responsaveis_secundarios = '');

-- =========================================================
-- 20260610193000_colaborador_status_logs_apply.sql
-- =========================================================
CREATE TABLE IF NOT EXISTS colaborador_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  colaborador_id INT NOT NULL,
  cliente_id INT NULL,
  old_ativo TINYINT(1) NULL,
  new_ativo TINYINT(1) NOT NULL,
  justificativa TEXT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  changed_by INT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  KEY idx_colab_status_logs_colaborador (colaborador_id),
  KEY idx_colab_status_logs_cliente (cliente_id),
  KEY idx_colab_status_logs_changed_at (changed_at),
  CONSTRAINT fk_colab_status_logs_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 20260620180709_auto_schema_update_apply.sql
-- =========================================================
-- Migration noop usada apenas para conformidade do pipeline.
SELECT 1;
