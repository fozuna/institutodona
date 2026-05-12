DELIMITER $$

DROP PROCEDURE IF EXISTS sp_add_column_if_missing $$
CREATE PROCEDURE sp_add_column_if_missing(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128),
    IN p_ddl TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table
          AND column_name = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
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

DELIMITER ;

CREATE TABLE IF NOT EXISTS colaboradores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  email VARCHAR(180) NULL,
  documento VARCHAR(20) NULL,
  data_nascimento DATE NULL,
  celular VARCHAR(15) NULL,
  funcao_id INT NOT NULL,
  lider ENUM('não','sim') NOT NULL DEFAULT 'não',
  cliente_id INT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_colaboradores_cliente (cliente_id),
  INDEX idx_colaboradores_funcao (funcao_id),
  UNIQUE INDEX ux_colaboradores_documento (documento),
  UNIQUE INDEX ux_colaboradores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL sp_add_column_if_missing('colaboradores', 'documento', 'documento VARCHAR(20) NULL AFTER nome');
CALL sp_add_column_if_missing('colaboradores', 'data_nascimento', 'data_nascimento DATE NULL');
CALL sp_add_column_if_missing('colaboradores', 'celular', 'celular VARCHAR(15) NULL');
CALL sp_add_column_if_missing('colaboradores', 'ativo', 'ativo TINYINT(1) NOT NULL DEFAULT 1');

CALL sp_add_index_if_missing('colaboradores', 'idx_colaboradores_cliente', 'INDEX idx_colaboradores_cliente (cliente_id)');
CALL sp_add_index_if_missing('colaboradores', 'idx_colaboradores_funcao', 'INDEX idx_colaboradores_funcao (funcao_id)');
CALL sp_add_index_if_missing('colaboradores', 'ux_colaboradores_documento', 'UNIQUE INDEX ux_colaboradores_documento (documento)');
CALL sp_add_index_if_missing('colaboradores', 'ux_colaboradores_email', 'UNIQUE INDEX ux_colaboradores_email (email)');

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DROP PROCEDURE IF EXISTS sp_add_index_if_missing;

