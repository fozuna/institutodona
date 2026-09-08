-- Rollback de 20260908150519_manuais_pre_cadastro_status_apply.sql
--
-- CONDIÇÃO DE SEGURANÇA (documentada e aplicada automaticamente abaixo):
-- reverter `arquivo`/`tipo_arquivo` para NOT NULL só é seguro se NENHUM
-- Manual estiver hoje em pré-cadastro (arquivo/tipo_arquivo NULL ou
-- status='pre_cadastro') - caso contrário o ALTER teria que ou destruir
-- essas linhas ou fabricar um `arquivo`/`tipo_arquivo` que nunca existiu.
-- Nesta Sprint 01 isso é sempre verdade (o Controller ainda exige arquivo
-- em toda criação), mas o guard abaixo protege também qualquer rollback
-- futuro, depois que a Sprint 02 já tiver permitido pré-cadastros reais:
-- o rollback é abortado com um erro explícito em vez de rodar às cegas.
DELIMITER $$

DROP PROCEDURE IF EXISTS _manuais_pre_cadastro_rollback_guard $$
CREATE PROCEDURE _manuais_pre_cadastro_rollback_guard()
BEGIN
    DECLARE incompativeis INT DEFAULT 0;
    SELECT COUNT(*) INTO incompativeis
      FROM manuais
     WHERE arquivo IS NULL OR tipo_arquivo IS NULL OR status = 'pre_cadastro';
    IF incompativeis > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Rollback abortado: existem Manuais em pre_cadastro (arquivo/tipo_arquivo NULL). Publique ou exclua-os manualmente antes de reverter esta migration.';
    END IF;
END $$

DELIMITER ;

CALL _manuais_pre_cadastro_rollback_guard();
DROP PROCEDURE IF EXISTS _manuais_pre_cadastro_rollback_guard;

ALTER TABLE manuais
  DROP INDEX idx_manuais_status,
  DROP COLUMN publicado_em,
  DROP COLUMN status,
  MODIFY COLUMN tipo_arquivo VARCHAR(10) NOT NULL,
  MODIFY COLUMN arquivo VARCHAR(255) NOT NULL;
