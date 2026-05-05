SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'departamentos'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE departamentos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
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
        AND TABLE_NAME = 'setores'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE setores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
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
        AND TABLE_NAME = 'colaboradores'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'indicador'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN indicador VARCHAR(255) NULL AFTER cliente_id'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'departamento_id'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN departamento_id INT NULL AFTER indicador'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'setor_id'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN setor_id INT NULL AFTER departamento_id'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'periodicidade_tipo'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN periodicidade_tipo VARCHAR(20) NOT NULL DEFAULT ''mensal'' AFTER setor_id'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'data_inicial'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN data_inicial DATE NULL AFTER periodicidade_tipo'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'data_final'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN data_final DATE NULL AFTER data_inicial'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'valor'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN valor DECIMAL(15,4) NULL AFTER data_final'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'unidade_medida_id'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN unidade_medida_id INT NULL AFTER valor'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'valor_minimo'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN valor_minimo DECIMAL(15,4) NULL AFTER unidade_medida_id'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'valor_maximo'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN valor_maximo DECIMAL(15,4) NULL AFTER valor_minimo'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'updated_at'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN updated_at DATETIME NULL DEFAULT NULL AFTER created_at'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'deleted_at'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'created_by'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN created_by INT NULL AFTER deleted_at'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'updated_by'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN updated_by INT NULL AFTER created_by'
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
        AND TABLE_NAME = 'indicadores'
        AND COLUMN_NAME = 'deleted_by'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD COLUMN deleted_by INT NULL AFTER updated_by'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE indicadores
SET indicador = COALESCE(NULLIF(indicador, ''), NULLIF(nome, ''))
WHERE (indicador IS NULL OR indicador = '')
  AND nome IS NOT NULL
  AND nome <> '';

UPDATE indicadores
SET periodicidade_tipo = 'mensal'
WHERE periodicidade_tipo IS NULL
   OR periodicidade_tipo = '';

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
WHERE unidade_medida_id IS NULL;

CREATE TABLE IF NOT EXISTS indicador_responsavel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  indicador_id INT NOT NULL,
  colaborador_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_indicador_responsavel (indicador_id, colaborador_id),
  KEY idx_indicador_responsavel_indicador (indicador_id),
  KEY idx_indicador_responsavel_colaborador (colaborador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'departamentos'
        AND INDEX_NAME = 'idx_departamentos_cliente_ativo'
    ),
    'SELECT 1',
    'ALTER TABLE departamentos ADD INDEX idx_departamentos_cliente_ativo (cliente_id, ativo)'
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
        AND TABLE_NAME = 'setores'
        AND INDEX_NAME = 'idx_setores_departamento_ativo'
    ),
    'SELECT 1',
    'ALTER TABLE setores ADD INDEX idx_setores_departamento_ativo (departamento_id, ativo)'
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
        AND INDEX_NAME = 'idx_colaboradores_cliente_ativo'
    ),
    'SELECT 1',
    'ALTER TABLE colaboradores ADD INDEX idx_colaboradores_cliente_ativo (cliente_id, ativo)'
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
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_cliente'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_cliente (cliente_id)'
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
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_departamento'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_departamento (departamento_id)'
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
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_setor'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_setor (setor_id)'
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
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_unidade_medida'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_unidade_medida (unidade_medida_id)'
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
        AND TABLE_NAME = 'indicadores'
        AND INDEX_NAME = 'idx_indicadores_deleted_at'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD INDEX idx_indicadores_deleted_at (deleted_at)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND CONSTRAINT_NAME = 'fk_indicadores_cliente'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND CONSTRAINT_NAME = 'fk_indicadores_departamento'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND CONSTRAINT_NAME = 'fk_indicadores_setor'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_setor FOREIGN KEY (setor_id) REFERENCES setores(id)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicadores'
        AND CONSTRAINT_NAME = 'fk_indicadores_unidade_medida'
    ),
    'SELECT 1',
    'ALTER TABLE indicadores ADD CONSTRAINT fk_indicadores_unidade_medida FOREIGN KEY (unidade_medida_id) REFERENCES unidades_medida(id)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicador_responsavel'
        AND CONSTRAINT_NAME = 'fk_indicador_responsavel_indicador'
    ),
    'SELECT 1',
    'ALTER TABLE indicador_responsavel ADD CONSTRAINT fk_indicador_responsavel_indicador FOREIGN KEY (indicador_id) REFERENCES indicadores(id) ON DELETE CASCADE'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'indicador_responsavel'
        AND CONSTRAINT_NAME = 'fk_indicador_responsavel_colaborador'
    ),
    'SELECT 1',
    'ALTER TABLE indicador_responsavel ADD CONSTRAINT fk_indicador_responsavel_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
