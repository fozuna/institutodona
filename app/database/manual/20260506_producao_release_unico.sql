-- Release SQL unico para producao
-- Aplicacao: Institutodona
-- Versao alvo: v1.24.0
-- Gerado em: 2026-05-06
-- Este script e idempotente e contem alteracoes de schema + dados essenciais

SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;

-- Migration gerada automaticamente por schema_diff_from_dump.php
-- Objetivo: sincronizar dump (producao) com schema de desenvolvimento.
START TRANSACTION;

-- Tabela ausente: indicador_eventos
CREATE TABLE IF NOT EXISTS `indicador_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `indicador_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `data_evento` date NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fim` date NOT NULL,
  `valor_meta` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `valor_atingido` decimal(15,4) DEFAULT NULL,
  `percentual_cumprimento` decimal(8,2) DEFAULT NULL,
  `status_meta` varchar(20) NOT NULL DEFAULT 'pendente',
  `observacao` text,
  `lancado_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_indicador_eventos_data` (`indicador_id`,`data_evento`),
  KEY `idx_indicador_eventos_cliente_data` (`cliente_id`,`data_evento`),
  KEY `idx_indicador_eventos_status` (`status_meta`),
  KEY `idx_indicador_eventos_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_indicador_eventos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_indicador_eventos_indicador` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela ausente: indicador_responsavel
CREATE TABLE IF NOT EXISTS `indicador_responsavel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `indicador_id` int NOT NULL,
  `colaborador_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_indicador_responsavel` (`indicador_id`,`colaborador_id`),
  KEY `idx_indicador_responsavel_indicador` (`indicador_id`),
  KEY `idx_indicador_responsavel_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_indicador_responsavel_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_indicador_responsavel_indicador` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabela ausente: unidades_medida
CREATE TABLE IF NOT EXISTS `unidades_medida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `simbolo` varchar(32) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `fator_conversao_base` decimal(18,8) NOT NULL DEFAULT '1.00000000',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unidades_medida_nome_tipo` (`nome`,`tipo`),
  KEY `idx_unidades_medida_ativo` (`ativo`),
  KEY `idx_unidades_medida_tipo` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Coluna ausente: avaliacoes.financeiro_nota
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'financeiro_nota');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `financeiro_nota` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.financeiro_realidade
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'financeiro_realidade');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `financeiro_realidade` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.id_cliente
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'id_cliente');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `id_cliente` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.mercado_nota
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'mercado_nota');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `mercado_nota` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.mercado_realidade
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'mercado_realidade');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `mercado_realidade` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.pessoas_nota
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'pessoas_nota');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `pessoas_nota` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.pessoas_realidade
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'pessoas_realidade');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `pessoas_realidade` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.processo_nota
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'processo_nota');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `processo_nota` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.processo_realidade
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'processo_realidade');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `processo_realidade` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.realidade_media
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'realidade_media');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `realidade_media` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: avaliacoes.total
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND column_name = 'total');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `avaliacoes` ADD COLUMN `total` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: colaboradores.ativo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'ativo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `colaboradores` ADD COLUMN `ativo` tinyint(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: cronograma_eventos.evento_pai_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'cronograma_eventos' AND column_name = 'evento_pai_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `cronograma_eventos` ADD COLUMN `evento_pai_id` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: cronograma_eventos.periodicidade
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'cronograma_eventos' AND column_name = 'periodicidade');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `cronograma_eventos` ADD COLUMN `periodicidade` varchar(20) NOT NULL DEFAULT ''unico''', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.cliente_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'cliente_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `cliente_id` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.created_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'created_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.created_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'created_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `created_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.data_evento
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'data_evento');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `data_evento` date NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.deleted_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'deleted_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `deleted_at` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.deleted_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'deleted_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `deleted_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `id` int NOT NULL AUTO_INCREMENT', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.indicador_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'indicador_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `indicador_id` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.lancado_em
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'lancado_em');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `lancado_em` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.observacao
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'observacao');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `observacao` text NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.percentual_cumprimento
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'percentual_cumprimento');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `percentual_cumprimento` decimal(8,2) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.periodo_fim
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'periodo_fim');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `periodo_fim` date NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.periodo_inicio
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'periodo_inicio');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `periodo_inicio` date NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.status_meta
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'status_meta');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `status_meta` varchar(20) NOT NULL DEFAULT ''pendente''', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.updated_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'updated_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `updated_at` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.updated_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'updated_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `updated_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.valor_atingido
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'valor_atingido');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `valor_atingido` decimal(15,4) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_eventos.valor_meta
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND column_name = 'valor_meta');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_eventos` ADD COLUMN `valor_meta` decimal(15,4) NOT NULL DEFAULT 0.0000', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_responsavel.colaborador_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND column_name = 'colaborador_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_responsavel` ADD COLUMN `colaborador_id` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_responsavel.created_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND column_name = 'created_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_responsavel` ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_responsavel.id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND column_name = 'id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_responsavel` ADD COLUMN `id` int NOT NULL AUTO_INCREMENT', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicador_responsavel.indicador_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND column_name = 'indicador_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicador_responsavel` ADD COLUMN `indicador_id` int NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.created_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'created_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `created_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.data_final
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'data_final');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `data_final` date NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.data_inicial
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'data_inicial');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `data_inicial` date NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.deleted_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'deleted_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `deleted_at` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.deleted_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'deleted_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `deleted_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.departamento_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'departamento_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `departamento_id` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.indicador
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'indicador');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `indicador` varchar(255) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.periodicidade_tipo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'periodicidade_tipo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `periodicidade_tipo` varchar(20) NOT NULL DEFAULT ''mensal''', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.setor_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'setor_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `setor_id` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.unidade_medida_id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'unidade_medida_id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `unidade_medida_id` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.updated_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'updated_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `updated_at` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.updated_by
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'updated_by');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `updated_by` int NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.valor
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'valor');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `valor` decimal(15,4) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.valor_maximo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'valor_maximo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `valor_maximo` decimal(15,4) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: indicadores.valor_minimo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND column_name = 'valor_minimo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `indicadores` ADD COLUMN `valor_minimo` decimal(15,4) NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: setores.ativo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'setores' AND column_name = 'ativo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `setores` ADD COLUMN `ativo` tinyint(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.ativo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'ativo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `ativo` tinyint(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.created_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'created_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.fator_conversao_base
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'fator_conversao_base');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `fator_conversao_base` decimal(18,8) NOT NULL DEFAULT 1.00000000', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.id
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'id');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `id` int NOT NULL AUTO_INCREMENT', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.nome
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'nome');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `nome` varchar(120) NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.simbolo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'simbolo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `simbolo` varchar(32) NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.tipo
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'tipo');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `tipo` varchar(40) NOT NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Coluna ausente: unidades_medida.updated_at
SET @exists_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND column_name = 'updated_at');
SET @sql_col := IF(@exists_col = 0, 'ALTER TABLE `unidades_medida` ADD COLUMN `updated_at` datetime NULL', 'SELECT 1');
PREPARE stmt_col FROM @sql_col; EXECUTE stmt_col; DEALLOCATE PREPARE stmt_col;

-- Indice ausente: auditorias.idx_auditorias_status_data
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'auditorias' AND index_name = 'idx_auditorias_status_data');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_auditorias_status_data` ON `auditorias` (`status`,`data_auditoria`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes_publicas.idx_avaliacoes_publicas_data_conclusao
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes_publicas' AND index_name = 'idx_avaliacoes_publicas_data_conclusao');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_avaliacoes_publicas_data_conclusao` ON `avaliacoes_publicas` (`data_conclusao`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes_publicas.idx_avaliacoes_publicas_status_data
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes_publicas' AND index_name = 'idx_avaliacoes_publicas_status_data');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_avaliacoes_publicas_status_data` ON `avaliacoes_publicas` (`status`,`data_criacao`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes_publicas.uq_avaliacoes_publicas_slug
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes_publicas' AND index_name = 'uq_avaliacoes_publicas_slug');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE UNIQUE INDEX `uq_avaliacoes_publicas_slug` ON `avaliacoes_publicas` (`slug`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes_publicas.uq_avaliacoes_publicas_token
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes_publicas' AND index_name = 'uq_avaliacoes_publicas_token');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE UNIQUE INDEX `uq_avaliacoes_publicas_token` ON `avaliacoes_publicas` (`token`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes.fk_avaliacao_cliente
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND index_name = 'fk_avaliacao_cliente');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `fk_avaliacao_cliente` ON `avaliacoes` (`id_cliente`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes.idx_avaliacoes_cliente_associado_em
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND index_name = 'idx_avaliacoes_cliente_associado_em');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_avaliacoes_cliente_associado_em` ON `avaliacoes` (`cliente_associado_em`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes.idx_avaliacoes_created_by_user_id
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND index_name = 'idx_avaliacoes_created_by_user_id');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_avaliacoes_created_by_user_id` ON `avaliacoes` (`created_by_user_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: avaliacoes.idx_avaliacoes_origem_cliente
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'avaliacoes' AND index_name = 'idx_avaliacoes_origem_cliente');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_avaliacoes_origem_cliente` ON `avaliacoes` (`origem_cadastro`,`cliente_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: colaboradores.idx_colaboradores_cliente_ativo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND index_name = 'idx_colaboradores_cliente_ativo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_colaboradores_cliente_ativo` ON `colaboradores` (`cliente_id`,`ativo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: cronograma_eventos.idx_cronograma_eventos_data
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'cronograma_eventos' AND index_name = 'idx_cronograma_eventos_data');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_cronograma_eventos_data` ON `cronograma_eventos` (`id_cronograma`,`data`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: cronograma_eventos.idx_cronograma_eventos_pai
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'cronograma_eventos' AND index_name = 'idx_cronograma_eventos_pai');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_cronograma_eventos_pai` ON `cronograma_eventos` (`evento_pai_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: departamentos.idx_departamentos_cliente_ativo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'departamentos' AND index_name = 'idx_departamentos_cliente_ativo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_departamentos_cliente_ativo` ON `departamentos` (`cliente_id`,`ativo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_eventos.idx_indicador_eventos_cliente_data
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND index_name = 'idx_indicador_eventos_cliente_data');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicador_eventos_cliente_data` ON `indicador_eventos` (`cliente_id`,`data_evento`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_eventos.idx_indicador_eventos_deleted_at
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND index_name = 'idx_indicador_eventos_deleted_at');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicador_eventos_deleted_at` ON `indicador_eventos` (`deleted_at`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_eventos.idx_indicador_eventos_status
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND index_name = 'idx_indicador_eventos_status');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicador_eventos_status` ON `indicador_eventos` (`status_meta`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_eventos.uq_indicador_eventos_data
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos' AND index_name = 'uq_indicador_eventos_data');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE UNIQUE INDEX `uq_indicador_eventos_data` ON `indicador_eventos` (`indicador_id`,`data_evento`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_responsavel.idx_indicador_responsavel_colaborador
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND index_name = 'idx_indicador_responsavel_colaborador');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicador_responsavel_colaborador` ON `indicador_responsavel` (`colaborador_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_responsavel.idx_indicador_responsavel_indicador
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND index_name = 'idx_indicador_responsavel_indicador');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicador_responsavel_indicador` ON `indicador_responsavel` (`indicador_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicador_responsavel.uq_indicador_responsavel
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel' AND index_name = 'uq_indicador_responsavel');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE UNIQUE INDEX `uq_indicador_responsavel` ON `indicador_responsavel` (`indicador_id`,`colaborador_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.fk_ind_cliente
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'fk_ind_cliente');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `fk_ind_cliente` ON `indicadores` (`cliente_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.idx_indicadores_cliente
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'idx_indicadores_cliente');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicadores_cliente` ON `indicadores` (`cliente_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.idx_indicadores_deleted_at
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'idx_indicadores_deleted_at');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicadores_deleted_at` ON `indicadores` (`deleted_at`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.idx_indicadores_departamento
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'idx_indicadores_departamento');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicadores_departamento` ON `indicadores` (`departamento_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.idx_indicadores_setor
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'idx_indicadores_setor');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicadores_setor` ON `indicadores` (`setor_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: indicadores.idx_indicadores_unidade_medida
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'indicadores' AND index_name = 'idx_indicadores_unidade_medida');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_indicadores_unidade_medida` ON `indicadores` (`unidade_medida_id`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: setores.idx_setores_departamento_ativo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'setores' AND index_name = 'idx_setores_departamento_ativo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_setores_departamento_ativo` ON `setores` (`departamento_id`,`ativo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: unidades_medida.idx_unidades_medida_ativo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND index_name = 'idx_unidades_medida_ativo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_unidades_medida_ativo` ON `unidades_medida` (`ativo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: unidades_medida.idx_unidades_medida_tipo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND index_name = 'idx_unidades_medida_tipo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE INDEX `idx_unidades_medida_tipo` ON `unidades_medida` (`tipo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- Indice ausente: unidades_medida.uq_unidades_medida_nome_tipo
SET @exists_idx := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'unidades_medida' AND index_name = 'uq_unidades_medida_nome_tipo');
SET @sql_idx := IF(@exists_idx = 0, 'CREATE UNIQUE INDEX `uq_unidades_medida_nome_tipo` ON `unidades_medida` (`nome`,`tipo`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx; EXECUTE stmt_idx; DEALLOCATE PREPARE stmt_idx;

-- FK ausente: fk_auditoria_relatorios_auditoria
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_auditoria_relatorios_auditoria');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `auditoria_relatorios` ADD CONSTRAINT `fk_auditoria_relatorios_auditoria` FOREIGN KEY (`auditoria_id`) REFERENCES `auditorias` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_auditorias_cliente
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_auditorias_cliente');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `auditorias` ADD CONSTRAINT `fk_auditorias_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_auditorias_responsavel
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_auditorias_responsavel');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `auditorias` ADD CONSTRAINT `fk_auditorias_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `colaboradores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_auditorias_setor
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_auditorias_setor');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `auditorias` ADD CONSTRAINT `fk_auditorias_setor` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_avaliacao_cliente
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_avaliacao_cliente');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `avaliacoes` ADD CONSTRAINT `fk_avaliacao_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_avaliacoes_created_by_user
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_avaliacoes_created_by_user');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `avaliacoes` ADD CONSTRAINT `fk_avaliacoes_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `usuarios` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicador_eventos_cliente
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicador_eventos_cliente');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicador_eventos` ADD CONSTRAINT `fk_indicador_eventos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicador_eventos_indicador
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicador_eventos_indicador');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicador_eventos` ADD CONSTRAINT `fk_indicador_eventos_indicador` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicador_responsavel_colaborador
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicador_responsavel_colaborador');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicador_responsavel` ADD CONSTRAINT `fk_indicador_responsavel_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicador_responsavel_indicador
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicador_responsavel_indicador');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicador_responsavel` ADD CONSTRAINT `fk_indicador_responsavel_indicador` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicadores_cliente
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicadores_cliente');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicadores` ADD CONSTRAINT `fk_indicadores_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicadores_departamento
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicadores_departamento');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicadores` ADD CONSTRAINT `fk_indicadores_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicadores_setor
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicadores_setor');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicadores` ADD CONSTRAINT `fk_indicadores_setor` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- FK ausente: fk_indicadores_unidade_medida
SET @exists_fk := (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_indicadores_unidade_medida');
SET @sql_fk := IF(@exists_fk = 0, 'ALTER TABLE `indicadores` ADD CONSTRAINT `fk_indicadores_unidade_medida` FOREIGN KEY (`unidade_medida_id`) REFERENCES `unidades_medida` (`id`)', 'SELECT 1');
PREPARE stmt_fk FROM @sql_fk; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

COMMIT;

-- Seed essencial de dados (unidades de medida)
INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Monetaria', 'R$', 'monetaria', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = 'R$' AND `tipo` = 'monetaria');

INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Percentual', '%', 'percentual', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = '%' AND `tipo` = 'percentual');

INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Inteiro', 'un', 'inteiro', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = 'un' AND `tipo` = 'inteiro');

INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Volume', 'L', 'volume', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = 'L' AND `tipo` = 'volume');

INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Peso', 'kg', 'peso', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = 'kg' AND `tipo` = 'peso');

INSERT INTO `unidades_medida` (`nome`,`simbolo`,`tipo`,`fator_conversao_base`,`ativo`)
SELECT 'Tempo', 'h', 'tempo', 1.00000000, 1
WHERE NOT EXISTS (SELECT 1 FROM `unidades_medida` WHERE `simbolo` = 'h' AND `tipo` = 'tempo');

-- Verificacoes rapidas pos-aplicacao
SELECT 'indicador_eventos_exists' AS check_name, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'indicador_eventos';
SELECT 'indicador_responsavel_exists' AS check_name, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'indicador_responsavel';
SELECT 'unidades_medida_exists' AS check_name, COUNT(*) AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'unidades_medida';
SELECT 'unidades_medida_count' AS check_name, COUNT(*) AS total FROM `unidades_medida`;

