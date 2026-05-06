-- 20260507_indicadores_treinamentos_consolidado_rollback.sql
-- Rollback conservador da migration consolidada 20260507.
-- Observacao: remove objetos/colunas introduzidos para os modulos de Indicadores e Treinamentos.

SET @schema_name = DATABASE();

DROP PROCEDURE IF EXISTS sp_drop_fk_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_index_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_column_if_exists;

DELIMITER //
CREATE PROCEDURE sp_drop_fk_if_exists(
    IN p_table VARCHAR(128),
    IN p_fk_name VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_fk_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table, ' DROP FOREIGN KEY ', p_fk_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE sp_drop_index_if_exists(
    IN p_table VARCHAR(128),
    IN p_index VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = CONCAT('DROP INDEX ', p_index, ' ON ', p_table);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE sp_drop_column_if_exists(
    IN p_table VARCHAR(128),
    IN p_column VARCHAR(128)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table, ' DROP COLUMN ', p_column);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------
-- Remocao de FKs criadas no consolidado.
-- ---------------------------------------------------------------------
CALL sp_drop_fk_if_exists('indicador_eventos', 'fk_indicador_eventos_indicador');
CALL sp_drop_fk_if_exists('indicador_eventos', 'fk_indicador_eventos_cliente');
CALL sp_drop_fk_if_exists('indicador_responsavel', 'fk_indicador_responsavel_indicador');
CALL sp_drop_fk_if_exists('indicador_responsavel', 'fk_indicador_responsavel_colaborador');
CALL sp_drop_fk_if_exists('indicadores', 'fk_indicadores_cliente');
CALL sp_drop_fk_if_exists('indicadores', 'fk_indicadores_departamento');
CALL sp_drop_fk_if_exists('indicadores', 'fk_indicadores_setor');
CALL sp_drop_fk_if_exists('indicadores', 'fk_indicadores_unidade_medida');

-- ---------------------------------------------------------------------
-- Remocao de tabelas introduzidas no consolidado.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS indicador_eventos;
DROP TABLE IF EXISTS indicador_responsavel;
DROP TABLE IF EXISTS treinamento_export_cache;
DROP TABLE IF EXISTS treinamento_auditoria_logs;
DROP TABLE IF EXISTS unidades_medida;

-- ---------------------------------------------------------------------
-- Limpeza de indices e colunas em tabelas existentes.
-- ---------------------------------------------------------------------
CALL sp_drop_index_if_exists('indicadores', 'idx_indicadores_deleted_at');
CALL sp_drop_index_if_exists('indicadores', 'idx_indicadores_unidade_medida');
CALL sp_drop_index_if_exists('indicadores', 'idx_indicadores_setor');
CALL sp_drop_index_if_exists('indicadores', 'idx_indicadores_departamento');
CALL sp_drop_index_if_exists('indicadores', 'idx_indicadores_cliente');

CALL sp_drop_column_if_exists('indicadores', 'deleted_by');
CALL sp_drop_column_if_exists('indicadores', 'updated_by');
CALL sp_drop_column_if_exists('indicadores', 'created_by');
CALL sp_drop_column_if_exists('indicadores', 'deleted_at');
CALL sp_drop_column_if_exists('indicadores', 'updated_at');
CALL sp_drop_column_if_exists('indicadores', 'valor_maximo');
CALL sp_drop_column_if_exists('indicadores', 'valor_minimo');
CALL sp_drop_column_if_exists('indicadores', 'unidade_medida_id');
CALL sp_drop_column_if_exists('indicadores', 'valor');
CALL sp_drop_column_if_exists('indicadores', 'data_final');
CALL sp_drop_column_if_exists('indicadores', 'data_inicial');
CALL sp_drop_column_if_exists('indicadores', 'periodicidade_tipo');
CALL sp_drop_column_if_exists('indicadores', 'setor_id');
CALL sp_drop_column_if_exists('indicadores', 'departamento_id');
CALL sp_drop_column_if_exists('indicadores', 'indicador');

CALL sp_drop_index_if_exists('colaboradores', 'idx_colaboradores_status_atual');
CALL sp_drop_index_if_exists('colaboradores', 'idx_colaboradores_matricula');
CALL sp_drop_column_if_exists('colaboradores', 'status_atual');
CALL sp_drop_column_if_exists('colaboradores', 'data_admissao');
CALL sp_drop_column_if_exists('colaboradores', 'cpf');
CALL sp_drop_column_if_exists('colaboradores', 'matricula');

CALL sp_drop_column_if_exists('treinamentos', 'assinatura_responsavel');
CALL sp_drop_column_if_exists('treinamentos', 'template_certificado');
CALL sp_drop_column_if_exists('treinamentos', 'tipo_treinamento');

CALL sp_drop_column_if_exists('treinamento_participantes', 'observacao');
CALL sp_drop_column_if_exists('treinamento_participantes', 'hora_saida');
CALL sp_drop_column_if_exists('treinamento_participantes', 'hora_entrada');
CALL sp_drop_column_if_exists('treinamento_participantes', 'presenca_confirmada_em');
CALL sp_drop_column_if_exists('treinamento_participantes', 'certificado_arquivo');
CALL sp_drop_column_if_exists('treinamento_participantes', 'certificado_emitido_em');
CALL sp_drop_column_if_exists('treinamento_participantes', 'certificado_codigo');
CALL sp_drop_column_if_exists('treinamento_participantes', 'certificado_numero');

DROP PROCEDURE IF EXISTS sp_drop_fk_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_index_if_exists;
DROP PROCEDURE IF EXISTS sp_drop_column_if_exists;
