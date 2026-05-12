DELIMITER $$

DROP PROCEDURE IF EXISTS sp_drop_index_if_exists $$
CREATE PROCEDURE sp_drop_index_if_exists(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table
          AND index_name = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP INDEX `', p_index, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS sp_drop_column_if_exists $$
CREATE PROCEDURE sp_drop_column_if_exists(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table
          AND column_name = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_column, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

CALL sp_drop_index_if_exists('colaboradores', 'ux_colaboradores_documento');
CALL sp_drop_index_if_exists('colaboradores', 'ux_colaboradores_email');
CALL sp_drop_index_if_exists('colaboradores', 'idx_colaboradores_cliente');
CALL sp_drop_index_if_exists('colaboradores', 'idx_colaboradores_funcao');

CALL sp_drop_column_if_exists('colaboradores', 'documento');
CALL sp_drop_column_if_exists('colaboradores', 'data_nascimento');
CALL sp_drop_column_if_exists('colaboradores', 'celular');
CALL sp_drop_column_if_exists('colaboradores', 'ativo');

DROP PROCEDURE IF EXISTS sp_drop_index_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_column_if_exists;

