-- Reconciles auditoria responsibility tables created previously outside schema_migrations
-- so the live database matches the versioned schema and expected constraint names.

SET @has_old_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_responsaveis'
    AND index_name = 'uniq_auditoria_responsavel'
);
SET @has_new_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_responsaveis'
    AND index_name = 'uq_auditoria_responsavel'
);
SET @sql = IF(
  @has_old_idx > 0 AND @has_new_idx = 0,
  'ALTER TABLE auditoria_responsaveis RENAME INDEX uniq_auditoria_responsavel TO uq_auditoria_responsavel',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk = (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'auditoria_responsaveis'
    AND constraint_name = 'fk_auditoria_responsaveis_colaborador'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql = IF(
  @has_fk = 0,
  'ALTER TABLE auditoria_responsaveis ADD CONSTRAINT fk_auditoria_responsaveis_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'idx_auditoria_qresp_questao'
);
SET @has_new_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'idx_auditoria_questao_responsaveis_questao'
);
SET @sql = IF(
  @has_old_idx > 0 AND @has_new_idx = 0,
  'ALTER TABLE auditoria_questao_responsaveis RENAME INDEX idx_auditoria_qresp_questao TO idx_auditoria_questao_responsaveis_questao',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'idx_auditoria_qresp_colaborador'
);
SET @has_new_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'idx_auditoria_questao_responsaveis_colaborador'
);
SET @sql = IF(
  @has_old_idx > 0 AND @has_new_idx = 0,
  'ALTER TABLE auditoria_questao_responsaveis RENAME INDEX idx_auditoria_qresp_colaborador TO idx_auditoria_questao_responsaveis_colaborador',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'uniq_questao_responsavel'
);
SET @has_new_idx = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND index_name = 'uq_auditoria_questao_responsavel'
);
SET @sql = IF(
  @has_old_idx > 0 AND @has_new_idx = 0,
  'ALTER TABLE auditoria_questao_responsaveis RENAME INDEX uniq_questao_responsavel TO uq_auditoria_questao_responsavel',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_fk = (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND constraint_name = 'fk_auditoria_qresp_questao'
    AND constraint_type = 'FOREIGN KEY'
);
SET @has_new_fk = (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND constraint_name = 'fk_auditoria_questao_responsaveis_questao'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql = IF(
  @has_old_fk > 0 AND @has_new_fk = 0,
  'ALTER TABLE auditoria_questao_responsaveis DROP FOREIGN KEY fk_auditoria_qresp_questao, ADD CONSTRAINT fk_auditoria_questao_responsaveis_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE',
  IF(
    @has_old_fk = 0 AND @has_new_fk = 0,
    'ALTER TABLE auditoria_questao_responsaveis ADD CONSTRAINT fk_auditoria_questao_responsaveis_questao FOREIGN KEY (questao_id) REFERENCES auditoria_questoes(id) ON DELETE CASCADE',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk = (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'auditoria_questao_responsaveis'
    AND constraint_name = 'fk_auditoria_questao_responsaveis_colaborador'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql = IF(
  @has_fk = 0,
  'ALTER TABLE auditoria_questao_responsaveis ADD CONSTRAINT fk_auditoria_questao_responsaveis_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
