-- Script manual de sincronizacao do banco de producao com o estado atual do projeto
-- Baseado no dump: public/institutodona_dump.sql
-- Objetivo: materializar objetos ausentes e registrar schema_migrations de forma compativel com MigrationRunner.php

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(190) PRIMARY KEY,
  checksum CHAR(64) NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20260415_public_avaliacoes_estaticas_apply.sql
SET @has_dominio_publico := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'clientes'
    AND column_name = 'dominio_publico'
);
SET @sql := IF(@has_dominio_publico = 0,
  'ALTER TABLE clientes ADD COLUMN dominio_publico VARCHAR(255) NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_dominio_index := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'clientes'
    AND index_name = 'uq_clientes_dominio_publico'
);
SET @sql := IF(@has_dominio_index = 0,
  'CREATE UNIQUE INDEX uq_clientes_dominio_publico ON clientes (dominio_publico)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 20260416_auditoria_responsaveis_apply.sql
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

-- 20260417_auditoria_historico_apply.sql
CREATE TABLE IF NOT EXISTS auditoria_historico (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auditoria_id INT NOT NULL,
  dados_anteriores JSON NOT NULL,
  usuario_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_aud_hist_auditoria (auditoria_id),
  CONSTRAINT fk_aud_hist_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20260417_faturamento_faixas_apply.sql
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

SET @has_av_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes'
    AND column_name = 'faturamento_faixa_id'
);
SET @sql := IF(@has_av_col = 0,
  'ALTER TABLE avaliacoes ADD COLUMN faturamento_faixa_id INT NULL AFTER faturamento_medio_anual',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_pub_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes_publicas'
    AND column_name = 'faturamento_faixa_id'
);
SET @sql := IF(@has_pub_col = 0,
  'ALTER TABLE avaliacoes_publicas ADD COLUMN faturamento_faixa_id INT NULL AFTER faturamento_anual',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE avaliacoes
SET faturamento_faixa_id = CASE
  WHEN faturamento_medio_anual <= 100000 THEN 1
  WHEN faturamento_medio_anual <= 250000 THEN 2
  WHEN faturamento_medio_anual <= 500000 THEN 3
  WHEN faturamento_medio_anual <= 750000 THEN 4
  WHEN faturamento_medio_anual <= 1000000 THEN 5
  WHEN faturamento_medio_anual > 1000000 THEN 6
  ELSE faturamento_faixa_id
END
WHERE faturamento_faixa_id IS NULL
  AND faturamento_medio_anual IS NOT NULL
  AND faturamento_medio_anual > 0;

SET @has_av_pub := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'avaliacoes_publicas'
);
SET @sql := IF(@has_av_pub > 0, '
UPDATE avaliacoes_publicas
SET faturamento_faixa_id = CASE
  WHEN faturamento_anual <= 100000 THEN 1
  WHEN faturamento_anual <= 250000 THEN 2
  WHEN faturamento_anual <= 500000 THEN 3
  WHEN faturamento_anual <= 750000 THEN 4
  WHEN faturamento_anual <= 1000000 THEN 5
  WHEN faturamento_anual > 1000000 THEN 6
  ELSE faturamento_faixa_id
END
WHERE faturamento_faixa_id IS NULL
  AND faturamento_anual IS NOT NULL
  AND faturamento_anual > 0
', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 20260417_manuais_apply.sql
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

-- 20260417_manual_portal_tokens_apply.sql
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

-- Registro de migrations no repositório do projeto
INSERT INTO schema_migrations (version, checksum) VALUES
  ('20260325_baseline_from_import_all_tables.php', '970dc530754c5b52443d6afe412f6812cb4a5d91caa4fa451969ebacba5641ee'),
  ('20260326_tenant_scope.php', 'b9618e6b42d46e3d438b188e5db4dbf932d2d80f62de9522cf0b91a1456bb139'),
  ('20260413_avaliacoes_publicas_e_potenciais_apply.sql', '35437c529e65ac1ff3e9b98a2f612a44f82a35e2c798bdd3bf698987c403156c'),
  ('20260415_public_avaliacoes_estaticas_apply.sql', 'f46d0f07a2fea041ce0f7596b4f22b075a9b7bfeb6fc8fec1eb72e215be18788'),
  ('20260416_auditoria_responsaveis_apply.sql', '94b47f24a2f380854fb7eaacf41efffb12ff64b3dff5a26c4409648453133561'),
  ('20260417_auditoria_historico_apply.sql', '02861b14a81a2891933900e6003c7a124022a66b3048c72a762ce0a66809eea6'),
  ('20260417_auditoria_responsaveis_reconcile_apply.sql', 'f4b7a0f332b7b3ba4aedab1e7263f7046306de32af3f71e8cb9516765b60c98b'),
  ('20260417_faturamento_faixas_apply.sql', '1600497ddd056fd8f2baaec8d478af4f5527268a193d99f15b399596dd7acbdb'),
  ('20260417_manuais_apply.sql', 'a6fb325cc8f1b43da0c7ff28f8e2b029b7772abdbe51b57f24c3cb2873f41c78'),
  ('20260417_manual_portal_tokens_apply.sql', '5cfbc059e2b5173603eb1db3f91f13ed241bde606e4599a21ac36338053ca0d4')
ON DUPLICATE KEY UPDATE checksum = VALUES(checksum);
