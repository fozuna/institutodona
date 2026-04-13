-- ============================================================================
-- Rollback: avaliações internas flexíveis + links públicos permanentes
-- Data: 2026-04-13
--
-- Atenção:
-- 1. Este rollback remove apenas o que foi introduzido para este fluxo.
-- 2. Se existirem dados de produção usando os novos campos/tabela, faça backup.
-- 3. Assim como na aplicação, DDL em MySQL possui commit implícito.
-- ============================================================================

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

DROP PROCEDURE IF EXISTS sp_drop_fk_if_exists $$
CREATE PROCEDURE sp_drop_fk_if_exists(
    IN p_table VARCHAR(128),
    IN p_fk VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.referential_constraints
        WHERE constraint_schema = DATABASE()
          AND table_name = p_table
          AND constraint_name = p_fk
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` DROP FOREIGN KEY `', p_fk, '`');
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
-- 1. Rollback da tabela/estrutura pública
-- ============================================================================

CALL sp_drop_check_if_exists('avaliacoes_publicas', 'chk_avaliacoes_publicas_status');
CALL sp_drop_index_if_exists('avaliacoes_publicas', 'idx_avaliacoes_publicas_status_data');
CALL sp_drop_index_if_exists('avaliacoes_publicas', 'idx_avaliacoes_publicas_data_conclusao');
CALL sp_drop_index_if_exists('avaliacoes_publicas', 'uq_avaliacoes_publicas_token');
CALL sp_drop_fk_if_exists('avaliacoes_publicas', 'fk_avaliacoes_publicas_avaliacao');

-- Se a equipe optar por reverter completamente o recurso público, remove a tabela.
DROP TABLE IF EXISTS avaliacoes_publicas;

-- ============================================================================
-- 2. Rollback da rastreabilidade de avaliacoes internas
-- ============================================================================

CALL sp_drop_check_if_exists('avaliacoes', 'chk_avaliacoes_origem_cadastro');
CALL sp_drop_fk_if_exists('avaliacoes', 'fk_avaliacoes_created_by_user');
CALL sp_drop_index_if_exists('avaliacoes', 'idx_avaliacoes_created_by_user_id');
CALL sp_drop_index_if_exists('avaliacoes', 'idx_avaliacoes_origem_cliente');
CALL sp_drop_index_if_exists('avaliacoes', 'idx_avaliacoes_cliente_associado_em');

-- Mantém idx_avaliacoes_cliente_id porque ele já é útil ao domínio e pode ter
-- sido criado por migração anterior. Não removemos para evitar regressão.

CALL sp_drop_column_if_exists('avaliacoes', 'cliente_associado_em');
CALL sp_drop_column_if_exists('avaliacoes', 'created_by_user_id');
CALL sp_drop_column_if_exists('avaliacoes', 'origem_cadastro');

-- ============================================================================
-- 3. Limpeza de helpers temporários
-- ============================================================================

DROP PROCEDURE IF EXISTS sp_drop_index_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_fk_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_column_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_check_if_exists;

