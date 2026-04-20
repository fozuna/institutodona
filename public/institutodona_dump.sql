-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 17/04/2026 às 14:22
-- Versão do servidor: 8.4.7-7
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `institutodona`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacao_arquivos`
--

CREATE TABLE `aplicacao_arquivos` (
  `id` int NOT NULL,
  `aplicacao_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `arquivo_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` int NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacao_colaboradores`
--

CREATE TABLE `aplicacao_colaboradores` (
  `aplicacao_id` int NOT NULL,
  `colaborador_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aplicacao_colaboradores`
--

INSERT INTO `aplicacao_colaboradores` (`aplicacao_id`, `colaborador_id`) VALUES
(1, 26),
(2, 26),
(3, 39),
(4, 39),
(5, 26);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacao_funcoes`
--

CREATE TABLE `aplicacao_funcoes` (
  `aplicacao_id` int NOT NULL,
  `funcao_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacao_updates`
--

CREATE TABLE `aplicacao_updates` (
  `id` int NOT NULL,
  `aplicacao_id` int NOT NULL,
  `user_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `summary` text COLLATE utf8mb4_general_ci NOT NULL,
  `payload_json` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aplicacao_updates`
--

INSERT INTO `aplicacao_updates` (`id`, `aplicacao_id`, `user_email`, `user_nome`, `summary`, `payload_json`, `created_at`) VALUES
(1, 2, 'jpcatrinck@donaconsultorias.com.br', 'João Pedro', 'Obs: Realizada a coleta das primeiras informações. — Data prevista: — → —', '{\"antes\":{\"status\":\"A Fazer\",\"data_prevista\":null,\"consultor_id\":null,\"colabs\":[26]},\"depois\":{\"status\":\"A Fazer\",\"data_prevista\":\"\",\"consultor_id\":0,\"colabs\":[26]}}', '2026-01-16 12:41:10'),
(2, 5, 'admin@agencialester.com.br', 'Admin', 'Obs: Testes — Sem alterações de campos', '{\"antes\":{\"status\":\"Em Andamento\",\"data_prevista\":\"2026-02-10\",\"consultor_id\":0,\"colabs\":[26]},\"depois\":{\"status\":\"Em Andamento\",\"data_prevista\":\"2026-02-10\",\"consultor_id\":null,\"colabs\":[26]}}', '2026-01-16 13:26:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacoes`
--

CREATE TABLE `aplicacoes` (
  `id` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_metodologia` int NOT NULL,
  `status` enum('A Fazer','Em Andamento','Concluído','Pendente') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'A Fazer',
  `consultor_id` int DEFAULT NULL,
  `data_prevista` date DEFAULT NULL,
  `data_conclusao` date DEFAULT NULL,
  `funcao_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aplicacoes`
--

INSERT INTO `aplicacoes` (`id`, `id_cliente`, `id_metodologia`, `status`, `consultor_id`, `data_prevista`, `data_conclusao`, `funcao_id`) VALUES
(1, 1, 3, 'Concluído', NULL, NULL, NULL, NULL),
(2, 1, 2, 'A Fazer', NULL, NULL, NULL, NULL),
(5, 1, 3, 'Em Andamento', NULL, '2026-02-10', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditorias`
--

CREATE TABLE `auditorias` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `setor_id` int NOT NULL,
  `responsavel_id` int DEFAULT NULL,
  `data_auditoria` date NOT NULL,
  `nome_auditoria` varchar(180) NOT NULL DEFAULT '',
  `pergunta` varchar(500) NOT NULL,
  `objetivo` text NOT NULL,
  `referencia_esperada` varchar(255) NOT NULL,
  `status` enum('Rascunho','Agendada','Em Auditoria','Realizada') NOT NULL DEFAULT 'Rascunho',
  `avaliacao` text,
  `conformidade_pct` decimal(5,2) DEFAULT NULL,
  `semaforo` enum('vermelho','amarelo','verde') DEFAULT NULL,
  `obs` text,
  `realizada_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `lock_version` int NOT NULL DEFAULT '1',
  `deleted_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `auditorias`
--

INSERT INTO `auditorias` (`id`, `cliente_id`, `setor_id`, `responsavel_id`, `data_auditoria`, `nome_auditoria`, `pergunta`, `objetivo`, `referencia_esperada`, `status`, `avaliacao`, `conformidade_pct`, `semaforo`, `obs`, `realizada_at`, `created_by`, `updated_by`, `lock_version`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 6, 15, NULL, '2026-03-17', 'PROCESSO DE COLHEITA - GUANANDI', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Alex Afonso Dona', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', 'Realizada', 'Conforme: 5 | Não conforme: 2 | N/A: 3', 71.43, 'vermelho', NULL, '2026-03-28 19:05:34', 5, 5, 1, 5, '2026-03-28 19:04:07', '2026-03-29 22:47:56', '2026-03-29 22:47:56'),
(2, 6, 16, NULL, '2026-03-17', 'PROCESSO DE ARRASTE - GUANANDI', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'ALEX AFONSO DONA', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', 'Realizada', 'Conforme: 6 | Não conforme: 6 | N/A: 0', 50.00, 'vermelho', NULL, '2026-03-28 19:29:00', 5, 5, 1, 5, '2026-03-28 19:23:03', '2026-03-29 22:42:26', '2026-03-29 22:42:26'),
(3, 6, 17, NULL, '2026-03-17', 'PROCESSO DE PICAGEM - GUANANDI', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'ALEX AFONSO DONA', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-28 19:36:40', 5, 5, 1, 5, '2026-03-28 19:35:17', '2026-03-29 22:46:18', '2026-03-29 22:46:18'),
(4, 6, 18, NULL, '2026-03-17', 'PROCESSO DE CARREGAMENTO - GUANANDI', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'ALEX AFONSO DONA', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-28 19:42:29', 5, 5, 1, 5, '2026-03-28 19:40:36', '2026-03-29 22:44:35', '2026-03-29 22:44:35'),
(5, 6, 19, NULL, '2026-03-17', 'AUDITORIA DE 5S - GUANANDI', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 0 | Não conforme: 5 | N/A: 0', 0.00, 'vermelho', NULL, '2026-03-28 20:40:02', 5, 5, 1, 5, '2026-03-28 20:00:55', '2026-03-29 22:42:21', '2026-03-29 22:42:21'),
(6, 6, 16, NULL, '2026-03-29', 'PROCESSO DE COLHEITA - GUANANDI', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Alex Afonso Dona', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', 'Rascunho', NULL, NULL, NULL, NULL, NULL, 5, 5, 1, 5, '2026-03-29 21:37:50', '2026-03-29 21:39:27', '2026-03-29 21:39:27'),
(7, 6, 19, NULL, '2026-03-18', 'AUDITORIA DE 5S - APARECIDINHA', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 0 | Não conforme: 5 | N/A: 0', 0.00, 'vermelho', NULL, '2026-03-29 21:47:48', 5, 5, 1, 5, '2026-03-29 21:45:26', '2026-03-29 22:42:15', '2026-03-29 22:42:15'),
(8, 6, 19, NULL, '2026-03-29', 'AUDITORIA DE 5S - FLOR', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 0 | Não conforme: 5 | N/A: 0', 0.00, 'vermelho', NULL, '2026-03-29 21:49:42', 5, 5, 1, 5, '2026-03-29 21:45:49', '2026-03-29 21:51:29', '2026-03-29 21:51:29'),
(9, 6, 19, NULL, '2026-03-27', 'AUDITORIA DE 5S - FLOR', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 1 | Não conforme: 4 | N/A: 0', 20.00, 'vermelho', NULL, '2026-03-29 22:25:14', 5, 5, 1, 5, '2026-03-29 21:51:25', '2026-03-29 22:40:57', '2026-03-29 22:40:57'),
(10, 6, 18, NULL, '2026-03-18', 'PROCESSO DE CARREGAMENTO - APARECIDINHA - MARÇO26', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'ALEX AFONSO DONA', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', 'Realizada', 'Conforme: 0 | Não conforme: 3 | N/A: 7', 0.00, 'vermelho', NULL, '2026-03-30 00:06:15', 5, 5, 1, NULL, '2026-03-29 22:29:06', '2026-03-30 00:06:15', NULL),
(11, 6, 18, NULL, '2026-03-27', 'PROCESSO DE CARREGAMENTO - FLOR - MARÇO26', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'ALEX AFONSO DONA', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', 'Realizada', 'Conforme: 5 | Não conforme: 5 | N/A: 0', 50.00, 'vermelho', NULL, '2026-03-30 00:35:44', 5, 5, 1, NULL, '2026-03-29 22:29:14', '2026-03-30 00:35:44', NULL),
(12, 6, 19, NULL, '2026-03-27', 'AUDITORIA DE 5S - FLOR - MARÇO26', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 1 | Não conforme: 4 | N/A: 0', 20.00, 'vermelho', NULL, '2026-03-29 23:21:11', 5, 5, 1, NULL, '2026-03-29 22:40:39', '2026-03-29 23:21:11', NULL),
(13, 6, 19, NULL, '2026-03-18', 'AUDITORIA DE 5S - APARECIDINHA - MARÇO26 TESTE', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 0 | Não conforme: 5 | N/A: 0', 0.00, 'vermelho', NULL, '2026-03-29 23:25:30', 5, 5, 1, NULL, '2026-03-29 22:41:39', '2026-03-30 15:04:30', NULL),
(14, 6, 19, NULL, '2026-03-17', 'AUDITORIA DE 5S - GUANANDI - MARÇO26', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Realizada', 'Conforme: 0 | Não conforme: 5 | N/A: 0', 0.00, 'vermelho', NULL, '2026-03-29 23:29:21', 5, 5, 1, NULL, '2026-03-29 22:41:58', '2026-03-29 23:29:21', NULL),
(15, 6, 18, NULL, '2026-03-17', 'PROCESSO DE CARREGAMENTO -  GUANANDI - MARÇO26', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'ALEX AFONSO DONA', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-29 23:51:14', 5, 5, 1, NULL, '2026-03-29 22:44:12', '2026-03-29 23:51:14', NULL),
(16, 6, 17, NULL, '2026-03-27', 'PROCESSO DE PICAGEM - FLOR - MARÇO26', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'ALEX AFONSO DONA', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-30 00:32:00', 5, 5, 1, NULL, '2026-03-29 22:45:33', '2026-03-30 00:32:00', NULL),
(17, 6, 17, NULL, '2026-03-18', 'PROCESSO DE PICAGEM - APARECIDINHA - MARÇO26', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'ALEX AFONSO DONA', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-30 00:03:44', 5, 5, 1, NULL, '2026-03-29 22:46:06', '2026-03-30 00:03:44', NULL),
(18, 6, 17, NULL, '2026-03-17', 'PROCESSO DE PICAGEM -  GUANANDI - MARÇO26', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'ALEX AFONSO DONA', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', 'Realizada', 'Conforme: 7 | Não conforme: 3 | N/A: 0', 70.00, 'vermelho', NULL, '2026-03-29 23:49:26', 5, 5, 1, NULL, '2026-03-29 22:47:06', '2026-03-29 23:49:26', NULL),
(19, 6, 15, NULL, '2026-03-27', 'PROCESSO DE COLHEITA - FLOR - MARÇO26', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Alex Afonso Dona', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', 'Realizada', 'Conforme: 6 | Não conforme: 4 | N/A: 0', 60.00, 'vermelho', NULL, '2026-03-30 00:10:28', 5, 5, 1, NULL, '2026-03-29 22:47:29', '2026-03-30 00:10:28', NULL),
(20, 6, 15, NULL, '2026-03-18', 'PROCESSO DE COLHEITA - APARECIDINHA - MARÇO26', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Alex Afonso Dona', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', 'Realizada', 'Conforme: 5 | Não conforme: 5 | N/A: 0', 50.00, 'vermelho', NULL, '2026-03-29 23:59:12', 5, 5, 1, NULL, '2026-03-29 22:47:38', '2026-03-29 23:59:12', NULL),
(21, 6, 15, NULL, '2026-03-17', 'PROCESSO DE COLHEITA -  GUANANDI - MARÇO26', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Alex Afonso Dona', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', 'Realizada', 'Conforme: 6 | Não conforme: 1 | N/A: 3', 85.71, 'amarelo', NULL, '2026-03-29 23:42:25', 5, 5, 1, NULL, '2026-03-29 22:47:47', '2026-03-29 23:42:25', NULL),
(22, 6, 16, NULL, '2026-03-17', 'AUDITORIA DE ARRASTE -  GUANANDI', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'ALEX AFONSO DONA', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', 'Agendada', NULL, NULL, NULL, NULL, NULL, 5, 5, 1, 5, '2026-03-29 22:50:04', '2026-03-29 22:54:08', '2026-03-29 22:54:08'),
(23, 6, 16, NULL, '2026-03-27', 'AUDITORIA DE ARRASTE - FLOR - MARÇO26', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'ALEX AFONSO DONA', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', 'Realizada', 'Conforme: 10 | Não conforme: 2 | N/A: 0', 83.33, 'amarelo', NULL, '2026-03-30 00:16:21', 5, 5, 1, NULL, '2026-03-29 22:58:49', '2026-03-30 00:16:21', NULL),
(24, 6, 16, NULL, '2026-03-18', 'AUDITORIA DE ARRASTE - APARECIDINHA - MARÇO26', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'ALEX AFONSO DONA', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', 'Realizada', 'Conforme: 3 | Não conforme: 1 | N/A: 8', 75.00, 'amarelo', NULL, '2026-03-30 00:01:02', 5, 5, 1, NULL, '2026-03-29 22:59:23', '2026-03-30 00:01:02', NULL),
(25, 6, 16, NULL, '2026-03-17', 'AUDITORIA DE ARRASTE - GUANANDI - MARÇO26', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'ALEX AFONSO DONA', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', 'Realizada', 'Conforme: 6 | Não conforme: 6 | N/A: 0', 50.00, 'vermelho', NULL, '2026-03-29 23:46:44', 5, 5, 1, NULL, '2026-03-29 22:59:44', '2026-03-29 23:46:44', NULL),
(26, 6, 19, NULL, '2026-03-30', 'AUDITORIA DE 5S - APARECIDINHA - ABRIL', '1º S - Existe um processo de descarte correto na área?', 'ALEX AFONSO DONA', 'Área sem a presença de itens a serem descartados.', 'Em Auditoria', NULL, NULL, NULL, NULL, NULL, 5, 5, 1, 5, '2026-03-30 15:03:56', '2026-03-30 17:35:02', '2026-03-30 17:35:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_arquivos`
--

CREATE TABLE `auditoria_arquivos` (
  `id` int NOT NULL,
  `auditoria_id` int NOT NULL,
  `questao_id` int NOT NULL,
  `path` varchar(255) NOT NULL,
  `compressed_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int NOT NULL,
  `sha256` char(64) NOT NULL,
  `thumb_path` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `auditoria_arquivos`
--

INSERT INTO `auditoria_arquivos` (`id`, `auditoria_id`, `questao_id`, `path`, `compressed_path`, `original_name`, `mime`, `size`, `sha256`, `thumb_path`, `created_by`, `created_at`) VALUES
(1, 2, 14, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/f_69c82aca3cdcb1.86761560_WhatsApp_Image_2026-03-25_at_19.18.51_12_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/f_69c82aca3cdcb1.86761560_WhatsApp_Image_2026-03-25_at_19.18.51_12_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_12_.jpeg', 'image/jpeg', 162141, '09f559a0d66265da8b8cff0e4d7e02a539804e42a48a677c7954b3610ee0a870', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/thumb_69c82aca3e8bd.jpg', 5, '2026-03-28 19:23:54'),
(2, 2, 14, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/f_69c82aca4c8264.62607906_WhatsApp_Image_2026-03-25_at_19.18.51_13_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/f_69c82aca4c8264.62607906_WhatsApp_Image_2026-03-25_at_19.18.51_13_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_13_.jpeg', 'image/jpeg', 190616, '1bf01bb773986acab419f322dfbb5f80de732cd02fc7bb7ec1d20bf773cdcbab', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/14/thumb_69c82aca4e1f4.jpg', 5, '2026-03-28 19:23:54'),
(3, 2, 20, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/20/f_69c82b3f168f80.61485274_1.jpg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/20/f_69c82b3f168f80.61485274_1.jpg.gz', '1.jpg', 'image/jpeg', 163129, '37e285b783957ecd5fdfa66e64d933fde2c452a99d8051abf8be9ceaffc95c83', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/20/thumb_69c82b3f17b89.jpg', 5, '2026-03-28 19:25:51'),
(4, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8910d17.92907444_WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8910d17.92907444_WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', 'image/jpeg', 143152, 'b937597800a97d9bb6c64883ef2b85a6b9026eedce61ba9a4ffe083697ec6db8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf892602.jpg', 5, '2026-03-28 19:28:56'),
(5, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf89c9c18.19427298_WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf89c9c18.19427298_WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', 'image/jpeg', 154331, 'b1116fa7ffd124620ef0471666e533d400871cea7dfb2b77d94a629a00719f30', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf89e027.jpg', 5, '2026-03-28 19:28:56'),
(6, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8a9ba48.76908458_WhatsApp_Image_2026-03-25_at_19.18.51_27_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8a9ba48.76908458_WhatsApp_Image_2026-03-25_at_19.18.51_27_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_27_.jpeg', 'image/jpeg', 235593, '8decdefaa5de04acf2a644458c077b199752814c856a46da963ebef191b3a66f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf8ab82a.jpg', 5, '2026-03-28 19:28:56'),
(7, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8b47220.93686164_WhatsApp_Image_2026-03-25_at_19.18.51_28_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8b47220.93686164_WhatsApp_Image_2026-03-25_at_19.18.51_28_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_28_.jpeg', 'image/jpeg', 156924, '81182f108992ffd50839e3edfc9112be7c3faead9741986c1f43fae847d87665', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf8b5b39.jpg', 5, '2026-03-28 19:28:56'),
(8, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8be64d7.94056129_WhatsApp_Image_2026-03-25_at_19.18.51_29_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8be64d7.94056129_WhatsApp_Image_2026-03-25_at_19.18.51_29_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_29_.jpeg', 'image/jpeg', 193827, '2bf61eb7e3a60f3c9e59561db099d1e20fa7254df990e761b596e9738b11f9bf', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf8bfd56.jpg', 5, '2026-03-28 19:28:56'),
(9, 2, 22, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8c91393.43745937_WhatsApp_Image_2026-03-25_at_19.18.51_30_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/f_69c82bf8c91393.43745937_WhatsApp_Image_2026-03-25_at_19.18.51_30_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_30_.jpeg', 'image/jpeg', 143516, 'cd2c93acfbf7ed152a9b748c3f05319b4f5cc2d1d55456ef0d46a24a6f9fae09', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/2/22/thumb_69c82bf8ca223.jpg', 5, '2026-03-28 19:28:56'),
(10, 3, 27, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da63eade5.39292215_WhatsApp_Image_2026-03-25_at_19.18.51_34_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da63eade5.39292215_WhatsApp_Image_2026-03-25_at_19.18.51_34_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_34_.jpeg', 'image/jpeg', 226677, 'ae846d81b6bc9e9bf2c0427dd089f8bafc16c927070b2e3cc6517584f6d92cf4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/thumb_69c82da640a16.jpg', 5, '2026-03-28 19:36:06'),
(11, 3, 27, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da64cc785.20200259_WhatsApp_Image_2026-03-25_at_19.18.51_35_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da64cc785.20200259_WhatsApp_Image_2026-03-25_at_19.18.51_35_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_35_.jpeg', 'image/jpeg', 209451, '0c1e26ce0e253021e03d3508dea6454757ceed866317f1bf8681aa0c0bccc47f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/thumb_69c82da64e659.jpg', 5, '2026-03-28 19:36:06'),
(12, 3, 27, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da6613414.47453111_WhatsApp_Video_2026-03-25_at_19.18.51_13_.mp4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/27/f_69c82da6613414.47453111_WhatsApp_Video_2026-03-25_at_19.18.51_13_.mp4.gz', 'WhatsApp_Video_2026-03-25_at_19.18.51_13_.mp4', 'video/mp4', 4173402, '0988a4b4c6c4dd957ec22424290f19f9959291c8f5ee662d9382e22fa7f27ceb', NULL, 5, '2026-03-28 19:36:06'),
(13, 3, 28, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/28/f_69c82db45289d1.24996560_WhatsApp_Image_2026-03-25_at_19.18.51_16_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/28/f_69c82db45289d1.24996560_WhatsApp_Image_2026-03-25_at_19.18.51_16_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_16_.jpeg', 'image/jpeg', 215389, '1bdc4b4dd046ec83f34860c819b8cca1f6fc5ebbfb6fa93ce0b029c2c312a1cd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/3/28/thumb_69c82db455192.jpg', 5, '2026-03-28 19:36:20'),
(14, 4, 34, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/f_69c82f048e79c9.88090164_estrada.jpg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/f_69c82f048e79c9.88090164_estrada.jpg.gz', 'estrada.jpg', 'image/jpeg', 73417, 'd5b3b36943ba53ef4457899f20c867634d09369cc9b7cc9bd1c919b1bff331ff', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/thumb_69c82f048fa81.jpg', 5, '2026-03-28 19:41:56'),
(15, 4, 34, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/f_69c82f04989ae3.85322666_WhatsApp_Image_2026-03-26_at_09.14.38.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/f_69c82f04989ae3.85322666_WhatsApp_Image_2026-03-26_at_09.14.38.jpeg.gz', 'WhatsApp_Image_2026-03-26_at_09.14.38.jpeg', 'image/jpeg', 157285, 'f1253863b55e921427085c6903bc9fc54ec6020e5b337303717ddcd689318389', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/4/34/thumb_69c82f049a7cf.jpg', 5, '2026-03-28 19:41:56'),
(16, 5, 43, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c26e7db02.49592787_WhatsApp_Image_2026-03-25_at_19.18.41_2_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c26e7db02.49592787_WhatsApp_Image_2026-03-25_at_19.18.41_2_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41_2_.jpeg', 'image/jpeg', 482606, '324edc6be35dc6d94031fa7222a477e6fa4580559ba413af8c501d632bb0fd23', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/thumb_69c83c26ebb46.jpg', 5, '2026-03-28 20:37:59'),
(17, 5, 43, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27063531.55020152_WhatsApp_Image_2026-03-25_at_19.18.41_3_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27063531.55020152_WhatsApp_Image_2026-03-25_at_19.18.41_3_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41_3_.jpeg', 'image/jpeg', 391545, '0f611d943d99e9c9de96ccde53552c272a7ee66b0656cb8f21f6a994e325225f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/thumb_69c83c27093ce.jpg', 5, '2026-03-28 20:37:59'),
(18, 5, 43, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27168601.89630813_WhatsApp_Image_2026-03-25_at_19.18.41_4_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27168601.89630813_WhatsApp_Image_2026-03-25_at_19.18.41_4_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41_4_.jpeg', 'image/jpeg', 465070, '22a0aef4c4b81d3d407cf40beb2f797b6e6682ad31af47b4ce73a6742bb8e182', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/thumb_69c83c2719f4a.jpg', 5, '2026-03-28 20:37:59'),
(19, 5, 43, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27266a34.76335594_WhatsApp_Image_2026-03-25_at_19.18.41_5_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/f_69c83c27266a34.76335594_WhatsApp_Image_2026-03-25_at_19.18.41_5_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41_5_.jpeg', 'image/jpeg', 465592, '4251d53ab580da61ad915310ccea07a11821926c326a965204bac0be73c7dcb3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/43/thumb_69c83c272aa50.jpg', 5, '2026-03-28 20:37:59'),
(20, 5, 44, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d435a38.52251940_WhatsApp_Image_2026-03-25_at_19.18.50_1_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d435a38.52251940_WhatsApp_Image_2026-03-25_at_19.18.50_1_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_1_.jpeg', 'image/jpeg', 196073, '54198acefa7306065394a2d676639163dd4ca5b6278606094c683211d13be83c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/thumb_69c83c3d457a3.jpg', 5, '2026-03-28 20:38:21'),
(21, 5, 44, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d536ff0.09162733_WhatsApp_Image_2026-03-25_at_19.18.51_8_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d536ff0.09162733_WhatsApp_Image_2026-03-25_at_19.18.51_8_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_8_.jpeg', 'image/jpeg', 150587, 'b95619bc016003c1637d0f45c02734086e96b5cc8ee53a77b660f7ca14a8c9bb', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/thumb_69c83c3d54b4e.jpg', 5, '2026-03-28 20:38:21'),
(22, 5, 44, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d5e7a80.65885684_WhatsApp_Image_2026-03-25_at_19.18.51_9_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d5e7a80.65885684_WhatsApp_Image_2026-03-25_at_19.18.51_9_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_9_.jpeg', 'image/jpeg', 156960, '8f32bc41868d52f2eca5d0caa218f2ebc84ce6db1dd0a4ae3c4f9c8665f1939a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/thumb_69c83c3d5f9d1.jpg', 5, '2026-03-28 20:38:21'),
(23, 5, 44, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d681918.65171972_WhatsApp_Image_2026-03-25_at_19.18.51_10_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/f_69c83c3d681918.65171972_WhatsApp_Image_2026-03-25_at_19.18.51_10_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_10_.jpeg', 'image/jpeg', 140178, 'f367ed696e15d15d48b7746ea595351a14ab52d5598886f687e2f38af12824b3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/44/thumb_69c83c3d69623.jpg', 5, '2026-03-28 20:38:21'),
(24, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c549d5064.54300382_WhatsApp_Image_2026-03-25_at_19.18.36_1_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c549d5064.54300382_WhatsApp_Image_2026-03-25_at_19.18.36_1_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.36_1_.jpeg', 'image/jpeg', 202799, 'cfe28b8293f93c4ed9ba6c6791b824eb4f1c134ea3473343ca804dbad7808520', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c549f1f2.jpg', 5, '2026-03-28 20:38:44'),
(25, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54ab6de6.71374468_WhatsApp_Image_2026-03-25_at_19.18.36_2_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54ab6de6.71374468_WhatsApp_Image_2026-03-25_at_19.18.36_2_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.36_2_.jpeg', 'image/jpeg', 201058, 'a14b3778c536ec73053bba360fb9a0ea0449e1bccf244f3218336d98782d15c6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54ad348.jpg', 5, '2026-03-28 20:38:44'),
(26, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54bb9f58.20047688_WhatsApp_Image_2026-03-25_at_19.18.36_3_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54bb9f58.20047688_WhatsApp_Image_2026-03-25_at_19.18.36_3_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.36_3_.jpeg', 'image/jpeg', 207183, '95d405d18883c810166968b6fad655011689d3e892beb5469579e6fe60db0c65', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54bd879.jpg', 5, '2026-03-28 20:38:44'),
(27, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54c906b5.08400849_WhatsApp_Image_2026-03-25_at_19.18.36_4_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54c906b5.08400849_WhatsApp_Image_2026-03-25_at_19.18.36_4_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.36_4_.jpeg', 'image/jpeg', 188392, '0985714656735134ba58a9871a3e76f2bd4b06096551579f65f4980b6930d16d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54caf3e.jpg', 5, '2026-03-28 20:38:44'),
(28, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54d68536.39193428_WhatsApp_Image_2026-03-25_at_19.18.36.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54d68536.39193428_WhatsApp_Image_2026-03-25_at_19.18.36.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.36.jpeg', 'image/jpeg', 207496, '81fbd54ec424ac7b9eac0bf64ee97118cda3a6872af6371360c7e5429ac3d3a6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54d84fc.jpg', 5, '2026-03-28 20:38:44'),
(29, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54e2eab1.78140181_WhatsApp_Image_2026-03-25_at_19.18.41_1_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54e2eab1.78140181_WhatsApp_Image_2026-03-25_at_19.18.41_1_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41_1_.jpeg', 'image/jpeg', 245220, '095e837e2401c9f17c40b6ef2cafd57b2a08ade231a738dc9fa86859ce5e975b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54e4b6b.jpg', 5, '2026-03-28 20:38:44'),
(30, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54ef3070.92307249_WhatsApp_Image_2026-03-25_at_19.18.41.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c54ef3070.92307249_WhatsApp_Image_2026-03-25_at_19.18.41.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.41.jpeg', 'image/jpeg', 222718, '6d8f1241a6775ed6d9937f47b66bac20abcc6e4c8ed323ddc75aaa599d305089', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c54f1235.jpg', 5, '2026-03-28 20:38:45'),
(31, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c550759a1.13839658_WhatsApp_Image_2026-03-25_at_19.18.51_2_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c550759a1.13839658_WhatsApp_Image_2026-03-25_at_19.18.51_2_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_2_.jpeg', 'image/jpeg', 117588, 'e14972267780c41263a31a6036ce8d8dcf07860afc904056989b1da31bb550dc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c5508609.jpg', 5, '2026-03-28 20:38:45'),
(32, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c55124031.58700648_WhatsApp_Image_2026-03-25_at_19.18.51_5_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c55124031.58700648_WhatsApp_Image_2026-03-25_at_19.18.51_5_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_5_.jpeg', 'image/jpeg', 114049, '7e97a528fcde69a943cc54c73d7bbaf202dd8b039e60756fc959f2c4646edfb1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c551371d.jpg', 5, '2026-03-28 20:38:45'),
(33, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c551e0264.19487109_WhatsApp_Image_2026-03-25_at_19.18.51_6_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c551e0264.19487109_WhatsApp_Image_2026-03-25_at_19.18.51_6_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_6_.jpeg', 'image/jpeg', 84928, '59f62290e6da30c992e048ef7595b2594c8ef4b749c902c870495a058fc67e19', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c551f05e.jpg', 5, '2026-03-28 20:38:45'),
(34, 5, 45, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c55276598.12566236_WhatsApp_Image_2026-03-25_at_19.18.51_7_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/f_69c83c55276598.12566236_WhatsApp_Image_2026-03-25_at_19.18.51_7_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_7_.jpeg', 'image/jpeg', 91846, 'b2a985f3863699561067ba7bf47751d92e828bcddfdf8c7e9940e0e3b4f5a21f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/45/thumb_69c83c5528363.jpg', 5, '2026-03-28 20:38:45'),
(35, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78b7c487.40382091_WhatsApp_Image_2026-03-25_at_19.18.50_2_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78b7c487.40382091_WhatsApp_Image_2026-03-25_at_19.18.50_2_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_2_.jpeg', 'image/jpeg', 264861, '61dd17e97dd4bf33fb436f0d5ecb589d0592f99abd7879ea5c5e6790b2ad9138', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c78b9a21.jpg', 5, '2026-03-28 20:39:20'),
(36, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78c68571.41167393_WhatsApp_Image_2026-03-25_at_19.18.50_3_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78c68571.41167393_WhatsApp_Image_2026-03-25_at_19.18.50_3_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_3_.jpeg', 'image/jpeg', 196114, 'e4f88c3b94a0b3df99dd80abd7a7288d6347141194143585bb3916a23e4e348b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c78c810d.jpg', 5, '2026-03-28 20:39:20'),
(37, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78d31c61.66487119_WhatsApp_Image_2026-03-25_at_19.18.50_4_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78d31c61.66487119_WhatsApp_Image_2026-03-25_at_19.18.50_4_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_4_.jpeg', 'image/jpeg', 221826, 'f74d6a50eb8b1d70f3520c80bd7e36bab2e363346d36afd10288ea479db60ab8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c78d4ec7.jpg', 5, '2026-03-28 20:39:20'),
(38, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78e10a94.92045170_WhatsApp_Image_2026-03-25_at_19.18.50_5_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78e10a94.92045170_WhatsApp_Image_2026-03-25_at_19.18.50_5_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_5_.jpeg', 'image/jpeg', 191127, '99ddc962ea1c3f4421b4d838ceddd9b0ced35faa61ca639c4867ecb9da758bd3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c78e2bd9.jpg', 5, '2026-03-28 20:39:20'),
(39, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78ec25c6.09387584_WhatsApp_Image_2026-03-25_at_19.18.50_6_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c78ec25c6.09387584_WhatsApp_Image_2026-03-25_at_19.18.50_6_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_6_.jpeg', 'image/jpeg', 181383, '46ae86c40fde31116ae605af7cdc29990d775522749ac97ac8be9b8a8f8ca352', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c78edc79.jpg', 5, '2026-03-28 20:39:21'),
(40, 5, 46, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c7904c528.49858757_WhatsApp_Image_2026-03-25_at_19.18.51_1_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/f_69c83c7904c528.49858757_WhatsApp_Image_2026-03-25_at_19.18.51_1_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.51_1_.jpeg', 'image/jpeg', 175905, '5070a3ba3497504627f4536dc2154c5ca7dc993031e465bfdab0486047583499', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/46/thumb_69c83c7906400.jpg', 5, '2026-03-28 20:39:21'),
(41, 5, 47, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/f_69c83c88970b78.88367820_WhatsApp_Image_2026-03-25_at_19.18.50_7_.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/f_69c83c88970b78.88367820_WhatsApp_Image_2026-03-25_at_19.18.50_7_.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50_7_.jpeg', 'image/jpeg', 373446, 'aaf08c4d0fb10bdbf5c322fb42a8645f49c4d46e69101d36059cf579ce12b0cd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/thumb_69c83c8899e7c.jpg', 5, '2026-03-28 20:39:36'),
(42, 5, 47, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/f_69c83c88a8cf07.65446393_WhatsApp_Image_2026-03-25_at_19.18.50.jpeg', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/f_69c83c88a8cf07.65446393_WhatsApp_Image_2026-03-25_at_19.18.50.jpeg.gz', 'WhatsApp_Image_2026-03-25_at_19.18.50.jpeg', 'image/jpeg', 440436, 'dce2e75a56afdbc715cd51b075b9e1675daa79434af97395ed62404c0e829f5a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/5/47/thumb_69c83c88ace1c.jpg', 5, '2026-03-28 20:39:36'),
(43, 7, 103, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/f_69c99da7230e79.13158113_WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', 'image/jpeg', 109116, 'ca738fae8dbbce4b1e5bb985e100afcbe6b4de0950ac3d2645f3d38efa0d2318', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/thumb_69c99da72328a.jpg', 5, '2026-03-29 21:46:15'),
(44, 7, 103, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/f_69c99da73190f1.92332593_WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', 'image/jpeg', 154004, 'c63ff108b3be9fe404d97634022dab674d878589491758081eec6e2406cdad4d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/thumb_69c99da731a12.jpg', 5, '2026-03-29 21:46:15'),
(45, 7, 103, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/f_69c99da73bd433.50462668_WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', 'image/jpeg', 159986, '85a15751866824452ba266bdab7f0960ee4b60c37909c8ed26faadce6516353d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/thumb_69c99da73bdf1.jpg', 5, '2026-03-29 21:46:15'),
(46, 7, 103, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/f_69c99da747c1e8.57926364_WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', 'image/jpeg', 151340, '637166ead9f6a05cdf1d38b2bfc05a2e891e080fbfb94f33b84d0cac8328dda0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/thumb_69c99da747ce7.jpg', 5, '2026-03-29 21:46:15'),
(47, 7, 103, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/f_69c99da7515af5.16167611_WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', 'image/jpeg', 128880, '72b1c1e59937cb48119a0c6243546724d6393591c675d69d5a6a349c4866f50a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/103/thumb_69c99da75164b.jpg', 5, '2026-03-29 21:46:15'),
(48, 7, 104, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/f_69c99dba8c7294.72434404_WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', 'image/jpeg', 200576, 'c095a6c48f0f4b88cf7f14bd7621c53f25ebbd58c4f04eba48da2c7aa8a8eb0a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/thumb_69c99dba8c90f.jpg', 5, '2026-03-29 21:46:34'),
(49, 7, 104, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/f_69c99dba9857b2.18516844_WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', 'image/jpeg', 158467, '023d6e5afbf7c837a31e7c451e83cf64db1ee46014f11bddd9f46b3cde70e73c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/thumb_69c99dba98634.jpg', 5, '2026-03-29 21:46:34'),
(50, 7, 104, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/f_69c99dbaa25dd8.99995881_WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', 'image/jpeg', 144627, 'a2aff77ff82d421211f49696c68e10efe09e6c7e6f76523d0f6fa2a7f66ebf62', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/104/thumb_69c99dbaa2701.jpg', 5, '2026-03-29 21:46:34'),
(51, 7, 105, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/f_69c99dca265003.55774182_WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', 'image/jpeg', 159039, 'cb5a6f50243eb44a2374c693299d72d6039fac9ec5e35245508619da009ee2b4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/thumb_69c99dca265ce.jpg', 5, '2026-03-29 21:46:50'),
(52, 7, 105, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/f_69c99dca327157.42193456_WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', 'image/jpeg', 94329, 'e421d88c8c42e41d10932c2e4adb3d42768fe8bcf93e66fd19172d167d677116', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/thumb_69c99dca32817.jpg', 5, '2026-03-29 21:46:50'),
(53, 7, 105, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/f_69c99dca3dc413.97632952_WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', 'image/jpeg', 105701, 'cb3efb12a2282232f502384c04e08e0fc1dbe90214ed66385ca6a76c4f3e485a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/thumb_69c99dca3dcf0.jpg', 5, '2026-03-29 21:46:50'),
(54, 7, 105, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/f_69c99dca490703.48337474_WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', 'image/jpeg', 124148, 'fab3c6961f53ddc86d91b6647a39522984698edfc0411ed19f7b504d29d097d6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/105/thumb_69c99dca49169.jpg', 5, '2026-03-29 21:46:50'),
(55, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4b51813.60934052_WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', 'image/jpeg', 94692, 'a02cf6af1631e91ce5d2270acc22fad17fb9832139b685f873d003d57913baf4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4b52c4.jpg', 5, '2026-03-29 21:47:16'),
(56, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4bf4750.52658828_WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', 'image/jpeg', 129800, '211d873c1b84ae2f2afd84fe609e856323fd54c10f6eae785274dca1e8a0b7bc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4bf507.jpg', 5, '2026-03-29 21:47:16'),
(57, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4c82171.29008753_WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', 'image/jpeg', 134287, 'd397b4c16345f1eff9c41a875b52096a97e8fb3384a7d61fe311bacfea7e3e63', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4c82ba.jpg', 5, '2026-03-29 21:47:16'),
(58, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4d11d35.81304529_WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', 'image/jpeg', 166209, '58922b3a58f8bb233bd4a37f0082f8caac4b72c65c56131f591216460efac2a4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4d1267.jpg', 5, '2026-03-29 21:47:16'),
(59, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4dabc04.20287775_WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', 'image/jpeg', 167432, '43fcecf419d653717217ecfcde8d0aab4784bdb49c4e36eab3ef2e213967db9f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4dac41.jpg', 5, '2026-03-29 21:47:16'),
(60, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4e4e1f5.98793817_WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', 'image/jpeg', 179753, 'eb96efbe6a268bcc463a6348b5f80a3641d8648d0ee5e8bbcdb2d1cea9734d7c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4e4ea2.jpg', 5, '2026-03-29 21:47:16'),
(61, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de4ed7050.94719195_WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', 'image/jpeg', 150908, 'a2dd379b840403da767b1396fbbd06c0c92dc9b67cd74460268360a26d0ea27d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de4ed79e.jpg', 5, '2026-03-29 21:47:17'),
(62, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de50185c0.56166788_WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', 'image/jpeg', 248972, 'c3129fda566bde76cb775f84bcdec18aebcbc79b62a5cbc49242a2445bb5cce0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de5019a2.jpg', 5, '2026-03-29 21:47:17'),
(63, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de50b6128.06669698_WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', 'image/jpeg', 206716, 'd7005ae556d8bdde403d8f964cd5beab084bf4b94b233e86fe49c46d199423de', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de50b693.jpg', 5, '2026-03-29 21:47:17'),
(64, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de5149848.77126812_WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', 'image/jpeg', 274089, '7303ea388e12d02b79c69e4ec6badb4f3e29d97e2e8924bc00ccb9d2d0f5921c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de514a3b.jpg', 5, '2026-03-29 21:47:17'),
(65, 7, 106, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/f_69c99de520a587.31409418_WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', 'image/jpeg', 197219, 'cd4d2910b035248847d38fb0e489a055c7f8d6db382683c95bc041f6042271b6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/106/thumb_69c99de520b4e.jpg', 5, '2026-03-29 21:47:17'),
(66, 7, 107, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/107/f_69c99dfd909db9.58638799_WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', 'image/jpeg', 216893, 'd2f7a99854054e5eea12108ef0a36ed6ca7381c722c9b01ab531c19d91295c9b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/7/107/thumb_69c99dfd90aa3.jpg', 5, '2026-03-29 21:47:41'),
(67, 8, 113, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/f_69c99e250e81b4.49216046_WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', 'image/jpeg', 109116, 'ca738fae8dbbce4b1e5bb985e100afcbe6b4de0950ac3d2645f3d38efa0d2318', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/thumb_69c99e250e8e4.jpg', 5, '2026-03-29 21:48:21'),
(68, 8, 113, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/f_69c99e2519b823.82415247_WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', 'image/jpeg', 154004, 'c63ff108b3be9fe404d97634022dab674d878589491758081eec6e2406cdad4d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/thumb_69c99e2519c19.jpg', 5, '2026-03-29 21:48:21'),
(69, 8, 113, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/f_69c99e2523a305.67366881_WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', 'image/jpeg', 159986, '85a15751866824452ba266bdab7f0960ee4b60c37909c8ed26faadce6516353d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/thumb_69c99e2523b78.jpg', 5, '2026-03-29 21:48:21'),
(70, 8, 113, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/f_69c99e252c73d9.71143021_WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', 'image/jpeg', 151340, '637166ead9f6a05cdf1d38b2bfc05a2e891e080fbfb94f33b84d0cac8328dda0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/thumb_69c99e252caf3.jpg', 5, '2026-03-29 21:48:21'),
(71, 8, 113, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/f_69c99e25350796.75338843_WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', 'image/jpeg', 128880, '72b1c1e59937cb48119a0c6243546724d6393591c675d69d5a6a349c4866f50a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/113/thumb_69c99e253510b.jpg', 5, '2026-03-29 21:48:21'),
(72, 8, 114, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/f_69c99e36359608.04883391_WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', 'image/jpeg', 200576, 'c095a6c48f0f4b88cf7f14bd7621c53f25ebbd58c4f04eba48da2c7aa8a8eb0a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/thumb_69c99e3635a85.jpg', 5, '2026-03-29 21:48:38'),
(73, 8, 114, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/f_69c99e36407df6.95704466_WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', 'image/jpeg', 158467, '023d6e5afbf7c837a31e7c451e83cf64db1ee46014f11bddd9f46b3cde70e73c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/thumb_69c99e3640879.jpg', 5, '2026-03-29 21:48:38'),
(74, 8, 114, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/f_69c99e3649e000.69252223_WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', 'image/jpeg', 144627, 'a2aff77ff82d421211f49696c68e10efe09e6c7e6f76523d0f6fa2a7f66ebf62', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/114/thumb_69c99e3649e8d.jpg', 5, '2026-03-29 21:48:38'),
(75, 8, 115, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/f_69c99e49f0f4f1.47873028_WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', 'image/jpeg', 159039, 'cb5a6f50243eb44a2374c693299d72d6039fac9ec5e35245508619da009ee2b4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/thumb_69c99e49f1010.jpg', 5, '2026-03-29 21:48:58'),
(76, 8, 115, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/f_69c99e4a091da1.76112280_WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', 'image/jpeg', 94329, 'e421d88c8c42e41d10932c2e4adb3d42768fe8bcf93e66fd19172d167d677116', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/thumb_69c99e4a092b2.jpg', 5, '2026-03-29 21:48:58'),
(77, 8, 115, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/f_69c99e4a139321.46333985_WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', 'image/jpeg', 105701, 'cb3efb12a2282232f502384c04e08e0fc1dbe90214ed66385ca6a76c4f3e485a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/thumb_69c99e4a13a7a.jpg', 5, '2026-03-29 21:48:58'),
(78, 8, 115, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/f_69c99e4a1d0260.84911977_WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', 'image/jpeg', 124148, 'fab3c6961f53ddc86d91b6647a39522984698edfc0411ed19f7b504d29d097d6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/115/thumb_69c99e4a1d160.jpg', 5, '2026-03-29 21:48:58'),
(79, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5b6c9021.38579923_WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', 'image/jpeg', 94692, 'a02cf6af1631e91ce5d2270acc22fad17fb9832139b685f873d003d57913baf4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5b6ca80.jpg', 5, '2026-03-29 21:49:15'),
(80, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5b78ff08.33395596_WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', 'image/jpeg', 129800, '211d873c1b84ae2f2afd84fe609e856323fd54c10f6eae785274dca1e8a0b7bc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5b790c3.jpg', 5, '2026-03-29 21:49:15'),
(81, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5b8b9716.76058152_WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', 'image/jpeg', 134287, 'd397b4c16345f1eff9c41a875b52096a97e8fb3384a7d61fe311bacfea7e3e63', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5b8ba65.jpg', 5, '2026-03-29 21:49:15'),
(82, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5b94d723.32907356_WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', 'image/jpeg', 166209, '58922b3a58f8bb233bd4a37f0082f8caac4b72c65c56131f591216460efac2a4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5b94e13.jpg', 5, '2026-03-29 21:49:15'),
(83, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5b9ddcd6.30718052_WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', 'image/jpeg', 167432, '43fcecf419d653717217ecfcde8d0aab4784bdb49c4e36eab3ef2e213967db9f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5b9decd.jpg', 5, '2026-03-29 21:49:15'),
(84, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5ba7b115.72001867_WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', 'image/jpeg', 179753, 'eb96efbe6a268bcc463a6348b5f80a3641d8648d0ee5e8bbcdb2d1cea9734d7c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5ba7bdb.jpg', 5, '2026-03-29 21:49:15'),
(85, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5bb00457.54663922_WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', 'image/jpeg', 150908, 'a2dd379b840403da767b1396fbbd06c0c92dc9b67cd74460268360a26d0ea27d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5bb016e.jpg', 5, '2026-03-29 21:49:15'),
(86, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5bb9e477.59732771_WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', 'image/jpeg', 248972, 'c3129fda566bde76cb775f84bcdec18aebcbc79b62a5cbc49242a2445bb5cce0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5bb9eec.jpg', 5, '2026-03-29 21:49:15'),
(87, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5bc3d198.07302168_WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', 'image/jpeg', 206716, 'd7005ae556d8bdde403d8f964cd5beab084bf4b94b233e86fe49c46d199423de', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5bc3dac.jpg', 5, '2026-03-29 21:49:15'),
(88, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5bcd7db2.31554675_WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', 'image/jpeg', 274089, '7303ea388e12d02b79c69e4ec6badb4f3e29d97e2e8924bc00ccb9d2d0f5921c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5bcd9a4.jpg', 5, '2026-03-29 21:49:15');
INSERT INTO `auditoria_arquivos` (`id`, `auditoria_id`, `questao_id`, `path`, `compressed_path`, `original_name`, `mime`, `size`, `sha256`, `thumb_path`, `created_by`, `created_at`) VALUES
(89, 8, 116, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/f_69c99e5bd898f2.29696214_WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', 'image/jpeg', 197219, 'cd4d2910b035248847d38fb0e489a055c7f8d6db382683c95bc041f6042271b6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/116/thumb_69c99e5bd8a2f.jpg', 5, '2026-03-29 21:49:15'),
(90, 8, 117, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/117/f_69c99e6fdfe233.51406615_WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', 'image/jpeg', 216893, 'd2f7a99854054e5eea12108ef0a36ed6ca7381c722c9b01ab531c19d91295c9b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/8/117/thumb_69c99e6fdfedf.jpg', 5, '2026-03-29 21:49:35'),
(91, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65f7ac835.86535690_WhatsApp_Image_2026-03-29_at_17.53.15_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_1_.jpeg', 'image/jpeg', 1427886, '0ddf99aef7eb6651d7d87c9e56953dc81789c78aa065981bfe8358b11d08ecd6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65f7ade5.jpg', 5, '2026-03-29 22:23:27'),
(92, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65f8d6bf7.47906071_WhatsApp_Image_2026-03-29_at_17.53.15_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_2_.jpeg', 'image/jpeg', 1353540, 'e52d3ed0b0c63baaaaf8ebec77681e7eff76ecb26d4f43db7cba6fe3dd2b135f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65f8d758.jpg', 5, '2026-03-29 22:23:27'),
(93, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65fa160c5.07551940_WhatsApp_Image_2026-03-29_at_17.53.15_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_3_.jpeg', 'image/jpeg', 1209089, 'd2da6fa1165f0c7fbc438e40f4cc5baddfc2f099612a1240654c8c25e8ac299e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65fa16ab.jpg', 5, '2026-03-29 22:23:27'),
(94, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65fb17324.92997437_WhatsApp_Image_2026-03-29_at_17.53.15_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_4_.jpeg', 'image/jpeg', 1147958, '93a6e58960cdd94e23d1a89feca29b2c6ce087bdf8e873e5cda8c6256e4f62e7', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65fb17e0.jpg', 5, '2026-03-29 22:23:27'),
(95, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65fc17801.37580350_WhatsApp_Image_2026-03-29_at_17.53.15_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_5_.jpeg', 'image/jpeg', 1200095, '071336c5cf7d18ae82caa6918e96d85b04c260d94ea65a9c0c9ac9198e70e148', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65fc1822.jpg', 5, '2026-03-29 22:23:27'),
(96, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65fd033f8.57503610_WhatsApp_Image_2026-03-29_at_17.53.15_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_6_.jpeg', 'image/jpeg', 803588, '44f5b833a34529da9616b39a48671342e5f042a7ac21b4297bb466d9f4be89e2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65fd05ed.jpg', 5, '2026-03-29 22:23:27'),
(97, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a65fe527d0.10646419_WhatsApp_Image_2026-03-29_at_17.53.15_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_7_.jpeg', 'image/jpeg', 813538, '32123b8898e1a548e8aa340f9ecb0409e296254ad87df462038c5f35213a8c18', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a65fe5315.jpg', 5, '2026-03-29 22:23:28'),
(98, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a660033f43.68495402_WhatsApp_Image_2026-03-29_at_17.53.15_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_8_.jpeg', 'image/jpeg', 1522404, '58933347abbf2606edda225c14847e09890f26e5b7e32dddf11ebf1340e9b595', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a660034bf.jpg', 5, '2026-03-29 22:23:28'),
(99, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a660194126.37720779_WhatsApp_Image_2026-03-29_at_17.53.15_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_9_.jpeg', 'image/jpeg', 1256814, '518dff217ef3eddbb5c0f7f500293dff7b497dbcafdc10204b1e6a5d54818dd2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a660194cf.jpg', 5, '2026-03-29 22:23:28'),
(100, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a6602f3af9.77788775_WhatsApp_Image_2026-03-29_at_17.53.15_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_10_.jpeg', 'image/jpeg', 1041492, 'dfad4bab93561e152d72d2fbc2111b45e7961e23a3934c9b81bf8dc6182a1e9d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a6602f48d.jpg', 5, '2026-03-29 22:23:28'),
(101, 9, 123, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/f_69c9a660412000.39443963_WhatsApp_Image_2026-03-29_at_17.53.15.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15.jpeg', 'image/jpeg', 1320999, '5a149c19c8eefe244a1296a8e9115ee7ea09717ce5ad325ed0dcbb8707e22f33', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/123/thumb_69c9a660412cb.jpg', 5, '2026-03-29 22:23:28'),
(102, 9, 124, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/f_69c9a6744b9de3.19148297_WhatsApp_Image_2026-03-29_at_17.55.13_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_1_.jpeg', 'image/jpeg', 915013, '807bac84368747f690c83574a3b5bdc41d81d50b80c07310ea199b2ea99238b7', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/thumb_69c9a6744bb20.jpg', 5, '2026-03-29 22:23:48'),
(103, 9, 124, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/f_69c9a6745f8fe8.29371874_WhatsApp_Image_2026-03-29_at_17.55.13_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_2_.jpeg', 'image/jpeg', 585079, '38ade0c2451af050e7d55931ebd474d3e4f4cd9f426baa690991c877bb4f5807', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/thumb_69c9a6745f988.jpg', 5, '2026-03-29 22:23:48'),
(104, 9, 124, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/f_69c9a674700c64.04891580_WhatsApp_Image_2026-03-29_at_17.55.13_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_3_.jpeg', 'image/jpeg', 927371, 'd05af1bbc311b77d751366238eb1fbf85fdc7cfff7776d44f33af5aa8954bb43', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/thumb_69c9a674701d7.jpg', 5, '2026-03-29 22:23:48'),
(105, 9, 124, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/f_69c9a6747ffd28.90117177_WhatsApp_Image_2026-03-29_at_17.55.13.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13.jpeg', 'image/jpeg', 1081018, '3f97e2308c7073dce398589c31b360ae5301811142443cac80cc1c2c74e75bfb', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/124/thumb_69c9a674800b0.jpg', 5, '2026-03-29 22:23:48'),
(106, 9, 125, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/f_69c9a68a4e60d7.02118354_WhatsApp_Image_2026-03-29_at_17.56.23_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_1_.jpeg', 'image/jpeg', 757354, '8b80902e5660cd05288d01392ae2472e172b9d3cb14ecaed374207cf8054b4b2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/thumb_69c9a68a4e6c9.jpg', 5, '2026-03-29 22:24:10'),
(107, 9, 125, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/f_69c9a68a5f8111.68107525_WhatsApp_Image_2026-03-29_at_17.56.23_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_2_.jpeg', 'image/jpeg', 607904, '4c871df0cf46136ea208082f26f2c8c339199927263ec7688dae418c6461508f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/thumb_69c9a68a5fc01.jpg', 5, '2026-03-29 22:24:10'),
(108, 9, 125, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/f_69c9a68a6d1548.92975468_WhatsApp_Image_2026-03-29_at_17.56.23_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_3_.jpeg', 'image/jpeg', 955788, 'c95fd65909e4bb71519087a67fb84bc88a74a8f875e89ab53b828e3ee65855fd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/thumb_69c9a68a6d20f.jpg', 5, '2026-03-29 22:24:10'),
(109, 9, 125, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/f_69c9a68a7af969.07714862_WhatsApp_Image_2026-03-29_at_17.56.23_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_4_.jpeg', 'image/jpeg', 838933, '480605505d1717bc2a18e901f7790350ecc3ae219479d2593f51e2d53c39387c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/thumb_69c9a68a7b03d.jpg', 5, '2026-03-29 22:24:10'),
(110, 9, 125, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/f_69c9a68a8b6813.99783898_WhatsApp_Image_2026-03-29_at_17.56.23.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23.jpeg', 'image/jpeg', 647245, 'a629f9da0baabdbcf27e1f309077ee0c51af4980b32cff361d2b76804f299626', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/125/thumb_69c9a68a8b7ca.jpg', 5, '2026-03-29 22:24:10'),
(111, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a53d4916.74626492_WhatsApp_Image_2026-03-29_at_17.57.35_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_1_.jpeg', 'image/jpeg', 527534, 'c771baca765e5c7c3f26f3da515f59ec7ac6f99f63568fe79eacf4fc0816e4aa', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a53d547.jpg', 5, '2026-03-29 22:24:37'),
(112, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a54d0af3.26675217_WhatsApp_Image_2026-03-29_at_17.57.35_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_2_.jpeg', 'image/jpeg', 1296522, '9624cad82400a8f76b5327b9315dc046e34cf99846bb291cfcef758acb1c2272', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a54d1d3.jpg', 5, '2026-03-29 22:24:37'),
(113, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a55fbfe1.89877338_WhatsApp_Image_2026-03-29_at_17.57.35_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_3_.jpeg', 'image/jpeg', 1121372, '2224e01320c65fa85489ebc82ccb87dc536412ec426d2952b01b486677792036', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a55fc9e.jpg', 5, '2026-03-29 22:24:37'),
(114, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a56ef7a0.25088925_WhatsApp_Image_2026-03-29_at_17.57.35_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_4_.jpeg', 'image/jpeg', 518422, '5dfa39c314f20c760ff30f7589a2b76b58da82e44df42413ae4413865cdbbc0e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a56f019.jpg', 5, '2026-03-29 22:24:37'),
(115, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a57e1c21.24846972_WhatsApp_Image_2026-03-29_at_17.57.35_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_5_.jpeg', 'image/jpeg', 745899, 'a1b9658243a252fc248489e82e2bda3d499f7523a7d4b54be92e6c422b349fb0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a57e25b.jpg', 5, '2026-03-29 22:24:37'),
(116, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a58ec986.52839133_WhatsApp_Image_2026-03-29_at_17.57.35_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_6_.jpeg', 'image/jpeg', 618435, '72685d119d9315db821acf8cee2720cff70b55044d4fa5a372d2eff5600054e1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a58ed62.jpg', 5, '2026-03-29 22:24:37'),
(117, 9, 126, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/f_69c9a6a5a062a7.24242628_WhatsApp_Image_2026-03-29_at_17.57.35.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35.jpeg', 'image/jpeg', 507200, 'cb983fc2bc58a3eba382f77f73cc84beb78cdd084d2aec9c9642cf3cd2e95b23', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/126/thumb_69c9a6a5a06c5.jpg', 5, '2026-03-29 22:24:37'),
(118, 9, 127, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/127/f_69c9a6bca71305.98871264_WhatsApp_Image_2026-03-29_at_17.58.47.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.58.47.jpeg', 'image/jpeg', 1125844, '453ea5e3626b5179b6266f5961a3fb898249dfd45370ad1b2275bf3ea023b53b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/9/127/thumb_69c9a6bca72ee.jpg', 5, '2026-03-29 22:25:00'),
(119, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335a2bbe0.42560599_WhatsApp_Image_2026-03-29_at_17.53.15_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_1_.jpeg', 'image/jpeg', 1427886, '0ddf99aef7eb6651d7d87c9e56953dc81789c78aa065981bfe8358b11d08ecd6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335a2d52.jpg', 5, '2026-03-29 23:18:13'),
(120, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335b4d9f6.41209908_WhatsApp_Image_2026-03-29_at_17.53.15_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_2_.jpeg', 'image/jpeg', 1353540, 'e52d3ed0b0c63baaaaf8ebec77681e7eff76ecb26d4f43db7cba6fe3dd2b135f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335b4e57.jpg', 5, '2026-03-29 23:18:13'),
(121, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335c5d9e2.16891053_WhatsApp_Image_2026-03-29_at_17.53.15_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_3_.jpeg', 'image/jpeg', 1209089, 'd2da6fa1165f0c7fbc438e40f4cc5baddfc2f099612a1240654c8c25e8ac299e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335c5e71.jpg', 5, '2026-03-29 23:18:13'),
(122, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335d4b849.46330884_WhatsApp_Image_2026-03-29_at_17.53.15_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_4_.jpeg', 'image/jpeg', 1147958, '93a6e58960cdd94e23d1a89feca29b2c6ce087bdf8e873e5cda8c6256e4f62e7', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335d4c25.jpg', 5, '2026-03-29 23:18:13'),
(123, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335e586a1.72164165_WhatsApp_Image_2026-03-29_at_17.53.15_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_5_.jpeg', 'image/jpeg', 1200095, '071336c5cf7d18ae82caa6918e96d85b04c260d94ea65a9c0c9ac9198e70e148', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335e58ed.jpg', 5, '2026-03-29 23:18:13'),
(124, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b335f3fe87.53782297_WhatsApp_Image_2026-03-29_at_17.53.15_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_6_.jpeg', 'image/jpeg', 803588, '44f5b833a34529da9616b39a48671342e5f042a7ac21b4297bb466d9f4be89e2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b335f408d.jpg', 5, '2026-03-29 23:18:14'),
(125, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b3360d17c4.45238322_WhatsApp_Image_2026-03-29_at_17.53.15_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_7_.jpeg', 'image/jpeg', 813538, '32123b8898e1a548e8aa340f9ecb0409e296254ad87df462038c5f35213a8c18', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b3360d2d5.jpg', 5, '2026-03-29 23:18:14'),
(126, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b3361ae161.41221526_WhatsApp_Image_2026-03-29_at_17.53.15_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_8_.jpeg', 'image/jpeg', 1522404, '58933347abbf2606edda225c14847e09890f26e5b7e32dddf11ebf1340e9b595', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b3361aeb9.jpg', 5, '2026-03-29 23:18:14'),
(127, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b3362b2170.26849016_WhatsApp_Image_2026-03-29_at_17.53.15_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_9_.jpeg', 'image/jpeg', 1256814, '518dff217ef3eddbb5c0f7f500293dff7b497dbcafdc10204b1e6a5d54818dd2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b3362b2d2.jpg', 5, '2026-03-29 23:18:14'),
(128, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b33638d8d2.64716302_WhatsApp_Image_2026-03-29_at_17.53.15_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15_10_.jpeg', 'image/jpeg', 1041492, 'dfad4bab93561e152d72d2fbc2111b45e7961e23a3934c9b81bf8dc6182a1e9d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b33638e3f.jpg', 5, '2026-03-29 23:18:14'),
(129, 12, 436, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/f_69c9b336466018.07566007_WhatsApp_Image_2026-03-29_at_17.53.15.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.53.15.jpeg', 'image/jpeg', 1320999, '5a149c19c8eefe244a1296a8e9115ee7ea09717ce5ad325ed0dcbb8707e22f33', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/436/thumb_69c9b336466af.jpg', 5, '2026-03-29 23:18:14'),
(130, 12, 437, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/f_69c9b347655ba7.27942721_WhatsApp_Image_2026-03-29_at_17.55.13_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_1_.jpeg', 'image/jpeg', 915013, '807bac84368747f690c83574a3b5bdc41d81d50b80c07310ea199b2ea99238b7', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/thumb_69c9b347656ce.jpg', 5, '2026-03-29 23:18:31'),
(131, 12, 437, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/f_69c9b34777b877.86985993_WhatsApp_Image_2026-03-29_at_17.55.13_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_2_.jpeg', 'image/jpeg', 585079, '38ade0c2451af050e7d55931ebd474d3e4f4cd9f426baa690991c877bb4f5807', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/thumb_69c9b34777c1b.jpg', 5, '2026-03-29 23:18:31'),
(132, 12, 437, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/f_69c9b3478563d2.10498415_WhatsApp_Image_2026-03-29_at_17.55.13_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13_3_.jpeg', 'image/jpeg', 927371, 'd05af1bbc311b77d751366238eb1fbf85fdc7cfff7776d44f33af5aa8954bb43', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/thumb_69c9b3478578b.jpg', 5, '2026-03-29 23:18:31'),
(133, 12, 437, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/f_69c9b347933eb3.32857810_WhatsApp_Image_2026-03-29_at_17.55.13.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.55.13.jpeg', 'image/jpeg', 1081018, '3f97e2308c7073dce398589c31b360ae5301811142443cac80cc1c2c74e75bfb', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/437/thumb_69c9b3479349b.jpg', 5, '2026-03-29 23:18:31'),
(134, 12, 438, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/f_69c9b358aab504.94503237_WhatsApp_Image_2026-03-29_at_17.56.23_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_1_.jpeg', 'image/jpeg', 757354, '8b80902e5660cd05288d01392ae2472e172b9d3cb14ecaed374207cf8054b4b2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/thumb_69c9b358aac66.jpg', 5, '2026-03-29 23:18:48'),
(135, 12, 438, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/f_69c9b358ba35e0.89300692_WhatsApp_Image_2026-03-29_at_17.56.23_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_2_.jpeg', 'image/jpeg', 607904, '4c871df0cf46136ea208082f26f2c8c339199927263ec7688dae418c6461508f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/thumb_69c9b358ba41e.jpg', 5, '2026-03-29 23:18:48'),
(136, 12, 438, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/f_69c9b358c83686.33859631_WhatsApp_Image_2026-03-29_at_17.56.23_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_3_.jpeg', 'image/jpeg', 955788, 'c95fd65909e4bb71519087a67fb84bc88a74a8f875e89ab53b828e3ee65855fd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/thumb_69c9b358c83ed.jpg', 5, '2026-03-29 23:18:48'),
(137, 12, 438, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/f_69c9b358d66396.26499387_WhatsApp_Image_2026-03-29_at_17.56.23_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23_4_.jpeg', 'image/jpeg', 838933, '480605505d1717bc2a18e901f7790350ecc3ae219479d2593f51e2d53c39387c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/thumb_69c9b358d66ed.jpg', 5, '2026-03-29 23:18:48'),
(138, 12, 438, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/f_69c9b358e56b20.50317007_WhatsApp_Image_2026-03-29_at_17.56.23.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.56.23.jpeg', 'image/jpeg', 647245, 'a629f9da0baabdbcf27e1f309077ee0c51af4980b32cff361d2b76804f299626', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/438/thumb_69c9b358e5750.jpg', 5, '2026-03-29 23:18:48'),
(139, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3acb06086.59792585_WhatsApp_Image_2026-03-29_at_17.57.35_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_1_.jpeg', 'image/jpeg', 527534, 'c771baca765e5c7c3f26f3da515f59ec7ac6f99f63568fe79eacf4fc0816e4aa', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3acb077b.jpg', 5, '2026-03-29 23:20:12'),
(140, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3acbeefa5.25426302_WhatsApp_Image_2026-03-29_at_17.57.35_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_2_.jpeg', 'image/jpeg', 1296522, '9624cad82400a8f76b5327b9315dc046e34cf99846bb291cfcef758acb1c2272', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3acbefda.jpg', 5, '2026-03-29 23:20:12'),
(141, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3acd0a217.14571241_WhatsApp_Image_2026-03-29_at_17.57.35_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_3_.jpeg', 'image/jpeg', 1121372, '2224e01320c65fa85489ebc82ccb87dc536412ec426d2952b01b486677792036', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3acd0bae.jpg', 5, '2026-03-29 23:20:12'),
(142, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3acdfb0c3.98317433_WhatsApp_Image_2026-03-29_at_17.57.35_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_4_.jpeg', 'image/jpeg', 518422, '5dfa39c314f20c760ff30f7589a2b76b58da82e44df42413ae4413865cdbbc0e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3acdfbaf.jpg', 5, '2026-03-29 23:20:12'),
(143, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3acee7805.99964158_WhatsApp_Image_2026-03-29_at_17.57.35_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_5_.jpeg', 'image/jpeg', 745899, 'a1b9658243a252fc248489e82e2bda3d499f7523a7d4b54be92e6c422b349fb0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3acee853.jpg', 5, '2026-03-29 23:20:13'),
(144, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3ad0b6295.80795647_WhatsApp_Image_2026-03-29_at_17.57.35_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35_6_.jpeg', 'image/jpeg', 618435, '72685d119d9315db821acf8cee2720cff70b55044d4fa5a372d2eff5600054e1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3ad0b6f9.jpg', 5, '2026-03-29 23:20:13'),
(145, 12, 439, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/f_69c9b3ad194b99.53284452_WhatsApp_Image_2026-03-29_at_17.57.35.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.57.35.jpeg', 'image/jpeg', 507200, 'cb983fc2bc58a3eba382f77f73cc84beb78cdd084d2aec9c9642cf3cd2e95b23', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/439/thumb_69c9b3ad19609.jpg', 5, '2026-03-29 23:20:13'),
(146, 12, 440, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/440/f_69c9b3d5184e12.57618355_WhatsApp_Image_2026-03-29_at_17.58.47.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_17.58.47.jpeg', 'image/jpeg', 1125844, '453ea5e3626b5179b6266f5961a3fb898249dfd45370ad1b2275bf3ea023b53b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/12/440/thumb_69c9b3d518686.jpg', 5, '2026-03-29 23:20:53'),
(147, 13, 335, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/f_69c9b462a8ea39.87825678_WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_12_.jpeg', 'image/jpeg', 109116, 'ca738fae8dbbce4b1e5bb985e100afcbe6b4de0950ac3d2645f3d38efa0d2318', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/thumb_69c9b462a8f7a.jpg', 5, '2026-03-29 23:23:14'),
(148, 13, 335, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/f_69c9b462b4a406.40745406_WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_13_.jpeg', 'image/jpeg', 154004, 'c63ff108b3be9fe404d97634022dab674d878589491758081eec6e2406cdad4d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/thumb_69c9b462b4ad2.jpg', 5, '2026-03-29 23:23:14'),
(149, 13, 335, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/f_69c9b462befd75.19568063_WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_14_.jpeg', 'image/jpeg', 159986, '85a15751866824452ba266bdab7f0960ee4b60c37909c8ed26faadce6516353d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/thumb_69c9b462bf0a8.jpg', 5, '2026-03-29 23:23:14'),
(150, 13, 335, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/f_69c9b462ca0cd1.10534057_WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_15_.jpeg', 'image/jpeg', 151340, '637166ead9f6a05cdf1d38b2bfc05a2e891e080fbfb94f33b84d0cac8328dda0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/thumb_69c9b462ca16d.jpg', 5, '2026-03-29 23:23:14'),
(151, 13, 335, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/f_69c9b462d27c43.37943718_WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_16_.jpeg', 'image/jpeg', 128880, '72b1c1e59937cb48119a0c6243546724d6393591c675d69d5a6a349c4866f50a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/335/thumb_69c9b462d285c.jpg', 5, '2026-03-29 23:23:14'),
(152, 13, 336, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/f_69c9b47b8e2690.64325874_WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_21_.jpeg', 'image/jpeg', 200576, 'c095a6c48f0f4b88cf7f14bd7621c53f25ebbd58c4f04eba48da2c7aa8a8eb0a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/thumb_69c9b47b8e334.jpg', 5, '2026-03-29 23:23:39'),
(153, 13, 336, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/f_69c9b47b992b63.37865703_WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_22_.jpeg', 'image/jpeg', 158467, '023d6e5afbf7c837a31e7c451e83cf64db1ee46014f11bddd9f46b3cde70e73c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/thumb_69c9b47b99337.jpg', 5, '2026-03-29 23:23:39'),
(154, 13, 336, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/f_69c9b47ba36227.42545810_WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_23_.jpeg', 'image/jpeg', 144627, 'a2aff77ff82d421211f49696c68e10efe09e6c7e6f76523d0f6fa2a7f66ebf62', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/336/thumb_69c9b47ba3734.jpg', 5, '2026-03-29 23:23:39'),
(155, 13, 337, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/f_69c9b497e5f5b9.34374923_WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_20_.jpeg', 'image/jpeg', 159039, 'cb5a6f50243eb44a2374c693299d72d6039fac9ec5e35245508619da009ee2b4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/thumb_69c9b497e613b.jpg', 5, '2026-03-29 23:24:07'),
(156, 13, 337, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/f_69c9b497f0ab93.10025912_WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_24_.jpeg', 'image/jpeg', 94329, 'e421d88c8c42e41d10932c2e4adb3d42768fe8bcf93e66fd19172d167d677116', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/thumb_69c9b497f0b83.jpg', 5, '2026-03-29 23:24:08'),
(157, 13, 337, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/f_69c9b498091048.89633608_WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_25_.jpeg', 'image/jpeg', 105701, 'cb3efb12a2282232f502384c04e08e0fc1dbe90214ed66385ca6a76c4f3e485a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/thumb_69c9b49809194.jpg', 5, '2026-03-29 23:24:08'),
(158, 13, 337, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/f_69c9b49811f911.03880447_WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_26_.jpeg', 'image/jpeg', 124148, 'fab3c6961f53ddc86d91b6647a39522984698edfc0411ed19f7b504d29d097d6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/337/thumb_69c9b498120ca.jpg', 5, '2026-03-29 23:24:08'),
(159, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b16e79c8.15970676_WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_1_.jpeg', 'image/jpeg', 94692, 'a02cf6af1631e91ce5d2270acc22fad17fb9832139b685f873d003d57913baf4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b16e861.jpg', 5, '2026-03-29 23:24:33'),
(160, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b17867d7.19719643_WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_2_.jpeg', 'image/jpeg', 129800, '211d873c1b84ae2f2afd84fe609e856323fd54c10f6eae785274dca1e8a0b7bc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1786fd.jpg', 5, '2026-03-29 23:24:33'),
(161, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1824ee2.50558511_WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_3_.jpeg', 'image/jpeg', 134287, 'd397b4c16345f1eff9c41a875b52096a97e8fb3384a7d61fe311bacfea7e3e63', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1825fd.jpg', 5, '2026-03-29 23:24:33'),
(162, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b18d5942.11575928_WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_4_.jpeg', 'image/jpeg', 166209, '58922b3a58f8bb233bd4a37f0082f8caac4b72c65c56131f591216460efac2a4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b18d62e.jpg', 5, '2026-03-29 23:24:33'),
(163, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b19710c2.70384196_WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_5_.jpeg', 'image/jpeg', 167432, '43fcecf419d653717217ecfcde8d0aab4784bdb49c4e36eab3ef2e213967db9f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1971a5.jpg', 5, '2026-03-29 23:24:33'),
(164, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1a20ba2.02846480_WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_6_.jpeg', 'image/jpeg', 179753, 'eb96efbe6a268bcc463a6348b5f80a3641d8648d0ee5e8bbcdb2d1cea9734d7c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1a219f.jpg', 5, '2026-03-29 23:24:33'),
(165, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1ac7474.31702697_WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_7_.jpeg', 'image/jpeg', 150908, 'a2dd379b840403da767b1396fbbd06c0c92dc9b67cd74460268360a26d0ea27d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1ac7f6.jpg', 5, '2026-03-29 23:24:33'),
(166, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1b56797.66970818_WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_8_.jpeg', 'image/jpeg', 248972, 'c3129fda566bde76cb775f84bcdec18aebcbc79b62a5cbc49242a2445bb5cce0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1b56f4.jpg', 5, '2026-03-29 23:24:33'),
(167, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1bfe350.11097247_WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_9_.jpeg', 'image/jpeg', 206716, 'd7005ae556d8bdde403d8f964cd5beab084bf4b94b233e86fe49c46d199423de', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1bfebc.jpg', 5, '2026-03-29 23:24:33'),
(168, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1c8ec71.85969662_WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_10_.jpeg', 'image/jpeg', 274089, '7303ea388e12d02b79c69e4ec6badb4f3e29d97e2e8924bc00ccb9d2d0f5921c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1c906d.jpg', 5, '2026-03-29 23:24:33'),
(169, 13, 338, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/f_69c9b4b1d341c4.37784294_WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_11_.jpeg', 'image/jpeg', 197219, 'cd4d2910b035248847d38fb0e489a055c7f8d6db382683c95bc041f6042271b6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/338/thumb_69c9b4b1d34f7.jpg', 5, '2026-03-29 23:24:33'),
(170, 13, 339, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/339/f_69c9b4d79d1c18.11868553_WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.39_3_.jpeg', 'image/jpeg', 216893, 'd2f7a99854054e5eea12108ef0a36ed6ca7381c722c9b01ab531c19d91295c9b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/13/339/thumb_69c9b4d79d283.jpg', 5, '2026-03-29 23:25:11'),
(171, 14, 471, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/f_69c9b56062b593.04329398_WhatsApp_Image_2026-03-25_at_19.18.41_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41_2_.jpeg', 'image/jpeg', 482606, '324edc6be35dc6d94031fa7222a477e6fa4580559ba413af8c501d632bb0fd23', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/thumb_69c9b56062c0b.jpg', 5, '2026-03-29 23:27:28'),
(172, 14, 471, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/f_69c9b5607204a2.61029993_WhatsApp_Image_2026-03-25_at_19.18.41_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41_3_.jpeg', 'image/jpeg', 391545, '0f611d943d99e9c9de96ccde53552c272a7ee66b0656cb8f21f6a994e325225f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/thumb_69c9b5607211e.jpg', 5, '2026-03-29 23:27:28'),
(173, 14, 471, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/f_69c9b5607fa034.95860231_WhatsApp_Image_2026-03-25_at_19.18.41_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41_4_.jpeg', 'image/jpeg', 465070, '22a0aef4c4b81d3d407cf40beb2f797b6e6682ad31af47b4ce73a6742bb8e182', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/thumb_69c9b5607facc.jpg', 5, '2026-03-29 23:27:28'),
(174, 14, 471, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/f_69c9b5608b8214.94698110_WhatsApp_Image_2026-03-25_at_19.18.41_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41_5_.jpeg', 'image/jpeg', 465592, '4251d53ab580da61ad915310ccea07a11821926c326a965204bac0be73c7dcb3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/471/thumb_69c9b5608b8a8.jpg', 5, '2026-03-29 23:27:28'),
(175, 14, 472, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/f_69c9b5741f7607.75458295_WhatsApp_Image_2026-03-25_at_19.18.50_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_1_.jpeg', 'image/jpeg', 196073, '54198acefa7306065394a2d676639163dd4ca5b6278606094c683211d13be83c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/thumb_69c9b5741f8b9.jpg', 5, '2026-03-29 23:27:48'),
(176, 14, 472, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/f_69c9b5742cbde3.29966027_WhatsApp_Image_2026-03-25_at_19.18.51_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_8_.jpeg', 'image/jpeg', 150587, 'b95619bc016003c1637d0f45c02734086e96b5cc8ee53a77b660f7ca14a8c9bb', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/thumb_69c9b5742cc92.jpg', 5, '2026-03-29 23:27:48'),
(177, 14, 472, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/f_69c9b57436f7e1.08997863_WhatsApp_Image_2026-03-25_at_19.18.51_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_9_.jpeg', 'image/jpeg', 156960, '8f32bc41868d52f2eca5d0caa218f2ebc84ce6db1dd0a4ae3c4f9c8665f1939a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/thumb_69c9b5743700d.jpg', 5, '2026-03-29 23:27:48'),
(178, 14, 472, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/f_69c9b57440c126.56229313_WhatsApp_Image_2026-03-25_at_19.18.51_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_10_.jpeg', 'image/jpeg', 140178, 'f367ed696e15d15d48b7746ea595351a14ab52d5598886f687e2f38af12824b3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/472/thumb_69c9b57440cc0.jpg', 5, '2026-03-29 23:27:48'),
(179, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b588bffe14.72709386_WhatsApp_Image_2026-03-25_at_19.18.36_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.36_1_.jpeg', 'image/jpeg', 202799, 'cfe28b8293f93c4ed9ba6c6791b824eb4f1c134ea3473343ca804dbad7808520', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b588c0087.jpg', 5, '2026-03-29 23:28:08'),
(180, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b588cde1a3.89629649_WhatsApp_Image_2026-03-25_at_19.18.36_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.36_2_.jpeg', 'image/jpeg', 201058, 'a14b3778c536ec73053bba360fb9a0ea0449e1bccf244f3218336d98782d15c6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b588cdec5.jpg', 5, '2026-03-29 23:28:08'),
(181, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b588db0643.77683010_WhatsApp_Image_2026-03-25_at_19.18.36_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.36_3_.jpeg', 'image/jpeg', 207183, '95d405d18883c810166968b6fad655011689d3e892beb5469579e6fe60db0c65', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b588db0ff.jpg', 5, '2026-03-29 23:28:08'),
(182, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b588e5db64.55557880_WhatsApp_Image_2026-03-25_at_19.18.36_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.36_4_.jpeg', 'image/jpeg', 188392, '0985714656735134ba58a9871a3e76f2bd4b06096551579f65f4980b6930d16d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b588e5e3e.jpg', 5, '2026-03-29 23:28:08'),
(183, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b588f01709.47006689_WhatsApp_Image_2026-03-25_at_19.18.36.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.36.jpeg', 'image/jpeg', 207496, '81fbd54ec424ac7b9eac0bf64ee97118cda3a6872af6371360c7e5429ac3d3a6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b588f024c.jpg', 5, '2026-03-29 23:28:09'),
(184, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b58905a8c2.46268743_WhatsApp_Image_2026-03-25_at_19.18.41_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41_1_.jpeg', 'image/jpeg', 245220, '095e837e2401c9f17c40b6ef2cafd57b2a08ade231a738dc9fa86859ce5e975b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b58905b2f.jpg', 5, '2026-03-29 23:28:09'),
(185, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b58910df95.30186456_WhatsApp_Image_2026-03-25_at_19.18.41.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.41.jpeg', 'image/jpeg', 222718, '6d8f1241a6775ed6d9937f47b66bac20abcc6e4c8ed323ddc75aaa599d305089', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b58910ea1.jpg', 5, '2026-03-29 23:28:09'),
(186, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b5891e2bd0.39695196_WhatsApp_Image_2026-03-25_at_19.18.51_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_2_.jpeg', 'image/jpeg', 117588, 'e14972267780c41263a31a6036ce8d8dcf07860afc904056989b1da31bb550dc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b5891e448.jpg', 5, '2026-03-29 23:28:09'),
(187, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b589292321.36105781_WhatsApp_Image_2026-03-25_at_19.18.51_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_5_.jpeg', 'image/jpeg', 114049, '7e97a528fcde69a943cc54c73d7bbaf202dd8b039e60756fc959f2c4646edfb1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b589292d2.jpg', 5, '2026-03-29 23:28:09'),
(188, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b589339654.28056787_WhatsApp_Image_2026-03-25_at_19.18.51_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_6_.jpeg', 'image/jpeg', 84928, '59f62290e6da30c992e048ef7595b2594c8ef4b749c902c870495a058fc67e19', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b58933c58.jpg', 5, '2026-03-29 23:28:09'),
(189, 14, 473, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/f_69c9b5893e9bb1.73897381_WhatsApp_Image_2026-03-25_at_19.18.51_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_7_.jpeg', 'image/jpeg', 91846, 'b2a985f3863699561067ba7bf47751d92e828bcddfdf8c7e9940e0e3b4f5a21f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/473/thumb_69c9b5893eb36.jpg', 5, '2026-03-29 23:28:09');
INSERT INTO `auditoria_arquivos` (`id`, `auditoria_id`, `questao_id`, `path`, `compressed_path`, `original_name`, `mime`, `size`, `sha256`, `thumb_path`, `created_by`, `created_at`) VALUES
(190, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a75e0723.97598422_WhatsApp_Image_2026-03-25_at_19.18.50_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_2_.jpeg', 'image/jpeg', 264861, '61dd17e97dd4bf33fb436f0d5ecb589d0592f99abd7879ea5c5e6790b2ad9138', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a75e165.jpg', 5, '2026-03-29 23:28:39'),
(191, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a76afd59.13055433_WhatsApp_Image_2026-03-25_at_19.18.50_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_3_.jpeg', 'image/jpeg', 196114, 'e4f88c3b94a0b3df99dd80abd7a7288d6347141194143585bb3916a23e4e348b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a76b042.jpg', 5, '2026-03-29 23:28:39'),
(192, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a77774b2.95002769_WhatsApp_Image_2026-03-25_at_19.18.50_4_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_4_.jpeg', 'image/jpeg', 221826, 'f74d6a50eb8b1d70f3520c80bd7e36bab2e363346d36afd10288ea479db60ab8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a777909.jpg', 5, '2026-03-29 23:28:39'),
(193, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a7804126.93913319_WhatsApp_Image_2026-03-25_at_19.18.50_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_5_.jpeg', 'image/jpeg', 191127, '99ddc962ea1c3f4421b4d838ceddd9b0ced35faa61ca639c4867ecb9da758bd3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a780592.jpg', 5, '2026-03-29 23:28:39'),
(194, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a78a6f00.96890465_WhatsApp_Image_2026-03-25_at_19.18.50_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_6_.jpeg', 'image/jpeg', 181383, '46ae86c40fde31116ae605af7cdc29990d775522749ac97ac8be9b8a8f8ca352', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a78a7a7.jpg', 5, '2026-03-29 23:28:39'),
(195, 14, 474, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/f_69c9b5a7947a58.48558477_WhatsApp_Image_2026-03-25_at_19.18.51_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_1_.jpeg', 'image/jpeg', 175905, '5070a3ba3497504627f4536dc2154c5ca7dc993031e465bfdab0486047583499', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/474/thumb_69c9b5a79495c.jpg', 5, '2026-03-29 23:28:39'),
(196, 14, 475, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/475/f_69c9b5c6b10139.37980714_WhatsApp_Image_2026-03-25_at_19.18.50_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50_7_.jpeg', 'image/jpeg', 373446, 'aaf08c4d0fb10bdbf5c322fb42a8645f49c4d46e69101d36059cf579ce12b0cd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/475/thumb_69c9b5c6b110e.jpg', 5, '2026-03-29 23:29:10'),
(197, 14, 475, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/475/f_69c9b5c6be1802.12847885_WhatsApp_Image_2026-03-25_at_19.18.50.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.50.jpeg', 'image/jpeg', 440436, 'dce2e75a56afdbc715cd51b075b9e1675daa79434af97395ed62404c0e829f5a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/14/475/thumb_69c9b5c6be217.jpg', 5, '2026-03-29 23:29:10'),
(198, 21, 368, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/21/368/f_69c9b88bbaa540.11304531_WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', 'image/jpeg', 143152, 'b937597800a97d9bb6c64883ef2b85a6b9026eedce61ba9a4ffe083697ec6db8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/21/368/thumb_69c9b88bbaba2.jpg', 5, '2026-03-29 23:40:59'),
(199, 21, 368, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/21/368/f_69c9b88bc5ed08.18846770_WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', 'image/jpeg', 154331, 'b1116fa7ffd124620ef0471666e533d400871cea7dfb2b77d94a629a00719f30', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/21/368/thumb_69c9b88bc6060.jpg', 5, '2026-03-29 23:40:59'),
(200, 25, 343, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/343/f_69c9b9898ddbb9.59099441_WhatsApp_Image_2026-03-25_at_19.18.51_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_12_.jpeg', 'image/jpeg', 162141, '09f559a0d66265da8b8cff0e4d7e02a539804e42a48a677c7954b3610ee0a870', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/343/thumb_69c9b9898de61.jpg', 5, '2026-03-29 23:45:13'),
(201, 25, 343, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/343/f_69c9b989990e19.08721908_WhatsApp_Image_2026-03-25_at_19.18.51_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_13_.jpeg', 'image/jpeg', 190616, '1bf01bb773986acab419f322dfbb5f80de732cd02fc7bb7ec1d20bf773cdcbab', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/343/thumb_69c9b989993ba.jpg', 5, '2026-03-29 23:45:13'),
(202, 25, 349, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/349/f_69c9b9b6213f56.53923318_1.jpg', NULL, '1.jpg', 'image/jpeg', 163129, '37e285b783957ecd5fdfa66e64d933fde2c452a99d8051abf8be9ceaffc95c83', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/349/thumb_69c9b9b6214cc.jpg', 5, '2026-03-29 23:45:58'),
(203, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7b5ba39.75765977_WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_25_.jpeg', 'image/jpeg', 143152, 'b937597800a97d9bb6c64883ef2b85a6b9026eedce61ba9a4ffe083697ec6db8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7b5c70.jpg', 5, '2026-03-29 23:46:31'),
(204, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7c2d863.21656522_WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_26_.jpeg', 'image/jpeg', 154331, 'b1116fa7ffd124620ef0471666e533d400871cea7dfb2b77d94a629a00719f30', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7c2e57.jpg', 5, '2026-03-29 23:46:31'),
(205, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7d16ae2.82183138_WhatsApp_Image_2026-03-25_at_19.18.51_27_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_27_.jpeg', 'image/jpeg', 235593, '8decdefaa5de04acf2a644458c077b199752814c856a46da963ebef191b3a66f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7d1780.jpg', 5, '2026-03-29 23:46:31'),
(206, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7dbd482.46596033_WhatsApp_Image_2026-03-25_at_19.18.51_28_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_28_.jpeg', 'image/jpeg', 156924, '81182f108992ffd50839e3edfc9112be7c3faead9741986c1f43fae847d87665', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7dbe06.jpg', 5, '2026-03-29 23:46:31'),
(207, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7e481b8.94279249_WhatsApp_Image_2026-03-25_at_19.18.51_29_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_29_.jpeg', 'image/jpeg', 193827, '2bf61eb7e3a60f3c9e59561db099d1e20fa7254df990e761b596e9738b11f9bf', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7e48bb.jpg', 5, '2026-03-29 23:46:31'),
(208, 25, 351, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/f_69c9b9d7f05252.42832451_WhatsApp_Image_2026-03-25_at_19.18.51_30_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_30_.jpeg', 'image/jpeg', 143516, 'cd2c93acfbf7ed152a9b748c3f05319b4f5cc2d1d55456ef0d46a24a6f9fae09', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/25/351/thumb_69c9b9d7f0679.jpg', 5, '2026-03-29 23:46:32'),
(209, 18, 398, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/398/f_69c9ba624b9158.38450531_WhatsApp_Image_2026-03-25_at_19.18.51_33_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_33_.jpeg', 'image/jpeg', 227877, '72bab1e6a57e72d6ccb92cd9d231fccf8b06ff2ae90deb55b16052a99e3f15b2', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/398/thumb_69c9ba624b9ed.jpg', 5, '2026-03-29 23:48:50'),
(210, 18, 398, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/398/f_69c9ba62569905.67314208_WhatsApp_Image_2026-03-25_at_19.18.51_35_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_35_.jpeg', 'image/jpeg', 209451, '0c1e26ce0e253021e03d3508dea6454757ceed866317f1bf8681aa0c0bccc47f', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/398/thumb_69c9ba6256ab7.jpg', 5, '2026-03-29 23:48:50'),
(211, 18, 399, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/399/f_69c9ba72c0e594.86652829_WhatsApp_Image_2026-03-25_at_19.18.51_16_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.18.51_16_.jpeg', 'image/jpeg', 215389, '1bdc4b4dd046ec83f34860c819b8cca1f6fc5ebbfb6fa93ce0b029c2c312a1cd', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/18/399/thumb_69c9ba72c11b4.jpg', 5, '2026-03-29 23:49:06'),
(212, 15, 462, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/15/462/f_69c9bacb42e589.26927581_estrada.jpg', NULL, 'estrada.jpg', 'image/jpeg', 73417, 'd5b3b36943ba53ef4457899f20c867634d09369cc9b7cc9bd1c919b1bff331ff', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/15/462/thumb_69c9bacb42f1a.jpg', 5, '2026-03-29 23:50:35'),
(213, 15, 462, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/15/462/f_69c9bacb48bce8.14589079_WhatsApp_Image_2026-03-26_at_09.14.38.jpeg', NULL, 'WhatsApp_Image_2026-03-26_at_09.14.38.jpeg', 'image/jpeg', 157285, 'f1253863b55e921427085c6903bc9fc54ec6020e5b337303717ddcd689318389', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/15/462/thumb_69c9bacb48c50.jpg', 5, '2026-03-29 23:50:35'),
(214, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7cb3a469.13144055_WhatsApp_Image_2026-03-25_at_18.56.06_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_1_.jpeg', 'image/jpeg', 192132, 'bfaef2c32dc0002f123379ccaad590f18ff21649932c5da73c9443c4c9215859', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7cb3b4b.jpg', 5, '2026-03-29 23:57:48'),
(215, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7cc05f13.18283856_WhatsApp_Image_2026-03-25_at_18.56.06_3_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_3_.jpeg', 'image/jpeg', 214039, '9a14fb1e4e49ef8f5ca816d9c1c8c0d1d9ac022b8615ce6f7c8a533e7e3a7803', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7cc06c9.jpg', 5, '2026-03-29 23:57:48'),
(216, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7cce98f0.97522826_WhatsApp_Image_2026-03-25_at_18.56.06_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_5_.jpeg', 'image/jpeg', 135109, '476682001d3e1a13bdab4f34cf0a24e74eafc596543e0da79263d71d02243365', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7ccec72.jpg', 5, '2026-03-29 23:57:48'),
(217, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7cd95ab8.51440794_WhatsApp_Image_2026-03-25_at_18.56.06_7_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_7_.jpeg', 'image/jpeg', 142059, '65a63beb67d6fc5cc22df9680595d2ef42de073fc63686b43cf0da95dca78c34', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7cd965d.jpg', 5, '2026-03-29 23:57:48'),
(218, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7ce44a18.83441092_WhatsApp_Image_2026-03-25_at_18.56.06_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_9_.jpeg', 'image/jpeg', 82221, '6b08436485603bf7fd3c7e05fd36d9b18a53205eaa9f8c8a4e4bcfc3b59fff23', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7ce4718.jpg', 5, '2026-03-29 23:57:48'),
(219, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7cef4064.46230497_WhatsApp_Image_2026-03-25_at_18.56.06_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_10_.jpeg', 'image/jpeg', 256420, 'ad8d52c75f208236e0eaa968d0ea36578bc2751a0460768beca9ef148d26b16c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7cef4bb.jpg', 5, '2026-03-29 23:57:49'),
(220, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d064d98.57834247_WhatsApp_Image_2026-03-25_at_18.56.06_11_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_11_.jpeg', 'image/jpeg', 179417, '766570a7f59dbf2662bdc327caf37933a11d068210c12aff274dae366c78132a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d065b8.jpg', 5, '2026-03-29 23:57:49'),
(221, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d10e9b9.22139796_WhatsApp_Image_2026-03-25_at_18.56.06_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_12_.jpeg', 'image/jpeg', 164792, '432a1a1d40b9a1c5981fb4a0807252cf4adcbfa939ca1f40cb487dc78561d820', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d10fca.jpg', 5, '2026-03-29 23:57:49'),
(222, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d1b63a3.75643583_WhatsApp_Image_2026-03-25_at_18.56.06_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06_13_.jpeg', 'image/jpeg', 241829, '295cb986c4d7483421ad8c390c2d432f638e25b361297b7ce3d00d37d800c085', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d1b7be.jpg', 5, '2026-03-29 23:57:49'),
(223, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d258991.50120748_WhatsApp_Image_2026-03-25_at_18.56.06.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_18.56.06.jpeg', 'image/jpeg', 115245, '07916cbd05e5f69396501ef59b128de9cd90cdb789e2b62f1ed579c34e6b91b3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d2596e.jpg', 5, '2026-03-29 23:57:49'),
(224, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d2e7115.37752056_WhatsApp_Image_2026-03-25_at_19.02.46_60_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_60_.jpeg', 'image/jpeg', 105104, '4bb154ef842558cd2aa7c4cbf35f21c9dffb26a22050345ac6f9fdce88d8a3ca', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d2e7ab.jpg', 5, '2026-03-29 23:57:49'),
(225, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d368dd4.40607776_WhatsApp_Image_2026-03-25_at_19.02.46_63_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_63_.jpeg', 'image/jpeg', 138083, '25876f2e7d2dcdd5efafb2960c68990f5cb7a8ee7b4c651e08109b0d53be8e21', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d36978.jpg', 5, '2026-03-29 23:57:49'),
(226, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d402ac6.26597682_WhatsApp_Image_2026-03-25_at_19.02.46_64_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_64_.jpeg', 'image/jpeg', 155197, 'cef6d7f41688ce8bb460bc2d0ff478ff7640840159f5735ece9278ecd1a4e27e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d4035e.jpg', 5, '2026-03-29 23:57:49'),
(227, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d4b4eb9.95707747_WhatsApp_Image_2026-03-25_at_19.02.46_65_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_65_.jpeg', 'image/jpeg', 158876, '9d21be8c664f72dcdffebb731283c3934b5dfcf0d38c68a7e0c0675c0a5228a3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d4b609.jpg', 5, '2026-03-29 23:57:49'),
(228, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d560dd3.88093250_WhatsApp_Image_2026-03-25_at_19.02.46_66_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_66_.jpeg', 'image/jpeg', 154510, 'a536d9aead66fbc36a31b7216c08a544e2d7c083a7939623e7aa5304e79f2ba6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d561dc.jpg', 5, '2026-03-29 23:57:49'),
(229, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d5f85e4.80655316_WhatsApp_Image_2026-03-25_at_19.02.46_67_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_67_.jpeg', 'image/jpeg', 183545, '7094f1355b45333ce7a1e08954aa6bc82fd8e3c117e5b342c9332e6bf4074abe', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d5f8f5.jpg', 5, '2026-03-29 23:57:49'),
(230, 20, 379, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/f_69c9bc7d67bae9.99384584_WhatsApp_Image_2026-03-25_at_19.02.46_68_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_68_.jpeg', 'image/jpeg', 149482, '663b9914945a61648e70d37ab26ef7d9e0f886380fa387aed3122f675e7659cc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/20/379/thumb_69c9bc7d67c56.jpg', 5, '2026-03-29 23:57:49'),
(231, 17, 408, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/408/f_69c9bd9e3f5e75.72886535_1.jpg', NULL, '1.jpg', 'image/jpeg', 54499, '1ea544b076670cd5dbe077a7f51e06ee72bf5eadbdfb04c383995005e59b64b6', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/408/thumb_69c9bd9e3f698.jpg', 5, '2026-03-30 00:02:38'),
(232, 17, 408, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/408/f_69c9bd9e433d66.92617221_WhatsApp_Image_2026-03-25_at_19.02.46_56_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_56_.jpeg', 'image/jpeg', 161036, '1e02270fbae905e240b4b8b7bccbc248761772c2225d594758cd2a7929cc857d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/408/thumb_69c9bd9e43458.jpg', 5, '2026-03-30 00:02:38'),
(233, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddbdaf0c0.45371165_WhatsApp_Image_2026-03-25_at_19.02.46_41_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_41_.jpeg', 'image/jpeg', 136001, '495f59a1daf4675a0c0383cb930a5652b116d669290eae4164d8477d49aaab48', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddbdafe6.jpg', 5, '2026-03-30 00:03:39'),
(234, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddbe63bb2.01983320_WhatsApp_Image_2026-03-25_at_19.02.46_43_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_43_.jpeg', 'image/jpeg', 119730, '76051b523abbb717c1e4db8a6f1a63369839e1dd08d0184704f84b6a0f81107e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddbe6450.jpg', 5, '2026-03-30 00:03:39'),
(235, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddbf08621.15928817_WhatsApp_Image_2026-03-25_at_19.02.46_44_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_44_.jpeg', 'image/jpeg', 178067, '0b1fead1c07a25865085de132f4e9530ef0e1d954e98368f399b563eb20bd2d0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddbf08f7.jpg', 5, '2026-03-30 00:03:40'),
(236, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddc06c715.04425451_WhatsApp_Image_2026-03-25_at_19.02.46_48_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_48_.jpeg', 'image/jpeg', 141665, 'e63a9e26aef74b2a0306b7a1061647df024bffc1aab4747c4f723f32dff330f3', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddc06dc1.jpg', 5, '2026-03-30 00:03:40'),
(237, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddc1135d8.78778764_WhatsApp_Image_2026-03-25_at_19.02.46_50_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_50_.jpeg', 'image/jpeg', 132197, 'a2a924654a18b67814299a144cf9b09afa22491bbbf291804b59863d03b0ce89', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddc11437.jpg', 5, '2026-03-30 00:03:40'),
(238, 17, 412, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/f_69c9bddc1ab7e6.72895186_WhatsApp_Image_2026-03-25_at_19.02.46_51_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_51_.jpeg', 'image/jpeg', 103301, '0a87c6d38ded90e52b6c91b9eee03846dc35332daaf701929311684ea2614ae1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/17/412/thumb_69c9bddc1ac1d.jpg', 5, '2026-03-30 00:03:40'),
(239, 10, 452, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/10/452/f_69c9be37e4c875.73558120_WhatsApp_Image_2026-03-25_at_19.02.39_2_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.39_2_.jpeg', 'image/jpeg', 154311, 'fde8e5841679473b5cadb97998a29f126fc2f9ce75182e7d35c3d9ed376d082b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/10/452/thumb_69c9be37e4d58.jpg', 5, '2026-03-30 00:05:11'),
(240, 10, 452, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/10/452/f_69c9be37f3d401.37438887_WhatsApp_Image_2026-03-25_at_19.02.46_17_.jpeg', NULL, 'WhatsApp_Image_2026-03-25_at_19.02.46_17_.jpeg', 'image/jpeg', 110998, 'ea9f2b629f2be51721414287dcb121864799c7e1f08b0da85b4ada8ba0d4651e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/10/452/thumb_69c9be37f3ef2.jpg', 5, '2026-03-30 00:05:12'),
(241, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf4fc535c9.81715318_WhatsApp_Image_2026-03-29_at_19.22.45_44_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_44_.jpeg', 'image/jpeg', 929269, '439b7de006b71c4c959035e6e508d5f15e556f78f0230a8a2a64fa21800eded8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf4fc5471.jpg', 5, '2026-03-30 00:09:51'),
(242, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf4fd6b832.34372981_WhatsApp_Image_2026-03-29_at_19.22.45_45_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_45_.jpeg', 'image/jpeg', 854911, 'cd0a809699f6986a970d03f73b7d583170ef5242b39a6ac576bc23d8775b6ff1', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf4fd6cdb.jpg', 5, '2026-03-30 00:09:51'),
(243, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf4feade78.42759931_WhatsApp_Image_2026-03-29_at_19.22.45_46_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_46_.jpeg', 'image/jpeg', 825438, '60914bb4ba2a294a0d0ad73fca53f76145ca29d590287d1c90b6d9fed1b98e63', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf4feae9b.jpg', 5, '2026-03-30 00:09:52'),
(244, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf50049841.28668798_WhatsApp_Image_2026-03-29_at_19.22.45_51_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_51_.jpeg', 'image/jpeg', 730765, 'cabb305ac05cf692a7e25dd50b192acab0136649d93c8a6625dfec07c2196eb4', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf5004acf.jpg', 5, '2026-03-30 00:09:52'),
(245, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf50149a18.55669048_WhatsApp_Image_2026-03-29_at_19.22.45_52_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_52_.jpeg', 'image/jpeg', 725739, '7f3283b4ab2d0243a9915ec869e94c8a8326f80f079039da45d089a02cf9ac29', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf5014ae7.jpg', 5, '2026-03-30 00:09:52'),
(246, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf50210a80.50636197_WhatsApp_Image_2026-03-29_at_19.22.45_53_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_53_.jpeg', 'image/jpeg', 609473, 'b60f1f692611d5f7130643a95261c78ca533ce1af98b66632772da13b50f0967', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf502112d.jpg', 5, '2026-03-30 00:09:52'),
(247, 19, 391, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/f_69c9bf502e8cd3.64865547_WhatsApp_Image_2026-03-29_at_19.22.45_56_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_56_.jpeg', 'image/jpeg', 604132, '1b87b0edafcabec864d31ca8cff1be3e678f4fd8e427ab6e7d57a7b7334b96a0', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/19/391/thumb_69c9bf502e969.jpg', 5, '2026-03-30 00:09:52'),
(248, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472364721.77207293_WhatsApp_Image_2026-03-29_at_19.22.45_5_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_5_.jpeg', 'image/jpeg', 614842, 'cc3b719cb46d3f6c3b481904176d947575d3ab1d31f2d3783d5ee019b76d42ac', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47236609.jpg', 5, '2026-03-30 00:31:46'),
(249, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472492248.10999998_WhatsApp_Image_2026-03-29_at_19.22.45_6_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_6_.jpeg', 'image/jpeg', 424618, '2bb31c5ee9abf1bd7440b31600d4810688bf98e699444b96deda42426a5f327b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47249426.jpg', 5, '2026-03-30 00:31:46'),
(250, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c47258d267.88022347_WhatsApp_Image_2026-03-29_at_19.22.45_8_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_8_.jpeg', 'image/jpeg', 818575, '26666525b6b16bb7a24ea7b0e72b418a8c9d366f945e2b964492b720cfbb7a13', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c4725900b.jpg', 5, '2026-03-30 00:31:46'),
(251, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c4726864e5.17165573_WhatsApp_Image_2026-03-29_at_19.22.45_9_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_9_.jpeg', 'image/jpeg', 828264, '993d434904eaf07b73675631c8bc5768bbc570aaf3d4685460689747cbf50b84', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47268778.jpg', 5, '2026-03-30 00:31:46'),
(252, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c47278c7f4.44237298_WhatsApp_Image_2026-03-29_at_19.22.45_10_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_10_.jpeg', 'image/jpeg', 543816, 'f741417e4967dfaf133172ac5e318fc968302cea89f900105eb5877209e5fee8', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47278d61.jpg', 5, '2026-03-30 00:31:46'),
(253, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472863686.32276297_WhatsApp_Image_2026-03-29_at_19.22.45_11_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_11_.jpeg', 'image/jpeg', 882360, '1c7289a01307c21dd66cf752aaa4739ed20988b05f9d4d828da790b1a67826bf', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472864a9.jpg', 5, '2026-03-30 00:31:46'),
(254, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472946ef9.55924492_WhatsApp_Image_2026-03-29_at_19.22.45_12_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_12_.jpeg', 'image/jpeg', 872050, '01ac342bf62f96b9114617220deab1f679ea3f7a3c7dd4964c3b6edaf3d08846', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47294869.jpg', 5, '2026-03-30 00:31:46'),
(255, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472a3d2e1.11114837_WhatsApp_Image_2026-03-29_at_19.22.45_13_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_13_.jpeg', 'image/jpeg', 824166, 'cc2606afeb801a7564d90a9885a103e898375d4099ec0e76ba5ae1ea61c00e13', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472a3dff.jpg', 5, '2026-03-30 00:31:46'),
(256, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472b45b83.19081918_WhatsApp_Image_2026-03-29_at_19.22.45_14_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_14_.jpeg', 'image/jpeg', 786053, '932b13c009b27922650b27b661254ad0633a2ca3adc70f190c3eeb87024849cc', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472b4671.jpg', 5, '2026-03-30 00:31:46'),
(257, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472c5af85.48379956_WhatsApp_Image_2026-03-29_at_19.22.45_15_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_15_.jpeg', 'image/jpeg', 1023615, '37766cd82540de93dc10365ca9b1d9bb0595a5375d7cd1d95cafef0e71c5b497', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472c5bd0.jpg', 5, '2026-03-30 00:31:46'),
(258, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472d3fcc0.50029648_WhatsApp_Image_2026-03-29_at_19.22.45_16_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_16_.jpeg', 'image/jpeg', 687866, '364c6474e3097e92ef87728a80bcc52477b161f8305b8ac698870ab030d21a96', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472d404c.jpg', 5, '2026-03-30 00:31:46'),
(259, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472e130b6.54756018_WhatsApp_Image_2026-03-29_at_19.22.45_19_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_19_.jpeg', 'image/jpeg', 448839, '38be60f110ba8f9e9477a706c863ecef4f5d639e28f085a682f2fb3b84fff4ca', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472e13a9.jpg', 5, '2026-03-30 00:31:46'),
(260, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c472ee8246.62150256_WhatsApp_Image_2026-03-29_at_19.22.45_20_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_20_.jpeg', 'image/jpeg', 792299, 'b35cf7b5399f67c6668ab1528e4b8a1f54326af48a91cdcbd4f6661c51ea563d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c472ee8f1.jpg', 5, '2026-03-30 00:31:47'),
(261, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c473091eb4.21411719_WhatsApp_Image_2026-03-29_at_19.22.45_21_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_21_.jpeg', 'image/jpeg', 798769, '5fb95ecabba898c6c92fecb0b7e4f229103632b6b1b09ba6e21026865d215442', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47309288.jpg', 5, '2026-03-30 00:31:47'),
(262, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c4731981a0.86909253_WhatsApp_Image_2026-03-29_at_19.22.45_22_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_22_.jpeg', 'image/jpeg', 751560, '714429a8fb094f9a2c9b019ea3b0c080aea61b017da98eddc0cb1ed12153ee9c', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c473198c4.jpg', 5, '2026-03-30 00:31:47'),
(263, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c4732785c0.40022476_WhatsApp_Image_2026-03-29_at_19.22.45_23_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_23_.jpeg', 'image/jpeg', 671208, 'fe2d8414dba8dd212fca3a3261402ba17bdcb653a45f32beeb9ac23e87bede0d', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47327905.jpg', 5, '2026-03-30 00:31:47'),
(264, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c47336d286.97343518_WhatsApp_Image_2026-03-29_at_19.22.45_24_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_24_.jpeg', 'image/jpeg', 668762, '254ccae228f14425a35afd2fb01e72f8b0b4c8eeef620e9b86dcb10d9ee44bfb', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47336dc2.jpg', 5, '2026-03-30 00:31:47'),
(265, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c47347ca06.29910648_WhatsApp_Image_2026-03-29_at_19.22.45_25_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_25_.jpeg', 'image/jpeg', 734955, '47f3d869b50ba8bc305a279643d1d3ece3e2ef3987bb6d1f5c6918493b286272', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47347d4f.jpg', 5, '2026-03-30 00:31:47'),
(266, 16, 422, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/f_69c9c473594c73.13766805_WhatsApp_Image_2026-03-29_at_19.22.45_26_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_26_.jpeg', 'image/jpeg', 619504, '1a857a9d1746904d528a4f2cd7d6c869d14926c4339a74e672f424aca68b6c4b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/16/422/thumb_69c9c47359674.jpg', 5, '2026-03-30 00:31:47'),
(267, 11, 442, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/442/f_69c9c4f6bf5377.19604918_WhatsApp_Image_2026-03-29_at_19.22.45.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45.jpeg', 'image/jpeg', 854165, 'c4583c4db866e8a65fd5a17490c1df8e9bb2c308f0d20c6e3cc113063119852e', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/442/thumb_69c9c4f6bf6bf.jpg', 5, '2026-03-30 00:33:58'),
(268, 11, 446, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/446/f_69c9c51be01987.93213615_WhatsApp_Image_2026-03-29_at_19.22.45_30_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_30_.jpeg', 'image/jpeg', 723938, '0841bf47397587c1b2bdc759fbd9591c03f9fb076d2c6a9d0c92034cfffffbca', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/446/thumb_69c9c51be02d6.jpg', 5, '2026-03-30 00:34:35'),
(269, 11, 449, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/449/f_69c9c53f378889.29610768_WhatsApp_Image_2026-03-29_at_19.22.45_1_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_1_.jpeg', 'image/jpeg', 621966, 'ecf89abeea24ac9214ebed0cca29193438d495fbb12b805783812f7d39ca234b', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/449/thumb_69c9c53f3798f.jpg', 5, '2026-03-30 00:35:11'),
(270, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c5576ab585.00054586_WhatsApp_Image_2026-03-29_at_19.22.45_31_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_31_.jpeg', 'image/jpeg', 555878, 'de002f7c7beb08b23db35f9ebe0ce0fb02b5a33ee2cacb2b0eff4b89b52aad1a', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c5576ac34.jpg', 5, '2026-03-30 00:35:35'),
(271, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c5577c1df1.50999050_WhatsApp_Image_2026-03-29_at_19.22.45_32_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_32_.jpeg', 'image/jpeg', 642509, 'acc9d6421f6c4dbd8cff9a2190d53ade92b8fb30f3193eaca71ae7d0957c7044', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c5577c288.jpg', 5, '2026-03-30 00:35:35'),
(272, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c5578d99b3.15137558_WhatsApp_Image_2026-03-29_at_19.22.45_33_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_33_.jpeg', 'image/jpeg', 533970, '3ec5b044a50db74ce8e64111696f68b0519a705ad483a6a56aa91be508500189', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c5578db60.jpg', 5, '2026-03-30 00:35:35'),
(273, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c5579b3532.92497697_WhatsApp_Image_2026-03-29_at_19.22.45_36_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_36_.jpeg', 'image/jpeg', 830639, 'e47678745adb8f5708efe00b0f061bd9e9700b4070f24d43c5320b306e54eded', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c5579b426.jpg', 5, '2026-03-30 00:35:35'),
(274, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c557acead2.69729602_WhatsApp_Image_2026-03-29_at_19.22.45_39_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_39_.jpeg', 'image/jpeg', 650393, '085fed5233781b1ca8a27d80cadffc6e087eae0b1144659d953a555f9265f7df', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c557acf82.jpg', 5, '2026-03-30 00:35:35'),
(275, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c557ba5f21.16398661_WhatsApp_Image_2026-03-29_at_19.22.45_40_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_40_.jpeg', 'image/jpeg', 812621, 'b33937a13a418433bc0d88e2f3a165a3477ea02faf6e576c7c680a409678abd5', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c557ba73d.jpg', 5, '2026-03-30 00:35:35'),
(276, 11, 450, '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/f_69c9c55d8af1e6.83581054_WhatsApp_Image_2026-03-29_at_19.22.45_41_.jpeg', NULL, 'WhatsApp_Image_2026-03-29_at_19.22.45_41_.jpeg', 'image/jpeg', 742987, 'e6285f6242e1dc97556f007a2a7dd2b18e9f039af4ebd283ef8ec7fdbf856f79', '/home/donaconsultorias/htdocs/donaconsultorias.com.br/app/controllers/../../storage/auditorias/11/450/thumb_69c9c55d8b060.jpg', 5, '2026-03-30 00:35:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_avaliacoes`
--

CREATE TABLE `auditoria_avaliacoes` (
  `id` int NOT NULL,
  `auditoria_id` int NOT NULL,
  `questao_id` int NOT NULL,
  `conformidade` enum('pendente','conforme','nao_conforme','nao_aplica') NOT NULL DEFAULT 'pendente',
  `observacoes` text,
  `auto_saved_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `auditoria_avaliacoes`
--

INSERT INTO `auditoria_avaliacoes` (`id`, `auditoria_id`, `questao_id`, `conformidade`, `observacoes`, `auto_saved_at`, `finalized_at`, `updated_by`, `created_at`, `updated_at`) VALUES
(31, 2, 11, 'nao_conforme', 'O microplanejamento não vem com local destinado a montagem das praças', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(32, 2, 12, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(33, 2, 13, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(34, 2, 14, 'nao_conforme', 'Árvore com dimensão muito grande, gerando excesso de peso no equipamento. Necessidade de trazer menos madeira em cada puxada.', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(35, 2, 15, 'nao_conforme', 'Operador deixando a madeira muito na traseira do equipamento, gerando muito arrasto no percurso.', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(36, 2, 16, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(37, 2, 17, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(38, 2, 18, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(39, 2, 19, 'conforme', '', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(40, 2, 20, 'nao_conforme', 'Equipamento manobrando muito próximo a grua', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(41, 2, 21, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(42, 2, 22, 'nao_conforme', 'Equipamento sem lubrificação diária e com desgastes excessivos em componentes básicos. Pneu com ressolagem solta, risco de acidente na área.', '2026-03-28 19:29:00', '2026-03-28 19:29:00', 5, '2026-03-28 19:23:41', '2026-03-28 19:29:00'),
(280, 8, 113, 'nao_conforme', 'Itens a serem descatados', '2026-03-29 21:49:42', '2026-03-29 21:49:42', 5, '2026-03-29 21:48:40', '2026-03-29 21:49:42'),
(281, 8, 114, 'nao_conforme', 'itens fora de local correto', '2026-03-29 21:49:42', '2026-03-29 21:49:42', 5, '2026-03-29 21:48:40', '2026-03-29 21:49:42'),
(282, 8, 115, 'nao_conforme', 'area de vivencia inadequada para uso', '2026-03-29 21:49:42', '2026-03-29 21:49:42', 5, '2026-03-29 21:48:40', '2026-03-29 21:49:42'),
(283, 8, 116, 'nao_conforme', 'Itens contaminando solo', '2026-03-29 21:49:42', '2026-03-29 21:49:42', 5, '2026-03-29 21:48:40', '2026-03-29 21:49:42'),
(284, 8, 117, 'nao_conforme', 'Contaminacao do solo', '2026-03-29 21:49:42', '2026-03-29 21:49:42', 5, '2026-03-29 21:48:40', '2026-03-29 21:49:42'),
(330, 12, 436, 'nao_conforme', 'Itens a descartar na área', '2026-03-29 23:21:11', '2026-03-29 23:21:11', 5, '2026-03-29 23:18:32', '2026-03-29 23:21:11'),
(331, 12, 437, 'nao_conforme', 'Itens fora do local ideal na área', '2026-03-29 23:21:11', '2026-03-29 23:21:11', 5, '2026-03-29 23:18:32', '2026-03-29 23:21:11'),
(332, 12, 438, 'nao_conforme', 'Manutenção e limpeza do comboio', '2026-03-29 23:21:11', '2026-03-29 23:21:11', 5, '2026-03-29 23:18:32', '2026-03-29 23:21:11'),
(333, 12, 439, 'nao_conforme', 'Estrutura de apoio e comboio com oportunidades de melhorias', '2026-03-29 23:21:11', '2026-03-29 23:21:11', 5, '2026-03-29 23:18:32', '2026-03-29 23:21:11'),
(334, 12, 440, 'conforme', 'Realocar galões de maneira correta', '2026-03-29 23:21:11', '2026-03-29 23:21:11', 5, '2026-03-29 23:18:32', '2026-03-29 23:21:11'),
(390, 14, 471, 'nao_conforme', 'Muitos itens a descartar na área', '2026-03-29 23:29:21', '2026-03-29 23:29:21', 5, '2026-03-29 23:27:48', '2026-03-29 23:29:21'),
(391, 14, 472, 'nao_conforme', 'Ferramental a organizar na área de vivencia', '2026-03-29 23:29:21', '2026-03-29 23:29:21', 5, '2026-03-29 23:27:48', '2026-03-29 23:29:21'),
(392, 14, 473, 'nao_conforme', 'Maquina de afiar facas e banheiro da área de vivencia sujos', '2026-03-29 23:29:21', '2026-03-29 23:29:21', 5, '2026-03-29 23:27:48', '2026-03-29 23:29:21'),
(393, 14, 474, 'nao_conforme', 'área de banheiro sendo usada como estoque, porém sem organização minima necessária', '2026-03-29 23:29:21', '2026-03-29 23:29:21', 5, '2026-03-29 23:27:48', '2026-03-29 23:29:21'),
(394, 14, 475, 'nao_conforme', 'Material contaminante em ambiente aberto.', '2026-03-29 23:29:21', '2026-03-29 23:29:21', 5, '2026-03-29 23:27:48', '2026-03-29 23:29:21'),
(415, 21, 364, 'nao_conforme', 'Não temos a padronização do microplanejamento na área', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(416, 21, 365, 'conforme', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(417, 21, 366, 'conforme', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(418, 21, 367, 'conforme', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(419, 21, 368, 'conforme', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(420, 21, 369, 'conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(421, 21, 370, 'conforme', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(422, 21, 371, 'nao_aplica', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(423, 21, 372, 'nao_aplica', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(424, 21, 373, 'nao_aplica', '', '2026-03-29 23:42:25', '2026-03-29 23:42:25', 5, '2026-03-29 23:38:48', '2026-03-29 23:42:25'),
(485, 25, 340, 'nao_conforme', 'O microplanejamento não vem com local destinado a montagem das praças', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(486, 25, 341, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(487, 25, 342, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(488, 25, 343, 'nao_conforme', 'Árvore com dimensão muito grande, gerando excesso de peso no equipamento. Necessidade de trazer menos madeira em cada puxada.', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(489, 25, 344, 'nao_conforme', 'Operador deixando a madeira muito na traseira do equipamento, gerando muito arrasto no percurso.', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(490, 25, 345, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(491, 25, 346, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(492, 25, 347, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(493, 25, 348, 'conforme', '', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(494, 25, 349, 'nao_conforme', 'Equipamento manobrando muito próximo a grua', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(495, 25, 350, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(496, 25, 351, 'nao_conforme', 'Equipamento sem lubrificação diária e com desgastes excessivos em componentes básicos. Pneu com ressolagem solta, risco de acidente na área.', '2026-03-29 23:46:44', '2026-03-29 23:46:44', 5, '2026-03-29 23:45:14', '2026-03-29 23:46:44'),
(545, 18, 394, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(546, 18, 395, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(547, 18, 396, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(548, 18, 397, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(549, 18, 398, 'nao_conforme', 'A madeira esta sendo colocada de maneira desalinhada a mesa de picagem', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(550, 18, 399, 'nao_conforme', 'Operador do picador fica em cima da pilha de cavado, risco de acidente', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(551, 18, 400, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(552, 18, 401, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(553, 18, 402, 'conforme', '', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(554, 18, 403, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-29 23:49:26', '2026-03-29 23:49:26', 5, '2026-03-29 23:48:33', '2026-03-29 23:49:26'),
(575, 15, 461, 'nao_conforme', 'Microplanejamento não tem o detalhamento da sequencia a ser seguida para o carregamento', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(576, 15, 462, 'nao_conforme', 'Acessos com condições ruins', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(577, 15, 463, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(578, 15, 464, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(579, 15, 465, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(580, 15, 466, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(581, 15, 467, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(582, 15, 468, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(583, 15, 469, 'conforme', '', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(584, 15, 470, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-29 23:51:14', '2026-03-29 23:51:14', 5, '2026-03-29 23:50:45', '2026-03-29 23:51:14'),
(595, 20, 374, 'nao_conforme', 'Não temos a padronização do microplanejamento na área', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(596, 20, 375, 'conforme', '', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(597, 20, 376, 'conforme', '', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(598, 20, 377, 'conforme', '', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(599, 20, 378, 'conforme', '', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(600, 20, 379, 'nao_conforme', 'Estamos somente com o BDO implantado na operação. E equipamento sem a realização da lubrificação, sopragem e lavagem.', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(601, 20, 380, 'nao_conforme', 'Equipamento com antena de dados avariada', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(602, 20, 381, 'nao_conforme', 'Equipamento com manutenção de campo pendente (lubrificação, sopragem e lavagem)', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(603, 20, 382, 'conforme', 'OBS. Operador informou que não tinha vidias reservas na operação.', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(604, 20, 383, 'nao_conforme', 'Equipamento com contador de arvores cortadas avariado.', '2026-03-29 23:59:12', '2026-03-29 23:59:12', 5, '2026-03-29 23:54:40', '2026-03-29 23:59:12'),
(705, 24, 352, 'nao_conforme', 'O microplanejamento não vem com local destinado a montagem das praças', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(706, 24, 353, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(707, 24, 354, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(708, 24, 355, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(709, 24, 356, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(710, 24, 357, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(711, 24, 358, 'conforme', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(712, 24, 359, 'conforme', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(713, 24, 360, 'conforme', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(714, 24, 361, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(715, 24, 362, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(716, 24, 363, 'nao_aplica', '', '2026-03-30 00:01:02', '2026-03-30 00:01:02', 5, '2026-03-30 00:00:44', '2026-03-30 00:01:02'),
(729, 17, 404, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(730, 17, 405, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(731, 17, 406, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(732, 17, 407, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(733, 17, 408, 'nao_conforme', 'A madeira esta sendo colocada de maneira desalinhada a mesa de picagem', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(734, 17, 409, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(735, 17, 410, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(736, 17, 411, 'conforme', '', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(737, 17, 412, 'nao_conforme', 'Equipamento com itens de limpeza geral pendente', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(738, 17, 413, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-30 00:03:44', '2026-03-30 00:03:44', 5, '2026-03-30 00:02:26', '2026-03-30 00:03:44'),
(769, 10, 451, 'nao_conforme', 'Microplanejamento não tem o detalhamento da sequencia a ser seguida para o carregamento', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(770, 10, 452, 'nao_conforme', 'Acessos com condições ruins', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(771, 10, 453, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(772, 10, 454, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(773, 10, 455, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(774, 10, 456, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(775, 10, 457, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(776, 10, 458, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(777, 10, 459, 'nao_aplica', 'Devido a chuva carregamento parado', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(778, 10, 460, 'nao_conforme', 'Estamos somente com o BDO implantado na operação', '2026-03-30 00:06:15', '2026-03-30 00:06:15', 5, '2026-03-30 00:05:00', '2026-03-30 00:06:15'),
(809, 19, 384, 'nao_conforme', 'Não temos a programação do microplanejamento', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(810, 19, 385, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(811, 19, 386, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(812, 19, 387, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(813, 19, 388, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(814, 19, 389, 'nao_conforme', 'Somente com BDO para controle de manutenção.', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(815, 19, 390, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(816, 19, 391, 'nao_conforme', 'Equipamento com sopragem e algumas manutenções pendentes.', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(817, 19, 392, 'conforme', '', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(818, 19, 393, 'nao_conforme', 'BDO ainda em fase de ajuste para esta medição.', '2026-03-30 00:10:28', '2026-03-30 00:10:28', 5, '2026-03-30 00:07:29', '2026-03-30 00:10:28'),
(879, 23, 424, 'nao_conforme', 'Microplanejamento ainda em desenvolvimento, sem local claro para as praças', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(880, 23, 425, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(881, 23, 426, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(882, 23, 427, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(883, 23, 428, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(884, 23, 429, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(885, 23, 430, 'conforme', 'OBS. Pilhas com aproximadamente 2,00 a 2,20m', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(886, 23, 431, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(887, 23, 432, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(888, 23, 433, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(889, 23, 434, 'nao_conforme', 'Somente com BDO para registro de manutenção.', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(890, 23, 435, 'conforme', '', '2026-03-30 00:16:21', '2026-03-30 00:16:21', 5, '2026-03-30 00:11:43', '2026-03-30 00:16:21'),
(1011, 16, 414, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1012, 16, 415, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1013, 16, 416, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1014, 16, 417, 'nao_conforme', 'Praça apertada dificultando área de manobra da grua', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1015, 16, 418, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1016, 16, 419, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1017, 16, 420, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1018, 16, 421, 'conforme', '', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1019, 16, 422, 'nao_conforme', 'Grua com vários itens pendentes de manutenção e picador com avarias.', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1020, 16, 423, 'nao_conforme', 'Estamos somente com o BDO para controle de manutenção.', '2026-03-30 00:32:00', '2026-03-30 00:32:00', 5, '2026-03-30 00:23:17', '2026-03-30 00:32:00'),
(1201, 11, 441, 'nao_conforme', 'Microplanejamento ainda não esta implementado corretamente.', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1202, 11, 442, 'nao_conforme', 'Muita areia devido a excesso de chuvas', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1203, 11, 443, 'conforme', '', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1204, 11, 444, 'conforme', '', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1205, 11, 445, 'conforme', '', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1206, 11, 446, 'nao_conforme', 'Cargas com excesso de altura', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1207, 11, 447, 'conforme', '', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1208, 11, 448, 'conforme', '', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1209, 11, 449, 'nao_conforme', 'Veículos indo até o ponto de apoio com a lona aberta', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44'),
(1210, 11, 450, 'nao_conforme', 'Carregadeira com itens pendentes de manutenção', '2026-03-30 00:35:44', '2026-03-30 00:35:44', 5, '2026-03-30 00:33:12', '2026-03-30 00:35:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_avaliacoes_log`
--

CREATE TABLE `auditoria_avaliacoes_log` (
  `id` int NOT NULL,
  `auditoria_id` int NOT NULL,
  `questao_id` int NOT NULL,
  `old_observacoes` text,
  `new_observacoes` text,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_questoes`
--

CREATE TABLE `auditoria_questoes` (
  `id` int NOT NULL,
  `auditoria_id` int NOT NULL,
  `responsavel_nome` varchar(180) NOT NULL,
  `pergunta` text NOT NULL,
  `referencia_esperada` text NOT NULL,
  `processos_json` text,
  `ordem` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `auditoria_questoes`
--

INSERT INTO `auditoria_questoes` (`id`, `auditoria_id`, `responsavel_nome`, `pergunta`, `referencia_esperada`, `processos_json`, `ordem`, `created_at`, `updated_at`) VALUES
(11, 2, 'ALEX AFONSO DONA', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', '[]', 1, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(12, 2, 'ALEX AFONSO DONA', 'O operador do skidder está respeitando o sentido de arraste definido a partir do posicionamento do pé das árvores?', 'Observação direta em campo e alinhamento das árvores nas trilhas de arraste.', '[]', 2, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(13, 2, 'ALEX AFONSO DONA', 'Durante o arraste estão sendo evitados trilheiros repetitivos no interior do talhão?', 'Inspeção visual do talhão e verificação das trilhas de operação.', '[]', 3, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(14, 2, 'ALEX AFONSO DONA', 'O equipamento skidder está operando sem sobrecarga e sem trabalhar apenas com a tração traseira?', 'Observação da operação, entrevista com operador e verificação de comportamento do equipamento.', '[]', 4, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(15, 2, 'ALEX AFONSO DONA', 'As toras estão sendo arrastadas com o mínimo de contato com o solo conforme orientado no manual?', 'Observação direta da operação e condição das toras arrastadas.', '[]', 5, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(16, 2, 'ALEX AFONSO DONA', 'O operador está respeitando a orientação de não passar por cima das pontas ou de madeira já arrastada?', 'Inspeção visual das trilhas de arraste e organização da madeira.', '[]', 6, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(17, 2, 'ALEX AFONSO DONA', 'A organização da praça de estocagem respeita a altura máxima de pilha de aproximadamente 1,80 metros?', 'Medição visual ou com régua de pilhas de madeira na praça.', '[]', 7, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(18, 2, 'ALEX AFONSO DONA', 'O raio médio da praça está próximo de 40 metros a partir da rampa ou conforme microplanejamento?', 'Medição aproximada em campo ou verificação do microplanejamento.', '[]', 8, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(19, 2, 'ALEX AFONSO DONA', 'As pilhas de madeira estão posicionadas de forma alinhada com a mesa de alimentação do picador?', 'Observação visual da orientação das pilhas em relação ao local de picagem.', '[]', 9, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(20, 2, 'ALEX AFONSO DONA', 'Existe espaço adequado para manobra segura dos equipamentos na praça de estocagem?', 'Inspeção visual da praça e avaliação da área de circulação dos equipamentos.', '[]', 10, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(21, 2, 'ALEX AFONSO DONA', 'O check-list diário de manutenção do skider está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 11, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(22, 2, 'ALEX AFONSO DONA', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 12, '2026-03-28 19:23:03', '2026-03-28 19:23:03'),
(48, 6, 'Alex Afonso Dona', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', '[]', 1, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(49, 6, 'Alex Afonso Dona', 'O operador está direcionando a derrubada das árvores a aproximadamente 90° em relação às linhas de plantio conforme definido no manual?', 'Observação em campo, alinhamento das árvores no solo, instruções operacionais.', '[]', 2, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(50, 6, 'Alex Afonso Dona', 'Antes do início da operação, foi realizada a avaliação das condições do terreno (formigueiros, subsolagem, curvas de nível e vento)?', 'Check-list operacional, registros de DDS ou orientação operacional, entrevista com operador.', '[]', 3, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(51, 6, 'Alex Afonso Dona', 'A altura média dos tocos está sendo mantida próxima ao padrão técnico de aproximadamente 5 cm?', 'Inspeção visual em campo, medição aleatória de tocos.', '[]', 4, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(52, 6, 'Alex Afonso Dona', 'O operador está realizando o corte seguindo uma linha por vez e minimizando o deslocamento do equipamento conforme orientado?', 'Observação direta da operação e entrevista com operador.', '[]', 5, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(53, 6, 'Alex Afonso Dona', 'O check-list diário de manutenção do Feller Buncher está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 6, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(54, 6, 'Alex Afonso Dona', 'Quando são identificadas falhas no equipamento, elas estão sendo reportadas imediatamente ao líder da operação?', 'Registro de manutenção, ordens de serviço, comunicação registrada ao líder.', '[]', 7, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(55, 6, 'Alex Afonso Dona', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 8, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(56, 6, 'Alex Afonso Dona', 'O operador monitora o desgaste das vidias e realiza inversão ou substituição quando necessário?', 'Registro de manutenção, estoque de vidias usadas e novas, entrevista com operador.', '[]', 9, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(57, 6, 'Alex Afonso Dona', 'Os boletins diários de colheita estão sendo preenchidos corretamente com o volume de árvores ou bunchers cortados no turno?', 'Boletins diários de colheita preenchidos, registros de produção ou planilhas operacionais.', '[]', 10, '2026-03-29 21:37:50', '2026-03-29 21:37:50'),
(68, 1, 'Alex Afonso Dona', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', '[]', 1, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(69, 1, 'Alex Afonso Dona', 'O operador está direcionando a derrubada das árvores a aproximadamente 90° em relação às linhas de plantio conforme definido no manual?', 'Observação em campo, alinhamento das árvores no solo, instruções operacionais.', '[]', 2, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(70, 1, 'Alex Afonso Dona', 'Antes do início da operação, foi realizada a avaliação das condições do terreno (formigueiros, subsolagem, curvas de nível e vento)?', 'Check-list operacional, registros de DDS ou orientação operacional, entrevista com operador.', '[]', 3, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(71, 1, 'Alex Afonso Dona', 'A altura média dos tocos está sendo mantida próxima ao padrão técnico de aproximadamente 5 cm?', 'Inspeção visual em campo, medição aleatória de tocos.', '[]', 4, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(72, 1, 'Alex Afonso Dona', 'O operador está realizando o corte seguindo uma linha por vez e minimizando o deslocamento do equipamento conforme orientado?', 'Observação direta da operação e entrevista com operador.', '[]', 5, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(73, 1, 'Alex Afonso Dona', 'O check-list diário de manutenção do Feller Buncher está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 6, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(74, 1, 'Alex Afonso Dona', 'Quando são identificadas falhas no equipamento, elas estão sendo reportadas imediatamente ao líder da operação?', 'Registro de manutenção, ordens de serviço, comunicação registrada ao líder.', '[]', 7, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(75, 1, 'Alex Afonso Dona', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 8, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(76, 1, 'Alex Afonso Dona', 'O operador monitora o desgaste das vidias e realiza inversão ou substituição quando necessário?', 'Registro de manutenção, estoque de vidias usadas e novas, entrevista com operador.', '[]', 9, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(77, 1, 'Alex Afonso Dona', 'Os boletins diários de colheita estão sendo preenchidos corretamente com o volume de árvores ou bunchers cortados no turno?', 'Boletins diários de colheita preenchidos, registros de produção ou planilhas operacionais.', '[]', 10, '2026-03-29 21:39:22', '2026-03-29 21:39:22'),
(83, 4, 'ALEX AFONSO DONA', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', '[]', 1, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(84, 4, 'ALEX AFONSO DONA', 'Antes do início do carregamento, o responsável logístico realizou vistoria dos acessos para caminhões e equipamentos?', 'Registro de inspeção da área, comunicação da logística ou entrevista com responsável.', '[]', 2, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(85, 4, 'ALEX AFONSO DONA', 'A rampa de carregamento foi construída e nivelada corretamente (terra + camada de cavaco) antes do início das atividades?', 'Inspeção visual da rampa e entrevista com operador ou líder de operação.', '[]', 3, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(86, 4, 'ALEX AFONSO DONA', 'O operador da pá carregadeira evita raspar diretamente a praça de cavaco durante o carregamento?', 'Observação direta da operação e inspeção visual da praça.', '[]', 4, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(87, 4, 'ALEX AFONSO DONA', 'Quando são identificados materiais indesejáveis na praça (galhos, pedras, madeira), eles são removidos antes do carregamento?', 'Observação em campo ou evidência de retirada manual/mecânica do material.', '[]', 5, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(88, 4, 'ALEX AFONSO DONA', 'O carregamento respeita o limite de altura da carga (aprox. 30 cm abaixo da borda do caixote ou nível da borda para madeira verde)?', 'Observação visual da carga nos caminhões e medição aproximada do nível da carga.', '[]', 6, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(89, 4, 'ALEX AFONSO DONA', 'O operador mantém comunicação visual ou sonora com o motorista antes de iniciar ou finalizar o carregamento?', 'Observação da operação e uso dos sinais sonoros definidos.', '[]', 7, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(90, 4, 'ALEX AFONSO DONA', 'Os sinais sonoros (1, 2, 3 buzinas e buzina contínua) estão sendo utilizados conforme o padrão definido no manual?', 'Observação em campo e entrevista com operador e motoristas.', '[]', 8, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(91, 4, 'ALEX AFONSO DONA', 'O caminhão é obrigatoriamente enlonado antes de sair da rampa de carregamento?', 'Inspeção visual do caminhão após carregamento.', '[]', 9, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(92, 4, 'ALEX AFONSO DONA', 'O check-list de manutenção da pá carregadeira está sendo preenchido no início do turno?', 'Check-list de manutenção preenchido e assinado pelo operador ou responsável.', '[]', 10, '2026-03-29 21:44:19', '2026-03-29 21:44:19'),
(93, 3, 'ALEX AFONSO DONA', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', '[]', 1, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(94, 3, 'ALEX AFONSO DONA', 'A retirada das toras da pilha está sendo realizada da ponta para o centro, alternando os lados da pilha conforme definido no procedimento?', 'Observação em campo e organização da pilha durante a alimentação.', '[]', 2, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(95, 3, 'ALEX AFONSO DONA', 'O picador está sendo posicionado e operado no sentido favorável ao vento durante a operação?', 'Observação da operação e posicionamento do equipamento em relação à direção do vento.', '[]', 3, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(96, 3, 'ALEX AFONSO DONA', 'A escavadeira está sendo posicionada a uma distância segura da pilha e do picador durante o processo de alimentação?', 'Observação direta da operação e posicionamento do equipamento.', '[]', 4, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(97, 3, 'ALEX AFONSO DONA', 'A madeira está sendo colocada primeiro sobre a mesa de alimentação ou rolo auxiliar antes de entrar no sistema principal do picador?', 'Observação da alimentação da madeira durante a operação.', '[]', 5, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(98, 3, 'ALEX AFONSO DONA', 'O operador mantém comunicação com a equipe de apoio por rádio ou sinais visuais antes de iniciar movimentos de alimentação?', 'Entrevista com operadores e observação da comunicação durante a operação.', '[]', 6, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(99, 3, 'ALEX AFONSO DONA', 'O operador monitora continuamente a rotação do equipamento e a qualidade dos cavacos produzidos (overs e finos)?', 'Inspeção visual dos cavacos produzidos e entrevista com operador.', '[]', 7, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(100, 3, 'ALEX AFONSO DONA', 'A inspeção visual do equipamento para identificação de vazamentos está sendo realizada aproximadamente a cada 20 minutos de operação?', 'Observação em campo e entrevista com operador responsável.', '[]', 8, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(101, 3, 'ALEX AFONSO DONA', 'A limpeza do picador está sendo realizada a cada 4 horas de operação conforme definido no procedimento?', 'Registro operacional, check-list ou observação das condições do equipamento.', '[]', 9, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(102, 3, 'ALEX AFONSO DONA', 'O check-list de manutenção do equipamento está sendo preenchido no início de cada turno?', 'Check-list de manutenção preenchido e validado pelo operador ou responsável.', '[]', 10, '2026-03-29 21:44:40', '2026-03-29 21:44:40'),
(108, 5, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 21:45:35', '2026-03-29 21:45:35'),
(109, 5, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 21:45:35', '2026-03-29 21:45:35'),
(110, 5, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 21:45:35', '2026-03-29 21:45:35'),
(111, 5, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 21:45:35', '2026-03-29 21:45:35'),
(112, 5, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 21:45:35', '2026-03-29 21:45:35'),
(113, 8, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 21:45:49', '2026-03-29 21:45:49'),
(114, 8, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 21:45:49', '2026-03-29 21:45:49'),
(115, 8, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 21:45:49', '2026-03-29 21:45:49'),
(116, 8, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 21:45:49', '2026-03-29 21:45:49'),
(117, 8, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 21:45:49', '2026-03-29 21:45:49'),
(148, 7, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 22:29:32', '2026-03-29 22:29:32'),
(149, 7, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 22:29:32', '2026-03-29 22:29:32'),
(150, 7, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 22:29:32', '2026-03-29 22:29:32'),
(151, 7, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 22:29:32', '2026-03-29 22:29:32'),
(152, 7, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 22:29:32', '2026-03-29 22:29:32'),
(153, 9, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 22:30:44', '2026-03-29 22:30:44'),
(154, 9, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 22:30:44', '2026-03-29 22:30:44'),
(155, 9, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 22:30:44', '2026-03-29 22:30:44'),
(156, 9, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 22:30:44', '2026-03-29 22:30:44'),
(157, 9, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 22:30:44', '2026-03-29 22:30:44'),
(298, 22, 'ALEX AFONSO DONA', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', '[]', 1, '2026-03-29 22:50:04', '2026-03-29 22:50:04'),
(340, 25, 'ALEX AFONSO DONA', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', '[]', 1, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(341, 25, 'ALEX AFONSO DONA', 'O operador do skidder está respeitando o sentido de arraste definido a partir do posicionamento do pé das árvores?', 'Observação direta em campo e alinhamento das árvores nas trilhas de arraste.', '[]', 2, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(342, 25, 'ALEX AFONSO DONA', 'Durante o arraste estão sendo evitados trilheiros repetitivos no interior do talhão?', 'Inspeção visual do talhão e verificação das trilhas de operação.', '[]', 3, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(343, 25, 'ALEX AFONSO DONA', 'O equipamento skidder está operando sem sobrecarga e sem trabalhar apenas com a tração traseira?', 'Observação da operação, entrevista com operador e verificação de comportamento do equipamento.', '[]', 4, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(344, 25, 'ALEX AFONSO DONA', 'As toras estão sendo arrastadas com o mínimo de contato com o solo conforme orientado no manual?', 'Observação direta da operação e condição das toras arrastadas.', '[]', 5, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(345, 25, 'ALEX AFONSO DONA', 'O operador está respeitando a orientação de não passar por cima das pontas ou de madeira já arrastada?', 'Inspeção visual das trilhas de arraste e organização da madeira.', '[]', 6, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(346, 25, 'ALEX AFONSO DONA', 'A organização da praça de estocagem respeita a altura máxima de pilha de aproximadamente 1,80 metros?', 'Medição visual ou com régua de pilhas de madeira na praça.', '[]', 7, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(347, 25, 'ALEX AFONSO DONA', 'O raio médio da praça está próximo de 40 metros a partir da rampa ou conforme microplanejamento?', 'Medição aproximada em campo ou verificação do microplanejamento.', '[]', 8, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(348, 25, 'ALEX AFONSO DONA', 'As pilhas de madeira estão posicionadas de forma alinhada com a mesa de alimentação do picador?', 'Observação visual da orientação das pilhas em relação ao local de picagem.', '[]', 9, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(349, 25, 'ALEX AFONSO DONA', 'Existe espaço adequado para manobra segura dos equipamentos na praça de estocagem?', 'Inspeção visual da praça e avaliação da área de circulação dos equipamentos.', '[]', 10, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(350, 25, 'ALEX AFONSO DONA', 'O check-list diário de manutenção do skider está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 11, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(351, 25, 'ALEX AFONSO DONA', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 12, '2026-03-29 23:00:39', '2026-03-29 23:00:39'),
(352, 24, 'ALEX AFONSO DONA', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', '[]', 1, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(353, 24, 'ALEX AFONSO DONA', 'O operador do skidder está respeitando o sentido de arraste definido a partir do posicionamento do pé das árvores?', 'Observação direta em campo e alinhamento das árvores nas trilhas de arraste.', '[]', 2, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(354, 24, 'ALEX AFONSO DONA', 'Durante o arraste estão sendo evitados trilheiros repetitivos no interior do talhão?', 'Inspeção visual do talhão e verificação das trilhas de operação.', '[]', 3, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(355, 24, 'ALEX AFONSO DONA', 'O equipamento skidder está operando sem sobrecarga e sem trabalhar apenas com a tração traseira?', 'Observação da operação, entrevista com operador e verificação de comportamento do equipamento.', '[]', 4, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(356, 24, 'ALEX AFONSO DONA', 'As toras estão sendo arrastadas com o mínimo de contato com o solo conforme orientado no manual?', 'Observação direta da operação e condição das toras arrastadas.', '[]', 5, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(357, 24, 'ALEX AFONSO DONA', 'O operador está respeitando a orientação de não passar por cima das pontas ou de madeira já arrastada?', 'Inspeção visual das trilhas de arraste e organização da madeira.', '[]', 6, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(358, 24, 'ALEX AFONSO DONA', 'A organização da praça de estocagem respeita a altura máxima de pilha de aproximadamente 1,80 metros?', 'Medição visual ou com régua de pilhas de madeira na praça.', '[]', 7, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(359, 24, 'ALEX AFONSO DONA', 'O raio médio da praça está próximo de 40 metros a partir da rampa ou conforme microplanejamento?', 'Medição aproximada em campo ou verificação do microplanejamento.', '[]', 8, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(360, 24, 'ALEX AFONSO DONA', 'As pilhas de madeira estão posicionadas de forma alinhada com a mesa de alimentação do picador?', 'Observação visual da orientação das pilhas em relação ao local de picagem.', '[]', 9, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(361, 24, 'ALEX AFONSO DONA', 'Existe espaço adequado para manobra segura dos equipamentos na praça de estocagem?', 'Inspeção visual da praça e avaliação da área de circulação dos equipamentos.', '[]', 10, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(362, 24, 'ALEX AFONSO DONA', 'O check-list diário de manutenção do skider está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 11, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(363, 24, 'ALEX AFONSO DONA', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 12, '2026-03-29 23:00:55', '2026-03-29 23:00:55'),
(364, 21, 'Alex Afonso Dona', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', '[]', 1, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(365, 21, 'Alex Afonso Dona', 'O operador está direcionando a derrubada das árvores a aproximadamente 90° em relação às linhas de plantio conforme definido no manual?', 'Observação em campo, alinhamento das árvores no solo, instruções operacionais.', '[]', 2, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(366, 21, 'Alex Afonso Dona', 'Antes do início da operação, foi realizada a avaliação das condições do terreno (formigueiros, subsolagem, curvas de nível e vento)?', 'Check-list operacional, registros de DDS ou orientação operacional, entrevista com operador.', '[]', 3, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(367, 21, 'Alex Afonso Dona', 'A altura média dos tocos está sendo mantida próxima ao padrão técnico de aproximadamente 5 cm?', 'Inspeção visual em campo, medição aleatória de tocos.', '[]', 4, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(368, 21, 'Alex Afonso Dona', 'O operador está realizando o corte seguindo uma linha por vez e minimizando o deslocamento do equipamento conforme orientado?', 'Observação direta da operação e entrevista com operador.', '[]', 5, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(369, 21, 'Alex Afonso Dona', 'O check-list diário de manutenção do Feller Buncher está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 6, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(370, 21, 'Alex Afonso Dona', 'Quando são identificadas falhas no equipamento, elas estão sendo reportadas imediatamente ao líder da operação?', 'Registro de manutenção, ordens de serviço, comunicação registrada ao líder.', '[]', 7, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(371, 21, 'Alex Afonso Dona', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 8, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(372, 21, 'Alex Afonso Dona', 'O operador monitora o desgaste das vidias e realiza inversão ou substituição quando necessário?', 'Registro de manutenção, estoque de vidias usadas e novas, entrevista com operador.', '[]', 9, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(373, 21, 'Alex Afonso Dona', 'Os boletins diários de colheita estão sendo preenchidos corretamente com o volume de árvores ou bunchers cortados no turno?', 'Boletins diários de colheita preenchidos, registros de produção ou planilhas operacionais.', '[]', 10, '2026-03-29 23:01:11', '2026-03-29 23:01:11'),
(374, 20, 'Alex Afonso Dona', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', '[]', 1, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(375, 20, 'Alex Afonso Dona', 'O operador está direcionando a derrubada das árvores a aproximadamente 90° em relação às linhas de plantio conforme definido no manual?', 'Observação em campo, alinhamento das árvores no solo, instruções operacionais.', '[]', 2, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(376, 20, 'Alex Afonso Dona', 'Antes do início da operação, foi realizada a avaliação das condições do terreno (formigueiros, subsolagem, curvas de nível e vento)?', 'Check-list operacional, registros de DDS ou orientação operacional, entrevista com operador.', '[]', 3, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(377, 20, 'Alex Afonso Dona', 'A altura média dos tocos está sendo mantida próxima ao padrão técnico de aproximadamente 5 cm?', 'Inspeção visual em campo, medição aleatória de tocos.', '[]', 4, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(378, 20, 'Alex Afonso Dona', 'O operador está realizando o corte seguindo uma linha por vez e minimizando o deslocamento do equipamento conforme orientado?', 'Observação direta da operação e entrevista com operador.', '[]', 5, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(379, 20, 'Alex Afonso Dona', 'O check-list diário de manutenção do Feller Buncher está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 6, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(380, 20, 'Alex Afonso Dona', 'Quando são identificadas falhas no equipamento, elas estão sendo reportadas imediatamente ao líder da operação?', 'Registro de manutenção, ordens de serviço, comunicação registrada ao líder.', '[]', 7, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(381, 20, 'Alex Afonso Dona', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 8, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(382, 20, 'Alex Afonso Dona', 'O operador monitora o desgaste das vidias e realiza inversão ou substituição quando necessário?', 'Registro de manutenção, estoque de vidias usadas e novas, entrevista com operador.', '[]', 9, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(383, 20, 'Alex Afonso Dona', 'Os boletins diários de colheita estão sendo preenchidos corretamente com o volume de árvores ou bunchers cortados no turno?', 'Boletins diários de colheita preenchidos, registros de produção ou planilhas operacionais.', '[]', 10, '2026-03-29 23:01:26', '2026-03-29 23:01:26'),
(384, 19, 'Alex Afonso Dona', 'A abertura dos talhões está sendo realizada conforme o planejamento e sem bloqueio de estradas ou acessos operacionais?', 'Planejamento operacional do talhão, mapa de colheita, inspeção visual em campo.', '[]', 1, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(385, 19, 'Alex Afonso Dona', 'O operador está direcionando a derrubada das árvores a aproximadamente 90° em relação às linhas de plantio conforme definido no manual?', 'Observação em campo, alinhamento das árvores no solo, instruções operacionais.', '[]', 2, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(386, 19, 'Alex Afonso Dona', 'Antes do início da operação, foi realizada a avaliação das condições do terreno (formigueiros, subsolagem, curvas de nível e vento)?', 'Check-list operacional, registros de DDS ou orientação operacional, entrevista com operador.', '[]', 3, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(387, 19, 'Alex Afonso Dona', 'A altura média dos tocos está sendo mantida próxima ao padrão técnico de aproximadamente 5 cm?', 'Inspeção visual em campo, medição aleatória de tocos.', '[]', 4, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(388, 19, 'Alex Afonso Dona', 'O operador está realizando o corte seguindo uma linha por vez e minimizando o deslocamento do equipamento conforme orientado?', 'Observação direta da operação e entrevista com operador.', '[]', 5, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(389, 19, 'Alex Afonso Dona', 'O check-list diário de manutenção do Feller Buncher está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 6, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(390, 19, 'Alex Afonso Dona', 'Quando são identificadas falhas no equipamento, elas estão sendo reportadas imediatamente ao líder da operação?', 'Registro de manutenção, ordens de serviço, comunicação registrada ao líder.', '[]', 7, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(391, 19, 'Alex Afonso Dona', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 8, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(392, 19, 'Alex Afonso Dona', 'O operador monitora o desgaste das vidias e realiza inversão ou substituição quando necessário?', 'Registro de manutenção, estoque de vidias usadas e novas, entrevista com operador.', '[]', 9, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(393, 19, 'Alex Afonso Dona', 'Os boletins diários de colheita estão sendo preenchidos corretamente com o volume de árvores ou bunchers cortados no turno?', 'Boletins diários de colheita preenchidos, registros de produção ou planilhas operacionais.', '[]', 10, '2026-03-29 23:01:45', '2026-03-29 23:01:45'),
(394, 18, 'ALEX AFONSO DONA', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', '[]', 1, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(395, 18, 'ALEX AFONSO DONA', 'A retirada das toras da pilha está sendo realizada da ponta para o centro, alternando os lados da pilha conforme definido no procedimento?', 'Observação em campo e organização da pilha durante a alimentação.', '[]', 2, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(396, 18, 'ALEX AFONSO DONA', 'O picador está sendo posicionado e operado no sentido favorável ao vento durante a operação?', 'Observação da operação e posicionamento do equipamento em relação à direção do vento.', '[]', 3, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(397, 18, 'ALEX AFONSO DONA', 'A escavadeira está sendo posicionada a uma distância segura da pilha e do picador durante o processo de alimentação?', 'Observação direta da operação e posicionamento do equipamento.', '[]', 4, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(398, 18, 'ALEX AFONSO DONA', 'A madeira está sendo colocada primeiro sobre a mesa de alimentação ou rolo auxiliar antes de entrar no sistema principal do picador?', 'Observação da alimentação da madeira durante a operação.', '[]', 5, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(399, 18, 'ALEX AFONSO DONA', 'O operador mantém comunicação com a equipe de apoio por rádio ou sinais visuais antes de iniciar movimentos de alimentação?', 'Entrevista com operadores e observação da comunicação durante a operação.', '[]', 6, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(400, 18, 'ALEX AFONSO DONA', 'O operador monitora continuamente a rotação do equipamento e a qualidade dos cavacos produzidos (overs e finos)?', 'Inspeção visual dos cavacos produzidos e entrevista com operador.', '[]', 7, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(401, 18, 'ALEX AFONSO DONA', 'A inspeção visual do equipamento para identificação de vazamentos está sendo realizada aproximadamente a cada 20 minutos de operação?', 'Observação em campo e entrevista com operador responsável.', '[]', 8, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(402, 18, 'ALEX AFONSO DONA', 'A limpeza do picador está sendo realizada a cada 4 horas de operação conforme definido no procedimento?', 'Registro operacional, check-list ou observação das condições do equipamento.', '[]', 9, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(403, 18, 'ALEX AFONSO DONA', 'O check-list de manutenção do equipamento está sendo preenchido no início de cada turno?', 'Check-list de manutenção preenchido e validado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:02:01', '2026-03-29 23:02:01'),
(404, 17, 'ALEX AFONSO DONA', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', '[]', 1, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(405, 17, 'ALEX AFONSO DONA', 'A retirada das toras da pilha está sendo realizada da ponta para o centro, alternando os lados da pilha conforme definido no procedimento?', 'Observação em campo e organização da pilha durante a alimentação.', '[]', 2, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(406, 17, 'ALEX AFONSO DONA', 'O picador está sendo posicionado e operado no sentido favorável ao vento durante a operação?', 'Observação da operação e posicionamento do equipamento em relação à direção do vento.', '[]', 3, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(407, 17, 'ALEX AFONSO DONA', 'A escavadeira está sendo posicionada a uma distância segura da pilha e do picador durante o processo de alimentação?', 'Observação direta da operação e posicionamento do equipamento.', '[]', 4, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(408, 17, 'ALEX AFONSO DONA', 'A madeira está sendo colocada primeiro sobre a mesa de alimentação ou rolo auxiliar antes de entrar no sistema principal do picador?', 'Observação da alimentação da madeira durante a operação.', '[]', 5, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(409, 17, 'ALEX AFONSO DONA', 'O operador mantém comunicação com a equipe de apoio por rádio ou sinais visuais antes de iniciar movimentos de alimentação?', 'Entrevista com operadores e observação da comunicação durante a operação.', '[]', 6, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(410, 17, 'ALEX AFONSO DONA', 'O operador monitora continuamente a rotação do equipamento e a qualidade dos cavacos produzidos (overs e finos)?', 'Inspeção visual dos cavacos produzidos e entrevista com operador.', '[]', 7, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(411, 17, 'ALEX AFONSO DONA', 'A inspeção visual do equipamento para identificação de vazamentos está sendo realizada aproximadamente a cada 20 minutos de operação?', 'Observação em campo e entrevista com operador responsável.', '[]', 8, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(412, 17, 'ALEX AFONSO DONA', 'A limpeza do picador está sendo realizada a cada 4 horas de operação conforme definido no procedimento?', 'Registro operacional, check-list ou observação das condições do equipamento.', '[]', 9, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(413, 17, 'ALEX AFONSO DONA', 'O check-list de manutenção do equipamento está sendo preenchido no início de cada turno?', 'Check-list de manutenção preenchido e validado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:02:18', '2026-03-29 23:02:18'),
(414, 16, 'ALEX AFONSO DONA', 'A praça de picagem está sendo limpa antes do início da operação, removendo galhos e resíduos do centro da área?', 'Inspeção visual da praça antes da operação ou registro de limpeza operacional.', '[]', 1, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(415, 16, 'ALEX AFONSO DONA', 'A retirada das toras da pilha está sendo realizada da ponta para o centro, alternando os lados da pilha conforme definido no procedimento?', 'Observação em campo e organização da pilha durante a alimentação.', '[]', 2, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(416, 16, 'ALEX AFONSO DONA', 'O picador está sendo posicionado e operado no sentido favorável ao vento durante a operação?', 'Observação da operação e posicionamento do equipamento em relação à direção do vento.', '[]', 3, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(417, 16, 'ALEX AFONSO DONA', 'A escavadeira está sendo posicionada a uma distância segura da pilha e do picador durante o processo de alimentação?', 'Observação direta da operação e posicionamento do equipamento.', '[]', 4, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(418, 16, 'ALEX AFONSO DONA', 'A madeira está sendo colocada primeiro sobre a mesa de alimentação ou rolo auxiliar antes de entrar no sistema principal do picador?', 'Observação da alimentação da madeira durante a operação.', '[]', 5, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(419, 16, 'ALEX AFONSO DONA', 'O operador mantém comunicação com a equipe de apoio por rádio ou sinais visuais antes de iniciar movimentos de alimentação?', 'Entrevista com operadores e observação da comunicação durante a operação.', '[]', 6, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(420, 16, 'ALEX AFONSO DONA', 'O operador monitora continuamente a rotação do equipamento e a qualidade dos cavacos produzidos (overs e finos)?', 'Inspeção visual dos cavacos produzidos e entrevista com operador.', '[]', 7, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(421, 16, 'ALEX AFONSO DONA', 'A inspeção visual do equipamento para identificação de vazamentos está sendo realizada aproximadamente a cada 20 minutos de operação?', 'Observação em campo e entrevista com operador responsável.', '[]', 8, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(422, 16, 'ALEX AFONSO DONA', 'A limpeza do picador está sendo realizada a cada 4 horas de operação conforme definido no procedimento?', 'Registro operacional, check-list ou observação das condições do equipamento.', '[]', 9, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(423, 16, 'ALEX AFONSO DONA', 'O check-list de manutenção do equipamento está sendo preenchido no início de cada turno?', 'Check-list de manutenção preenchido e validado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:02:31', '2026-03-29 23:02:31'),
(424, 23, 'ALEX AFONSO DONA', 'A localização da praça de estocagem está sendo definida conforme o microplanejamento da área?', 'Microplanejamento do talhão, mapa operacional, validação do líder ou supervisor.', '[]', 1, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(425, 23, 'ALEX AFONSO DONA', 'O operador do skidder está respeitando o sentido de arraste definido a partir do posicionamento do pé das árvores?', 'Observação direta em campo e alinhamento das árvores nas trilhas de arraste.', '[]', 2, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(426, 23, 'ALEX AFONSO DONA', 'Durante o arraste estão sendo evitados trilheiros repetitivos no interior do talhão?', 'Inspeção visual do talhão e verificação das trilhas de operação.', '[]', 3, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(427, 23, 'ALEX AFONSO DONA', 'O equipamento skidder está operando sem sobrecarga e sem trabalhar apenas com a tração traseira?', 'Observação da operação, entrevista com operador e verificação de comportamento do equipamento.', '[]', 4, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(428, 23, 'ALEX AFONSO DONA', 'As toras estão sendo arrastadas com o mínimo de contato com o solo conforme orientado no manual?', 'Observação direta da operação e condição das toras arrastadas.', '[]', 5, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(429, 23, 'ALEX AFONSO DONA', 'O operador está respeitando a orientação de não passar por cima das pontas ou de madeira já arrastada?', 'Inspeção visual das trilhas de arraste e organização da madeira.', '[]', 6, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(430, 23, 'ALEX AFONSO DONA', 'A organização da praça de estocagem respeita a altura máxima de pilha de aproximadamente 1,80 metros?', 'Medição visual ou com régua de pilhas de madeira na praça.', '[]', 7, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(431, 23, 'ALEX AFONSO DONA', 'O raio médio da praça está próximo de 40 metros a partir da rampa ou conforme microplanejamento?', 'Medição aproximada em campo ou verificação do microplanejamento.', '[]', 8, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(432, 23, 'ALEX AFONSO DONA', 'As pilhas de madeira estão posicionadas de forma alinhada com a mesa de alimentação do picador?', 'Observação visual da orientação das pilhas em relação ao local de picagem.', '[]', 9, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(433, 23, 'ALEX AFONSO DONA', 'Existe espaço adequado para manobra segura dos equipamentos na praça de estocagem?', 'Inspeção visual da praça e avaliação da área de circulação dos equipamentos.', '[]', 10, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(434, 23, 'ALEX AFONSO DONA', 'O check-list diário de manutenção do skider está sendo preenchido no início do turno?', 'Check-list diário preenchido e assinado pelo operador ou responsável.', '[]', 11, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(435, 23, 'ALEX AFONSO DONA', 'A limpeza do equipamento está sendo realizada ao final da jornada ou na troca de turno conforme definido no manual?', 'Inspeção visual do equipamento, rotina registrada ou checklist operacional.', '[]', 12, '2026-03-29 23:02:54', '2026-03-29 23:02:54'),
(436, 12, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 23:03:09', '2026-03-29 23:03:09'),
(437, 12, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 23:03:09', '2026-03-29 23:03:09'),
(438, 12, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 23:03:09', '2026-03-29 23:03:09'),
(439, 12, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 23:03:09', '2026-03-29 23:03:09'),
(440, 12, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 23:03:09', '2026-03-29 23:03:09'),
(441, 11, 'ALEX AFONSO DONA', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', '[]', 1, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(442, 11, 'ALEX AFONSO DONA', 'Antes do início do carregamento, o responsável logístico realizou vistoria dos acessos para caminhões e equipamentos?', 'Registro de inspeção da área, comunicação da logística ou entrevista com responsável.', '[]', 2, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(443, 11, 'ALEX AFONSO DONA', 'A rampa de carregamento foi construída e nivelada corretamente (terra + camada de cavaco) antes do início das atividades?', 'Inspeção visual da rampa e entrevista com operador ou líder de operação.', '[]', 3, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(444, 11, 'ALEX AFONSO DONA', 'O operador da pá carregadeira evita raspar diretamente a praça de cavaco durante o carregamento?', 'Observação direta da operação e inspeção visual da praça.', '[]', 4, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(445, 11, 'ALEX AFONSO DONA', 'Quando são identificados materiais indesejáveis na praça (galhos, pedras, madeira), eles são removidos antes do carregamento?', 'Observação em campo ou evidência de retirada manual/mecânica do material.', '[]', 5, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(446, 11, 'ALEX AFONSO DONA', 'O carregamento respeita o limite de altura da carga (aprox. 30 cm abaixo da borda do caixote ou nível da borda para madeira verde)?', 'Observação visual da carga nos caminhões e medição aproximada do nível da carga.', '[]', 6, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(447, 11, 'ALEX AFONSO DONA', 'O operador mantém comunicação visual ou sonora com o motorista antes de iniciar ou finalizar o carregamento?', 'Observação da operação e uso dos sinais sonoros definidos.', '[]', 7, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(448, 11, 'ALEX AFONSO DONA', 'Os sinais sonoros (1, 2, 3 buzinas e buzina contínua) estão sendo utilizados conforme o padrão definido no manual?', 'Observação em campo e entrevista com operador e motoristas.', '[]', 8, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(449, 11, 'ALEX AFONSO DONA', 'O caminhão é obrigatoriamente enlonado antes de sair da rampa de carregamento?', 'Inspeção visual do caminhão após carregamento.', '[]', 9, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(450, 11, 'ALEX AFONSO DONA', 'O check-list de manutenção da pá carregadeira está sendo preenchido no início do turno?', 'Check-list de manutenção preenchido e assinado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:03:18', '2026-03-29 23:03:18'),
(451, 10, 'ALEX AFONSO DONA', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', '[]', 1, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(452, 10, 'ALEX AFONSO DONA', 'Antes do início do carregamento, o responsável logístico realizou vistoria dos acessos para caminhões e equipamentos?', 'Registro de inspeção da área, comunicação da logística ou entrevista com responsável.', '[]', 2, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(453, 10, 'ALEX AFONSO DONA', 'A rampa de carregamento foi construída e nivelada corretamente (terra + camada de cavaco) antes do início das atividades?', 'Inspeção visual da rampa e entrevista com operador ou líder de operação.', '[]', 3, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(454, 10, 'ALEX AFONSO DONA', 'O operador da pá carregadeira evita raspar diretamente a praça de cavaco durante o carregamento?', 'Observação direta da operação e inspeção visual da praça.', '[]', 4, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(455, 10, 'ALEX AFONSO DONA', 'Quando são identificados materiais indesejáveis na praça (galhos, pedras, madeira), eles são removidos antes do carregamento?', 'Observação em campo ou evidência de retirada manual/mecânica do material.', '[]', 5, '2026-03-29 23:03:47', '2026-03-29 23:03:47');
INSERT INTO `auditoria_questoes` (`id`, `auditoria_id`, `responsavel_nome`, `pergunta`, `referencia_esperada`, `processos_json`, `ordem`, `created_at`, `updated_at`) VALUES
(456, 10, 'ALEX AFONSO DONA', 'O carregamento respeita o limite de altura da carga (aprox. 30 cm abaixo da borda do caixote ou nível da borda para madeira verde)?', 'Observação visual da carga nos caminhões e medição aproximada do nível da carga.', '[]', 6, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(457, 10, 'ALEX AFONSO DONA', 'O operador mantém comunicação visual ou sonora com o motorista antes de iniciar ou finalizar o carregamento?', 'Observação da operação e uso dos sinais sonoros definidos.', '[]', 7, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(458, 10, 'ALEX AFONSO DONA', 'Os sinais sonoros (1, 2, 3 buzinas e buzina contínua) estão sendo utilizados conforme o padrão definido no manual?', 'Observação em campo e entrevista com operador e motoristas.', '[]', 8, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(459, 10, 'ALEX AFONSO DONA', 'O caminhão é obrigatoriamente enlonado antes de sair da rampa de carregamento?', 'Inspeção visual do caminhão após carregamento.', '[]', 9, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(460, 10, 'ALEX AFONSO DONA', 'O check-list de manutenção da pá carregadeira está sendo preenchido no início do turno?', 'Check-list de manutenção preenchido e assinado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:03:47', '2026-03-29 23:03:47'),
(461, 15, 'ALEX AFONSO DONA', 'O carregamento está seguindo a sequência das praças conforme definido no microplanejamento (priorizando as áreas picadas primeiro)?', 'Microplanejamento, programação logística ou registro de sequência de carregamento.', '[]', 1, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(462, 15, 'ALEX AFONSO DONA', 'Antes do início do carregamento, o responsável logístico realizou vistoria dos acessos para caminhões e equipamentos?', 'Registro de inspeção da área, comunicação da logística ou entrevista com responsável.', '[]', 2, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(463, 15, 'ALEX AFONSO DONA', 'A rampa de carregamento foi construída e nivelada corretamente (terra + camada de cavaco) antes do início das atividades?', 'Inspeção visual da rampa e entrevista com operador ou líder de operação.', '[]', 3, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(464, 15, 'ALEX AFONSO DONA', 'O operador da pá carregadeira evita raspar diretamente a praça de cavaco durante o carregamento?', 'Observação direta da operação e inspeção visual da praça.', '[]', 4, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(465, 15, 'ALEX AFONSO DONA', 'Quando são identificados materiais indesejáveis na praça (galhos, pedras, madeira), eles são removidos antes do carregamento?', 'Observação em campo ou evidência de retirada manual/mecânica do material.', '[]', 5, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(466, 15, 'ALEX AFONSO DONA', 'O carregamento respeita o limite de altura da carga (aprox. 30 cm abaixo da borda do caixote ou nível da borda para madeira verde)?', 'Observação visual da carga nos caminhões e medição aproximada do nível da carga.', '[]', 6, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(467, 15, 'ALEX AFONSO DONA', 'O operador mantém comunicação visual ou sonora com o motorista antes de iniciar ou finalizar o carregamento?', 'Observação da operação e uso dos sinais sonoros definidos.', '[]', 7, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(468, 15, 'ALEX AFONSO DONA', 'Os sinais sonoros (1, 2, 3 buzinas e buzina contínua) estão sendo utilizados conforme o padrão definido no manual?', 'Observação em campo e entrevista com operador e motoristas.', '[]', 8, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(469, 15, 'ALEX AFONSO DONA', 'O caminhão é obrigatoriamente enlonado antes de sair da rampa de carregamento?', 'Inspeção visual do caminhão após carregamento.', '[]', 9, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(470, 15, 'ALEX AFONSO DONA', 'O check-list de manutenção da pá carregadeira está sendo preenchido no início do turno?', 'Check-list de manutenção preenchido e assinado pelo operador ou responsável.', '[]', 10, '2026-03-29 23:04:13', '2026-03-29 23:04:13'),
(471, 14, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-29 23:04:21', '2026-03-29 23:04:21'),
(472, 14, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-29 23:04:21', '2026-03-29 23:04:21'),
(473, 14, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-29 23:04:21', '2026-03-29 23:04:21'),
(474, 14, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-29 23:04:21', '2026-03-29 23:04:21'),
(475, 14, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-29 23:04:21', '2026-03-29 23:04:21'),
(476, 26, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-30 15:03:56', '2026-03-30 15:03:56'),
(477, 26, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-30 15:03:56', '2026-03-30 15:03:56'),
(478, 26, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-30 15:03:56', '2026-03-30 15:03:56'),
(479, 26, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-30 15:03:56', '2026-03-30 15:03:56'),
(480, 26, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-30 15:03:56', '2026-03-30 15:03:56'),
(481, 13, 'ALEX AFONSO DONA', '1º S - Existe um processo de descarte correto na área?', 'Área sem a presença de itens a serem descartados.', '[]', 1, '2026-03-30 15:04:30', '2026-03-30 15:04:30'),
(482, 13, 'ALEX AFONSO DONA', '2º S - Todos os itens existentes na área estão organizados?', 'Itens locados e identificados corretamente.', '[]', 2, '2026-03-30 15:04:30', '2026-03-30 15:04:30'),
(483, 13, 'ALEX AFONSO DONA', '3º S - Existe um padrão de limpeza geral na área?', 'Ambientes limpos e organizados.', '[]', 3, '2026-03-30 15:04:30', '2026-03-30 15:04:30'),
(484, 13, 'ALEX AFONSO DONA', '4º S - Existe uma preocupação ativa com a segurança na área?', 'Utilização correta de EPI, uniformes adequados e higiene pessoal adequada.', '[]', 4, '2026-03-30 15:04:30', '2026-03-30 15:04:30'),
(485, 13, 'ALEX AFONSO DONA', '5º S - Existe uma preocupação genuína com saúde e segurança na área?', 'Aspecto geral do ambiente e equipamentos, limpos e organizados.', '[]', 5, '2026-03-30 15:04:30', '2026-03-30 15:04:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria_relatorios`
--

CREATE TABLE `auditoria_relatorios` (
  `id` int NOT NULL,
  `auditoria_id` int NOT NULL,
  `relatorio_ref` varchar(120) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `empresa_nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `whatsapp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `email` varchar(180) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `numero_funcionarios` int UNSIGNED NOT NULL DEFAULT '0',
  `numero_lideres` int UNSIGNED NOT NULL DEFAULT '0',
  `faturamento_medio_anual` bigint UNSIGNED NOT NULL DEFAULT '0',
  `tomador_decisao` tinyint(1) NOT NULL DEFAULT '0',
  `origem_cadastro` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cliente_existente',
  `created_by_user_id` int DEFAULT NULL,
  `cliente_associado_em` datetime DEFAULT NULL,
  `contato` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `respostas_json` text COLLATE utf8mb4_general_ci,
  `nota_financeiro` tinyint NOT NULL DEFAULT '0',
  `nota_mercado` tinyint NOT NULL DEFAULT '0',
  `nota_pessoas` tinyint NOT NULL DEFAULT '0',
  `nota_processo` tinyint NOT NULL DEFAULT '0',
  `realidade_financeiro` tinyint DEFAULT NULL,
  `realidade_mercado` tinyint DEFAULT NULL,
  `realidade_pessoas` tinyint DEFAULT NULL,
  `realidade_processo` tinyint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id`, `cliente_id`, `empresa_nome`, `nome`, `whatsapp`, `email`, `numero_funcionarios`, `numero_lideres`, `faturamento_medio_anual`, `tomador_decisao`, `origem_cadastro`, `created_by_user_id`, `cliente_associado_em`, `contato`, `respostas_json`, `nota_financeiro`, `nota_mercado`, `nota_pessoas`, `nota_processo`, `realidade_financeiro`, `realidade_mercado`, `realidade_pessoas`, `realidade_processo`, `created_at`) VALUES
(1, NULL, 'AGÊNCIA LESTER', '', '', '', 0, 0, 0, 0, 'cliente_existente', NULL, NULL, 'Ozuna', '{\"financeiro\":[\"1\",\"3\",\"7\"],\"mercado\":[\"1\",\"3\",\"4\",\"5\",\"6\"],\"pessoas\":[\"1\",\"2\",\"3\",\"4\",\"7\"],\"processo\":[\"1\",\"2\",\"3\",\"4\",\"5\"]}', 3, 5, 5, 5, NULL, NULL, NULL, NULL, '2026-01-15 14:13:26'),
(2, NULL, 'Traxter', 'Fabio Ozuna', '5567993256260', 'fozuna@gmail.com', 1, 1, 65000, 1, 'potencial_cliente', 1, NULL, 'Fabio Ozuna', '{\"financeiro\":[1,3,6],\"mercado\":[4,5,6],\"pessoas\":[3,4],\"processo\":[3]}', 3, 3, 2, 1, 43, 43, 29, 14, '2026-04-14 20:24:41'),
(3, NULL, 'Doná Desenvolvimento e Gestão Ltda', 'Alex Afonso Doná', '67981524635', 'alex@donainstituto.com.br', 5, 2, 800000, 1, 'potencial_cliente', 5, NULL, 'Alex Afonso Doná', '{\"financeiro\":[\"1\",\"2\",\"3\",\"5\",\"7\"],\"mercado\":[\"1\",\"3\",\"4\"],\"pessoas\":[\"2\",\"4\",\"6\",\"7\"],\"processo\":[\"3\",\"4\",\"6\"]}', 5, 3, 4, 3, 71, 43, 57, 43, '2026-04-15 13:45:10'),
(4, NULL, 'Traxter', 'Fabio Ozuna', '5567993256260', 'fozuna@gmail.com', 5, 1, 100000, 1, 'potencial_cliente', 1, NULL, 'Fabio Ozuna', '{\"financeiro\":[1,2,3,4],\"mercado\":[5,6,7],\"pessoas\":[6,7],\"processo\":[5,6,7]}', 4, 3, 2, 3, 57, 43, 29, 43, '2026-04-15 13:50:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes_publicas`
--

CREATE TABLE `avaliacoes_publicas` (
  `id` int NOT NULL,
  `avaliacao_id` int DEFAULT NULL,
  `token` char(36) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `created_by_user_id` int DEFAULT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `numero_funcionarios` int UNSIGNED DEFAULT NULL,
  `numero_lideres` int UNSIGNED DEFAULT NULL,
  `faturamento_anual` bigint UNSIGNED DEFAULT NULL,
  `tomador_decisao` tinyint(1) DEFAULT NULL,
  `respostas_json` text,
  `nota_financeiro` tinyint NOT NULL DEFAULT '0',
  `nota_mercado` tinyint NOT NULL DEFAULT '0',
  `nota_pessoas` tinyint NOT NULL DEFAULT '0',
  `nota_processo` tinyint NOT NULL DEFAULT '0',
  `realidade_financeiro` tinyint DEFAULT NULL,
  `realidade_mercado` tinyint DEFAULT NULL,
  `realidade_pessoas` tinyint DEFAULT NULL,
  `realidade_processo` tinyint DEFAULT NULL,
  `status` enum('pendente','iniciada','concluida') NOT NULL DEFAULT 'pendente',
  `expiracao` datetime DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_conclusao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `avaliacoes_publicas`
--

INSERT INTO `avaliacoes_publicas` (`id`, `avaliacao_id`, `token`, `slug`, `created_by_user_id`, `nome`, `empresa`, `whatsapp`, `email`, `numero_funcionarios`, `numero_lideres`, `faturamento_anual`, `tomador_decisao`, `respostas_json`, `nota_financeiro`, `nota_mercado`, `nota_pessoas`, `nota_processo`, `realidade_financeiro`, `realidade_mercado`, `realidade_pessoas`, `realidade_processo`, `status`, `expiracao`, `data_criacao`, `data_conclusao`) VALUES
(1, 1, 'cb777222-8d35-439d-856f-8115a281bd7b', NULL, NULL, 'Fabio Ozuna', 'AGÊNCIA LESTER', '5567993256260', 'fozuna@gmail.com', 10, 1, 10000, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'iniciada', NULL, '2026-04-13 14:54:36', NULL),
(2, NULL, 'f58ffca2-91d1-4869-b02f-cd12a323a355', NULL, 1, 'Fabio Ozuna', 'Traxter', '5567993256260', 'fozuna@gmail.com', 15, 3, 500000, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'iniciada', NULL, '2026-04-14 15:33:37', NULL),
(3, NULL, 'f12158be-cacb-4ada-8a3a-da5873c9e912', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-14 15:35:40', NULL),
(4, NULL, 'c8c5a284-abd4-4a7e-8d10-ac2e9ffd8461', NULL, 1, 'Fabio Ozuna', 'Traxter', '5567993256260', 'fozuna@gmail.com', 15, 1, 15000, 1, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'iniciada', NULL, '2026-04-14 15:35:56', NULL),
(5, NULL, 'b2b021ea-d231-44d2-ab6e-9663c13d55ec', 'avaliar-606cb561ab66', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-14 19:16:57', NULL),
(6, NULL, 'a27750e2-96a7-447f-bce1-37368c508b36', 'avaliar-90ad68616d2c', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-14 19:45:34', NULL),
(7, NULL, '6a8cbaa7-33e8-4857-abf0-3901e4ee4d34', 'avaliar-5583cf61be5a', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-14 20:23:52', NULL),
(8, NULL, '783e7c44-4d07-4b3d-896f-d67587d34153', 'avaliar-27ac583b0441', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-14 23:57:20', NULL),
(9, NULL, 'db9bfb85-34c9-4fae-8dbe-e0530890e3c7', 'avaliar-0950c0015f78', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-15 13:18:30', NULL),
(10, NULL, 'd83e1b56-d242-49be-a1dc-95d261412623', 'avaliar-112f7bc078ae', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-15 13:34:10', NULL),
(11, NULL, 'bd388563-f099-4a91-a3b4-8795cd58c9e4', 'avaliar-75a02ecc9c2f', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'pendente', NULL, '2026-04-15 13:49:20', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `nome_empresa` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `CNPJ` varchar(18) COLLATE utf8mb4_general_ci NOT NULL,
  `contato` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_matriz` tinyint(1) NOT NULL DEFAULT '1',
  `matriz_id` int DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `acesso_restrito` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome_empresa`, `CNPJ`, `contato`, `logo_path`, `is_matriz`, `matriz_id`, `ativo`, `acesso_restrito`) VALUES
(1, 'DINEX ENGENHARIA MINERAL LTDA', '42929315000168', 'PATRICIA GERMANA', NULL, 1, NULL, 1, 0),
(2, 'DINEX FILIAL MS', '42929315000249', 'FABIANE', NULL, 0, 1, 1, 0),
(3, 'DINEX FILIAL MG', '42929315000400', 'FABIANE', NULL, 0, 1, 1, 0),
(4, 'DINEX FILIAL BA', '42929315000591', 'FABIANE', NULL, 0, 1, 1, 0),
(5, 'DONÁ DESENVOLVIMENTO E GESTÃO LTDA', '42734593000160', 'ALEX DONÁ', NULL, 1, NULL, 1, 0),
(6, 'MADEPLANT FLORESTAL LTDA', '08519091000188', 'FABIO KUKIEL', NULL, 1, NULL, 1, 0),
(7, 'AGÊNCIA LESTER', '30358115000113', 'Fabio Ozuna', 'assets/img/clients/ag-ncia-lester-699f2e9ae8a32.jpg', 1, NULL, 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `colaboradores`
--

CREATE TABLE `colaboradores` (
  `id` int NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `funcao_id` int NOT NULL,
  `lider` enum('não','sim') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'não',
  `cliente_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `colaboradores`
--

INSERT INTO `colaboradores` (`id`, `nome`, `email`, `funcao_id`, `lider`, `cliente_id`) VALUES
(1, 'ADRIANE SENA DA FONSECA', NULL, 1, 'não', 1),
(2, 'ALLANA MACHADO FERRAZ BARBOSA', NULL, 2, 'não', 1),
(3, 'ANDRE LUIZ SOARES DE OLIVEIRA', NULL, 3, 'sim', 1),
(4, 'BRENO EMANOEL NASCIMENTO DO CARMO', NULL, 4, 'não', 1),
(5, 'BRUNO HENRIQUE PEREIRA DE ASSIS', NULL, 5, 'não', 1),
(6, 'CARLOS EDUARDO LOPES CORREA', NULL, 6, 'não', 1),
(7, 'CARLUCIO ALVES MOTA', NULL, 7, 'não', 1),
(8, 'DENIS HENRIQUE MACHADO TEIXEIRA', NULL, 8, 'não', 1),
(9, 'DRUMOND', NULL, 9, 'sim', 1),
(10, 'ELIZANGELA APARECIDA ANUNCIACAO SANTOS', NULL, 10, 'não', 1),
(11, 'FABIO LUIZ DE JESUS', NULL, 1, 'não', 1),
(12, 'FABRICIO SAMUEL DE JESUS ROCHA', NULL, 5, 'não', 1),
(13, 'FREDERICO BRETAS ROLIM DE OLIVEIRA', NULL, 11, 'sim', 1),
(14, 'GUSTAVO BRETAS ROLIM DE OLIVEIRA', NULL, 12, 'sim', 1),
(15, 'GUSTAVO JUSTINO DA SILVA', NULL, 2, 'não', 1),
(16, 'HELBERT MARQUES MIRANDA', NULL, 13, 'não', 1),
(17, 'JEANE CRISTINA SPIAZZI PEDROSA FARNESI', NULL, 5, 'não', 1),
(18, 'KEITI FERREIRA', NULL, 14, 'sim', 1),
(19, 'LARYSSA LINDEMBERG RANGEL', NULL, 15, 'não', 1),
(20, 'LEONARDO BITENCOURT DOS REIS', NULL, 15, 'não', 1),
(21, 'LUCAS MATEUS SANTOS AQUINO', NULL, 15, 'não', 1),
(22, 'MARCIO RUBENS GABRIEL SOARES JERONIMO', NULL, 16, 'não', 1),
(23, 'MARIA LUIZA SILVA GOMES', NULL, 4, 'não', 1),
(24, 'MIRIAN CHAGAS VELOSO', NULL, 17, 'sim', 1),
(25, 'NATALIA MATEUS DA SILVA SANTOS', NULL, 18, 'não', 1),
(26, 'PATRICIA GERMANA LOUREIRO', NULL, 19, 'sim', 1),
(27, 'POLLYANA BERNUCCI', NULL, 20, 'não', 1),
(28, 'RAFAELLA OLIVEIRA SILVA ALVES', NULL, 2, 'não', 1),
(29, 'RAMIRO DIAS DO ESPIRITO SANTO', NULL, 21, 'sim', 1),
(30, 'RAYSSA DE SOUZA FERNANDES', NULL, 2, 'não', 1),
(31, 'ROBSON FERREIRA SILVA', NULL, 22, 'sim', 1),
(32, 'ROSANA PEREIRA SILVA', NULL, 23, 'não', 1),
(33, 'SANTOS FRANCISCO DE SOUZA', NULL, 24, 'não', 1),
(34, 'VANDERLEY FERNANDES SILVA', NULL, 25, 'não', 1),
(35, 'WANDERSON RAFAEL DE QUEIROZ', NULL, 26, 'não', 1),
(36, 'WENDER MATEUS DE BASTOS', NULL, 27, 'sim', 1),
(37, 'RENAN DUTRA', NULL, 28, 'sim', 1),
(38, 'RONALDO ROLIM', NULL, 29, 'sim', 1),
(39, 'IGOR JORGE ROLIM', '', 30, 'sim', 1),
(40, 'TIAGO AMÓS', NULL, 31, 'sim', 1),
(41, 'THIAGO SILVA', NULL, 32, 'sim', 1),
(42, 'WANDERSON RAFAEL DE QUEIROZ', NULL, 26, 'não', 3),
(43, 'VANDERLEY FERNANDES SILVA', NULL, 25, 'não', 3),
(44, 'ROBSON FERREIRA SILVA', NULL, 22, 'sim', 3),
(45, 'DENIS HENRIQUE MACHADO TEIXEIRA', NULL, 8, 'não', 3),
(46, 'ALEXSANDRO GONCALVES TOMAZ', NULL, 25, 'não', 3),
(47, 'ANDRE LUIZ FERREIRA CARNEIRO', NULL, 24, 'não', 3),
(48, 'CAIO VERGOLINO RODRIGUES', NULL, 33, 'não', 3),
(49, 'CLAUDINEI ALVES FERNANDES', NULL, 34, 'sim', 3),
(50, 'DIEGO MOREIRA MENDES', NULL, 8, 'não', 3),
(51, 'FELIPE ANCELMO PINTO', NULL, 33, 'não', 3),
(52, 'GERALDO MAGELA FARIAS JUNIOR', NULL, 35, 'não', 3),
(53, 'GUILHERME DE SOUZA RODRIGUES', NULL, 33, 'não', 3),
(54, 'JEAN VERGOLINO CARVALHO', NULL, 8, 'não', 3),
(55, 'JOSE MARINHO SOARES', NULL, 33, 'não', 3),
(56, 'JULIANO SOUZA CARNEIRO', NULL, 8, 'não', 3),
(57, 'KAYK JULIO MENEZES', NULL, 33, 'não', 3),
(58, 'LUIZ CARLOS SILVA FONSECA', NULL, 33, 'não', 3),
(59, 'MARCELO VERGOLINO RODRIGUES', NULL, 8, 'não', 3),
(60, 'MARCUS ALEXANDRE DE LIMA (GERENTE)', NULL, 42, 'sim', 3),
(61, 'MATHEUS ROSA CORREA TAVARES', NULL, 37, 'não', 3),
(62, 'MILLER ROSA CORREA', NULL, 33, 'não', 3),
(63, 'PAULO RICARDO DE FREITAS', NULL, 8, 'não', 3),
(64, 'PEDRO ROBSON MARTINS MOTTA JUNIOR', NULL, 38, 'não', 3),
(65, 'RAFAEL MENDES PRIMO', NULL, 33, 'não', 3),
(66, 'RAMON VERGOLINO RODRIGUES', NULL, 33, 'não', 3),
(67, 'RICHARD SALVA DE OLIVEIRA', NULL, 27, 'sim', 3),
(68, 'RITHELISON DE OLIVEIRA FERREIRA', NULL, 33, 'não', 3),
(69, 'ROBERTO RIBEIRO DE JESUS', NULL, 33, 'não', 3),
(70, 'RONIALDO PAZ DA SILVA', NULL, 33, 'não', 3),
(71, 'SAMUEL ARAUJO ARRUDA', NULL, 8, 'não', 3),
(72, 'SAMUEL MOREIRA COIMBRA', NULL, 37, 'não', 3),
(73, 'THIAGO LOMBONE COELHO', NULL, 8, 'não', 3),
(74, 'VALQUIRIA CAMARGO DE OLIVEIRA', NULL, 39, 'não', 3),
(75, 'VICTOR EMANUEL VIEIRA DE SOUSA', NULL, 40, 'não', 3),
(76, 'WELINGTON ALBERTO COSTA', NULL, 33, 'não', 3),
(77, 'ADALBERTO RIBAS DE SANT ANA', NULL, 2, 'não', 2),
(78, 'ADILSON ELIAS DE BARROS', NULL, 41, 'não', 2),
(79, 'ADMILTON SILVA', NULL, 42, 'sim', 2),
(80, 'ADRIANNY MONTENEGRO FARDIN', NULL, 43, 'não', 2),
(81, 'AGNALDO DA SILVA BARROS', NULL, 44, 'não', 2),
(82, 'AILTON ANDRADE AQUINO', NULL, 45, 'não', 2),
(83, 'ALDEMIL DA CONCEICAO', NULL, 46, 'não', 2),
(84, 'ALDO CELSON PIRES DA COSTA', NULL, 43, 'não', 2),
(85, 'ALEX COSTA BRITTO', NULL, 43, 'não', 2),
(86, 'ALEX LUCAS BUDIB FERREIRA', NULL, 43, 'não', 2),
(87, 'ALEXSANDRO DE OLIVEIRA JARCEM', NULL, 47, 'não', 2),
(88, 'ALLAN LARREA VIANA MARTINEZ', NULL, 48, 'não', 2),
(89, 'AMANDA GABRIELLY MARTINEZ DE SOUZA', NULL, 4, 'não', 2),
(90, 'ANDERSON MARIA MACHUGA', NULL, 46, 'não', 2),
(91, 'ANDRE LUIZ FLORES PIRES', NULL, 49, 'não', 2),
(92, 'ANDREY DOS SANTOS BARBOZA', NULL, 25, 'não', 2),
(93, 'ANMERSON DA COSTA SILVA', NULL, 43, 'não', 2),
(94, 'ANTONIELE WENDY MENDONCA GUERREIRO', NULL, 4, 'não', 2),
(95, 'ANTONINO DE ARRUDA CAMPOS', NULL, 43, 'não', 2),
(96, 'ANTONIO CARLOS CRAVO DOS SANTOS', NULL, 50, 'sim', 2),
(97, 'ANTONIO DOS SANTOS RICCO', NULL, 43, 'não', 2),
(98, 'ANTONIO JOAO GOMES DE MORAES', NULL, 44, 'não', 2),
(99, 'AUGUSTO SERAFIM DE FREITAS NETO', NULL, 44, 'não', 2),
(100, 'CARLOS ALBERTO CACERES', NULL, 46, 'não', 2),
(101, 'CARLOS HENRIQUE BEZERRA DOS SANTOS', NULL, 51, 'não', 2),
(102, 'CAROLINA DE FATIMA MENDES NEVES DE CARVALHO', NULL, 15, 'sim', 2),
(103, 'CLEBER DE SOUZA PRADO', NULL, 43, 'não', 2),
(104, 'CLEITON MOREIRA SOARES', NULL, 43, 'não', 2),
(105, 'CRISTOVAO OLIVEIRA COSTA', NULL, 43, 'não', 2),
(106, 'DALVAN AREVALO IBARRA', NULL, 52, 'não', 2),
(107, 'DANIEL AGUILAR ALVARO', NULL, 49, 'não', 2),
(108, 'DAVI MOURA VELASQUE', NULL, 43, 'não', 2),
(109, 'DAVID CARVALHO CARRELO', NULL, 43, 'não', 2),
(110, 'DIOGO DOS SANTOS BOGADO', NULL, 1, 'não', 2),
(111, 'DIOGO PALMEIRA DE OLIVEIRA NERI', NULL, 53, 'sim', 2),
(112, 'EDER DE OLIVEIRA RAMOS', NULL, 44, 'não', 2),
(113, 'EDER FERREIRA SANTIAGO', NULL, 43, 'não', 2),
(114, 'EDMILSON MALDONADO ALVES', NULL, 27, 'sim', 2),
(115, 'EDSON LUIZ DE CAMARGO FREIRE', NULL, 43, 'não', 2),
(116, 'EDSON MIRANDA', NULL, 46, 'não', 2),
(117, 'EDUARDO VILALVA SILVEIRA JUNIOR', NULL, 45, 'não', 2),
(118, 'EDVALDO MASCARENHAS VALEJO', NULL, 25, 'não', 2),
(119, 'EDVAM EMANOEL GONCALVES PINHEIRO', NULL, 26, 'não', 2),
(120, 'ELIEL PAULO DA SILVA ARRUDA', NULL, 54, 'sim', 2),
(121, 'ESTEPHANY APARECIDA SILVA SOARES', NULL, 4, 'não', 2),
(122, 'EVANDER DA SILVA CALONGA', NULL, 24, 'não', 2),
(123, 'EWERSON DA SILVA ARANHA', NULL, 54, 'sim', 2),
(124, 'FABIANO CORREA DA CUNHA', NULL, 25, 'não', 2),
(125, 'FABIO CORREA DA CUNHA', NULL, 55, 'não', 2),
(126, 'FABIO FERNANDES FLORES', NULL, 43, 'não', 2),
(127, 'FABRICIO GUILHEN ROQUE', NULL, 44, 'não', 2),
(128, 'FAUSTINO FERREIRA SANTIAGO', NULL, 43, 'não', 2),
(129, 'FELIPE GONCALVES SOARES', NULL, 48, 'não', 2),
(130, 'FELIPE RENAN ALVES', NULL, 56, 'não', 2),
(131, 'FERNANDO ANTONIO DE OLIVEIRA', NULL, 45, 'não', 2),
(132, 'FERNANDO BARROS ORTIGOSA', NULL, 43, 'não', 2),
(133, 'FERNANDO CELSO DA ROSA FERNANDES', NULL, 43, 'não', 2),
(134, 'FRANCISCO BUENO DA SILVA NETO', NULL, 57, 'não', 2),
(135, 'FRANCISCO CARLOS LOPES DA SILVA JUNIOR', NULL, 37, 'não', 2),
(136, 'FRANCISCO RAYLSON SILVA DAS CHAGAS', NULL, 58, 'sim', 2),
(137, 'GABRIEL DA SILVA SOARES PEREIRA', NULL, 25, 'não', 2),
(138, 'GABRYEL VIEIRA BENITES', NULL, 26, 'não', 2),
(139, 'GILSON FRANCO DO NASCIMENTO', NULL, 59, 'não', 2),
(140, 'GISELE SOUZA BATISTA', NULL, 60, 'sim', 2),
(141, 'GLAUBER DOS SANTOS RONDON', NULL, 43, 'não', 2),
(142, 'GLAUCO DOS SANTOS RONDON', NULL, 43, 'não', 2),
(143, 'GUSTAVO DE AMORIM APONTES', NULL, 46, 'não', 2),
(144, 'HELLEN CRISTIANE DE PINHO PEREIRA', NULL, 2, 'não', 2),
(145, 'HELTHON CABRAL DA COSTA', NULL, 46, 'não', 2),
(146, 'HUMBERTO DOS SANTOS VILLALBA', NULL, 44, 'não', 2),
(147, 'INGRIDY SOARES DOS SANTOS', NULL, 4, 'não', 2),
(148, 'IRANY FRANCA VIANNA SILVEIRA', NULL, 49, 'não', 2),
(149, 'IREMAR FERNANDES DE SOUZA', NULL, 43, 'não', 2),
(150, 'IVAN ROCHA DOS SANTOS', NULL, 61, 'não', 2),
(151, 'IVANILDE OLIVEIRA SANTANA', NULL, 43, 'não', 2),
(152, 'JAILSON BARBA HURTADO PINTO', NULL, 60, 'não', 2),
(153, 'JEAN CARLOS BENITES DE ANDRADE', NULL, 43, 'não', 2),
(154, 'JEFFERSON DANILO DA SILVA SOUZA', NULL, 43, 'não', 2),
(155, 'JEFFERSON MARTINS ZARATE', NULL, 44, 'não', 2),
(156, 'JEFFERSON VIEGAS', NULL, 44, 'não', 2),
(157, 'JOAO ABREU DE OLIVEIRA', NULL, 41, 'não', 2),
(158, 'JOAO CARLOS VALDEZ DUARTE', NULL, 43, 'não', 2),
(159, 'JOAO LUIZ DE LIMA', NULL, 44, 'não', 2),
(160, 'JOAO VILALVA DE MORAES', NULL, 45, 'não', 2),
(161, 'JOAQUIM PEREIRA DA LUZ', NULL, 57, 'não', 2),
(162, 'JOCIVALDO MACHADO SATIRO', NULL, 43, 'não', 2),
(163, 'JOEL DA SILVA', NULL, 43, 'não', 2),
(164, 'JOELSON DA SILVA CARVALHO', NULL, 2, 'não', 2),
(165, 'JONILSON GOMES MACHADO', NULL, 63, 'sim', 2),
(166, 'JORGE FREDY PEREYRA PAZ', NULL, 43, 'não', 2),
(167, 'JORGE GARCIA ALFONSO', NULL, 43, 'não', 2),
(168, 'JORGE ORTEGA SAMOZA JUNIOR', NULL, 43, 'não', 2),
(169, 'JOSE DIVINO BUENO LIMA', NULL, 43, 'não', 2),
(170, 'JOSE ELOY DE MAGALHAES JUNIOR', NULL, 46, 'não', 2),
(171, 'JOSE FABIO CAVALCANTE', NULL, 59, 'não', 2),
(172, 'JOSE JULIO DA SILVA', NULL, 43, 'não', 2),
(173, 'JOSE PARE NETO', NULL, 46, 'não', 2),
(174, 'JOSIEL PAULO DA SILVA', NULL, 64, 'sim', 2),
(175, 'JULIANO CATARINO BARBOSA DOS SANTOS', NULL, 43, 'não', 2),
(176, 'JULIANO DOS SANTOS LISBOA', NULL, 65, 'sim', 2),
(177, 'JULIANO GARCIA DE MATOS', NULL, 43, 'não', 2),
(178, 'JUNIOR DE OLIVEIRA ORTIZ', NULL, 55, 'não', 2),
(179, 'JUSSIE ANTONIO SAS DOS SANTOS', NULL, 26, 'não', 2),
(180, 'KATYANA MACIEL DUARTE DE ARAUJO', NULL, 2, 'não', 2),
(181, 'KELLY SANTANA DA COSTA', NULL, 66, 'sim', 2),
(182, 'KELVINN EOMAYANN SOUZA MORAES', NULL, 43, 'não', 2),
(183, 'KLEBER ESPIRITO SANTO DA SILVA', NULL, 43, 'não', 2),
(184, 'KLINTON QUIRINO MARTINS', NULL, 43, 'não', 2),
(185, 'LAIS DA SILVA LIMA DO NASCIMENTO', NULL, 67, 'sim', 2),
(186, 'LAUDELINO DOS SANTOS BRANDAO', NULL, 43, 'não', 2),
(187, 'LAURINEY IBRAHIM DENIZ', NULL, 27, 'sim', 2),
(188, 'LEANDRO SANTOS DA CONCEICAO', NULL, 59, 'não', 2),
(189, 'LUCIANO OLIVEIRA BARBOSA', NULL, 68, 'não', 2),
(190, 'LUIS ANTONIO OLIVEIRA DA SILVA', NULL, 54, 'sim', 2),
(191, 'LUIS MARCIO COLMAN DE CASTRO', NULL, 46, 'não', 2),
(192, 'LUIZ CLAUDIO ABREU DA SILVA', NULL, 43, 'não', 2),
(193, 'LUIZ DOS SANTOS FILHO', NULL, 45, 'não', 2),
(194, 'LUIZ EDUARDO ARRUDA DE SOUZA', NULL, 43, 'não', 2),
(195, 'LUIZ HENRIQUE LOPES DA COSTA', NULL, 59, 'não', 2),
(196, 'LUIZ MARCIO NEREU GOMES', NULL, 43, 'não', 2),
(197, 'LUIZ OCTAVIO OLIVEIRA DE SOUZA', NULL, 51, 'não', 2),
(198, 'LUIZ OCTAVIO VILALVA DOS SANTOS', NULL, 4, 'não', 2),
(199, 'LUIZ OTAVIO DAS NEVES ARRUDA', NULL, 46, 'não', 2),
(200, 'LUIZ VINICIUS MORAES DOS SANTOS', NULL, 25, 'não', 2),
(201, 'MARCELO DE ARRUDA PAULO', NULL, 43, 'não', 2),
(202, 'MARCIO RODRIGUES DA PAZ', NULL, 44, 'não', 2),
(203, 'MARCO AURELIO DE ANDRADE ROCHA', NULL, 43, 'não', 2),
(204, 'MARCONDES GOMES ROA', NULL, 44, 'não', 2),
(205, 'MARCONZET PEREIRA DA SILVA', NULL, 27, 'sim', 2),
(206, 'MARCOS ANTONIO DO PRADO', NULL, 46, 'não', 2),
(207, 'MARCOS ORTIZ DANTAS', NULL, 44, 'não', 2),
(208, 'MARCOS PEREIRA DA SILVA', NULL, 27, 'sim', 2),
(209, 'MARCOS ROSENES PIRES', NULL, 57, 'não', 2),
(210, 'MARIO MARCIO DE ARRUDA', NULL, 38, 'não', 2),
(211, 'MARIO MARCIO SENNA DA SILVA', NULL, 46, 'não', 2),
(212, 'MARIVALDO WAGNER DOS SANTOS QUIRINO', NULL, 44, 'não', 2),
(213, 'MARTINHO DE ALCANTARA RODRIGUES FILHO', NULL, 43, 'não', 2),
(214, 'MAURICIO CALONGA DA ROCHA', NULL, 43, 'não', 2),
(215, 'MAURICIO DE OLIVEIRA PORI', NULL, 43, 'não', 2),
(216, 'MELQUESEDEQUE VELASQUEZ GONCALVES', NULL, 43, 'não', 2),
(217, 'MILLER PENHA DOS SANTOS', NULL, 25, 'não', 2),
(218, 'NATHANAEL PIRES DE MENDONCA', NULL, 57, 'não', 2),
(219, 'NEORI VIEIRA SOUZA', NULL, 44, 'não', 2),
(220, 'NEVERSON DOS SANTOS DE LIMA', NULL, 46, 'não', 2),
(221, 'NILSON RODRIGUES DE MAGALHAES DOS SANTOS', NULL, 47, 'não', 2),
(222, 'ODEMIR DUARTE DE ANDRADE', NULL, 55, 'não', 2),
(223, 'ODILSON ROA PEREIRA', NULL, 46, 'não', 2),
(224, 'ODVALDO FERREIRA BRAGA', NULL, 44, 'não', 2),
(225, 'OLOIRDE DE OLIVEIRA', NULL, 43, 'não', 2),
(226, 'OSNIR OLIVEIRA DOS SANTOS', NULL, 46, 'não', 2),
(227, 'PAULO CESAR DA SILVA', NULL, 43, 'não', 2),
(228, 'PAULO DA COSTA SILVA', NULL, 43, 'não', 2),
(229, 'PEDRO HENRIQUE MARCIANO POUSO', NULL, 69, 'sim', 2),
(230, 'RAFAEL DE OLIVEIRA SILVEIRA', NULL, 43, 'não', 2),
(231, 'RAIMUNDO DE LIMA SILVA', NULL, 43, 'não', 2),
(232, 'REGIANE SOARES DE OLIVEIRA', NULL, 43, 'não', 2),
(233, 'REINALDO ALMIRON', NULL, 43, 'não', 2),
(234, 'REINALDO FIGUEIREDO DE JESUS', NULL, 66, 'sim', 2),
(235, 'RENAN DA SILVA JULIANO', NULL, 26, 'não', 2),
(236, 'REVERTHON RIVER MARTINES', NULL, 43, 'não', 2),
(237, 'ROBERTO RODRIGUES FLORENTINO', NULL, 44, 'não', 2),
(238, 'ROBSON DE SOUZA RUIZ', NULL, 52, 'não', 2),
(239, 'RODINEY DA SILVA NASCIMENTO', NULL, 70, 'não', 2),
(240, 'RODINEY JUNIOR SOARES NASCIMENTO', NULL, 70, 'não', 2),
(241, 'RODOLFO DE JESUS DA SILVA', NULL, 43, 'não', 2),
(242, 'ROGERIO DE LIMA SOUZA', NULL, 44, 'não', 2),
(243, 'RONALDO RODRIGUES DE SOUZA', NULL, 68, 'não', 2),
(244, 'RONEY GODOY RODRIGUES', NULL, 44, 'não', 2),
(245, 'RONEY ROCHA SOARES', NULL, 55, 'não', 2),
(246, 'ROZENILSON CRISTO DA SILVA', NULL, 25, 'não', 2),
(247, 'RUBENS HENRIQUE MAIA DIAZ JUNIOR', NULL, 59, 'não', 2),
(248, 'RUDISON DE SOUZA MASCARENHAS', NULL, 70, 'não', 2),
(249, 'SAMUEL SOARES DA SILVA', NULL, 43, 'não', 2),
(250, 'SANDRO ALVES DOS SANTOS', NULL, 43, 'não', 2),
(251, 'SANDRO APONTE ALMANZA', NULL, 43, 'não', 2),
(252, 'SANTOS ARANDA DA SILVA', NULL, 46, 'não', 2),
(253, 'SIDNEY RODRIGUES FLORENTINO', NULL, 54, 'sim', 2),
(254, 'SIDNEY SORRILHA DA SILVA', NULL, 38, 'não', 2),
(255, 'SOLOMAR BENIGNO DE SALES JUNIOR', NULL, 47, 'não', 2),
(256, 'SOLON MONTEIRO DOS SANTOS', NULL, 41, 'não', 2),
(257, 'STEFERSON SENNA DO CARMO', NULL, 43, 'não', 2),
(258, 'THIAGO AUGUSTO PEDREIRA DE SOUZA', NULL, 43, 'não', 2),
(259, 'THIAGO RODEM DE MORAES', NULL, 68, 'não', 2),
(260, 'VANDERLEI TAVARES MARTINEZ', NULL, 43, 'não', 2),
(261, 'VANDIR DA COSTA VILALVA', NULL, 70, 'não', 2),
(262, 'VICTOR HUGO DE ANDRADE DUARTE', NULL, 2, 'não', 2),
(263, 'VINICIUS DE SOUZA SILVA', NULL, 67, 'sim', 2),
(264, 'WAGNER DIVINO BUENO', NULL, 43, 'não', 2),
(265, 'WALDENILSON FERREIRA RODRIGUES', NULL, 44, 'não', 2),
(266, 'WANDER CAMPOS RAMOS', NULL, 68, 'não', 2),
(267, 'WANDERCY AGUILHERA DE OLIVEIRA', NULL, 44, 'não', 2),
(268, 'WILLIAM CLIMACO GUERREIRO', NULL, 25, 'não', 2),
(269, 'WILSON ELEOTERIO DA SILVA', NULL, 43, 'não', 2),
(270, 'WLADEMIR COFFACI ARAUJO', NULL, 55, 'não', 2),
(271, 'WOLNEY NERY DE ANDRADE FREITAS', NULL, 43, 'não', 2),
(272, 'YENE GOMES DE LIMA ROSSONI', NULL, 43, 'não', 2),
(273, 'ADAILTON DOS SANTOS SILVA', NULL, 71, 'não', 4),
(274, 'ADALBERTO DE OLIVEIRA BASTOS', NULL, 43, 'não', 4),
(275, 'ADELMO FREITAS BARBOSA', NULL, 43, 'não', 4),
(276, 'ADENIL CUNHA COSTA', NULL, 43, 'não', 4),
(277, 'ADENILSON DOS SANTOS FILHO', NULL, 44, 'não', 4),
(278, 'ADILSON DE NOVAIS SANTANA', NULL, 8, 'não', 4),
(279, 'ADILSON DOS SANTOS', NULL, 43, 'não', 4),
(280, 'ADILSON SOUZA DOS SANTOS', NULL, 44, 'não', 4),
(281, 'ADRIANO CESAR DA SILVA', NULL, 43, 'não', 4),
(282, 'ADRIANO NOVAIS DOS SANTOS', NULL, 43, 'não', 4),
(283, 'ADRIEL LOPES DE ALMEIDA', NULL, 43, 'não', 4),
(284, 'ADSON DO LAGO DAMASCENO', NULL, 55, 'não', 4),
(285, 'ADSON MARQUES DOS SANTOS', NULL, 72, 'não', 4),
(286, 'AGNALDO FELICIANO SANTOS', NULL, 73, 'não', 4),
(287, 'AGNALDO SILVA SANTANA', NULL, 72, 'não', 4),
(288, 'AILTON DE JESUS SANTOS', NULL, 46, 'não', 4),
(289, 'ALAIDE GOMES DE SOUZA', NULL, 38, 'não', 4),
(290, 'ALAN SOUZA BRANDAO', NULL, 43, 'não', 4),
(291, 'ALBERTO MACHADO DOS SANTOS', NULL, 74, 'não', 4),
(292, 'ALDO JARDIM BISPO', NULL, 72, 'não', 4),
(293, 'ALEILTON DOS SANTOS GUIMARAES', NULL, 43, 'não', 4),
(294, 'ALENILSON DE OLIVEIRA LISBOA', NULL, 48, 'não', 4),
(295, 'ALESSANDRO PEREIRA DE OLIVEIRA', NULL, 43, 'não', 4),
(296, 'ALEX SANDRO SILVA MASSENO', NULL, 43, 'não', 4),
(297, 'ALEX SANTOS FERREIRA', NULL, 43, 'não', 4),
(298, 'ALEXANDRE LIMA DE ALMEIDA', NULL, 46, 'não', 4),
(299, 'ALLANDAQUE ARGOLO DE SOUZA', NULL, 43, 'não', 4),
(300, 'ALMIR SANTOS OLIVEIRA', NULL, 43, 'não', 4),
(301, 'ALOISIO ROCHA NOGUEIRA TERCEIRO', NULL, 43, 'não', 4),
(302, 'AMADEU SOUZA RODRIGUES', NULL, 43, 'não', 4),
(303, 'AMAURI DE JESUS SANTOS ANATOLIO', NULL, 43, 'não', 4),
(304, 'ANATOLIO BOMFIM SOARES DOS SANTOS', NULL, 43, 'não', 4),
(305, 'ANDERSON SILVA DOS SANTOS', NULL, 8, 'não', 4),
(306, 'ANDRE SANTOS CARDOSO', NULL, 43, 'não', 4),
(307, 'ANDRESSON TORQUATO SANTOS', NULL, 46, 'não', 4),
(308, 'ANGELO MARCOS COSTA SILVA', NULL, 43, 'não', 4),
(309, 'ANGELO SANTOS DE AMORIM', NULL, 43, 'não', 4),
(310, 'ANILTON ANDRADE DOS SANTOS', NULL, 43, 'não', 4),
(311, 'ANTONIO CARLOS DO ESPIRITO SANTO GOMES', NULL, 43, 'não', 4),
(312, 'ANTONIO CESAR DAMASCENO DOS SANTOS', NULL, 71, 'não', 4),
(313, 'ANTONIO LEITE SOUZA', NULL, 43, 'não', 4),
(314, 'ANTONIO MARCOS DOS SANTOS RIBEIRO', NULL, 43, 'não', 4),
(315, 'ANTONIO MARCOS DOS SANTOS UMBURANA', NULL, 47, 'não', 4),
(316, 'ANTONIO MEIRA DA SILVA', NULL, 72, 'não', 4),
(317, 'ANTONIO UMBURANA DE SOUZA', NULL, 72, 'não', 4),
(318, 'ANTONY VICTOR BOTELHO DAMASCENO', NULL, 75, 'não', 4),
(319, 'APOLONO SOUZA SALES', NULL, 43, 'não', 4),
(320, 'ARIELSON STHANILEY SALES DE SOUSA', NULL, 44, 'não', 4),
(321, 'ARTUR ANTONIO SILVA NETO', NULL, 8, 'não', 4),
(322, 'AVELINO ANTHERO VALENTE DO COUTO NETO', NULL, 54, 'sim', 4),
(323, 'AZENILTON DOS SANTOS SENA', NULL, 55, 'não', 4),
(324, 'BARBARA GEISA DOS SANTOS', NULL, 56, 'não', 4),
(325, 'BASTIAO VIEIRA DA MOTA NETO', NULL, 66, 'não', 4),
(326, 'BRENO SILVA FROES DO LAGO ', NULL, 25, 'não', 4),
(327, 'BRUNO LOPES DOS ANJOS', NULL, 76, 'não', 4),
(328, 'BRUNO MOURA COSTA DA CRUZ', NULL, 25, 'não', 4),
(329, 'BRUNO SANTOS GONCALVES', NULL, 47, 'não', 4),
(330, 'BRUNO SILVA FERREIRA', NULL, 44, 'não', 4),
(331, 'CAIO NASCIMENTO DE LIMA', NULL, 77, 'não', 4),
(332, 'CAIQUE ADIMARAES DE ARRUDA', NULL, 52, 'não', 4),
(333, 'CAIQUE CAMPOS DOS SANTOS', NULL, 71, 'não', 4),
(334, 'CAMILE SANTOS BARRETO DA SILVA', NULL, 56, 'não', 4),
(335, 'CARLOS EDUARDO SANTOS BRITO CARDOSO', NULL, 78, 'sim', 4),
(336, 'CARLOS HENRIQUE SALES DA SILVA', NULL, 76, 'não', 4),
(337, 'CARLOS HENRIQUE SILVA SANTOS', NULL, 70, 'não', 4),
(338, 'CARLOS MAGNO NASCIMENTO OLIVEIRA', NULL, 43, 'não', 4),
(339, 'CARLOS MIGUEL DA COSTA SILVA', NULL, 46, 'não', 4),
(340, 'CARLOS ROBERTO SILVA DE NOVAES', NULL, 45, 'não', 4),
(341, 'CAROLINI DE SOUZA SILVA', NULL, 66, 'não', 4),
(342, 'CELIO JOSE DA SILVA', NULL, 44, 'não', 4),
(343, 'CHARLES SOUZA VIEIRA', NULL, 43, 'não', 4),
(344, 'CICERO DA SILVA GUERRA', NULL, 43, 'não', 4),
(345, 'CIRO MACHADO CAMPOS', NULL, 43, 'não', 4),
(346, 'CLAUDEMIR DOS REIS CARDOSO', NULL, 71, 'não', 4),
(347, 'CLAUDIO GONCALVES DE SOUZA', NULL, 43, 'não', 4),
(348, 'CLAUDIO SILVA MACHADO', NULL, 8, 'não', 4),
(349, 'CLEBER DA SILVA RAMOS', NULL, 8, 'não', 4),
(350, 'CLEBER SANTANA MEIRA', NULL, 46, 'não', 4),
(351, 'CLEBIS DE JESUS CORREIA', NULL, 43, 'não', 4),
(352, 'CLEBSON AGUIAR DO NASCIMENTO', NULL, 45, 'não', 4),
(353, 'CLEISON GALVAO LAGO', NULL, 46, 'não', 4),
(354, 'CLEITON SILVA SOUZA', NULL, 8, 'não', 4),
(355, 'CLODOALDO MAGALHAES DOS SANTOS', NULL, 59, 'não', 4),
(356, 'CRISTIANO SANTOS DA SILVA', NULL, 70, 'não', 4),
(357, 'CRISTIANO SOUZA COSTA', NULL, 73, 'não', 4),
(358, 'CRISTINIANO RODRIGUES DE OLIVEIRA', NULL, 43, 'não', 4),
(359, 'DALTON BRENDO DE JESUS SILVA', NULL, 46, 'não', 4),
(360, 'DAMIAO NASCIMENTO PEREIRA', NULL, 43, 'não', 4),
(361, 'DANIEL FERREIRA SANTOS', NULL, 43, 'não', 4),
(362, 'DANIEL LIRA DE ARAUJO', NULL, 46, 'não', 4),
(363, 'DANILO CAMPOS DOS SANTOS', NULL, 43, 'não', 4),
(364, 'DANILO DE SANTANA DE SOUZA', NULL, 38, 'não', 4),
(365, 'DANILO RAMOS TEIXEIRA', NULL, 43, 'não', 4),
(366, 'DANILO SILVA DE OLIVEIRA', NULL, 47, 'não', 4),
(367, 'DANILO SOUZA SANTOS', NULL, 48, 'não', 4),
(368, 'DARLAN BASTOS DE SOUZA', NULL, 43, 'não', 4),
(369, 'DARLAN BORGES DOS SANTOS', NULL, 43, 'não', 4),
(370, 'DAVI LOPES DOS SANTOS', NULL, 43, 'não', 4),
(371, 'DELEON AZEVEDO ANDRE', NULL, 43, 'não', 4),
(372, 'DENILSON GUIMARAES DIAS', NULL, 79, 'não', 4),
(373, 'DENILSON SANTANA DOS SANTOS', NULL, 43, 'não', 4),
(374, 'DENILTON RIBAS DOS SANTOS', NULL, 46, 'não', 4),
(375, 'DEOCLECIANO CARDOSO SANTOS NETO', NULL, 80, 'não', 4),
(376, 'DERINALDO DOS ANJOS SANTOS', NULL, 44, 'não', 4),
(377, 'DERNIVAN DOS SANTOS BORBA GONZAGA', NULL, 8, 'não', 4),
(378, 'DIEGO CAMPOS DOS SANTOS', NULL, 81, 'sim', 4),
(379, 'DIEGO DOS SANTOS DE SOUZA', NULL, 43, 'não', 4),
(380, 'DIEGO MACHADO CAJADO', NULL, 43, 'não', 4),
(381, 'DIEGO SILVA PEREIRA', NULL, 44, 'não', 4),
(382, 'DINAILSON AQUINO DOS SANTOS', NULL, 43, 'não', 4),
(383, 'DIONES SILVA PIRES', NULL, 43, 'não', 4),
(384, 'DJACKSON LIMA SANTIAGO', NULL, 82, 'sim', 4),
(385, 'DOUGLAS VINICIUS DA SILVA LONGO', NULL, 72, 'não', 4),
(386, 'EDILSON DA SILVA NOVAES', NULL, 71, 'não', 4),
(387, 'EDINALDO ALMEIDA RODRIGUES', NULL, 45, 'não', 4),
(388, 'EDIVANIA JESUS DA SILVA', NULL, 66, 'não', 4),
(389, 'EDMUNDO ALVES DOS SANTOS', NULL, 71, 'não', 4),
(390, 'EDNA BRITO DA SILVA COSTA', NULL, 43, 'não', 4),
(391, 'EDNALDO FREITAS BARBOSA', NULL, 71, 'não', 4),
(392, 'EDSON PEREIRA DO LAGO', NULL, 46, 'não', 4),
(393, 'EDUARDO DA SILVA MOTA', NULL, 61, 'não', 4),
(394, 'EDUARDO XAVIER MOTA', NULL, 76, 'não', 4),
(395, 'EDVALDO DAMASCENO SOUZA', NULL, 43, 'não', 4),
(396, 'EDVALDO RAMOS DA SILVA', NULL, 46, 'não', 4),
(397, 'ELDEMIRO SILVA DOS REIS', NULL, 8, 'não', 4),
(398, 'ELENILSON ARAUJO SANTOS', NULL, 72, 'não', 4),
(399, 'ELIAS MACHADO DOS SANTOS', NULL, 46, 'não', 4),
(400, 'ELIEZER SARAIVA DE SOUZA', NULL, 74, 'não', 4),
(401, 'ELIOMAR SANTOS SOUZA', NULL, 43, 'não', 4),
(402, 'ELISANE MACHADO CARDOSO', NULL, 43, 'não', 4),
(403, 'ELMA DAS NEVES DOS SANTOS DE SANTANA', NULL, 83, 'não', 4),
(404, 'ELMANO MOTTA NEVES LEITE', NULL, 80, 'não', 4),
(405, 'ELTON LENON SOUZA ALVES', NULL, 60, 'não', 4),
(406, 'EMERSON BERNARDO DA SILVA', NULL, 76, 'não', 4),
(407, 'EMERSON SANTOS DE JESUS', NULL, 44, 'não', 4),
(408, 'ENNYK PIZZANI SANTOS MACHADO', NULL, 71, 'não', 4),
(409, 'ENRIQUE SAAVEDRA GUZMAN JUNIOR', NULL, 43, 'não', 4),
(410, 'ERICK PEREIRA LAGO DE SOUZA', NULL, 47, 'não', 4),
(411, 'ERISVALDO SOUZA GOMES', NULL, 43, 'não', 4),
(412, 'ERIVAN SANTOS DA SILVA BRANDAO', NULL, 44, 'não', 4),
(413, 'ERIVELTON MACHADO DOS SANTOS', NULL, 43, 'não', 4),
(414, 'ERNEY DIMITRE MARQUES LIMA', NULL, 43, 'não', 4),
(415, 'EVALDO RIBEIRO DE SOUZA', NULL, 71, 'não', 4),
(416, 'EVERTON ALEXANDRINO DE OLIVEIRA', NULL, 71, 'não', 4),
(417, 'FABIO ALVARO SANTOS BOMFIM', NULL, 46, 'não', 4),
(418, 'FABIO DOS SANTOS FREITAS', NULL, 43, 'não', 4),
(419, 'FABIO JULIO GONCALVES DO NASCIMENTO', NULL, 43, 'não', 4),
(420, 'FABIO NOVAES SANTOS', NULL, 76, 'não', 4),
(421, 'FABIO RODRIGO BRAGA', NULL, 54, 'sim', 4),
(422, 'FABIO SANTANA MEIRA', NULL, 43, 'não', 4),
(423, 'FABRICIO SANTANA DE JESUS OLIVEIRA', NULL, 43, 'não', 4),
(424, 'FAGNER SENA PASSOS', NULL, 45, 'não', 4),
(425, 'FELICIANO SANTOS SOUZA', NULL, 84, 'não', 4),
(426, 'FELIPE SANTANA DOS SANTOS', NULL, 75, 'não', 4),
(427, 'FERNANDA FREITAS DOS SANTOS', NULL, 71, 'não', 4),
(428, 'FERNANDO CIRILO DE SOUZA', NULL, 71, 'não', 4),
(429, 'FERNANDO DA SILVA MORAES', NULL, 43, 'não', 4),
(430, 'FLAVIO DOS SANTOS COSTA', NULL, 43, 'não', 4),
(431, 'FLAVIO NOVAIS SIQUEIRA', NULL, 43, 'não', 4),
(432, 'FRANCISCO GUIMARAES CAIRES', NULL, 44, 'não', 4),
(433, 'FRANCISCO NASCIMENTO RAMOS FILHO', NULL, 46, 'não', 4),
(434, 'FRANCISCO RODRIGUES SANTOS FILHO', NULL, 72, 'não', 4),
(435, 'GEISA SANTANA DA COSTA', NULL, 43, 'não', 4),
(436, 'GENICARLOS DOS SANTOS DE JESUS', NULL, 71, 'não', 4),
(437, 'GENIELTON SILVA PIRES', NULL, 43, 'não', 4),
(438, 'GENILDO BATISTA DOS REIS', NULL, 44, 'não', 4),
(439, 'GENIVAL SOUZA OLIVEIRA', NULL, 43, 'não', 4),
(440, 'GEOMARIO CARNEIRO DA SILVA', NULL, 44, 'não', 4),
(441, 'GEOVANE SANTOS DE SOUZA', NULL, 71, 'não', 4),
(442, 'GEOVANY DA SILVA BENTO', NULL, 46, 'não', 4),
(443, 'GERFSON DA SILVA COSTA', NULL, 81, 'sim', 4),
(444, 'GERINALDO FERREIRA CALADO', NULL, 55, 'não', 4),
(445, 'GERSON SANTANA MENEZES', NULL, 68, 'não', 4),
(446, 'GETULIANA NOVAES DOS SANTOS DE OLIVEIRA', NULL, 60, 'não', 4),
(447, 'GETULIO SANTOS GUSMAO', NULL, 43, 'não', 4),
(448, 'GILBERTO ALVES BRITO JUNIOR', NULL, 43, 'não', 4),
(449, 'GILDEON APARECIDO SOUZA DA SILVA', NULL, 43, 'não', 4),
(450, 'GILDESIO JOAQUIM DOS SANTOS JUNIOR', NULL, 43, 'não', 4),
(451, 'GILMAR DE SANTANA PINHEIRO', NULL, 43, 'não', 4),
(452, 'GILMAR SILVA DOS SANTOS', NULL, 46, 'não', 4),
(453, 'GILSON QUEIROZ LISBOA', NULL, 46, 'não', 4),
(454, 'GILVAN ALVES SANTOS', NULL, 46, 'não', 4),
(455, 'GILVAN BATISTA DE JESUS', NULL, 43, 'não', 4),
(456, 'GILVAN DE SOUZA ROSA', NULL, 43, 'não', 4),
(457, 'GIRLAN JESUS DOS SANTOS', NULL, 8, 'não', 4),
(458, 'GIVANILDO SILVA DOS SANTOS', NULL, 44, 'não', 4),
(459, 'GIVONEI DE OLIVEIRA SILVA', NULL, 54, 'sim', 4),
(460, 'GLAUBER MOREIRA COSTA LISBOA', NULL, 43, 'não', 4),
(461, 'GLEIDSON PEREIRA ALVES', NULL, 46, 'não', 4),
(462, 'GUSTAVO LOPES DOS ANJOS', NULL, 25, 'não', 4),
(463, 'HEBER SOUZA DA SILVA', NULL, 25, 'não', 4),
(464, 'HELDERJON DA SILVA SANTOS', NULL, 43, 'não', 4),
(465, 'HELIO BATISTA DE JESUS', NULL, 43, 'não', 4),
(466, 'HELIO DA SILVA CARDOSO', NULL, 46, 'não', 4),
(467, 'HELIO ROCHA LIMA', NULL, 8, 'não', 4),
(468, 'HENRIQUE DE ALMEIDA SANTOS', NULL, 80, 'não', 4),
(469, 'HENRIQUE SOUZA NOVAES', NULL, 43, 'não', 4),
(470, 'HENRY SANTOS SANTANA', NULL, 56, 'não', 4),
(471, 'HERLON DOS SANTOS ANJOS', NULL, 8, 'não', 4),
(472, 'HUDSON ELOI SANTOS', NULL, 77, 'não', 4),
(473, 'IAGO SOUZA DOS SANTOS', NULL, 43, 'não', 4),
(474, 'IEDA ALCANTARA LAGO', NULL, 15, 'não', 4),
(475, 'IGOR DE JESUS', NULL, 70, 'não', 4),
(476, 'INACIO DE SOUZA BRAGA', NULL, 46, 'não', 4),
(477, 'ISAIAS DE JESUS DA SILVA', NULL, 76, 'não', 4),
(478, 'ISAQUIEL BARROS DA SILVA', NULL, 43, 'não', 4),
(479, 'ISAQUIEL SANTANA DE SOUZA SANTOS', NULL, 43, 'não', 4),
(480, 'ISMAEL MAGALHAES LAGO', NULL, 43, 'não', 4),
(481, 'ISRAEL BORGES VENTURA', NULL, 44, 'não', 4),
(482, 'ISRAEL DE ABREU ARAUJO', NULL, 85, 'sim', 4),
(483, 'ITALO CESAR DE SOUZA', NULL, 71, 'não', 4),
(484, 'ITAMAR ALVES GONCALVES', NULL, 46, 'não', 4),
(485, 'IURI CIRILO FROES', NULL, 8, 'não', 4),
(486, 'IVAN JUNQUEIRA REIS', NULL, 85, 'sim', 4),
(487, 'IVAN PAULO BARROS DOS SANTOS', NULL, 43, 'não', 4),
(488, 'JACKSON DA SILVA COSTA', NULL, 54, 'sim', 4),
(489, 'JACKSON NOVAIS ROCHA', NULL, 43, 'não', 4),
(490, 'JACSON CRUZ SANTOS', NULL, 71, 'não', 4),
(491, 'JADIEL DA SILVA COSTA', NULL, 44, 'não', 4),
(492, 'JAIME LAGO DA GUARDA', NULL, 43, 'não', 4),
(493, 'JAIR MOURA GONCALVES', NULL, 43, 'não', 4),
(494, 'JAMILE DO NASCIMENTO VIEIRA', NULL, 38, 'não', 4),
(495, 'JEAN GONCALVES DE OLIVEIRA', NULL, 86, 'não', 4),
(496, 'JEFERSON FERNANDES COSTA', NULL, 43, 'não', 4),
(497, 'JEREMIAS BARROS DE SOUZA', NULL, 75, 'não', 4),
(498, 'JEREMIAS SANTOS DA CRUZ', NULL, 46, 'não', 4),
(499, 'JESSE VIEIRA SANTOS', NULL, 87, 'não', 4),
(500, 'JOAO BATISTA SANTOS DA SILVA', NULL, 55, 'não', 4),
(501, 'JOAO CARLOS DE SOUZA', NULL, 43, 'não', 4),
(502, 'JOAO DA SILVA CERQUEIRA', NULL, 43, 'não', 4),
(503, 'JOAO IMIDIO DA SILVA NETO', NULL, 70, 'não', 4),
(504, 'JOAO SANTANA DE JESUS', NULL, 43, 'não', 4),
(505, 'JOAO VICTOR RIBEIRO DAMASCENO', NULL, 71, 'não', 4),
(506, 'JOAO VICTOR TELES LISBOA', NULL, 75, 'não', 4),
(507, 'JOAO VITOR SANTOS DA CRUZ', NULL, 87, 'não', 4),
(508, 'JOAO VITOR SOUZA GUIMARAES LIMA', NULL, 48, 'não', 4),
(509, 'JOELMA BISPO', NULL, 56, 'não', 4),
(510, 'JOEMERSON DE SOUZA VEIGA', NULL, 43, 'não', 4),
(511, 'JOICE LEANE DO NASCIMENTO VIEIRA', NULL, 56, 'não', 4),
(512, 'JOILSON DOS SANTOS FRANCA', NULL, 73, 'não', 4),
(513, 'JOILSON OLIVEIRA LEAL', NULL, 43, 'não', 4),
(514, 'JONAS DA SILVA ALMADA', NULL, 43, 'não', 4),
(515, 'JONAS SANTANA DA SILVA', NULL, 43, 'não', 4),
(516, 'JONAS SANTOS NETO', NULL, 66, 'não', 4),
(517, 'JONIVAL NOVAES DOS SANTOS', NULL, 46, 'não', 4),
(518, 'JORDAN DA SILVA PEREIRA', NULL, 46, 'não', 4),
(519, 'JORGE COSTA SILVA', NULL, 43, 'não', 4),
(520, 'JORGE LEANDRO SOUZA SANTOS', NULL, 43, 'não', 4),
(521, 'JORGE NUNES PIRES', NULL, 8, 'não', 4),
(522, 'JORGE PAULO FONTES DOS SANTOS', NULL, 8, 'não', 4),
(523, 'JOSE ACURCO ROCHA VIEIRA NETO', NULL, 38, 'não', 4),
(524, 'JOSE ANTONIO CAFUNDO', NULL, 22, 'sim', 4),
(525, 'JOSE CARLOS ALMEIDA DE OLIVEIRA', NULL, 43, 'não', 4),
(526, 'JOSE CARLOS PEREIRA PIRES', NULL, 72, 'não', 4),
(527, 'JOSE LUIZ ALVES', NULL, 43, 'não', 4),
(528, 'JOSE MESSIAS NUNES DO CARMO', NULL, 59, 'não', 4),
(529, 'JOSE ORLANDO AIRO DE ALMEIDA', NULL, 44, 'não', 4),
(530, 'JOSE PAULO GONZAGA DA CRUZ FONTES', NULL, 8, 'não', 4),
(531, 'JOSE PEREIRA ALVES', NULL, 51, 'não', 4),
(532, 'JOSE RAIMUNDO SOUZA DA SILVA', NULL, 85, 'sim', 4),
(533, 'JOSE ROBERTO DOS ANJOS NASCIMENTO', NULL, 73, 'não', 4),
(534, 'JOSE ROBERTO ELIOTERIO DOS SANTOS', NULL, 43, 'não', 4),
(535, 'JOSEMAR DE OLIVEIRA SANTOS', NULL, 8, 'não', 4),
(536, 'JOSENICIO FIGUEIREDO MACHADO JUNIOR', NULL, 88, 'não', 4),
(537, 'JOSENILDO QUEIROZ LISBOA', NULL, 46, 'não', 4),
(538, 'JOSIEL GONCALVES DOS REIS', NULL, 43, 'não', 4),
(539, 'JOSIVAL GRANJA LIMA', NULL, 68, 'não', 4),
(540, 'JOSUE DUTRA SANTOS', NULL, 45, 'não', 4),
(541, 'JOVAN DOS SANTOS DO NASCIMENTO', NULL, 43, 'não', 4),
(542, 'JOZELIA DE SANTANA REIS', NULL, 71, 'não', 4),
(543, 'JOZIVAL AMORIM DOS SANTOS', NULL, 79, 'não', 4),
(544, 'JUAREZ CALDAS DE JESUS', NULL, 55, 'não', 4),
(545, 'JUAREZ DE SOUZA SANTOS', NULL, 8, 'não', 4),
(546, 'JUCELINO SANTOS SOUZA', NULL, 25, 'não', 4),
(547, 'JUCELIO DE SANTANA PINHEIRO', NULL, 43, 'não', 4),
(548, 'JUCIVALDO MARCELO ANUNCIACAO BORGES', NULL, 38, 'não', 4),
(549, 'JULIANE NASCIMENTO', NULL, 46, 'não', 4),
(550, 'JULIO CESAR MORAIS BORBA', NULL, 75, 'não', 4),
(551, 'JUMARINA COELHO SILVA', NULL, 56, 'não', 4),
(552, 'JURANDI FREITAS BARBOSA', NULL, 45, 'não', 4),
(553, 'JUSCILEIDE SILVA SANTOS', NULL, 43, 'não', 4),
(554, 'JUSICLEIDE FROES DA SILVA', NULL, 43, 'não', 4),
(555, 'KATIA GONCALVES SILVA BOMFIM', NULL, 43, 'não', 4),
(556, 'KLEBER ARAGAO SANTANA', NULL, 43, 'não', 4),
(557, 'KLEITON LIMA CARDOSO', NULL, 66, 'não', 4),
(558, 'LASARO BISPO DOS SANTOS', NULL, 43, 'não', 4),
(559, 'LAZARO FRANCA SANTANA', NULL, 44, 'não', 4),
(560, 'LAZARO WASHINGTON REIS DOS SANTOS', NULL, 46, 'não', 4),
(561, 'LEANDRO CIRILO FROES', NULL, 8, 'não', 4),
(562, 'LEANDRO DOS REIS AGUIAR', NULL, 43, 'não', 4),
(563, 'LEANDRO DOS SANTOS SILVA', NULL, 44, 'não', 4),
(564, 'LEANDRO DOS SANTOS SILVA', NULL, 55, 'não', 4),
(565, 'LEANDRO SANTOS DA PAZ', NULL, 46, 'não', 4),
(566, 'LEANDRO SANTOS MENEZES', NULL, 46, 'não', 4),
(567, 'LEANDRO SOARES DOS SANTOS', NULL, 46, 'não', 4),
(568, 'LEOMIR GONCALVES DA CRUZ', NULL, 43, 'não', 4),
(569, 'LEONARDO BASTOS DE MAGALHAES', NULL, 43, 'não', 4),
(570, 'LIOMIR FRANCA MIRANDA', NULL, 43, 'não', 4),
(571, 'LORENA PIRES LAURENCIO DE NOVAES', NULL, 56, 'não', 4),
(572, 'LOURIVAL PESSOA DA SILVA FILHO', NULL, 68, 'não', 4),
(573, 'LUCAS BASTOS SALES', NULL, 52, 'não', 4),
(574, 'LUCAS BOTELHO LAGO', NULL, 81, 'sim', 4),
(575, 'LUCAS BURITI MOTA DA SILVA', NULL, 43, 'não', 4),
(576, 'LUCAS DAMASCENO CARDOSO', NULL, 68, 'não', 4),
(577, 'LUCAS DOS SANTOS GALVAO', NULL, 8, 'não', 4),
(578, 'LUCAS SILVA NASCIMENTO', NULL, 72, 'não', 4),
(579, 'LUCAS SOUZA DA SILVA', NULL, 25, 'não', 4),
(580, 'LUCIAN DE SOUZA ARGOLO', NULL, 43, 'não', 4),
(581, 'LUCIANO BISPO DOS SANTOS', NULL, 43, 'não', 4),
(582, 'LUCIANO CAMPOS SILVA', NULL, 43, 'não', 4),
(583, 'LUCIANO SANTANA BARBOSA', NULL, 44, 'não', 4),
(584, 'LUIS CARLOS MIRANDA RIBEIRO', NULL, 61, 'não', 4),
(585, 'LUIS EDUARDO DE JESUS', NULL, 25, 'não', 4),
(586, 'LUIS HENRIQUE NASCIMENTO SANTIAGO', NULL, 72, 'não', 4),
(587, 'LUIS SOUZA COSTA', NULL, 44, 'não', 4),
(588, 'LUIZ DOS SANTOS BRAGA', NULL, 43, 'não', 4),
(589, 'LUIZ FELIPE SANTOS ALEXANDRE DE SOUZA', NULL, 52, 'não', 4),
(590, 'LUIZ RICARDO CRUZ QUEIROS', NULL, 8, 'não', 4),
(591, 'MAICON LUCAS BRUCIERI', NULL, 54, 'sim', 4),
(592, 'MANACEIS DE OLIVEIRA DUARTE', NULL, 44, 'não', 4),
(593, 'MANOEL DOS SANTOS AMPARO', NULL, 43, 'não', 4),
(594, 'MARCELO DOS SANTOS MAIA', NULL, 8, 'não', 4),
(595, 'MARCELO SOUSA JESUS', NULL, 43, 'não', 4),
(596, 'MARCIANO FERREIRA DA SILVA', NULL, 54, 'sim', 4),
(597, 'MARCIO BRITO RIBEIRO', NULL, 52, 'não', 4),
(598, 'MARCIO JOSE DA SILVA OLIVEIRA', NULL, 25, 'não', 4),
(599, 'MARCIO SILVA CRUZ', NULL, 46, 'não', 4),
(600, 'MARCIO SOUZA LAGO', NULL, 43, 'não', 4),
(601, 'MARCONI RIBEIRO CIRQUEIRA', NULL, 25, 'não', 4),
(602, 'MARCOS ANTONIO BASTOS BISPO', NULL, 47, 'não', 4),
(603, 'MARCOS ANTONIO DOS SANTOS CARLOS', NULL, 85, 'sim', 4),
(604, 'MARCOS FREITAS DOS SANTOS', NULL, 74, 'não', 4),
(605, 'MARCOS LUANO DE MACEDO GOMES', NULL, 81, 'sim', 4),
(606, 'MARCOS LUIZ LIMA MOTA', NULL, 75, 'não', 4),
(607, 'MARINALVA NOVAES BOTELHO', NULL, 43, 'não', 4),
(608, 'MARIO HENRIQUE SILVA DE NOVAIS', NULL, 48, 'não', 4),
(609, 'MARIVAL RANGEL DE OLIVEIRA JUNIOR', NULL, 43, 'não', 4),
(610, 'MARLON CELESTINO DOS SANTOS', NULL, 43, 'não', 4),
(611, 'MARLOS SILVA PIRES', NULL, 44, 'não', 4),
(612, 'MATEUS DA SILVA LISBOA', NULL, 8, 'não', 4),
(613, 'MATEUS DE JESUS SILVA', NULL, 46, 'não', 4),
(614, 'MATHEUS DE SANTANA CELESTINO', NULL, 46, 'não', 4),
(615, 'MATHEUS SENA DE SOUZA', NULL, 48, 'não', 4),
(616, 'MAURICIO CESAR RESENDE', NULL, 64, 'não', 4),
(617, 'MAURICIO FARES MEIRA SILVA', NULL, 46, 'não', 4),
(618, 'MAURICIO OLIVEIRA NUNES', NULL, 43, 'não', 4),
(619, 'MAURINO APARECIDO DE JESUS', NULL, 7, 'não', 4),
(620, 'MAURO DE OLIVEIRA DA COSTA', NULL, 43, 'não', 4),
(621, 'MAXWELL PALMEIRA ANDRADE', NULL, 52, 'não', 4),
(622, 'MESSIAS SILVA SOUZA', NULL, 43, 'não', 4),
(623, 'MICHEL SANTOS DOS REIS', NULL, 43, 'não', 4),
(624, 'MIRAILTON ANDRADE SAMPAIO BRITO', NULL, 43, 'não', 4),
(625, 'MIROSMAR JOSE DIAS DA SILVA', NULL, 25, 'não', 4),
(626, 'MOACYR LUIZ FATEL JUNIOR', NULL, 43, 'não', 4),
(627, 'MURILO ALMEIDA NOVAIS DOS SANTOS', NULL, 56, 'não', 4),
(628, 'MURILO RIBEIRO DOS SANTOS', NULL, 43, 'não', 4),
(629, 'NADIA SAO PAULO DE CASTRO NOVAES', NULL, 38, 'não', 4),
(630, 'NAIARA SILVA SANTOS', NULL, 56, 'não', 4),
(631, 'NAIARA SOUZA DOS SANTOS', NULL, 43, 'não', 4),
(632, 'NAILSON CORTES FIGUEIREDO', NULL, 43, 'não', 4),
(633, 'NEILSON GUIMARAES MOREIRA', NULL, 89, 'não', 4),
(634, 'NEIVA CAIRES SILVA', NULL, 71, 'não', 4),
(635, 'NEVI SOUZA DOS SANTOS', NULL, 46, 'não', 4),
(636, 'NILVAN OLIVEIRA DE NOVAES', NULL, 46, 'não', 4),
(637, 'OSMAR CUSTODIO DOS SANTOS', NULL, 44, 'não', 4),
(638, 'OSMAR RODRIGUES CELESTINO', NULL, 43, 'não', 4),
(639, 'OSMAR SANTOS DOS SANTOS', NULL, 44, 'não', 4),
(640, 'OSMAR VIANA FROES', NULL, 43, 'não', 4),
(641, 'PABLO ROBERTO SALES FIGUEIREDO', NULL, 44, 'não', 4),
(642, 'PALOMA DE NOVAES FONTES', NULL, 38, 'não', 4),
(643, 'PAULO LIMA QUINTELLA', NULL, 43, 'não', 4),
(644, 'PAULO RICARDO BERNARDO SOUSA', NULL, 43, 'não', 4),
(645, 'PAULO RICARDO DE SOUZA COUTO', NULL, 43, 'não', 4),
(646, 'PAULO VITOR PEREIRA SANTOS', NULL, 68, 'não', 4),
(647, 'PEDRO LUCAS SANTOS PEREIRA', NULL, 71, 'não', 4),
(648, 'PEDRO NELTON LAGO DOS ANJOS', NULL, 43, 'não', 4),
(649, 'PETRUCIO ESTEVAM MOURA', NULL, 48, 'não', 4),
(650, 'RAFAEL JOSE DA CRUZ', NULL, 63, 'sim', 4),
(651, 'RAFAEL NOVAES CAIRES SANTANA', NULL, 43, 'não', 4),
(652, 'RAIANE DE ARAUJO CAMPOS', NULL, 43, 'não', 4),
(653, 'RAILDO CARVALHO DOS REIS', NULL, 71, 'não', 4),
(654, 'RAILTON DOS SANTOS SANTANA', NULL, 43, 'não', 4),
(655, 'RAIMUNDO AMORIM DE ALMEIDA', NULL, 70, 'não', 4),
(656, 'RAIMUNDO DOS SANTOS LISBOA FILHO', NULL, 43, 'não', 4),
(657, 'RAIMUNDO JOSÉ DE JESUS', NULL, 27, 'sim', 4),
(658, 'RAIMUNDO PANTALIAO DE SOUZA', NULL, 43, 'não', 4),
(659, 'RAIMUNDO RUI OLIVEIRA SANTANA', NULL, 43, 'não', 4),
(660, 'RAMON FIGUEIREDO SANTANA', NULL, 52, 'não', 4),
(661, 'RAMON MENDES ALVES', NULL, 46, 'não', 4),
(662, 'REGINALDO MOTA DE OLIVEIRA', NULL, 68, 'não', 4),
(663, 'REINALDO CARDOSO DOS SANTOS', NULL, 43, 'não', 4),
(664, 'REINALDO DA PURIFICACAO DE ALMEIDA', NULL, 55, 'não', 4),
(665, 'REINALDO DIAS PRATA', NULL, 43, 'não', 4),
(666, 'REINALDO REIS DA SILVA', NULL, 43, 'não', 4),
(667, 'RENAN LEITE JESUS', NULL, 25, 'não', 4),
(668, 'RENAN OLIVEIRA SANTOS', NULL, 43, 'não', 4),
(669, 'RENATO ANTONIO DE OLIVEIRA JUNIOR', NULL, 43, 'não', 4),
(670, 'RENATO PEREIRA DA SILVA', NULL, 59, 'não', 4),
(671, 'RENER ALMEIDA GONCALVES', NULL, 76, 'não', 4),
(672, 'RENILSON AZEVEDO CASTRO', NULL, 43, 'não', 4),
(673, 'RENILSON BARBOSA UCHOA', NULL, 43, 'não', 4),
(674, 'RENILTON DE JESUS UMBURANAS', NULL, 85, 'sim', 4),
(675, 'RICARDO DOS SANTOS BRITO', NULL, 8, 'não', 4),
(676, 'ROBERIO DA SILVA VIEIRA', NULL, 76, 'não', 4),
(677, 'ROBERIO DOS SANTOS SANTANA', NULL, 43, 'não', 4),
(678, 'ROBERTO DOS SANTOS FROES', NULL, 52, 'não', 4),
(679, 'ROBSON ARAGAO MAGALHAES', NULL, 55, 'não', 4),
(680, 'ROBSON MARQUEJANY SOUZA SANTOS', NULL, 66, 'não', 4),
(681, 'ROBSON PESSOA DA SILVA', NULL, 44, 'não', 4),
(682, 'ROBSON ROGERIO OLIVEIRA FROES', NULL, 61, 'não', 4),
(683, 'RODRIGO DOS ANJOS MARQUES', NULL, 43, 'não', 4),
(684, 'RODRIGO FELIX DE SOUZA', NULL, 44, 'não', 4),
(685, 'ROGERIO BARRETO SILVA', NULL, 72, 'não', 4),
(686, 'ROGERIO MARCIO DE QUEIROZ', NULL, 8, 'não', 4),
(687, 'ROGERIO SANTOS VIEIRA', NULL, 43, 'não', 4),
(688, 'ROMARIO ALVES DOS SANTOS', NULL, 56, 'não', 4),
(689, 'ROMARIO GURUNGA SANTANA', NULL, 71, 'não', 4),
(690, 'ROMARIO LAGO DOS SANTOS', NULL, 8, 'não', 4),
(691, 'ROMARIO SOUZA SILVA', NULL, 43, 'não', 4),
(692, 'ROMERITO DA SILVA DE OLIVEIRA', NULL, 71, 'não', 4),
(693, 'ROMILDO SANTOS E SILVA', NULL, 43, 'não', 4),
(694, 'RONALDO BARBOSA DOS SANTOS', NULL, 71, 'não', 4),
(695, 'RONALDO MOREIRA COSTA', NULL, 68, 'não', 4),
(696, 'RONALDO SANTOS DO NASCIMENTO', NULL, 44, 'não', 4),
(697, 'RONISDETE SALES NOVAIS', NULL, 85, 'sim', 4),
(698, 'RONIVON MORAES RAMOS', NULL, 45, 'não', 4),
(699, 'ROQUE LAGO DA SILVA', NULL, 52, 'não', 4),
(700, 'ROQUE SOUZA COSTA FILHO', NULL, 45, 'não', 4),
(701, 'ROSINEIA PEREIRA VIEIRA', NULL, 43, 'não', 4),
(702, 'ROVEL DE JESUS LEITE JUNIOR', NULL, 43, 'não', 4),
(703, 'SAIDISON DIMITRE MARQUES LIMA', NULL, 46, 'não', 4),
(704, 'SAMUEL DA SILVA SOUZA', NULL, 43, 'não', 4),
(705, 'SANDRO DA SILVA DE CARVALHO', NULL, 46, 'não', 4),
(706, 'SANDRO FERREIRA DAS DORES', NULL, 43, 'não', 4),
(707, 'SANDRO RIBEIRO CIRQUEIRA', NULL, 45, 'não', 4),
(708, 'SERGIO REIS DA SILVA', NULL, 46, 'não', 4),
(709, 'SERGIO SOUZA DE OLIVEIRA', NULL, 43, 'não', 4),
(710, 'SIDINEI SILVA DOS SANTOS', NULL, 43, 'não', 4),
(711, 'SILAS SANTOS BATISTA', NULL, 46, 'não', 4),
(712, 'SILVANA LIMA DE SOUZA', NULL, 46, 'não', 4),
(713, 'SILVANE DOS REIS DE CARVALHO', NULL, 70, 'não', 4),
(714, 'SILVANE DOS SANTOS LAGO', NULL, 43, 'não', 4),
(715, 'SILVIO DOS SANTOS SILVA', NULL, 46, 'não', 4),
(716, 'SIMAO PEDRO CALIXTO DOS SANTOS', NULL, 55, 'não', 4),
(717, 'SIVALDO RODRIGUES DOS SANTOS', NULL, 43, 'não', 4),
(718, 'SIVANILDO MATOS SANTOS', NULL, 55, 'não', 4),
(719, 'SONIVALDO SANTOS DE ALMEIDA', NULL, 43, 'não', 4),
(720, 'SULIELCIO MONTEIRO BATISTA', NULL, 90, 'não', 4),
(721, 'TANIEL DE SANTANA', NULL, 43, 'não', 4),
(722, 'TARCIZIO NOVAES DOS SANTOS', NULL, 81, 'sim', 4),
(723, 'THACIO DO NASCIMENTO NOVAES', NULL, 52, 'não', 4),
(724, 'THALES ALEJANDRO BIARIZ MOIZES', NULL, 60, 'não', 4),
(725, 'THIAGO OLIVEIRA GOMES', NULL, 71, 'não', 4),
(726, 'THIAGO SOUZA GONCALVES', NULL, 76, 'não', 4),
(727, 'THYRCIANA TEIXEIRA SANTOS', NULL, 56, 'não', 4),
(728, 'TIAGO BORTOLOTTI LEAL', NULL, 72, 'não', 4),
(729, 'TIAGO NOVAES DOS SANTOS', NULL, 45, 'não', 4),
(730, 'TIAGO OLIVEIRA E SILVA SOUZA', NULL, 8, 'não', 4),
(731, 'TIAGO RAIMUNDO SANTOS DA SILVA', NULL, 43, 'não', 4),
(732, 'UANDERSON SANTOS', NULL, 25, 'não', 4),
(733, 'UELTON ARAGAO DOS SANTOS', NULL, 44, 'não', 4),
(734, 'UELTON DA SILVA SANTOS', NULL, 44, 'não', 4),
(735, 'UILLIAN BARBOSA SOUZA', NULL, 45, 'não', 4),
(736, 'UILSON SANTOS SILVEIRA', NULL, 43, 'não', 4),
(737, 'UOSTON SOUZA DOS SANTOS', NULL, 43, 'não', 4),
(738, 'VAGNER MOISES DE SOUZA', NULL, 69, 'sim', 4),
(739, 'VAGNER SOUZA ARAUJO', NULL, 43, 'não', 4),
(740, 'VALDELICIO PIRES DA SILVA', NULL, 73, 'não', 4),
(741, 'VALDEMIR ALVES DOS SANTOS', NULL, 51, 'não', 4),
(742, 'VALDINEI MATOS DOS SANTOS', NULL, 55, 'não', 4),
(743, 'VALDIR SOUZA SILVA', NULL, 43, 'não', 4),
(744, 'VALNEY DA SILVA GURUNGA', NULL, 75, 'não', 4),
(745, 'VALTER SERGIO SOUZA SANTOS', NULL, 46, 'não', 4),
(746, 'VANDERLANDIO BARBOSA DOS SANTOS ARAUJO', NULL, 43, 'não', 4),
(747, 'VANDILEI DOS SANTOS FROIS', NULL, 71, 'não', 4),
(748, 'VANDINEI DO LAGO OLIVEIRA', NULL, 46, 'não', 4),
(749, 'VICTOR FIDEL DOS SANTOS CARVALHO', NULL, 80, 'não', 4),
(750, 'VIVIANE DE JESUS SANTOS', NULL, 56, 'não', 4),
(751, 'VOLMIR CORDEIRO DOS SANTOS', NULL, 48, 'não', 4),
(752, 'WALLISON SILVA SOUZA', NULL, 46, 'não', 4),
(753, 'WASHINGTON LUIZ DOS SANTOS', NULL, 44, 'não', 4),
(754, 'WASHINGTON SANTANA DOS SANTOS', NULL, 68, 'não', 4),
(755, 'WASLLIAN CARLOS SANTOS ARAUJO', NULL, 44, 'não', 4),
(756, 'WELISON RODRIGUES DE OLIVEIRA', NULL, 52, 'não', 4),
(757, 'WELITON DOS SANTOS ARAUJO', NULL, 71, 'não', 4),
(758, 'WELLIAM WALTER ROCHA', NULL, 46, 'não', 4),
(759, 'WELLINGTON DOS ANJOS MARQUES', NULL, 43, 'não', 4),
(760, 'WELYDA DA SILVA VIANA', NULL, 50, 'sim', 4),
(761, 'WHELITON DOS SANTOS DA SILVA', NULL, 46, 'não', 4),
(762, 'WILHAN COSTA LAPA', NULL, 61, 'não', 4),
(763, 'WILKER SANTOS NOVAES', NULL, 43, 'não', 4),
(764, 'WILLIAM SENA DA SILVA', NULL, 72, 'não', 4),
(765, 'WILLIAN MORAIS DOS SANTOS', NULL, 72, 'não', 4),
(766, 'ZENILTON OLIVEIRA DOS SANTOS', NULL, 8, 'não', 4),
(767, 'Fabio Ozuna Lima', 'fozuna@gmail.com', 91, 'sim', 7),
(768, 'ALEX AFONSO DONA', 'alex@donainstituto.com.br', 92, 'sim', 6),
(769, 'JOÃO PEDRO DA SILVA CATRINCK', 'joaopedro@donainstituto.com.br', 92, 'sim', 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultores`
--

CREATE TABLE `consultores` (
  `id` int NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `especialidade` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senioridade` enum('Junior','Pleno','Senior') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `usuario_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cronogramas`
--

CREATE TABLE `cronogramas` (
  `id` int NOT NULL,
  `id_cliente` int NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `ano` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `cronogramas`
--

INSERT INTO `cronogramas` (`id`, `id_cliente`, `nome`, `ano`) VALUES
(1, 7, '', 2026);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cronograma_eventos`
--

CREATE TABLE `cronograma_eventos` (
  `id` int NOT NULL,
  `id_cronograma` int NOT NULL,
  `data` date NOT NULL,
  `topico` varchar(120) NOT NULL,
  `unidade` varchar(120) DEFAULT NULL,
  `atividade` varchar(255) NOT NULL,
  `responsavel` varchar(255) DEFAULT NULL,
  `modelo` enum('Online','Presencial') DEFAULT NULL,
  `status` enum('Planejado','Realizado','Não Realizado') NOT NULL DEFAULT 'Planejado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `cliente_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `departamentos`
--

INSERT INTO `departamentos` (`id`, `nome`, `cliente_id`) VALUES
(1, 'ADMINISTRATIVO', 1),
(2, 'OPERACIONAL', 1),
(6, 'Produção de Campo - Aparecidinha', 6),
(5, 'Produção de campo - Flor', 6),
(4, 'Produção de Campo - Guanandi', 6),
(3, 'DESENVOLVIMENTO', 7);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcoes`
--

CREATE TABLE `funcoes` (
  `id` int NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `setor_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcoes`
--

INSERT INTO `funcoes` (`id`, `nome`, `setor_id`) VALUES
(1, 'ANALISTA DE PESSOAL', 1),
(17, 'SUPERVISOR DEPARTAMENTO PESSOAL', 1),
(67, 'ASSISTENTE ADMINISTRATIVO', 2),
(56, 'AUXILIAR ADMINISTRATIVO', 2),
(23, 'AUXILIAR DE COPA/COZINHA', 2),
(2, 'AUXILIAR DE ESCRITORIO', 2),
(4, 'AUXILIAR DE ESCRITORIO/JOVEM APRENDIZ', 2),
(10, 'COZINHEIRO (A)', 2),
(14, 'GERENTE ADMINISTRATIVO', 2),
(30, 'GERENTE COMERCIAL', 2),
(36, 'GERENTE DE CONTRATO', 2),
(74, 'PEDREIRO', 2),
(50, 'SUPERVISOR ADMINISTRATIVO', 2),
(3, 'ANALISTA DE PCM', 3),
(40, 'AUXILIAR DE  CONTROLE TECNICO MANUTENCAO', 3),
(86, 'AUXILIAR DE ELETRICISTA', 3),
(52, 'AUXILIAR DE LUBRIFICACAO', 3),
(26, 'AUXILIAR DE MANUTENCAO', 3),
(37, 'AUXILIAR DE MANUTENCAO MECANICA', 3),
(76, 'AUXILIAR DE MECANICO', 3),
(70, 'BORRACHEIRO', 3),
(58, 'COORDENADOR DE MANUTENÇÃO', 3),
(48, 'ELETRICISTA', 3),
(89, 'ELETRICISTA PREDIAL', 3),
(84, 'FUNILEIRO DE VEICULOS', 3),
(9, 'GERENTE DE MANUTENÇÃO', 3),
(77, 'INSPETOR', 3),
(75, 'LAVADOR DE VEICULOS', 3),
(85, 'LIDER DE MANUTENCAO', 3),
(51, 'LUBRIFICADOR', 3),
(72, 'MECANICO DE EQUIPAMENTO PESADO', 3),
(90, 'MECANICO DE PERFURATRIZ', 3),
(25, 'MECANICO I', 3),
(55, 'MOTORISTA CAMINHAO COMBOIO', 3),
(62, 'PLANEJADOR', 3),
(68, 'SOLDADOR', 3),
(27, 'SUPERVISOR DE MANUTENCAO', 3),
(5, 'ANALISTA FISCAL CONTABIL', 4),
(18, 'ASSISTENTE CONTABIL', 4),
(20, 'ANALISTA DE CONTAS A PAGAR', 5),
(6, 'ASSISTENTE FINANCEIRO', 5),
(11, 'COORDENADOR FINANCEIRO', 5),
(33, 'AJUDANTE DE PERFURACAO', 6),
(35, 'AJUDANTE TECNICO DE MINERACAO', 6),
(78, 'ANALISTA DE PCP', 6),
(39, 'ANALISTA DE PRODUCAO', 6),
(66, 'APONTADOR DE PRODUCAO', 6),
(71, 'AUXILIAR DE SERVICOS GERAIS', 6),
(49, 'AUXILIAR DE SERVICOS GERAIS (MINA)', 6),
(79, 'AUXILIAR DE TOPOGRAFO', 6),
(31, 'CONTROLADORIA', 6),
(53, 'COORDENADOR DE OPERAÇÃO DE MINA', 6),
(42, 'GERENTE DE CONTRATO', 6),
(28, 'GERENTE GERAL DE OPERAÇÕES', 6),
(64, 'INSTRUTOR', 6),
(81, 'LIDER OPERACIONAL', 6),
(7, 'MESTRE DE OBRA', 6),
(24, 'MOTORISTA', 6),
(43, 'MOTORISTA CAMINHAO FORA DE ESTRADA', 6),
(57, 'MOTORISTA CAMINHAO PIPA', 6),
(88, 'MOTORISTA DE AUTOMOVEIS', 6),
(73, 'MOTORISTA DE CAMINHÃO HIDROVACUO', 6),
(44, 'OPERADOR DE ESCAVADEIRA', 6),
(61, 'OPERADOR DE GUINDASTE / MUNCK', 6),
(59, 'OPERADOR DE MOTONIVELADORA', 6),
(46, 'OPERADOR DE PA CARREGADEIRA', 6),
(8, 'OPERADOR DE PERFURATRIZ', 6),
(41, 'OPERADOR DE RETROESCAVADEIRA', 6),
(45, 'OPERADOR DE TRATOR ESTEIRA', 6),
(60, 'PLANEJADOR', 6),
(54, 'SUPERVISOR DE MINA (OPER)', 6),
(65, 'SUPERVISOR DE MINA (TRANSP)', 6),
(82, 'SUPERVISOR DE PCP', 6),
(22, 'SUPERVISOR DE PERFURACAO', 6),
(34, 'SUPERVISOR DE PERFURACAO E DESMONTE', 6),
(83, 'TECNICO DE ENFERMAGEM DO TRABALHO', 6),
(80, 'TECNICO DE PERFURACAO', 6),
(87, 'TOPOGRAFO', 6),
(12, 'ASSESSOR DE DIRETORIA', 7),
(13, 'ANALISTA DE SUPORTE COMPUTACIONAL', 8),
(15, 'ANALISTA DE SUPRIMENTOS', 9),
(32, 'GERENTE DE COMPRAS', 9),
(21, 'SUPERVISOR DE SUPRIMENTOS', 9),
(16, ' ALMOXARIFE', 10),
(47, 'ALMOXARIFE', 10),
(69, 'ENCARREGADO DE ESTOQUE', 10),
(19, 'COORDENADOR DE QUALIDADE/RH', 11),
(29, 'DIRETOR', 12),
(63, 'ENGENHEIRO DE SEGURANÇA DO TRABALHO', 13),
(38, 'TEC EM SEGURANCA NO TRABALHO', 13),
(91, 'PROGRAMADOR', 14),
(92, 'Consultor', 16);

-- --------------------------------------------------------

--
-- Estrutura para tabela `indicadores`
--

CREATE TABLE `indicadores` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `nome` varchar(180) NOT NULL,
  `unidade` varchar(32) DEFAULT NULL,
  `referencia` date DEFAULT NULL,
  `meta` decimal(14,2) NOT NULL DEFAULT '0.00',
  `realizado` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `indicadores`
--

INSERT INTO `indicadores` (`id`, `cliente_id`, `nome`, `unidade`, `referencia`, `meta`, `realizado`, `created_at`) VALUES
(1, 7, 'Entregas', 'R$', '2026-03-01', 100000.00, 131000.00, '2026-02-26 23:20:13'),
(2, 7, 'teste', 'R$', '2026-04-01', 50.00, 0.00, '2026-03-27 11:38:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metodologias`
--

CREATE TABLE `metodologias` (
  `id` int NOT NULL,
  `id_pilar` int NOT NULL,
  `item_pilar` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tarefa',
  `arquivo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `cliente_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `metodologias`
--

INSERT INTO `metodologias` (`id`, `id_pilar`, `item_pilar`, `tipo`, `arquivo_path`, `observacoes`, `cliente_id`) VALUES
(1, 2, 'Reunião Mensal', 'tarefa', NULL, 'Teste', 1),
(2, 2, 'Mapeamento do manual de Mobilização e Desmobilização Estrutural', 'tarefa', NULL, '', 1),
(3, 1, 'Mapeamento do manual de Mobilização e Desmobilização Estrutural', 'tarefa', NULL, '', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdca_actions`
--

CREATE TABLE `pdca_actions` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `owner` varchar(120) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Planejado','Em Execução','Concluído') NOT NULL DEFAULT 'Planejado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdca_checks`
--

CREATE TABLE `pdca_checks` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `gap` decimal(12,2) DEFAULT NULL,
  `analise` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdca_metrics`
--

CREATE TABLE `pdca_metrics` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `nome` varchar(120) NOT NULL,
  `planejado` decimal(12,2) DEFAULT NULL,
  `realizado` decimal(12,2) DEFAULT NULL,
  `unidade` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdca_tasks`
--

CREATE TABLE `pdca_tasks` (
  `id` int NOT NULL,
  `id_cliente` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text,
  `meta_valor` text,
  `meta_unidade` varchar(255) DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `responsavel` varchar(120) DEFAULT NULL,
  `fase` enum('PLAN','DO','CHECK','ACT') NOT NULL DEFAULT 'PLAN',
  `status` enum('Planejado','Em Andamento','Concluído','Pendente') NOT NULL DEFAULT 'Planejado',
  `progresso` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `pdca_tasks`
--

INSERT INTO `pdca_tasks` (`id`, `id_cliente`, `titulo`, `descricao`, `meta_valor`, `meta_unidade`, `prazo`, `responsavel`, `fase`, `status`, `progresso`, `created_at`) VALUES
(2, 7, 'Criar módulo do Pilar de Pessoas', 'Criar o módulo para incluir as informações de gestão de pessoas', '1.00', 'un', '2026-03-05', 'Ozuna', 'DO', 'Concluído', 100, '2026-02-24 21:10:39'),
(3, 6, 'Valor considerado para depreciação no orçamento gerou um alto custo da operação.', 'Avaliação inicial definido que seria em 36 meses, porém houve um impacto negativo no orçamento gerencial', 'Estudar qual modelo de depreciação será utilizada para o orçamento gerencial', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 0, '2026-02-24 21:59:13'),
(4, 6, 'Melhorar interpretação dos relatórios', 'Falta de alinhamento das informações que serão apresentadas', 'Gustavo solicitou que a controladoria sente com todos os responsáveis das áreas para alinhamento e tirar dúvidas dos números que serão apresentados.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 100, '2026-02-25 13:59:52'),
(5, 6, 'Custo com serviço de destoca lançamento em conta incorreta', 'Conceito de lançamento incorreto', 'Incluir uma linha para serviços de produção de terceiros para identificar os custos com esses prestadores', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 0, '2026-02-25 14:00:27'),
(6, 6, 'Falta de refenrencia dos valores realizados no mês', 'Orçamento traz apenas o realizado, e não tem o orçado', 'Incluir as colunas dos valores orçados, ajustado e realizado.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Em Andamento', 0, '2026-02-25 14:01:09'),
(7, 6, 'Classes de faturamento mescladas no mesmo item', 'Faturamento apresentado de modo geral', 'Detalhar quais classes de faturamento estão dentro da receita bruta.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Em Andamento', 0, '2026-02-25 14:01:51'),
(8, 6, 'Muitas variantes em relação ao custo de madeira', 'Falta de diretriz para lançamento deste custo', 'Reavaliar como será lançado o custo com madeira para adequar o custo que esta lançado no orçamento de janeiro.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 0, '2026-02-25 14:02:27'),
(9, 6, 'DRE Criada por frente, dificultando as análises dos projetos', 'Melhorar a divisão dos projetos para facilitar as análises', 'Criar DRE por projetos para identificação dos pontos que são específicos de cada tipo de operação.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Em Andamento', 0, '2026-02-25 14:09:06'),
(10, 6, 'Falta de avaliação do fluxo de caixa para avaliação', 'Relatórios foram construídos com base no orçamento gerencial, também vamos precisar de um relatório para avaliação do fluxo de caixa', 'Criar uma outra análise baseado no fluxo de caixa para identificar qual o resultado liquido necessário que a operação deve gerar para a saúde financeira do negócio.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Em Andamento', 0, '2026-02-25 14:09:56'),
(11, 6, 'Custo da logística dentro do DRE sendo lançado como frete e não como custo', 'Ajustar para custo e não como receita de transporte', 'Ajustar a apresentação dos números de receita de logística para custo e não receita. Utilizar mês anterior ao mês de fechamento.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 0, '2026-02-25 14:10:46'),
(12, 6, 'RMS não trás encargos para avaliação gerencial em relação ao mês anterior', 'Falta de pedido de ajuste do sistema', 'Estudar como o RMS pode gerar relatório financeiro do mês anterior, pois hoje precisa fazer um fechamento manual.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO / GUSTAVO', 'DO', 'Pendente', 0, '2026-02-25 14:15:00'),
(13, 6, 'Melhorar visualização dos relatórios das áreas', 'Relatório muito misturados com modelos de medição diferentes', 'Fazer os painéis por pilar Produção, Logística e Administrativo e criar um painel consolidado para avaliação geral.', 'Reunião Mensal Fevereiro', '2026-03-10', 'RODRIGO', 'DO', 'Concluído', 0, '2026-02-25 14:15:42'),
(14, 6, 'Receita da logística superdimensionada devido a lançamento incorreto no sistema', 'Lançamento sendo realizado do valor total da tonelada e não fracionado', 'Ajustar no RMS a receita da tonelada produzida, considerando somente a fatia de receita referente ao transporte e não da receita total por tonelada.', 'Bruna', '2026-03-10', 'BRUNA', 'DO', 'Concluído', 0, '2026-02-25 14:16:36'),
(15, 6, 'Falta de controle da gestão de folga, por estarmos utilizando planilhas para este controle', 'Controle de folgas sendo realizado de forma manual', 'Criar processo de gestão de folga utilizando RMS para uma gestão eficiente de gestão de folga', 'Reunião Mensal Fevereiro', '2026-06-01', 'ALEX CRISTALDO / Supervisores', 'DO', 'Planejado', 0, '2026-02-25 14:17:33'),
(16, 6, 'Custo com corretiva lançadas junto com as manutenções preventivas', 'Não esta sendo lançado de forma separada as classes de quebras', 'Separar do custo de manutenção as falhas que são operacionais para essas serem suportadas pela área de produção', 'Reunião Mensal Fevereiro', '2026-03-10', 'FABIO KUKIEL', 'DO', 'Planejado', 0, '2026-02-25 14:18:22'),
(17, 6, 'Alto volume de horas extras pontuais nos departamentos', 'Melhorar avaliação das horas extras', 'Realizar uma avaliação detalhada dos custos com horas extras dentro das áreas', 'Reunião Mensal Fevereiro', '2026-03-10', 'GUSTAVO / MARLI / RODRIGO / FABIO KUKIEL', 'DO', 'Em Andamento', 0, '2026-02-25 14:20:05'),
(18, 6, 'Renan trouxe a demanda de avaliação de ponto ideal de troca de equipamentos, baseado em DF, custo de manutenção e investimento para um novo equipamento.', 'Melhorar relatório de ponto de troca de equipamento', 'Realizar uma avaliação de ponto de ideal de troca de equipamentos', 'Reunião Mensal Fevereiro', '2026-03-10', 'FABIO KUKIEL / ODAIR', 'DO', 'Em Andamento', 0, '2026-02-25 14:21:01'),
(19, 7, 'Plano de melhoria', 'melhorar dados', 'atuando', 'controladoria', '2026-01-28', 'Ozuna', 'DO', 'Planejado', 0, '2026-02-26 23:01:37'),
(20, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(21, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(22, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(23, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(24, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(25, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(26, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(27, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5´S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(28, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5´S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(29, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5´S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(30, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5´S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(31, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5´S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(32, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5´S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(33, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5´S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(34, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5´S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(35, 2, 'MANUAL MUDANÇA DE FUNÇÃO - FALTA DE EVIDENCIA DE FORMULARIO / FICHA DE EPI SEM EVIDENCIA DE ATUALIZAÇÃO ', 'DOCUMENTAÇÃO E RESPONSABILIDADES DIVIDIDAS ENTRE DP E SESMT', 'SEGUIR AS NORMAS DO MANUAL EM CASO DE REVISÃO COMUNICAR O SETOR DA QUALIDADE COM AS SOLICITAÇÕES DE MELHORIAS ', 'AUDITORIA DE PROCESSOS', '2024-11-29', 'LAIS/JONILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(36, 2, 'MANUAL AFASTAMENTO - ITENS DE 1.6 A 1.8 DA AUDITORIA SERÃO DIRECIONADOS PARA O MANUAL DO SESMT', 'O DP NÃO TEM UM CONTROLE EFETIVO DA DEMANDA AUDITADA', 'INICIAR A CONSTRUÇÃO DOS MANUAIS DO SESMT', 'AUDITORIA DE PROCESSOS', '2024-12-16', 'PATRICIA/ROSANE', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(37, 2, 'Senso de saúde e segurança - Água empoçada em frente ao container sanitario', 'Adequação as normas de saúde e segurança', 'Substituição do banheiro', 'AUDITORIA 2024', '2025-01-13', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(38, 2, 'DIFICULDADE NA IDENTIFICAÇÃO DAS PEÇAS QUE ESTÃO DISPONIVEIS NO ESTOQUE', 'FALTA DE SISTEMA PARA IDENTIFICAÇÃO CORRETA NO SISTEMA', 'FINALIZAR ORGANIZAÇÃO DO ESTOQUE PARA REALIZAR O CONTROLE DE ITENS DISPONIVEIS NO NOVO ESTOQUE', 'ALINHAMENTO MANUTENÇÃO', '2026-03-20', 'THIAGO SILVA / PEDRO', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:01:16'),
(39, 2, 'FALTA DE PROGRAMADOR DE OFICINA PARA AJUSTAR O PROCESSO DE COMPRAS', 'PROCESSO SENDO CUMPRIDO DE FORMA FRACIONADA, ONDE CADA UM FAZ UMA PARTE DO PROCESSO, GERANDO MUITOS ERROS', 'REALIZAR A CONTRATAÇÃO DE UM PROGRAMADOR DE SERVIÇOS COM UM PERFIL IDEAL PARA A VAGA', 'ALINHAMENTO MANUTENÇÃO', '2025-03-10', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(40, 2, 'Utilização abaixo do resultado, atingindo 67%, e baixa confiabilidade nos resultados apresentados', 'Falta de avaliação real dos motivos da baixa utilização', 'Reestruturar a função de controller do Vinicius, colocando o mesmo dentro do processo de gestão para traçar ações efetivas no atingimento da utilização e se necessário, envolver o cliente com dados mais concretos. Reunir toda quinta feira.', 'PROJETO CORUMBÁ 2025', '2025-04-15', 'Admilton / Diogo / Rayson', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(41, 2, 'Falta de um processo de governança e gestão e dia-a-dia;', 'Não organização de modelo de reunião', 'Implantar rotina de reunião estabelecida para o dia 14 de cada mês realizando a reunião da qualidade de maneira eficiente', 'PROJETO CORUMBÁ 2025', '2025-03-14', 'Admilton / Qualidade', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(42, 2, 'FALTA DE CONTROLE DE HORAS EXTRAS', 'MELHORAR PROCESSO DE ACOMPANHAMENTO DE HORAS EXTRAS', 'IMPLEMENTAR CONTROLE DE HORAS EXTRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(43, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(44, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(45, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(46, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(47, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(48, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(49, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(50, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(51, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(52, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(53, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(54, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(55, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(56, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(57, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(58, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(59, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(60, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(61, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(62, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(63, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(64, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(65, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(66, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(67, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(68, 2, 'RESOLVER PROBLEMA DO ARMARIO QUE ESTA COM UMA PORTA SÓ', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'FOI RETIRADO O ARMARIO DO LOCAL, O MATERIAL DO MESMO FOI REALOCADO.', 'AUDITORIA DE 5S', '2023-01-12', 'JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(69, 2, 'CONTAINER 1 - RETIRAR AS PEÇAS QUE ESTÃO NO LOCAL E LEVAR PARA O ALMOXARIFADO.', 'VAMOS LIBERAR O CONTAINER PARA O RH', 'RETIAR AS PEÇAS DESSE LOCAL', 'AUDITORIA DE 5S', '2023-01-28', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(70, 2, 'LIMPEZA E ORGANIZAÇÃO DO ANEXO I E II', 'DEVIDO A MUITA CHUVA NA REGIÃO CRESCEU MATO NESSES LOCAIS', 'SERÁ FEITO LIMPEZA DESSAS AREAS E READEQUAÇÃO DO LOCAL.', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(71, 2, 'ORGANIZAÇÃO DAS PEÇAS QUE ESTÃO NA PAREDE DO ALMOXARIFADO', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'SERÃO ALOCADAS EM OUTRO LUGAR APROPRIADO', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(72, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(73, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(74, 2, 'PINTURA DA AREA DE ENTRADA E SAIDA DE MERCADORIA DENTRO DO ALMOXARIFADO.', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'TEMOS A AREA JÁ, FALTANDO APENAS A INDENTIFICAÇÃO DA MESMA', 'AUDITORIA DE 5S', '2023-03-02', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(75, 2, 'FALTA DE HABILIDADE TÉCNICA DOS MOTORISTAS ', 'FALTA DE MÉTODO PARA TREINAMENTO DE MOTORISTAS', 'REDESENHAR PROGRAMA DE INTEGRAÇÃO MAIS FORTE SOBRE A PARTE TÉCNICA DOS EQUIPAMENTOS DE NO MINIMO 3 DIAS COM PROVA DE VALIDADAÇÃO NO FINAL.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(76, 2, 'DIFICULDADE D ECONTRATAÇÃO DE MOTORISTAS', 'BAIXA DISPONIBILIDADE DE MOTORISTAS NA REGIÃO', 'CRIAÇÃO DE UM MODELO DE ESCOLA DE MOTORISTAS PARA TREINAMENTO E CAPACITAÇÃO DE FUTUROS OPERADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(77, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'IMPLANTAR MODELO DE PREMIO PARA EQUIPE, MELHORANDO ASSIM NOSSA COMPETITIVIDADE EM RELAÇÃO A SALARIO.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(78, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO DE SAUDE PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(79, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO ODONTOLÓGICO PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(80, 2, 'FALTA DE PROCESSO DE FEEDBACK', 'COMEÇAR O TRABALHO  DE DESENVOLVIMENTO HUMANO COM 100% DA EQUIPE', 'AMPLIAR O FEEDBACK PARA TODAS AS EQUIPES DA DINEX, CRIANDO UMA PRIMEIRA RODADA COM 100% DOS COLABORADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(81, 2, 'FALTA DE PESQUISA DE CLIMA', 'FALTA DE AVALIAÇÃO DO CLIMA ORGANIZACIONAL DA EQUIPE', 'REALIZAR A PESQUISA DE CLIMA ORGANIZACIONAL PARA IDENTIFICAÇÃO DE PONTOS QUE PODEM ESTAR PREJUDICANDO A PERMANENCIA DE FUNCIONÁRIOS NA UNIDADE', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(82, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '4 – Treinamento de reciclagem de condução (02 e 16 Abril / 30 de abril e 07 maio);', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(83, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '5 – Turma de inicio 13/03 será orientada pelo Josiel;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(84, 2, 'LIMPEZA DOS BANHEIROS ', 'FALTA DE LIMPEZA ADEQUADA DOS BANHEIROS ', 'COBRAR ZELADORES DA LIMPEZA ADEQUADA DOS BANHEIROS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(85, 2, 'QUADRO DE 5S COM DEFEITO', 'QUADRO DE 5S QUEBRADO', 'REALIZAR A CORREÇÃO DOS QUADROS DE 5S', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(86, 2, 'FALTA DE MANUTENÇÃO PREDIAL', 'CAIXAS ABERTAS, CANOS NAS CALÇADAS E PAREDES E PISO SUJOS.', 'REALIZAR A MANUTENÇÃO PREDIAL DE FORMA CORRETA', 'AUDITORIA DE 5S- JUNHO', '2023-07-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(87, 2, 'ITENS DA FERRAMENTARIA DE MONJOLINHO E DESORGANIZADA', 'MELHOR IDENTIFICAÇÃO DAS FERRAMENTAS', 'REORGANIZAR FERRAMENTAS DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(88, 2, 'AREA DE CAFÉ E ÁGUA DA MONJOLINHO SUJA E DESORGANIZADO', 'MELHORAR CONDIÇÃO DE TRABALHO DA EQUIPE', 'ORGANIZAR UMA ÁREA ADEQUADA PARA O CAFÉ DA EQUIPE', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'FABIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(89, 2, 'ITENS DE FERRAMENTA FORA DO PADRÃO DE COR ADEQUADO NAS OFICINA DE MONJOLINHO E LAIS', 'ITENS NÃO FORAM PINTADOS', 'REALIAZAR A PINTURA DOS MESMOS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(90, 2, 'NECESSIDADE DE PINTURA E MANUTENÇÃO DE ALGUMAS PAREDES DA OFICINA E BANHEIRO DA OFICINA', 'ITENS COM DESGASTE ', 'REALIZAR A PINTURA E MANUTENÇÃO NECESSÁRIAS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(91, 2, 'FALTA DE PINTURA DO PISO DO BANHEIRO DO PATIO 2 DA MONJOLINHO', 'MELHORAR ASPECTO FISICO DA ÁREA', 'REALIZAR PINTURA DA ÁREA', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:04:28'),
(92, 2, 'FALTA DE CONTROLE DE HORAS EXTRAS', 'MELHORAR PROCESSO DE ACOMPANHAMENTO DE HORAS EXTRAS', 'IMPLEMENTAR CONTROLE DE HORAS EXTRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(93, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(94, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(95, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(96, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(97, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(98, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(99, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(100, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(101, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(102, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(103, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(104, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(105, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(106, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(107, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(108, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(109, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(110, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(111, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(112, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(113, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(114, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(115, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(116, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(117, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(118, 2, 'RESOLVER PROBLEMA DO ARMARIO QUE ESTA COM UMA PORTA SÓ', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'FOI RETIRADO O ARMARIO DO LOCAL, O MATERIAL DO MESMO FOI REALOCADO.', 'AUDITORIA DE 5S', '2023-01-12', 'JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(119, 2, 'CONTAINER 1 - RETIRAR AS PEÇAS QUE ESTÃO NO LOCAL E LEVAR PARA O ALMOXARIFADO.', 'VAMOS LIBERAR O CONTAINER PARA O RH', 'RETIAR AS PEÇAS DESSE LOCAL', 'AUDITORIA DE 5S', '2023-01-28', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(120, 2, 'LIMPEZA E ORGANIZAÇÃO DO ANEXO I E II', 'DEVIDO A MUITA CHUVA NA REGIÃO CRESCEU MATO NESSES LOCAIS', 'SERÁ FEITO LIMPEZA DESSAS AREAS E READEQUAÇÃO DO LOCAL.', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(121, 2, 'ORGANIZAÇÃO DAS PEÇAS QUE ESTÃO NA PAREDE DO ALMOXARIFADO', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'SERÃO ALOCADAS EM OUTRO LUGAR APROPRIADO', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(122, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(123, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(124, 2, 'PINTURA DA AREA DE ENTRADA E SAIDA DE MERCADORIA DENTRO DO ALMOXARIFADO.', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'TEMOS A AREA JÁ, FALTANDO APENAS A INDENTIFICAÇÃO DA MESMA', 'AUDITORIA DE 5S', '2023-03-02', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(125, 2, 'FALTA DE HABILIDADE TÉCNICA DOS MOTORISTAS ', 'FALTA DE MÉTODO PARA TREINAMENTO DE MOTORISTAS', 'REDESENHAR PROGRAMA DE INTEGRAÇÃO MAIS FORTE SOBRE A PARTE TÉCNICA DOS EQUIPAMENTOS DE NO MINIMO 3 DIAS COM PROVA DE VALIDADAÇÃO NO FINAL.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(126, 2, 'DIFICULDADE D ECONTRATAÇÃO DE MOTORISTAS', 'BAIXA DISPONIBILIDADE DE MOTORISTAS NA REGIÃO', 'CRIAÇÃO DE UM MODELO DE ESCOLA DE MOTORISTAS PARA TREINAMENTO E CAPACITAÇÃO DE FUTUROS OPERADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(127, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'IMPLANTAR MODELO DE PREMIO PARA EQUIPE, MELHORANDO ASSIM NOSSA COMPETITIVIDADE EM RELAÇÃO A SALARIO.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(128, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO DE SAUDE PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(129, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO ODONTOLÓGICO PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(130, 2, 'FALTA DE PROCESSO DE FEEDBACK', 'COMEÇAR O TRABALHO  DE DESENVOLVIMENTO HUMANO COM 100% DA EQUIPE', 'AMPLIAR O FEEDBACK PARA TODAS AS EQUIPES DA DINEX, CRIANDO UMA PRIMEIRA RODADA COM 100% DOS COLABORADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(131, 2, 'FALTA DE PESQUISA DE CLIMA', 'FALTA DE AVALIAÇÃO DO CLIMA ORGANIZACIONAL DA EQUIPE', 'REALIZAR A PESQUISA DE CLIMA ORGANIZACIONAL PARA IDENTIFICAÇÃO DE PONTOS QUE PODEM ESTAR PREJUDICANDO A PERMANENCIA DE FUNCIONÁRIOS NA UNIDADE', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(132, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '4 – Treinamento de reciclagem de condução (02 e 16 Abril / 30 de abril e 07 maio);', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(133, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '5 – Turma de inicio 13/03 será orientada pelo Josiel;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(134, 2, 'LIMPEZA DOS BANHEIROS ', 'FALTA DE LIMPEZA ADEQUADA DOS BANHEIROS ', 'COBRAR ZELADORES DA LIMPEZA ADEQUADA DOS BANHEIROS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(135, 2, 'QUADRO DE 5S COM DEFEITO', 'QUADRO DE 5S QUEBRADO', 'REALIZAR A CORREÇÃO DOS QUADROS DE 5S', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(136, 2, 'FALTA DE MANUTENÇÃO PREDIAL', 'CAIXAS ABERTAS, CANOS NAS CALÇADAS E PAREDES E PISO SUJOS.', 'REALIZAR A MANUTENÇÃO PREDIAL DE FORMA CORRETA', 'AUDITORIA DE 5S- JUNHO', '2023-07-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(137, 2, 'ITENS DA FERRAMENTARIA DE MONJOLINHO E DESORGANIZADA', 'MELHOR IDENTIFICAÇÃO DAS FERRAMENTAS', 'REORGANIZAR FERRAMENTAS DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(138, 2, 'AREA DE CAFÉ E ÁGUA DA MONJOLINHO SUJA E DESORGANIZADO', 'MELHORAR CONDIÇÃO DE TRABALHO DA EQUIPE', 'ORGANIZAR UMA ÁREA ADEQUADA PARA O CAFÉ DA EQUIPE', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'FABIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(139, 2, 'ITENS DE FERRAMENTA FORA DO PADRÃO DE COR ADEQUADO NAS OFICINA DE MONJOLINHO E LAIS', 'ITENS NÃO FORAM PINTADOS', 'REALIAZAR A PINTURA DOS MESMOS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(140, 2, 'NECESSIDADE DE PINTURA E MANUTENÇÃO DE ALGUMAS PAREDES DA OFICINA E BANHEIRO DA OFICINA', 'ITENS COM DESGASTE ', 'REALIZAR A PINTURA E MANUTENÇÃO NECESSÁRIAS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(141, 2, 'FALTA DE PINTURA DO PISO DO BANHEIRO DO PATIO 2 DA MONJOLINHO', 'MELHORAR ASPECTO FISICO DA ÁREA', 'REALIZAR PINTURA DA ÁREA', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:06:45'),
(142, 2, 'FALTA DE CONTROLE DE HORAS EXTRAS', 'MELHORAR PROCESSO DE ACOMPANHAMENTO DE HORAS EXTRAS', 'IMPLEMENTAR CONTROLE DE HORAS EXTRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:10'),
(143, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(144, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(145, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(146, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(147, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(148, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(149, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(150, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(151, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(152, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(153, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(154, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(155, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(156, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(157, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(158, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(159, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(160, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(161, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(162, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(163, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(164, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(165, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(166, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(167, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(168, 2, 'RESOLVER PROBLEMA DO ARMARIO QUE ESTA COM UMA PORTA SÓ', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'FOI RETIRADO O ARMARIO DO LOCAL, O MATERIAL DO MESMO FOI REALOCADO.', 'AUDITORIA DE 5S', '2023-01-12', 'JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(169, 2, 'CONTAINER 1 - RETIRAR AS PEÇAS QUE ESTÃO NO LOCAL E LEVAR PARA O ALMOXARIFADO.', 'VAMOS LIBERAR O CONTAINER PARA O RH', 'RETIAR AS PEÇAS DESSE LOCAL', 'AUDITORIA DE 5S', '2023-01-28', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(170, 2, 'LIMPEZA E ORGANIZAÇÃO DO ANEXO I E II', 'DEVIDO A MUITA CHUVA NA REGIÃO CRESCEU MATO NESSES LOCAIS', 'SERÁ FEITO LIMPEZA DESSAS AREAS E READEQUAÇÃO DO LOCAL.', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(171, 2, 'ORGANIZAÇÃO DAS PEÇAS QUE ESTÃO NA PAREDE DO ALMOXARIFADO', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'SERÃO ALOCADAS EM OUTRO LUGAR APROPRIADO', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(172, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(173, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(174, 2, 'PINTURA DA AREA DE ENTRADA E SAIDA DE MERCADORIA DENTRO DO ALMOXARIFADO.', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'TEMOS A AREA JÁ, FALTANDO APENAS A INDENTIFICAÇÃO DA MESMA', 'AUDITORIA DE 5S', '2023-03-02', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(175, 2, 'FALTA DE HABILIDADE TÉCNICA DOS MOTORISTAS ', 'FALTA DE MÉTODO PARA TREINAMENTO DE MOTORISTAS', 'REDESENHAR PROGRAMA DE INTEGRAÇÃO MAIS FORTE SOBRE A PARTE TÉCNICA DOS EQUIPAMENTOS DE NO MINIMO 3 DIAS COM PROVA DE VALIDADAÇÃO NO FINAL.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(176, 2, 'DIFICULDADE D ECONTRATAÇÃO DE MOTORISTAS', 'BAIXA DISPONIBILIDADE DE MOTORISTAS NA REGIÃO', 'CRIAÇÃO DE UM MODELO DE ESCOLA DE MOTORISTAS PARA TREINAMENTO E CAPACITAÇÃO DE FUTUROS OPERADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(177, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'IMPLANTAR MODELO DE PREMIO PARA EQUIPE, MELHORANDO ASSIM NOSSA COMPETITIVIDADE EM RELAÇÃO A SALARIO.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(178, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO DE SAUDE PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(179, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO ODONTOLÓGICO PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(180, 2, 'FALTA DE PROCESSO DE FEEDBACK', 'COMEÇAR O TRABALHO  DE DESENVOLVIMENTO HUMANO COM 100% DA EQUIPE', 'AMPLIAR O FEEDBACK PARA TODAS AS EQUIPES DA DINEX, CRIANDO UMA PRIMEIRA RODADA COM 100% DOS COLABORADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:35'),
(181, 2, 'FALTA DE PESQUISA DE CLIMA', 'FALTA DE AVALIAÇÃO DO CLIMA ORGANIZACIONAL DA EQUIPE', 'REALIZAR A PESQUISA DE CLIMA ORGANIZACIONAL PARA IDENTIFICAÇÃO DE PONTOS QUE PODEM ESTAR PREJUDICANDO A PERMANENCIA DE FUNCIONÁRIOS NA UNIDADE', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(182, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '4 – Treinamento de reciclagem de condução (02 e 16 Abril / 30 de abril e 07 maio);', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(183, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '5 – Turma de inicio 13/03 será orientada pelo Josiel;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(184, 2, 'LIMPEZA DOS BANHEIROS ', 'FALTA DE LIMPEZA ADEQUADA DOS BANHEIROS ', 'COBRAR ZELADORES DA LIMPEZA ADEQUADA DOS BANHEIROS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(185, 2, 'QUADRO DE 5S COM DEFEITO', 'QUADRO DE 5S QUEBRADO', 'REALIZAR A CORREÇÃO DOS QUADROS DE 5S', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(186, 2, 'FALTA DE MANUTENÇÃO PREDIAL', 'CAIXAS ABERTAS, CANOS NAS CALÇADAS E PAREDES E PISO SUJOS.', 'REALIZAR A MANUTENÇÃO PREDIAL DE FORMA CORRETA', 'AUDITORIA DE 5S- JUNHO', '2023-07-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(187, 2, 'ITENS DA FERRAMENTARIA DE MONJOLINHO E DESORGANIZADA', 'MELHOR IDENTIFICAÇÃO DAS FERRAMENTAS', 'REORGANIZAR FERRAMENTAS DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(188, 2, 'AREA DE CAFÉ E ÁGUA DA MONJOLINHO SUJA E DESORGANIZADO', 'MELHORAR CONDIÇÃO DE TRABALHO DA EQUIPE', 'ORGANIZAR UMA ÁREA ADEQUADA PARA O CAFÉ DA EQUIPE', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'FABIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(189, 2, 'ITENS DE FERRAMENTA FORA DO PADRÃO DE COR ADEQUADO NAS OFICINA DE MONJOLINHO E LAIS', 'ITENS NÃO FORAM PINTADOS', 'REALIAZAR A PINTURA DOS MESMOS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(190, 2, 'NECESSIDADE DE PINTURA E MANUTENÇÃO DE ALGUMAS PAREDES DA OFICINA E BANHEIRO DA OFICINA', 'ITENS COM DESGASTE ', 'REALIZAR A PINTURA E MANUTENÇÃO NECESSÁRIAS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36');
INSERT INTO `pdca_tasks` (`id`, `id_cliente`, `titulo`, `descricao`, `meta_valor`, `meta_unidade`, `prazo`, `responsavel`, `fase`, `status`, `progresso`, `created_at`) VALUES
(191, 2, 'FALTA DE PINTURA DO PISO DO BANHEIRO DO PATIO 2 DA MONJOLINHO', 'MELHORAR ASPECTO FISICO DA ÁREA', 'REALIZAR PINTURA DA ÁREA', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(192, 2, 'BANHEIRO DA MONJOLINHO INADEQUADO', 'BANHEIRO NECESSITANDO DE REFORMA', 'REALIZAR REFORMA DO BANHEIRO DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-12-10', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:07:36'),
(193, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(194, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(195, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(196, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(197, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(198, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(199, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(200, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(201, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(202, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(203, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(204, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(205, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(206, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(207, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(208, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(209, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(210, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(211, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(212, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(213, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(214, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(215, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(216, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(217, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(218, 2, 'RESOLVER PROBLEMA DO ARMARIO QUE ESTA COM UMA PORTA SÓ', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'FOI RETIRADO O ARMARIO DO LOCAL, O MATERIAL DO MESMO FOI REALOCADO.', 'AUDITORIA DE 5S', '2023-01-12', 'JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(219, 2, 'CONTAINER 1 - RETIRAR AS PEÇAS QUE ESTÃO NO LOCAL E LEVAR PARA O ALMOXARIFADO.', 'VAMOS LIBERAR O CONTAINER PARA O RH', 'RETIAR AS PEÇAS DESSE LOCAL', 'AUDITORIA DE 5S', '2023-01-28', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(220, 2, 'LIMPEZA E ORGANIZAÇÃO DO ANEXO I E II', 'DEVIDO A MUITA CHUVA NA REGIÃO CRESCEU MATO NESSES LOCAIS', 'SERÁ FEITO LIMPEZA DESSAS AREAS E READEQUAÇÃO DO LOCAL.', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(221, 2, 'ORGANIZAÇÃO DAS PEÇAS QUE ESTÃO NA PAREDE DO ALMOXARIFADO', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'SERÃO ALOCADAS EM OUTRO LUGAR APROPRIADO', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(222, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(223, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(224, 2, 'PINTURA DA AREA DE ENTRADA E SAIDA DE MERCADORIA DENTRO DO ALMOXARIFADO.', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'TEMOS A AREA JÁ, FALTANDO APENAS A INDENTIFICAÇÃO DA MESMA', 'AUDITORIA DE 5S', '2023-03-02', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(225, 2, 'FALTA DE HABILIDADE TÉCNICA DOS MOTORISTAS ', 'FALTA DE MÉTODO PARA TREINAMENTO DE MOTORISTAS', 'REDESENHAR PROGRAMA DE INTEGRAÇÃO MAIS FORTE SOBRE A PARTE TÉCNICA DOS EQUIPAMENTOS DE NO MINIMO 3 DIAS COM PROVA DE VALIDADAÇÃO NO FINAL.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(226, 2, 'DIFICULDADE D ECONTRATAÇÃO DE MOTORISTAS', 'BAIXA DISPONIBILIDADE DE MOTORISTAS NA REGIÃO', 'CRIAÇÃO DE UM MODELO DE ESCOLA DE MOTORISTAS PARA TREINAMENTO E CAPACITAÇÃO DE FUTUROS OPERADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(227, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'IMPLANTAR MODELO DE PREMIO PARA EQUIPE, MELHORANDO ASSIM NOSSA COMPETITIVIDADE EM RELAÇÃO A SALARIO.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(228, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO DE SAUDE PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(229, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO ODONTOLÓGICO PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(230, 2, 'FALTA DE PROCESSO DE FEEDBACK', 'COMEÇAR O TRABALHO  DE DESENVOLVIMENTO HUMANO COM 100% DA EQUIPE', 'AMPLIAR O FEEDBACK PARA TODAS AS EQUIPES DA DINEX, CRIANDO UMA PRIMEIRA RODADA COM 100% DOS COLABORADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(231, 2, 'FALTA DE PESQUISA DE CLIMA', 'FALTA DE AVALIAÇÃO DO CLIMA ORGANIZACIONAL DA EQUIPE', 'REALIZAR A PESQUISA DE CLIMA ORGANIZACIONAL PARA IDENTIFICAÇÃO DE PONTOS QUE PODEM ESTAR PREJUDICANDO A PERMANENCIA DE FUNCIONÁRIOS NA UNIDADE', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(232, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '4 – Treinamento de reciclagem de condução (02 e 16 Abril / 30 de abril e 07 maio);', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(233, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '5 – Turma de inicio 13/03 será orientada pelo Josiel;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(234, 2, 'LIMPEZA DOS BANHEIROS ', 'FALTA DE LIMPEZA ADEQUADA DOS BANHEIROS ', 'COBRAR ZELADORES DA LIMPEZA ADEQUADA DOS BANHEIROS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(235, 2, 'QUADRO DE 5S COM DEFEITO', 'QUADRO DE 5S QUEBRADO', 'REALIZAR A CORREÇÃO DOS QUADROS DE 5S', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(236, 2, 'FALTA DE MANUTENÇÃO PREDIAL', 'CAIXAS ABERTAS, CANOS NAS CALÇADAS E PAREDES E PISO SUJOS.', 'REALIZAR A MANUTENÇÃO PREDIAL DE FORMA CORRETA', 'AUDITORIA DE 5S- JUNHO', '2023-07-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(237, 2, 'ITENS DA FERRAMENTARIA DE MONJOLINHO E DESORGANIZADA', 'MELHOR IDENTIFICAÇÃO DAS FERRAMENTAS', 'REORGANIZAR FERRAMENTAS DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(238, 2, 'AREA DE CAFÉ E ÁGUA DA MONJOLINHO SUJA E DESORGANIZADO', 'MELHORAR CONDIÇÃO DE TRABALHO DA EQUIPE', 'ORGANIZAR UMA ÁREA ADEQUADA PARA O CAFÉ DA EQUIPE', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'FABIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(239, 2, 'ITENS DE FERRAMENTA FORA DO PADRÃO DE COR ADEQUADO NAS OFICINA DE MONJOLINHO E LAIS', 'ITENS NÃO FORAM PINTADOS', 'REALIAZAR A PINTURA DOS MESMOS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(240, 2, 'NECESSIDADE DE PINTURA E MANUTENÇÃO DE ALGUMAS PAREDES DA OFICINA E BANHEIRO DA OFICINA', 'ITENS COM DESGASTE ', 'REALIZAR A PINTURA E MANUTENÇÃO NECESSÁRIAS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(241, 2, 'FALTA DE PINTURA DO PISO DO BANHEIRO DO PATIO 2 DA MONJOLINHO', 'MELHORAR ASPECTO FISICO DA ÁREA', 'REALIZAR PINTURA DA ÁREA', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(242, 2, 'BANHEIRO DA MONJOLINHO INADEQUADO', 'BANHEIRO NECESSITANDO DE REFORMA', 'REALIZAR REFORMA DO BANHEIRO DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-12-10', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:08:24'),
(243, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(244, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(245, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(246, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(247, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(248, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(249, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(250, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(251, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(252, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(253, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(254, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(255, 2, 'Piso da sala da supervisão  (Monj.)', 'Piso desgastado e encardido', 'Pintura do piso', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(256, 2, 'Pia e bebedouro   (Monj.)', 'Padronização / organização e limpeza', 'Pintura do tambor / organizaçao e limpeza da pia', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(257, 2, 'Produto de limpeza armazenado no banheiro', 'Padronização / organização e limpeza', 'Definir um local para armazenamento', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(258, 2, 'Cadeira quebrada', 'Não conformidade e perigo ', 'Descartar', 'AUDITORIA DE 5S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(259, 2, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, FÁBIO, BRENO E LUCIANO, PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(260, 2, 'DIFICULDADE DE LANÇAMENTO DAS NF NO CADASTRO E CRIAÇÃO DE RC.', 'RETIRADO A LAIS DO PROCESSO DE CRIAÇÃO DE RC E LANÇAMENTO DE NOTAS DA CONTABILIDADE.', 'DEFINIR COLABORADOR QUE IRA SUBSTITUIR AS ATIVIDADES DA LAIS EM RELAÇÃO A RC E LANÇAMENTO DE NF´S.', 'TREINAMENTO DE PROCESSOS DE COMPRAS', '2023-02-28', 'ADMILTON / LUCIANO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(261, 2, 'FALTA DE PADRÃO DE ENVIO DE FROTA ATUALIZADA PARA QUE O TIAGO POSSA FAZER OS RATEIOS DE FORMA CORRETA', 'FALTA DE PROCESSO PARA ATUALIZAÇÃO DE FROTA E EQUIPE', 'ATUALIZAÇÃO DE FROTA E EQUIPE PELA JULIANA PARA QUE OS RELATÓRIOS SEJAM GERADOS DE MANEIRA CORRETA E ASSIM TENHAMOS UM INDICADOR DE RESULTADO LÍQUIDO CORRETO POR UNIDADE.', 'REUNIÃO DE ALINHAMENTO', '2023-02-28', 'ADMILTON / JULIANA', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(262, 2, 'reforma geral oficina II', 'aumento demanda de monjolinho', 'reformadno antiga oficina que era da Julyquartzo', 'AUDITORIA DE 5S', '2023-03-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(263, 2, 'Baia de reziduos cheia Lais', 'falta de descarte de rezíduos', 'voltar rotina de descarte nos sabaddos', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(264, 2, 'sucata de pneus  Lais', 'falta de descarte sucata Lais', 'programar descarte da sucata pneus', 'AUDITORIA DE 5S', '2023-02-01', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(265, 2, 'Ferrramentas de uso comum sem pintura Monjolinho', 'Falta de pintura', 'Pintar de padrão vermelho suportes e ferramentas de uso comum  monjolinho', 'AUDITORIA DE 5S', '2023-02-20', 'Lauriney', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(266, 2, 'Tambores e lixeira de resíduos cheios Monjolinho', 'Falta de baia para resíduos', 'Concluir as baias', 'AUDITORIA DE 5S', '2023-02-20', 'Luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(267, 2, 'Desorganização área de borracharia Monjolinho', 'falta de espaço no compressor e borracharia e itens desnecessários', 'Liberação da Borracharia no anexo 2 e armários para guarda de ferramenta', 'AUDITORIA DE 5S', '2023-02-20', 'luciano', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(268, 2, 'RESOLVER PROBLEMA DO ARMARIO QUE ESTA COM UMA PORTA SÓ', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'FOI RETIRADO O ARMARIO DO LOCAL, O MATERIAL DO MESMO FOI REALOCADO.', 'AUDITORIA DE 5S', '2023-01-12', 'JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(269, 2, 'CONTAINER 1 - RETIRAR AS PEÇAS QUE ESTÃO NO LOCAL E LEVAR PARA O ALMOXARIFADO.', 'VAMOS LIBERAR O CONTAINER PARA O RH', 'RETIAR AS PEÇAS DESSE LOCAL', 'AUDITORIA DE 5S', '2023-01-28', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(270, 2, 'LIMPEZA E ORGANIZAÇÃO DO ANEXO I E II', 'DEVIDO A MUITA CHUVA NA REGIÃO CRESCEU MATO NESSES LOCAIS', 'SERÁ FEITO LIMPEZA DESSAS AREAS E READEQUAÇÃO DO LOCAL.', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(271, 2, 'ORGANIZAÇÃO DAS PEÇAS QUE ESTÃO NA PAREDE DO ALMOXARIFADO', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'SERÃO ALOCADAS EM OUTRO LUGAR APROPRIADO', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(272, 2, 'COLOCAR PLACAS DE IDENTIFICAÇÃO NO ALMOXARIFADOS', 'FALTA IDENTIFICAÇÃO', 'SERÃO COLOCADDAS AS PLACAS DE IDENTIFICAÇÃO NAS PRATILEIRAS PARA FACILITAR A LOCALIZAÇÃO .', 'AUDITORIA DE 5S', '2023-01-31', 'ITAMAR/JAILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(273, 2, 'FAZER O CONSERTO DOS SUPORTES DE CORREIA ', 'ESTÃO CEDENDO COM O PESO DAS CORREIAS', 'SERÃO FIXADOS DE OUTRA MANEIRA ', 'AUDITORIA DE 5S', '2023-01-31', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(274, 2, 'PINTURA DA AREA DE ENTRADA E SAIDA DE MERCADORIA DENTRO DO ALMOXARIFADO.', 'NÃO ESTA EM CONFORMIDADE COM O 5S', 'TEMOS A AREA JÁ, FALTANDO APENAS A INDENTIFICAÇÃO DA MESMA', 'AUDITORIA DE 5S', '2023-03-02', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(275, 2, 'FALTA DE HABILIDADE TÉCNICA DOS MOTORISTAS ', 'FALTA DE MÉTODO PARA TREINAMENTO DE MOTORISTAS', 'REDESENHAR PROGRAMA DE INTEGRAÇÃO MAIS FORTE SOBRE A PARTE TÉCNICA DOS EQUIPAMENTOS DE NO MINIMO 3 DIAS COM PROVA DE VALIDADAÇÃO NO FINAL.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(276, 2, 'DIFICULDADE D ECONTRATAÇÃO DE MOTORISTAS', 'BAIXA DISPONIBILIDADE DE MOTORISTAS NA REGIÃO', 'CRIAÇÃO DE UM MODELO DE ESCOLA DE MOTORISTAS PARA TREINAMENTO E CAPACITAÇÃO DE FUTUROS OPERADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(277, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'IMPLANTAR MODELO DE PREMIO PARA EQUIPE, MELHORANDO ASSIM NOSSA COMPETITIVIDADE EM RELAÇÃO A SALARIO.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(278, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO DE SAUDE PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(279, 2, 'MOTORISTAS PEDINDO DEMISSÃO PARA IR ATUAR EM OUTRA EMPRESA', 'BENEFICIOS MENORES QUE NOSSOS CONCORRENTES', 'ESTUDAR A POSSIBILIDAE DE MELHORIA DO PLANO ODONTOLÓGICO PARA A EQUIPE SENDO MAIS ATRATIVOS NA CAPTAÇÃO DE MÃO DE OBRA.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'ADMILTON / RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(280, 2, 'FALTA DE PROCESSO DE FEEDBACK', 'COMEÇAR O TRABALHO  DE DESENVOLVIMENTO HUMANO COM 100% DA EQUIPE', 'AMPLIAR O FEEDBACK PARA TODAS AS EQUIPES DA DINEX, CRIANDO UMA PRIMEIRA RODADA COM 100% DOS COLABORADORES.', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(281, 2, 'FALTA DE PESQUISA DE CLIMA', 'FALTA DE AVALIAÇÃO DO CLIMA ORGANIZACIONAL DA EQUIPE', 'REALIZAR A PESQUISA DE CLIMA ORGANIZACIONAL PARA IDENTIFICAÇÃO DE PONTOS QUE PODEM ESTAR PREJUDICANDO A PERMANENCIA DE FUNCIONÁRIOS NA UNIDADE', 'VISITA MENSAL - MARÇO', '2023-03-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(282, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '3 - Definir participantes do curso de condução que será realizado na Treviso 13/03;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(283, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '4 – Treinamento de reciclagem de condução (02 e 16 Abril / 30 de abril e 07 maio);', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(284, 2, 'FALTA DE QUALIFICAÇÃO DE MOTORISTAS', 'MELHORAR QUALIDADE DOS MOTORISTAS', '5 – Turma de inicio 13/03 será orientada pelo Josiel;', 'VISITA MENSAL - MARÇO', '2023-03-30', 'DANIEL', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(285, 2, 'LIMPEZA DOS BANHEIROS ', 'FALTA DE LIMPEZA ADEQUADA DOS BANHEIROS ', 'COBRAR ZELADORES DA LIMPEZA ADEQUADA DOS BANHEIROS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(286, 2, 'QUADRO DE 5S COM DEFEITO', 'QUADRO DE 5S QUEBRADO', 'REALIZAR A CORREÇÃO DOS QUADROS DE 5S', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(287, 2, 'FALTA DE MANUTENÇÃO PREDIAL', 'CAIXAS ABERTAS, CANOS NAS CALÇADAS E PAREDES E PISO SUJOS.', 'REALIZAR A MANUTENÇÃO PREDIAL DE FORMA CORRETA', 'AUDITORIA DE 5S- JUNHO', '2023-07-30', 'AILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(288, 2, 'ITENS DA FERRAMENTARIA DE MONJOLINHO E DESORGANIZADA', 'MELHOR IDENTIFICAÇÃO DAS FERRAMENTAS', 'REORGANIZAR FERRAMENTAS DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(289, 2, 'AREA DE CAFÉ E ÁGUA DA MONJOLINHO SUJA E DESORGANIZADO', 'MELHORAR CONDIÇÃO DE TRABALHO DA EQUIPE', 'ORGANIZAR UMA ÁREA ADEQUADA PARA O CAFÉ DA EQUIPE', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'FABIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(290, 2, 'ITENS DE FERRAMENTA FORA DO PADRÃO DE COR ADEQUADO NAS OFICINA DE MONJOLINHO E LAIS', 'ITENS NÃO FORAM PINTADOS', 'REALIAZAR A PINTURA DOS MESMOS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(291, 2, 'NECESSIDADE DE PINTURA E MANUTENÇÃO DE ALGUMAS PAREDES DA OFICINA E BANHEIRO DA OFICINA', 'ITENS COM DESGASTE ', 'REALIZAR A PINTURA E MANUTENÇÃO NECESSÁRIAS', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(292, 2, 'FALTA DE PINTURA DO PISO DO BANHEIRO DO PATIO 2 DA MONJOLINHO', 'MELHORAR ASPECTO FISICO DA ÁREA', 'REALIZAR PINTURA DA ÁREA', 'AUDITORIA DE 5S- JUNHO', '2023-07-20', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(293, 2, 'BANHEIRO DA MONJOLINHO INADEQUADO', 'BANHEIRO NECESSITANDO DE REFORMA', 'REALIZAR REFORMA DO BANHEIRO DA MONJOLINHO', 'AUDITORIA DE 5S- JUNHO', '2023-12-10', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(294, 2, 'ALTO CUSTO COM HORAS EXTRAS NA OPERAÇÃO', 'FALTA DE ALINHAMENTO DE BATIDA DE PONTO NO GALPÃO DEVIDO AO DESLOCAMENTO DOS MECÂNICOS', 'AJUSTAR HORÁRIO DE TRABALHO PENSANDO NOS DESLOCAMENTOS PARA REDUZIR HORAS EXTRAS SENDO ENTRADA 07:30 E SAIDA 16:30 PARA A EQUIPE DO GALPÃO', 'VISITA OUTUBRO', '2023-10-18', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(295, 2, 'ALTO CUSTO COM HORAS EXTRAS NA OPERAÇÃO', 'OPERAÇÃO TRABALHANDO EM FERIADOS E DOMINGOS GERANDO ALTO VOLUME DE HORAS EXTRAS', 'ZERAR VOLUME DE HORAS EXTRAS NOS FERIADOS E DOMINGOS DEIXANDO APENAS OS TURNOS INTERRUPTOS NA OPERAÇÃO NESTAS DATAS', 'VISITA OUTUBRO', '2023-10-20', 'DIOGO / RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(296, 2, 'FALTA DE DIRECIONAMENTO PARA COMPRAS', 'FALTA DE ORÇAMENTO PLANEJADO PARA DIRECIONAMENTO DE COMPRAS', 'CRIAR UMA PLANILHA BASE PARA ACOMPANHAMENTO DE ORÇAMENTO PELA ÁREA DE PRODUÇÃO E ESTOQUE', 'VISITA OUTUBRO', '2023-10-20', 'DIOGO / RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(297, 2, 'BAIXAR CUSTO COM MOBILIZAÇÃO DE EQUIPAMENTOS', 'EQUIPAMENTOS RESERVAS ESTÃO GERANDO ALTO CUSTO DE OPERAÇÃO', 'DESMOBILIZAR DA 3A CB66, CB67 E PC17.', 'VISITA OUTUBRO', '2023-10-18', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(298, 2, 'ALTO CUSTO COM EQUIPAMENTOS NA OBRA', 'DESMOBILIZAÇÃO NÃO REALIZADA DE FORMA CORRETA CONFORME DEMANDA DA OBRA', 'FINALIZAR DESMOBILIZAÇÃO SUGERIDA PELA OPERAÇÃO', 'REUNIÃO RENAN', '2024-05-21', 'RENAN / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(299, 2, 'ALTO CUSTO COM EH07 E EH09', 'PARCELA MENSAL DA EH09 INCORRETO', 'REVISAR O VALOR DA PARCELA DA EH07 E EH09 JUNTO AO TIAGO (R$ 35.000,00)', 'REUNIÃO RENAN', '2025-06-15', 'RENAN', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(300, 2, 'ALTO CUSTO COM VEICULOS LEVES', 'DEVIDO A PARADA DA OFICINA DO GALPÃO, IRÁ SOBRAR UM CARRO LEVE', 'REDUZIR UM CARRO LEVE DEVIDO A DESMOBILIZAÇÃO DO GALPÃO', 'REUNIÃO RENAN', '2024-06-30', 'ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(301, 2, 'ALTO CUSTO COM MÃO DE OBRA DE SUPERVISORES', 'ALTO CUSTO COM MÃO DE OBRA DEVIDO A ATIVIDADE DE SUPERVISORES', 'RETIRAR A FUNÇÃO DE ENCARREGADO DE OPERAÇÃO (3) E TAMBÉM RETIRAR A CAMINHONETE QUE HOJE ATENDE ESTA FUNÇÃO', 'REUNIÃO RENAN', '2024-05-20', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(302, 2, 'FALTA DE ÁREA DE VIVENCIA PARA OPERAÇÃO MONJOLINHO', 'FALTA DE ÁREA ADEQUADA', 'CONSTRUIR ÁREA DE VIVENCIA PARA MOTORISTAS EM MONJOLINHO', 'VISITA MARÇO', '2025-03-10', 'DIOGO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(303, 2, 'NÃO UTILIZAÇÃO CORRETA DO SISTEMA, NÃO TENDO REGISTRO CORRETO DAS MANUTENÇÕES', 'FALTA DE EQUIPE IDEAL PARA REALIZAÇÃO DAS ATIVIDADES', 'CONTRATAR 3 AUX DE ESTOQUE PARA CUMPRIMENTO DAS ATIVIDADES DO ESTOQUE E ABERTURA DE ORDENS DE SERVIÇOS DA MANUTENÇÃO', 'VISITA JULHO', '2024-08-20', 'RAYSON / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(304, 2, 'FALTA DE GESTÃO DA OFICINA NO TERCEIRO TURNO', 'FALTA DE SUPERVISOR DE MANUTENÇÃO PARA GESTÃO DA MANUTENÇÃO PARA A GESTÃO DO TURNO DO TERCEIRO TURNO', 'REALIZAR A CONTRATAÇÃO DE UM SUPERVISOR DA MANUTENÇÃO PARA O TERCEIRO TURNO DA MONJOLINHO', 'VISITA JULHO', '2024-08-25', 'RAYSON / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(305, 2, 'FALTA DE AUXILIAR DE MANUTENÇÃO, NÃO DEIXANDO UM TÉCNICO DISPONIVEL PARA SUPORTE AO MECÂNICO', 'FALTA DE MÃO DE OBRA NO QUADRO', 'REALIZAR A CONTRATAÇÃO DE TRÊS AUXILIAR DE MECÂNICO PARA SUPORTE AOS MECÂNICOS DOS TURNOS', 'VISITA JULHO', '2024-08-25', 'RAYSON / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(306, 2, 'LEVAR CAIXA DAGUA DA LAIS PARA O LAVADOR EM MONJOLINHOS', 'PARA EFETIVAR JUNTO A MANUTENÇÃO A LAVAGEM DAS MAQUINAS', 'MOVIMENTAR A ESTRUTURA', 'APRESENTAÇÃO MPS ', '2024-09-20', 'RAYLSON / ADMILTON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(307, 2, 'MANUAL MUDANÇA DE FUNÇÃO - FALTA DE EVIDENCIA DE FORMULARIO / FICHA DE EPI SEM EVIDENCIA DE ATUALIZAÇÃO ', 'DOCUMENTAÇÃO E RESPONSABILIDADES DIVIDIDAS ENTRE DP E SESMT', 'SEGUIR AS NORMAS DO MANUAL EM CASO DE REVISÃO COMUNICAR O SETOR DA QUALIDADE COM AS SOLICITAÇÕES DE MELHORIAS ', 'AUDITORIA DE PROCESSOS', '2024-11-29', 'LAIS/JONILSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(308, 2, 'MANUAL AFASTAMENTO - ITENS DE 1.6 A 1.8 DA AUDITORIA SERÃO DIRECIONADOS PARA O MANUAL DO SESMT', 'O DP NÃO TEM UM CONTROLE EFETIVO DA DEMANDA AUDITADA', 'INICIAR A CONSTRUÇÃO DOS MANUAIS DO SESMT', 'AUDITORIA DE PROCESSOS', '2024-12-16', 'PATRICIA/ROSANE', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(309, 2, 'Senso de saúde e segurança - Água empoçada em frente ao container sanitario', 'Adequação as normas de saúde e segurança', 'Substituição do banheiro', 'AUDITORIA 2024', '2025-01-13', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(310, 2, 'DIFICULDADE NA IDENTIFICAÇÃO DAS PEÇAS QUE ESTÃO DISPONIVEIS NO ESTOQUE', 'FALTA DE SISTEMA PARA IDENTIFICAÇÃO CORRETA NO SISTEMA', 'FINALIZAR ORGANIZAÇÃO DO ESTOQUE PARA REALIZAR O CONTROLE DE ITENS DISPONIVEIS NO NOVO ESTOQUE', 'ALINHAMENTO MANUTENÇÃO', '2026-03-20', 'THIAGO SILVA / PEDRO', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(311, 2, 'FALTA DE PROGRAMADOR DE OFICINA PARA AJUSTAR O PROCESSO DE COMPRAS', 'PROCESSO SENDO CUMPRIDO DE FORMA FRACIONADA, ONDE CADA UM FAZ UMA PARTE DO PROCESSO, GERANDO MUITOS ERROS', 'REALIZAR A CONTRATAÇÃO DE UM PROGRAMADOR DE SERVIÇOS COM UM PERFIL IDEAL PARA A VAGA', 'ALINHAMENTO MANUTENÇÃO', '2025-03-10', 'RAYLSON', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(312, 2, 'Utilização abaixo do resultado, atingindo 67%, e baixa confiabilidade nos resultados apresentados', 'Falta de avaliação real dos motivos da baixa utilização', 'Reestruturar a função de controller do Vinicius, colocando o mesmo dentro do processo de gestão para traçar ações efetivas no atingimento da utilização e se necessário, envolver o cliente com dados mais concretos. Reunir toda quinta feira.', 'PROJETO CORUMBÁ 2025', '2025-04-15', 'Admilton / Diogo / Rayson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(313, 2, 'Falta de um processo de governança e gestão e dia-a-dia;', 'Não organização de modelo de reunião', 'Implantar rotina de reunião estabelecida para o dia 14 de cada mês realizando a reunião da qualidade de maneira eficiente', 'PROJETO CORUMBÁ 2025', '2025-03-14', 'Admilton / Qualidade', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(314, 2, 'Fornecedores sem estoque disponível impactando em nosso processo de compra de peças;', 'Fornecedores com gestão de redução de custos, impactando nossa operação', 'Realizar conversas com os fornecedores, enfatizando nossos problemas com disponibilidade de peças nas concessionarias', 'PROJETO CORUMBÁ 2025', '2025-04-15', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(315, 2, 'FALTA DE ESTOQUE MINIMO DIFICULTANDO O PROCESSO DE PEDIDO DE PEÇAS, POIS TUDO ACABA FICANDO URGENTE', 'FALTA DE PEÇAS BÁSICAS PARA MANUTENÇÃO DO ESTOQUE', 'REALIZAR UMA AVALIAÇÃO DE UM ESTOQUE MINIMO EMERGENCIAL PARA ATENDER A OPERAÇÃO', 'PROJETO CORUMBÁ 2025', '2025-04-15', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(316, 2, 'Falta de orçamento para direcionamento da equipe', 'Não montado o mesmo para a unidade', 'Criar uma sugestão de orçamento para a unidade ao exercicio 2025', 'PROJETO CORUMBÁ 2025', '2025-04-15', 'Admilton / Alex', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(317, 2, 'Falta de treinamento para equipes operacionais', 'Falta de calendario de treinamentos detalhados', 'Criar um calendário para os treinamentos técnicos das funções de manutenção e operação', 'PROJETO CORUMBÁ 2025', '2025-07-15', 'Alex / Patricia / Drumont / André', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(318, 2, 'Potencial de melhorar faturamento de horas maquina', 'Ainda temos oportunidade de melhoria no número de horas vendidas', 'Orientar supervisores sobre melhora do número de horas vendidas por equipamento', 'PROJETO CORUMBÁ 2025', '2025-03-15', 'Admilton / Diogo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(319, 2, 'IMPLEMENTAÇÃO DE NOVOS MANUAIS ', 'IMPLANTAÇÃO DE NOVOS PROCESSOS ', 'REALIZAR O LANÇAMENTO JUNTO A EQUIPE DOS MANUAIS DE MANUTENÇÃO JUNTO COM RAYLSON NA MINA MOJOLINHO E MINA 3A.', 'VISITA DE ABRIL', '2025-04-30', 'ALEX / RAYLSON / JOÃO PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(320, 2, 'IMPLEMENTAÇÃO DE NOVOS MANUAIS ', 'IMPLANTAÇÃO DE NOVOS PROCESSOS ', 'REALIZAR O LANÇAMENTO JUNTO AOS LIDERES DOS MANUAIS DE SESMT E PRODUÇÃO JUNTO AO DIOGO NA MINA MOJOLINHO E MINA 3A.', 'VISITA DE ABRIL', '2001-04-25', 'ALEX / JONILSON / DIOGO /  JOÃO PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(321, 2, 'IMPLEMENTAÇÃO DE NOVOS MANUAIS ', 'IMPLANTAÇÃO DE NOVOS PROCESSOS ', 'REALIZAR O LANÇAMENTO JUNTO A EQUIPE DOS MANUAIS DE GESTÃO DE ESTOQUE JUNTO AO PEDRO NA MINA MOJOLINHO E MINA 3A.', 'VISITA DE ABRIL', '2025-04-30', 'ALEX / PEDRO /  JOÃO PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(322, 2, 'Erro nos lançamentos das ordens de compra devido a falta de orientação correta sobre centro de custos', 'Falta de orientação de sobre centro de custos', 'Reunir com equipes que geram RC para orientar sobre processo de compras de forma correta.', 'REUNIÃO ABRIL', '2025-04-30', 'Vinicuis', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(323, 2, 'Falta de contrato nas operações de Corumbá', 'Cliente reclamando da falta de proposta forma junto ao forma para a renovação do acordo de prestação de serviços junto a Vetorial.', 'Realizar um agenda presencial do Igor para iniciar uma conversa junto ao cliente.', 'REUNIÃO JUNHO', '2025-11-30', 'Igor / Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(324, 2, 'Falta de utilização de calços', 'Não disponivel nos equipamentos', 'Retornar o modelo de lombadas no estacionamento de caminhões para garantir a segurança dos mesmos. E devolver calços nos e.quipamentos que foram retirados pela oficina', 'IMPLANTAÇÃO DE MANUAIS', '2025-10-24', 'Raylson / Diogo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(325, 2, 'Pouca disponibilidade de agenda do motorista multiplicador', 'Utilização do instrutor em funções adm ou de motoristas', 'Motorista multiplicador sem disponibilidade para atendimento a supervisores para atualização de plano de marchas das minas', 'IMPLANTAÇÃO DE MANUAIS', '2025-05-30', 'Alex Doná / Diogo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(326, 2, 'Rampas de emergencias sem condições de operação em ambas a minas', 'Falta de manutenção nas rampas', 'Melhorar acesso das rampas da Monjolinho', 'IMPLANTAÇÃO DE MANUAIS', '2025-11-30', 'Diogo / Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(327, 2, 'Dificuldade de débitos corretos nos centros de custos corretos', 'Sem sistema para controle de estoque', 'Finalizar estudo do sistema de estoque a ser implantado na unidade de Corumbá para controle efetivo do estoque.', 'REUNIÃO JUNHO', '2025-08-30', 'Renan / Thiago Silva', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(328, 2, 'Equipamento TE10 fora de operação', 'Falta de peça do equipamento', 'Acompanhar liberação do TE10 que esta parado por conta de manutenção', 'REUNIÃO JUNHO', '2025-07-30', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(329, 2, 'Falta de rasteio da locação das peças em suas respectivas unidades', 'Falta de sistema para controle', 'Até que tenhamos o controle de estoque, devemos realizar a identificação das dos itens junto ao Tiago Amós na origem de envio das NF´s.', 'REUNIÃO JUNHO', '2025-09-30', 'Raylson / Tiago Amós', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(330, 2, 'Falta de acompanhamento de horas trabalhadas por equipamento', 'Falta de informação correta da obra para o Tiago', 'Incluir a planilha de horas trabalhadas das obras com base no boletim de medição, colocando as horas mês de cada equipamento', 'REUNIÃO JUNHO', '2025-07-30', 'Vinicuis', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(331, 2, 'Custo de varias frentes unificados', 'Falta de análise unitaria dos contratos', 'Renan pediu para separar os números do contrato da LHG para que no próximo mês tenhamos os relatório de avaliação da unidade.', 'REUNIÃO JUNHO', '2025-07-30', 'Admilton / Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(332, 2, 'Acidente na mina', 'Falta de cumprimento de padrão', 'Renan solicitou avaliar situação do colaborador que teve o acidente na unidade da 3A.', 'REUNIÃO JUNHO', '2025-07-30', 'Diogo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(333, 2, 'Problema no transporte de lama', 'Está vazando lama no acesso', 'Esudar uma solução e implementa-la', 'COMUNIDADE DO LÍDER', '2025-08-30', 'Eliel e Raylson', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(334, 2, 'Alto custo com escavadeira no contrato devido a estratégia de manter as escavadeiras no contrato', 'Escadeiras locadas de maneira estratégica na unidade', 'Admilton irá estudar uma avaliação de como iremos cobrar as escavadeiras que estão na unidade de Corumbá e encontrar uma solução viavel como um todo. E o Tiago e Renan irão validar esta proposta', 'REUNIÃO JULHO', '2025-08-18', 'Renan / Admilton / Tiago Amós', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(335, 2, 'Falta de identificação de ponto de corte ideal de custo de manutenção dos equipamentos', 'Identificar momento ideal de venda de equipamento', 'Realizar uma avaliação de ponto de corte para maquinas com horimetros elevados. Identificar qual o ponto de corte DF X Custo de manutenção. Vinicius irá avaliar se vai ser possível montar este relatório.', 'REUNIÃO JULHO', '2025-10-10', 'Vinicuis', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(336, 2, 'Equipamentos para vendas na obra', 'Demora para venda nos equipamentos parados', 'Reforçar a lista de equipamentos para venda para ser enviado para o Gigante realizar a venda de equipamento', 'REUNIÃO JULHO', '2025-08-18', 'Gustavo / Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(337, 2, 'Trator parado na Sotrec para processo de garantia', 'Falha em garantia', 'Realizar uma cobrança via matriz sobre o TE19 que esta na Sotrec sem uma posição de liberação por parte do concessionário', 'REUNIÃO JULHO', '2025-08-18', 'Renan / Drumond / Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(338, 2, 'Redução de 50% no faturamento da unidade da 3A com a mesma equipe', 'Utilizando a equipe na LHG', 'Finalizar a redução da folha de pagamento da 3A devido a baixa de faturamento na unidade.', 'REUNIÃO JULHO', '2025-08-18', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(339, 2, 'Falta de plano de ação referente a última auditoria da unidade', 'Falta de envio', 'Cobrar Carolina e Raylson do envio dos planos de ação atualizados referentes a ultima auditoria.', 'CONTATO SEMANAL', '2025-07-30', 'Caroline', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(340, 2, 'NOK 1.6: CONTRATO SEM ASSINATURA ANEXADO NO SISTEMA', 'Adequação a normas e procedimentos', 'O documento será anexado no novo processo a partir da proxima data do reajuste contratual.', 'AUDITORIA 2025', '2026-03-20', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(341, 2, 'Falta de identificação nas gavetas ', 'Desgaste natural da etiqueta', 'Reposição de etiquetas ', 'AUDITORIA 2025', '2025-07-23', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(342, 2, 'Cor fora do padrão da sala suprimentos/almoxarifado', 'Não conhecimento da paleta de cores ', 'Repintura com a cor correta ', 'AUDITORIA 2025', '2025-08-03', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(345, 2, 'grande quantidade de escavadeiras paradas para manutenção e em condições de manutenção que não são normais para a unidade', 'Grade número de máquinas com horímetro superior a 15.000 horas', 'Foi acionada mão de obra especializada para realizar as revisões necessárias, com o objetivo de deixar as máquinas mais apresentáveis e em plenas condições operacionais', 'RELATÓRIO DE ESCAVADEIRAS', '2025-09-10', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(346, 2, 'NOK 1.3 = SERVIÇO DE TORNEARIA PARA MANUTENÇÃO COM APENAS 1(UMA) COTAÇÃO', 'Adequação a normas e procedimentos', 'Criar lista de fornecedores homologados para esses serviços excepicionais. Neste caso não haverá necessidade das 3 cotações.', 'AUDITORIA 2025', '2026-03-20', 'Carolina / Thiago Silva', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(347, 2, 'Peças estão sendo entregues diretamente para oficina e não passam pelo estoque (Item 1.2)', 'Adequação a normas e procedimentos', 'Todas as peças serão entregues no almoxarifado', 'AUDITORIA 2025', '2025-07-23', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(348, 2, '(Item 1.4) - Iventario sem registro', 'Adequação a normas e procedimentos', 'Alimentar sistema - Inventario realizado', 'AUDITORIA 2025', '2025-08-23', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(349, 2, '(Item 1.5) Não há sistema de estoque', 'Adequação a normas e procedimentos', 'Sistema de estoque em processo de implantação', 'AUDITORIA 2025', '2025-09-23', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(350, 2, '(Item 1.6) NÃO HÁ CONTROLE PARALELO DE INVENTARIO', 'Adequação a normas e procedimentos', 'Sistema de estoque em processo de implantação', 'AUDITORIA 2025', '2025-09-23', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(351, 2, 'Lançamentos incorretos de contas na LHG e Monjolinho', 'Abertura para lançamento em contas incorretas.', 'Bloquear centro de custos para que não seja possivel realizar lançamentos incorretos', 'REUNIÃO OUTUBRO', '2026-03-20', 'Tiago Amós / Vinicius / Gustavo', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(352, 2, 'Excesso de equipamentos no contrato', 'Equipamentos em excesso no contrato', 'Revisar os equipamentos que devem ser desmobilizados para reduzir o custo do contrato. ', 'REUNIÃO OUTUBRO', '2025-11-30', 'Admilton / Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(353, 2, 'Falta de disponibilidade de escavadeira', 'Equipamentos obsoletos', 'Realizar o levantamento da viabilidade de adquirir uma nova escavadeira para o contrato. Avaliando os equipamentos que podem ser vendidas e também o valor que estamos perdendo de faturar com as maquinas que não estão produzindo.', 'REUNIÃO OUTUBRO', '2025-11-30', 'Vinicuis', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(354, 2, 'Falta de implementação da metodologia de estoque', 'Falta de alinhamento da gestão do estoque', 'Utilizar o Lauriney para auxiliar o Pedro na implantação do sistema da gestão de estoque', 'REUNIÃO OUTUBRO', '2025-12-01', 'Pedro, Lauriney e Vinicius', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(355, 2, 'Falta de informação de segurança', 'Alinhar informação entre os participantes', 'Inlcuir os temas de segurança na reunião mensal.', 'REUNIÃO NOVEMBRO', '2025-12-15', 'Vinicuis', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(356, 2, 'Melhorar qualificação da equipe de perfuração', 'Melhor eficiencia operacional', 'Visita do Robinho que é o especialista de ferramentas de perfuração na unidade para aprimoramento do setor.', 'REUNIÃO NOVEMBRO', '2025-12-15', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(357, 2, 'Falta de produtividade das escavadeiras', 'Escavadeiras com horimetros elevados', 'Simular uma avaliação de substituição das escadeira para finalizar o estudo de viabilidade da subsitutição das escavadeiras', 'REUNIÃO NOVEMBRO', '2025-12-15', 'Renan / Tiago Amós / Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(358, 2, 'Dificuldade com pós-venda da Sotrec', 'Atendimento fora de nossa expectativa', 'Realizar um levantamento do atendimento do técnico da Sotrec para identificarmos possiveis melhorias no pós-venda.', 'REUNIÃO NOVEMBRO', '2026-03-02', 'Raylson / Vinicius', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(359, 2, 'Falta de padrão para apresentação dos incidentes / acidentes', 'Melhorar apresentação ', 'Inlcuir no manual do sesmt os critérios para apresentação dos acidentes / incidentes nas reuniões mensais', 'REUNIÃO DEZEMBRO', '2026-03-20', 'Patricia / João', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(360, 2, 'Falta de peças para a perfuratriz', 'Dificuldade logistica para chegada de peças a unidade', 'Realizar uma avaliação se é viavel termos um estoque de peças mais abrangente para atender as demandas da maquina', 'REUNIÃO DEZEMBRO', '2026-01-10', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(361, 2, 'Contrato de perfuração com margem negativa', 'Contrato revisado sem garantia de metros perfurados', 'Revisar o contrato de perfuração pois o mesmo não sendo viavel para a Dinex', 'REUNIÃO DEZEMBRO', '2026-01-10', 'Renan', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(362, 2, 'Indicadores da unidade e da controladoria em discrepancia', 'Cada um gerando o seu indicador de maneira separada', 'Criar um modelo de indicadores unificados entre Vinicius e Tiago Amós, para devido a diferença entre os indicadores', 'REUNIÃO DEZEMBRO', '2026-03-10', 'Vinicius / Tiago Amós', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(363, 2, 'Baixa utilização da perfuratriz', 'Falta de operador baixando a Utilização da maquina', 'Contratação de dois operadores para rodar turno das perfuratriz', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(364, 2, 'Baixa DF da perfuratriz', 'Pouca disponibilidade de peças para a perfuratriz na unidade', 'Envio de perfuratriz reserva para garantir a DF da operação de perfuração da unidade', 'REUNIÃO FEVEREIRO', '2026-03-15', 'Drumond / Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(365, 2, 'Alto volume de deslocamento da perfuratriz para manutenção', 'Oficina longe da área de operação', 'Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\r\n##CANCELADO##', 'REUNIÃO FEVEREIRO', '2026-03-10', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(366, 2, 'Avaliação de renovação de frota', 'Avaliação de contrato ideal', 'Levantamento de maquinas a serem substituidas para avaliação comercial do Renan', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(367, 2, 'Utilização do sistema SGA para controle de ordens de serviço da manutenção', 'Falta de implementação do sistema', 'Implementar o sistema DMS no lugar do SGA para acompanhamento da manutenção', 'REUNIÃO COMITÊ', '2026-02-25', 'Gisele', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(368, 2, 'Falta de abertura de OS de corretivas no DMS', 'Falta apresentação do sistema e processo de abertura de OS para equipe', 'Realizar uma reunião de alinhamento com equipes para implementação do processo de abertura de OS de corretivas', 'REUNIÃO COMITÊ', '2026-02-27', 'Raylson / Gisele', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(369, 2, 'Falta de utilização do Backlog no sistema', 'Utilização da planilha para este controle', 'Passar as informações para o sistema DMS', 'REUNIÃO COMITÊ', '2026-03-20', 'Gisele', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(370, 2, 'Falta de apontamento do mecânico no sistema', 'Não utilização do DMS', 'Realizar o lançamento das horas trabalhadas no sistema DMS para acompanhamento de produtividade de oficina', 'REUNIÃO COMITÊ', '2026-03-02', 'Gisele', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(371, 2, 'Não aplicação do sistema de controle pneus', 'Não aplicação do processo', 'Implementar o processo de controle de Pneus e material rodante', 'REUNIÃO COMITÊ', '2026-03-20', 'Raylson / André', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24'),
(372, 2, 'Não utilização do DMS em relação ao controle e estoque', 'Não implementado o esto que na unidade', 'Realizar o alinhamento para implementação do estoque na unidade de Corumbá', 'REUNIÃO COMITÊ', '2026-03-20', 'Raylson / André', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:09:24');
INSERT INTO `pdca_tasks` (`id`, `id_cliente`, `titulo`, `descricao`, `meta_valor`, `meta_unidade`, `prazo`, `responsavel`, `fase`, `status`, `progresso`, `created_at`) VALUES
(373, 2, 'Baixa utilização da perfuratriz', 'Falta de operador baixando a Utilização da maquina', 'Contratação de dois operadores para rodar turno das perfuratriz', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(374, 2, 'Baixa DF da perfuratriz', 'Pouca disponibilidade de peças para a perfuratriz na unidade', 'Envio de perfuratriz reserva para garantir a DF da operação de perfuração da unidade', 'REUNIÃO FEVEREIRO', '2026-03-15', 'Drumond', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(375, 2, 'Alto volume de deslocamento da perfuratriz para manutenção', 'Oficina longe da área de operação', 'Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\r\n\r\n##CANCELADO##', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(376, 2, 'Avaliação de renovação de frota', 'Avaliação de contrato ideal', 'Levantamento de maquinas a serem substituidas para avaliação comercial do Renan', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(377, 2, 'Falta de indicadores confiaveis para gestão da unidade', 'Indicadores estão sendo utilizado na planilha porém a quantidade de informações', 'Aplicar processo de gestão de indicadores atraves de um software mais confiavel', 'REUNIÃO COMITÊ', '2026-03-30', 'Vinicius', 'PLAN', 'Planejado', 0, '2026-03-12 12:09:24'),
(378, 2, 'Itens a serem descartado na área de oficina', 'Itens a descartar', 'Realizar um descarte geral na área de oficina', 'REUNIÃO COMITÊ', '2026-03-30', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(379, 2, 'Telhado da oficina para fazer manutenção', 'Telha arrancada pelo vento', 'Realizar a correção do telhado da oficina da monjolinho', 'REUNIÃO COMITÊ', '2026-03-30', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(380, 2, 'Itens fora do seu local ideal prejudicando a nota de 5´s', 'Itens fora do local', 'Organização de itens fora do local', 'REUNIÃO COMITÊ', '2026-03-30', 'Raylson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(381, 4, 'Falta de programas sociais, por falta de iniciativa das área de segurança no trabalho.', 'Falta de cumprimento de cronograma de ações sociais', 'Implementar o calendários de ações sociais', 'VISITA MARÇO', '2025-04-15', 'SESMT', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(382, 4, 'Não esta sendo realizado o controle de incidentes e acidentes na unidade.', 'Falta de controle efetivo dos incidentes e acidentes', 'Criar controle de incidentes para acidentes', 'VISITA MARÇO', '2025-04-15', 'SESMT', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(383, 4, 'Falta de treinamento para alguns colaboradores', 'Não disponibilidade de sair da operação nos treinamentos no mês de maio', 'Relizar mais um grupo para a Comunidade do lider (Lider comportamental), e realizar o treinamento de manual de manutenção com a letra que não foi possivel ser treinada.', 'VISITA MAIO', '2025-07-30', 'Alex Doná', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(384, 4, 'Lançado dobra do cartão alimentação no mês de junho.', 'O lançamento da dobra era somente para o mês de maio.', 'O cartão alimentação esta sendo pago dobrado no mês de junho novamente, este custo não deveria estar lançado no mês. Assim devemos verificar se os pagamentos estão corretos.', 'REUNIÃO JULHO', '2025-08-18', ' Paulo Cezar', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(385, 4, 'Alto volume de horas extras devido a feriados', 'Contrato roda todos os dias', 'Assim, iremos realizar um estudo sobre as horas extras da unidade. Momentaneamente ainda teremos um valor de horas extras, devido a falta de equipamentos.', 'REUNIÃO JUNHO', '2025-07-30', 'Paulo Cezar', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(386, 4, 'Tiago apresentou uma lista de equipamentos que estão tendo um custo de 4.7 milhões em 2025', 'Falta de acompanhamento mais proxima dos equipamentos', 'Paulo ira avaliar todos os equipamentos que não estão produzindo de maneira eficiente. Hoje temos uma sobra de equipamento, que serão enviados após normalização de DF na unidade.', 'REUNIÃO JUNHO', '2025-11-30', 'Paulo Cezar', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(387, 4, 'Alto custo com equipamentos na unidade', 'Equipamentos que foram desmobilizados ainda pesando o custo da unidade', 'Formalizar junto as unidades sobre os equipamentos que foram desmobilizados e ainda estão sendo cobrados das unidades.', 'REUNIÃO JUNHO', '2025-07-30', 'Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(388, 4, 'Alto custo com reformas de equipamentos de equipamentos na manutenção central, impactanto negativamente no contrato de Maracás', 'Compras sendo realizadas em um grande volume para reformas de equipamentos', 'Avaliar um orçamento mensal para as reformas de equipamentos, nas unidades e na manutenção central, pois este custo esta sem um controle efetivo', 'REUNIÃO JULHO', '2025-08-18', 'Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(389, 4, 'Alto custo com reforma de equipamentos com horimetros elevados', 'Realizando reformas de equipamentos inviaveis', 'Realizar uma análise de viabilidade de reformas de equipamentos, avaliando se iremos reformar ou vender os equipamentos que estão com um horimetro elevado', 'REUNIÃO JULHO', '2025-10-15', 'Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(390, 4, 'Orçamento da mina e da britagem separados dificultandos algumas medições', 'No inicio dos contratos essa separação foi necessária, hoje já podemos unificar os contratos', 'Realizar uma avaliação de unificação do orçamento e dos números da mina e da britagem. Aguardando renovação do contrato.', 'REUNIÃO JUNHO', '2025-11-30', 'Tiago Amós', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(391, 4, 'Alto custo com lançamento do rompedor de maneira integral no orçamento', 'Lançamento incorreto', 'Realizar o parcelamento do valor do rompedor que esta lançado incorretamente, lançar o valor parcelado', 'REUNIÃO JULHO', '2025-08-18', 'Tiago Amós', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(392, 4, 'A programação das inspeções de 250 horas está sendo feita de acordo com o planejamento.', 'NÃO ESTA SENDO REALIZADAS A 250H PARA OS CAMINHÕES (OCORRE APENAS PARA PF) ', 'Emitir ordem de serviço para falha de inspeção de 250 e registrar quando não houver parada do equipamento por baixa disponibilidade', 'AUDITORIA 07/2025', '2025-08-11', 'Hudson, Caio e Valdemir', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(393, 4, 'O checklist de inspeção não está sendo seguido corretamente durante a execução das inspeções de 250 horas', ' NÃO SÃO REALIZADOS EM CAMINHÕES ', 'Emitir ordem de serviço para falha de inspeção de 250 e registrar quando não houver parada do equipamento por baixa disponibilidade', 'AUDITORIA 07/2025', '2025-09-30', 'Hudson, Caio e Valdemir', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(394, 4, 'Após a realização da manutenção preventiva, o processo de liberação dos equipamentos não há  documentação necessária sendo registrada', 'NÃO HÁ EVIDENCIA DE PROESSOS REALIZADOS E ASSINATURA DE REGISTRO', 'Em 04/08 será iniciado o alinhamento da programação dos backlogs, para posteriormente ser integrado à programação das preventivas.', 'AUDITORIA 07/2025', '2025-09-30', 'Carlos, Danilo, Joariane, Hudson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(395, 4, 'Os recursos necessários (mão de obra, ferramentas e peças) para a realização do reparo estão sendo avaliados de forma adequada, de acordo com as necessidades identificadas no checklist de inspeção?', 'FALTA PROVISAO DAS PARADAS, POREM EXISTE O CONTROLE DAS PENDENCIAS', 'Não há recursos necessários para execução dos backlogs falta mão de obra ', 'AUDITORIA 07/2025', '2025-09-30', 'Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(396, 4, 'As peças ou recursos disponíveis estão sendo utilizados de forma eficiente, e a programação de manutenção preventiva está sendo elaborada e enviada corretamente para os responsáveis?', 'HÁ CONTROLE REALIZADO, POREM SEM MÃO DE OBRA E TEMPO DO EQUIPAMENTO DISPONIVEL ', 'Orientar os líderes a confirmar a execução de todos os itens do backlog.', 'AUDITORIA 07/2025', '2025-09-30', 'Carlos / Danilo / Joariane / Hudson', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(397, 4, 'A programação de reparos está sendo acompanhada e executada conforme os prazos definidos, e os reparos estão sendo realizados de acordo com as necessidades dos equipamentos identificadas no backlog?', 'HÁ CONTROLE REALIZADO, POREM SEM MÃO DE OBRA E TEMPO DO EQUIPAMENTO DISPONIVEL ', 'Definiremos o grau de cada classificação conforme a urgência e estabeleceremos os prazos correspondentes.', 'AUDITORIA 07/2025', '2025-09-30', 'Danilo / Hudson / Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(398, 4, 'A liberação dos equipamentos para a operação está sendo feita de forma controlada, com registro adequado das informações no sistema Fast Mine, garantindo que os reparos foram realizados conforme os requisitos e padrões estabelecidos?', 'SEM REGISTRO E ASSINATURA ', 'A equipe da sala de controle de manutenção está realizando a liberação adequada no sistema de gestão de frota (F2M), além do acompanhamento no sistema DMS.', 'AUDITORIA 07/2025', '2025-08-30', 'Sala de controle de manutenção', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(399, 4, 'A inspeção e calibração semanal dos pneus são realizadas conforme o cronograma?', 'NÃO HÁ EVIDENCIA DO PROCESSO ANTERIOR A 07/2025 ', 'Está sendo executado e monitorado diariamente.', 'AUDITORIA 07/2025', '2025-07-30', 'Elton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(400, 4, 'Todas as movimentações de pneus são registradas em Ordem de Serviço (OS) para rastreabilidade?', 'EXISTE A OS MAS SEM REASTREABILIDADE ', 'Está sendo realizado acompanhamento diário e desenvolvido o Dashboard de OS.', 'AUDITORIA 07/2025', '2025-08-30', 'Elton', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(401, 4, 'O Supervisor de Oficina está realizando a liberação do equipamento para a operação de forma adequada, registrando todas as informações necessárias no sistema de controle, garantindo que o equipamento esteja em condições adequadas para uso?', 'SEM REGISTRO E ASSINATURA ', 'Iniciar, diariamente às 09h00, reunião com a equipe de PCM e Supervisores/Líderes de Manutenção para alinhamento da programação diária e semanal.', 'AUDITORIA 07/2025', '2025-09-30', 'Carlos / Joariane / Supervisores', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(402, 4, 'Está sendo garantido que o ambiente de coleta de óleo seja controlado para evitar contaminação das amostras, conforme as diretrizes estabelecidas no procedimento?', 'CONFORME RELATORIO FOI ENCONTRADO IMPUREZAS', 'Higienizar os equipamentos e a área antes da programação das coletas. Programar previamente a limpeza dos equipamentos. Além disso, Valdemir orientará a equipe de lubrificação a seguir o processo adequado de coleta.', 'AUDITORIA 07/2025', '2025-09-30', 'Joariane / Valdemir', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(403, 4, 'Concluir revitalização das escavadeiras pendentes ', 'pintura preventiva para rastrear danos operacionais', 'Um acompanhamento mais efetivo para evitar danos e a implementação de um novo conceito de gestão de consequências para avarias, que já está em funcionamento', 'Reunião Julho/25', '2025-09-30', 'Manutenção', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(404, 4, 'Reduzir horas extras com reforço do efetivo ', 'baixa disponibilidade das escavadeiras e o aumento das horas extras na equipe de manutenção, que impactaram negativamente a produção', 'Monitorando junto ao DP sede BH o apontamento realizado pela unidade que fará a apuração das batidas semanalmente', 'Reunião Julho/25', '2025-08-30', 'Welyda / Fabio Luiz', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(405, 4, 'Negociar com consorciadas substituição/desmobilização de equipamentos de alto custo.', 'Discussão sobre a alocação de recursos e equipamentos entre as consorciadas', 'custos de manutenção dos equipamentos D6 e escavadeira 322 sejam rateados entre as consorciadas', 'Reunião Julho/25', '2025-08-30', 'Paulo Cesar e Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(406, 4, 'Realocar caminhões para otimizar custo de propriedade.', 'Acompanhamento dos custos de manutenção e propriedade dos equipamentos', 'Implementar plano de recuperação de caminhões ', 'Reunião Julho/25', '2025-08-30', 'Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(407, 4, 'Acompanhar e controlar custos fixos e variáveis mensalmente.', 'Ter uma visão e controle mais efetivo dos custos ', 'Estruturar a apresentação de resultados e aprimorar o modelo para as próximas reuniões', 'Reunião Julho/25', '2025-09-15', 'Paulo Cesar e Carol ', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(408, 4, 'Estoque com alto volume de itens porém com baixa qualidade de itens em relação as demandas de peças da unidade', 'Estoque defasado', 'Thiago Silva irá visitar a unidade para criar um plano estratégico para melhoria do estoque da unidade.', 'REUNIÃO OUTUBRO', '2025-10-30', 'Thiago Silva', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(409, 4, 'Melhorar acompanhamento de despesas da unidade no dia-a-dia', 'Melhorar acompanhamento das contas da unidade', 'Caroline ira acompanhar as despesas da unidade auxiliando o gestor na tomada de decisões.', 'REUNIÃO OUTUBRO', '2025-10-30', 'Paulo / Caroline', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(410, 4, 'Equipamentos e materiais fora da identidade visual', 'Ajustas identidade visual', 'Ajuste da identidade visual da unidade com base no manual de cores e logos Dinex nas estruturas e equipamentos.', 'REUNIÃO NOVEMBRO', '2026-03-10', 'Paulo', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(411, 4, 'Mudança de área do estoque', 'Melhorar alocação de peças', 'Colocar o gerente de suprimentos para acompanhamento da mudança do estoque para a nova estrutura.', 'REUNIÃO NOVEMBRO', '2026-03-10', 'Thiago Silva', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(412, 4, 'Compras acima do esperado em outubro', 'Avaliar', 'Apresentar um demonstrativo das compras realizadas na unidade na área de manutenção no mês de outubro para uma análise mais detalhada.', 'REUNIÃO NOVEMBRO', '2025-12-10', 'Tiago Amós / Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(413, 4, 'Falta de controler interno na unidade', 'Melhorar controles', 'Envolver a Carolina com a área de controladoria para que tenhamos esse suporte na unidade.', 'REUNIÃO NOVEMBRO', '2025-12-10', 'Tiago Amós / Paulo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(414, 4, 'Falta de informação de segurança na reunião', 'Melhorar informações', 'Inlcuir os temas de segurança na reunião mensal.', 'REUNIÃO NOVEMBRO', '2025-12-10', 'Paulo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(415, 4, 'Falta de compartilhamento de boas praticas', 'Melhorar informações', 'Compartilhamento de informações via whats com todos os gestores Dinex para compartilhamento de boas praticas.', 'REUNIÃO NOVEMBRO', '2025-12-10', 'Alex Doná', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(416, 4, 'Tags fora do padrão Dinex', 'Facilitar identificação', 'Migrar as tags dos equipamentos para o padrão Dinex.', 'REUNIÃO NOVEMBRO', '2026-03-10', 'Tiago Amós / Joariane', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(417, 4, 'E-mail fora do padrão Dinex', 'Padronizar', 'Realizar o ajuste dos dominios do e-mail para @dinex', 'REUNIÃO NOVEMBRO', '2026-01-10', 'Paulo / Gustavo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(418, 4, 'Falta de padrão para apresentação dos incidentes / acidentes', 'Melhorar apresentação', 'Inlcuir no manual do sesmt os critérios para apresentação dos acidentes / incidentes nas reuniões mensais', 'REUNIÃO DEZEMBRO', '2026-03-10', 'Patricia / João', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(419, 4, 'Melhorar acompanhamento das preventivas', 'Monitaramento', 'Enviar fotos e OS das preventivas realizadas para acompanhamento dos reparos realizados', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Joariane', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(420, 4, 'Melhorar qualidade da operação', 'Muitos incidentes com equipamentos', 'Contratação do instrutor de operação para orientação dos operadores em relação a impactos nas maquinas', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Paulo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(421, 4, 'Muitas ocorrencias de avarias em equipamentos', 'falta de monitoramento das avarias', 'Realizar um acompanhamento das avarias nos equipamentos e realizar uma gestão de consequencias tomando as ações corretas', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Paulo / Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(422, 4, 'Vazamento no rompedor', 'Falha prematura', 'Buscar garantia com fabricante do rompedor que deu problema com poucas horas trabalhadas', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Andre', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(423, 4, 'Alto volume de backlog na unidade em aberto', 'Backlog de equipamentos que serão desmobilizados ainda na lista geral', 'Relizar uma avaliação geral dos backlogs para verificar o que sera desmobilizado atualizando a lista como um todo', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(424, 4, 'Equipamento para ser desmobilizado para se tornar estoque de peças', 'Equipamento sem condições de reforma', 'Realizar a desmobilização do CM501 para que o mesmo se torne peças de almoxarifado, criando o inventário das peças que vão ser geradas deste equipamento', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Joariane', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(425, 4, 'Equipamento para desmobilizar', 'Desmobilizar', 'Desmobilizar a CM301 que esta aguardando uma definição do Gustavo', 'REUNIÃO DEZEMBRO', '2026-01-20', 'Joariane / Gustavo', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(426, 4, 'Lançado 13º apenas em 2 meses sem uma divisão durante o ano', 'Não provisionamento de 13º', 'Adotar a metodologia de provisionamento das despesas de 13º das unidades para não termos este desencaixe financeiro em novembro e dezembro.', 'REUNIÃO FEVEREIRO', '2026-02-10', 'Tiago Amós', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(427, 4, 'Aplicar TAG nas caçambas', 'Para Melhor controle de reformas', 'Criar um processo de tagueamento das caçambas da unidade para acompanhamento de reformas', 'REUNIÃO FEVEREIRO', '2026-03-10', 'Thiago Amós', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(428, 4, 'Primarização dos containers', 'Diminuição de custos', 'Levantamento do custo e aquisição de containes atraves do suprimentos', 'REUNIÃO FEVEREIRO', '2026-03-01', 'Thiago Silva', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(429, 4, 'Falta de inspetor para avaliação pontual de dois equipamentos', 'Ausencia do colaborador', 'Realizada a contratação do inspetor de manutenção que estava faltando', 'REUNIÃO COMITÊ', '2026-03-10', 'Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(430, 4, 'Falta de assinatura de algumas OS', 'Falta de assintura', 'Incluir no sistema a opção de assinatura de OS no sistema DMS', 'REUNIÃO COMITÊ', '2026-03-10', 'Carlos Eduardo', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(431, 4, 'Falta de baixa de itens realizados em backlog', 'Falta de baixa', 'Alinhado o processo de baixa dos itens realizados em backlog. E realizado a montagem de uma equipe exclusiva para atendimento do backlog.', 'REUNIÃO COMITÊ', '2026-03-10', 'Carlos Eduardo/ Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(432, 4, 'Falta de validação da concessionária', 'Falta de evidência da validação da concessionária', 'Alinhado com concessionária a validação dos equipamentos que foram feitos por nossa equipe técnica', 'REUNIÃO COMITÊ', '2026-03-10', 'Danilo', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(433, 4, 'Processo de preditiva com contaminação e falta de registro dos itens criticos', 'Falta de acompanhamento das preditivas', 'Implantado acompanhamento de resultado das análise de óleo, e definido processo de melhoria para o processo de coleta de óleo', 'REUNIÃO COMITÊ', '2026-03-10', 'Danilo', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(434, 4, 'Coletas contaminadas', 'Itens com presença de terra nas amostras', 'Realizar um treinamento com a ALS sobre a coleta de óleo para melhorar a qualidade das amostas', 'REUNIÃO COMITÊ', '2026-03-25', 'André', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(435, 4, 'Falta de fogueamento nos pneus', 'Cenário fora do padrão da unidade gerando pneus sem controle', 'Realizado a contratação de outro sistema para controle de fogueamento dos pneus', 'REUNIÃO COMITÊ', '2026-02-05', 'Elton / Joariane', 'PLAN', 'Concluído', 0, '2026-03-12 12:09:24'),
(436, 4, 'Falta de mão de obra para realizadação de backlog das perfuratriz', 'Falta de colaborador', 'Adequação da equipe para realização do backlog', 'REUNIÃO COMITÊ', '2026-03-10', 'Joariane', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(437, 4, 'Falta de registro de peças', 'Falta de baixa de peças', 'Ajustar casos pontuais de baixa para ajustes de saldos', 'REUNIÃO COMITÊ', '2026-03-30', 'Vagner', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(438, 4, 'Falta de controle do estoque da área externa', 'Falta de entrada de peças no estoque das peças que estão no pátio externo', 'Ajustar saldo de itens que estão no estoque externo para dentro do sistema', 'REUNIÃO COMITÊ', '2026-03-30', 'Vagner / Joariane', 'PLAN', 'Pendente', 0, '2026-03-12 12:09:24'),
(439, 4, 'Falta de trava de ajuste de estoque de forma manual', 'Sem trava para ajuste de estoque', 'Nova versão do sistema já possui essa trava', 'REUNIÃO COMITÊ', '2026-03-30', 'Carlos Eduardo', 'PLAN', 'Concluído', 100, '2026-03-12 12:09:24'),
(441, 3, 'Perfuratriz Hidráulica PF-09', 'Senso Utilização do Equipamento', 'Adequar Plotagem janela lateral esquerda e colocar porta lateral direita.', 'AUDITORIA DE 5S', '2024-05-30', 'Manutenção (Paulo, Jaider, Alexandro)', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:01'),
(442, 3, 'Oficina de Manutenção de Perfuratriz', 'Senso de Organização do Ambiente', 'Realizar construção do piso, montagem da tenda, posicinamento do container, adequação da área.', 'AUDITORIA DE 5S', '2024-09-30', 'Marcus / Claudinei', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(443, 3, 'Oficina de Manutenção de Perfuratriz', 'Senso de Limpeza do Ambiente', 'Realizar 5S na área de oficina, necessário armários e estruturas para acondicionar ferramentas e insumos.', 'AUDITORIA DE 5S', '2024-09-30', 'Manutenção (Paulo, Jaider, Alexandro)', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(444, 3, 'Área de Vivência Operação', 'Senso de Utilização', 'Substituição de cadeiras quebradas,  5S da área.', 'AUDITORIA DE 5S', '2024-06-10', 'Pedro / Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(445, 3, 'Escritórios Operacionais', 'Senso de Organização do Ambiente', 'Substituição de etiquetas genéricas por etiquetas específicas, troca de placa de segurança (logo antigo).', 'AUDITORIA DE 5S', '2024-06-20', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(446, 3, 'Almoxarifado Operacional', 'Senso de Organização do Ambiente', 'Realizar organização do ambiente, etiquetar prateleiras e peças.', 'AUDITORIA DE 5S', '2024-06-20', 'Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(447, 3, 'Afiação de Bits - Operacional', 'Senso de Saúde e Segurança', 'Realizando a mudança e adequação do espaço lateral da área de vivência de forma isolada para minimizar os rúidos oriundos da atividade de afiação.', 'AUDITORIA DE 5S', '2024-07-20', 'Claudinei', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(448, 3, 'Falta de utilização dos planos de manutenção', 'Falta de padrão', 'Reunir técnicos para explicar sobre os plano de manutenção para implantação do processo de manutenção preventiva de forma correta.', 'VISITA AGOSTO - MP', '2024-09-30', 'Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(449, 3, 'Falta de planejamento de manutenção efetiva', 'Falta de padrão', 'Implementar o processo de programação de paradas de equipamento para manutenção e gerar esta informação para a área de manutenção semanalmente.', 'VISITA AGOSTO - MP', '2024-09-30', 'Richard / Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(450, 3, 'Falta de ferramental ideal', 'Falta de padrão', 'Finalizar o processo de compra de ferramentas básicas para montagem da ferramentaria de forma correta', 'VISITA AGOSTO - MP', '2025-12-10', 'Bruno / André', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(451, 3, 'Falta de procedimento de lavagem de equipamentos', 'Falta de padrão', 'Implantar processo de lavagem de equipamento no lavador externo para que nas preventivas tenhamos os equipamentos todos limpos para estes serviços', 'VISITA AGOSTO - MP', '2024-09-30', 'Richard', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(452, 3, 'Processo de coleta de óleo incorreto', 'Falta de padrão', 'Retomar o processo de informação para coleta de óleo junto ao laboratório ALS para realização das análises corretas', 'VISITA AGOSTO - MP', '2024-09-30', 'Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(453, 3, 'Falta de planilha para controle', 'Melhorar registro de Backlog', 'Enviar planilha de backlog para aplicação no dia-a-dia', 'VISITA AGOSTO - MP', '2024-08-20', 'Alex Doná', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(454, 3, 'Falta de registro das OS de corretiva', 'Falta de registro de reparos', 'Padronizar o processo de abertura de OS para registros das mão-de-obra e peças utilizadas no reparo de corretiva', 'VISITA AGOSTO - MP', '2024-08-30', 'Jaider', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(455, 3, 'Falta de padronização de fomulários', 'Falta de padrão', 'Adotar padrão de formulários para os processos de RH para melhorarmos as comunicações da área', 'VISITA AGOSTO - MP', '2024-08-30', 'Valquiria / Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(456, 3, 'Falta de acesso a pasta de documentos dos colaboradores', 'Falta de acesso', 'Pedir acesso a pasta de documentos dos colaboradores da unidade para troca de documentos', 'VISITA AGOSTO - MP', '2024-08-30', 'Fabio Luiz', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(457, 3, 'Atraso na entrega dos ASO com teste de chumbo e manganes', 'Atraso do fornecedor', 'Entrar em contato com a Fisioergo para melhorarmos o prazo de entrega do exame do chumbo e manganes para não atrasarmos as recisão', 'VISITA AGOSTO - MP', '2025-06-30', 'Fabio Luiz / Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(458, 3, 'Informações Contidas na RC (Insuficientes)', 'Falta Detalhamento RC', 'Realizar a orientação do detalhamento das Informações junto aos solicitantes', 'AUDITORIA 2024', '2025-01-10', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(459, 3, 'Realização de 03 Cotações Para Processo de Compra', 'Não realização de 03 cotações', 'Avaliar se é necessário e viável realizar 03 cotações, e aguardar a revisão do manual de suprimentos para definir novo processo junto a equipe.', 'AUDITORIA 2024', '2025-12-10', 'Thiago Silva / Patricia', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(460, 3, 'Informações Não de acordo com escopo do processo', 'Fora do pdrão de escopo do processo', 'Orientar como deve ser feita a avaliação da necessidade e sempre que necessário elaborar e-mail para área de compras', 'AUDITORIA 2024', '2025-01-10', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(461, 3, 'Validação Jurídica do Contrato', 'Não realizado validação jurídica', 'Realizar a orientação da equipe quanto a necessidade de validação juridica e anexar a mesma no sistema', 'AUDITORIA 2024', '2025-01-10', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(462, 3, 'Colaboradores Sem Crachá', 'Colaborador Não Recebe Crachá Dinex', 'Realizar Confecção Crachás Prórpios Dinex', 'AUDITORIA 2024', '2025-02-15', 'Marcus / Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(463, 3, 'Processo de Abertura de Vagas', 'Formulário Divergente e Sem Assinatura GG e Diretoria', 'Realizar junto ao Renan uma análise de contratação de um controler para a unidade, dividindo as funções com esta nova pessoa e Valquiria', 'AUDITORIA 2024', '2025-02-28', 'Marcus / Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(464, 3, 'Comunicação de Abertura de Vagas - Email', 'Comunicação Não Finalizada', 'Realizar a formalização por e-mail e arquivo destes nos sistema para evidencia em futuras auditorias', 'AUDITORIA 2024', '2025-02-15', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(465, 3, 'Triagem de Currículo Para as Vagas', 'Processo Sem Currículo', 'Realizar a formalização por e-mail e arquivo destes nos sistema para evidencia em futuras auditorias', 'AUDITORIA 2024', '2025-02-15', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(466, 3, 'Envio de Documentação Para DP de Recem Admitidos', 'Não Envio de Documentação', 'Realizar Envio de Documentação digital na pasta para BH', 'AUDITORIA 2024', '2025-04-15', 'Fábio / Pedro / Patricia', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(467, 3, 'Emissão de Contrato de Trabalho', 'Digitalização De documentação', 'Realizar Envio de Documentação digital na pasta para BH', 'AUDITORIA 2024', '2025-04-15', 'Fábio / Pedro / Patricia', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(468, 3, 'Evidências Processo Integração', 'Check List Documentação e Integração', 'Orientar Pedro a realizar Todo Check List Documentos e Intergração', 'AUDITORIA 2024', '2025-02-15', 'Marcus / Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(469, 3, 'Preventivas 250 Horas', 'Não realização de preventivas 250 horas', 'Alinhar e Manter Programação', 'AUDITORIA 2024', '2025-01-01', 'Richard / Victor', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(470, 3, 'Processo de Back Log', 'Levantamento de Back Log', 'Realizar Inspeções diárias e levantar Back Logs', 'AUDITORIA 2024', '2025-01-01', 'Richard / Victor', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(471, 3, 'Processo de Estoque', 'Levantamento de Estoque', 'Levantar Todo o Estoque e Manter Atualizado', 'AUDITORIA 2024', '2025-01-01', 'Richard / Victor', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(472, 3, 'Risco de DF baixo com a PF13', 'PF13 já com um horimetro avançado', 'Avaliar a possibilidade de realizar uma avaliação geral da maquina por parte do Vanderlei / Matheus, para avaliarmos qual a melhor opção para este equipamento.', 'PLANEJAMENTO 2025', '2025-04-15', 'Marcus / André', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(473, 3, 'Falta de certificação do sisema de ignição', 'Falta de capacitação', 'Realizar o treinamento de Sistema de iniciação eletrônica na unidade. 4 Participantes.', 'PLANEJAMENTO 2025', '2025-11-30', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(474, 3, 'Estudo de novas tecnologias de explosivos', 'Falta de planajemento para este ponto', 'Montar um projeto sobre as possibilidade de novas tecnologias na parte de explosivos para avaliar novas possibilidades', 'PLANEJAMENTO 2025', '2025-04-15', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(475, 3, 'Falta de sucessor na unidade', 'Não capacitação de equipe', 'Preparar um sucessor para a linha da gestão da unidade', 'PLANEJAMENTO 2025', '2025-08-15', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(476, 3, 'Dificuldade de desenvolvimento das equipes', 'Não temos um processo de capacitação ativo', 'Alinhar a implantação do manual de Processo de Desenvolvimento Profissional na unidade junto ao gestor', 'REUNIÃO MARÇO', '2025-04-04', 'Alex / Patricia /  João / Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(477, 3, 'Dificuldade de desenvolvimento das equipes', 'Não temos um processo de capacitação ativo', 'Revisar os descritivos de cargos para cada função para que possamos atualizar as avaliações de desempenho que serão utilizadas no processo', 'REUNIÃO EQUIPE', '2025-12-10', 'João / Patricia / Alex', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(478, 3, 'Dificuldade de desenvolvimento das equipes', 'Não temos um processo de capacitação ativo', 'Orientar Marcus e Claudinei sobre o correto processo de aplicação das ferramentas de avaliação de gaps e do formulário de feedback da unidade', 'REUNIÃO EQUIPE', '2025-09-30', 'João / Patricia / Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(479, 3, 'Dificuldade de desenvolvimento das equipes', 'Não temos um processo de capacitação ativo', 'Aplicar os feedbacks e registar as ações no plano de ação da unidade com o arquivamento dos documentos', 'REUNIÃO EQUIPE', '2025-12-10', 'João / Patricia / Marcus', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(480, 3, 'Novas possibilidades Dinex', 'Marcus tem novas ideias de produtos que podem ser ofertados pela Dinex', 'Realizar uma apresentação presencial do Marcus com o Ronaldo para mostrar e alinhar novos possiveis serviços da Dinex.', 'REUNIÃO JUNHO', '2025-08-20', 'Marcus / Ronaldo / Renan', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(481, 3, 'Possibilidade de melhora com custo de material de perfuração', 'Estudo de novo fornecedor de materiais', 'Finalizar avaliação de custos dos materiais de perfuração de Drilco em relação a Sandivick para avaliar qual a melhor opção em relação a custo beneficio.', 'REUNIÃO JUNHO', '2025-12-10', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(482, 3, 'Possibilidade de melhora na produção', 'Rodando 2 turnos apenas', 'Finalizar o estudo de possibilidade de implementação de 3 turnos para todas 24 horas na mina. Inviável devido a sindicato.', 'REUNIÃO JUNHO', '2025-07-30', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(483, 3, 'Falta de lançamento de algumas partes diarias', 'Erro no indicador de alguns equipamentos', 'Corrigir o lançamento das parte diarias que estão desatualizadas para termos os números corretos.', 'REUNIÃO JUNHO', '2025-07-30', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(484, 3, 'Falta de estrutura para receber as nossa quantidade de pessoas.', 'Aumento de equipe', 'Conduzir junto ao cliente a negociação de uma verba para melhoria de estrutura fisica no contrato.', 'REUNIÃO SETEMBRO', '2025-12-15', 'Igor / Marcus', 'PLAN', '', 0, '2026-03-12 12:48:02'),
(485, 3, 'Baixa no resultado financeiro da unidade nos últimos meses', 'Cliente socilitou um aumento de capacidade instalada e não esta disponibilizando condições de produção', 'Realizar um estudo para identificar o deficit de resultado entre orçado e realizado para uma futura negociação com o cliente.', 'REUNIÃO DEZEMBRO', '2026-01-15', 'Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(486, 3, 'PF13 em reforma em BH', 'Demora na entrega do equipamento', 'Definição de prazo de entrega da PF13 que esta em BH, para a liberação da PF09 melhorando a confiabilidade dos equipamentos.', 'REUNIÃO FEVEREIRO', '2026-02-28', 'Marcus', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(487, 3, 'Custo fixo elevado na unidade', 'Baixa produtividade por motivos operacionais do cliente', 'Redução de quadro previsto para o mês de fevereiro (2 auxiliares em fevereiro e 2 em março) (obs. Equipe de manutenção em avaliação para possível redução).', 'REUNIÃO FEVEREIRO', '2026-03-20', 'Marcus', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(488, 3, 'Sem registro de infofmações da manutenção no sistema', 'Sistema em transição, esta em fase de implementação', 'Finalizar a implementação do sistema DMS na unidade.', 'REUNIÃO COMITÊ', '2026-03-20', 'Bruno Correa', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(489, 3, 'Falta de assintura do coordenador no sistema', 'Sistema ainda não permite a assinatura do lider', 'Finalizar a implementação do sistema DMS na unidade, onde o programador esta implantando o campo para assinatura digital.', 'REUNIÃO COMITÊ', '2026-03-20', 'Bruno Correa', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(490, 3, 'Não há processo de apontamento de mecânico no sistema', 'Sistema em transição, esta em fase de implementação', 'Finalizar a implementação do sistema DMS na unidade.', 'REUNIÃO COMITÊ', '2026-03-20', 'Bruno Correa', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(491, 3, 'Programação de 250 horas com falha de registro do prcesso', 'Falta de evidência', 'Alinhar com equipe o registro da inspeção de 250 no sistema', 'REUNIÃO COMITÊ', '2026-02-10', 'Bruno Correa', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(492, 3, 'Falta de registro e acompanhamento das pendencias do Backlog', 'Falta de evidência', 'Alinhar com equipe a necessidade de registrar todas as ações realizadas criando o histórico das ocorrencias, e melhorar o acompanhamento da realização dos backlogs', 'REUNIÃO COMITÊ', '2026-03-20', 'Bruno Correa', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(493, 3, 'Falta de registro da manutenção do martelo da perfuratriz', 'Falta de evidência', 'Alinhar com equipe o registro de manutenção do martelo', 'REUNIÃO COMITÊ', '2026-03-20', 'Bruno Correa', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(494, 3, 'Comunição verbal sobre agendamento de exames', 'Processo com poucos envolvidos', 'Ajustar no manual a obs. De não necessidade nesta unidade de envio formal do agendamento de exame', 'REUNIÃO COMITÊ', '2026-03-20', 'João / patricia', 'PLAN', 'Em Andamento', 0, '2026-03-12 12:48:02'),
(495, 3, 'Falta de compartilhamento da unidade de rede', 'Falta de rede compartilhada', 'Solicitar ao Ti o compartilhamento de rede (ASO e Atestado)', 'REUNIÃO COMITÊ', '2026-02-10', 'Pedro', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(496, 3, 'Enviar termo de codução das caminhonetes ', 'Consientizar e responsabilizar a equipe sobre as normas da empresa para a condução do veiculo', 'Aplicar aos motoristas da unidade o termo de condução de caminhonete/veiculos para aqueles que estão aptos a operar.', 'REUNIÃO COMITÊ', '2026-02-10', 'Patricia/Marcus', 'PLAN', 'Concluído', 0, '2026-03-12 12:48:02'),
(498, 1, 'IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS', 'PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS', 'UTILIZANDO PLACAS DE ACETATO COM OS NOMES (PRODUTOS)', 'AUDITORIA DE 5S', '2022-11-30', 'TODOS', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(499, 1, 'CAIXAS BOX NA RECEPÇÃO E MOVEIS SEM UTILIZAÇÃO', 'INTENS DESNECESSARIOS NA AREA DA RECEPÇÃO', 'PARA AS CAIXAS BOX VALIDAR DOCUMENTOS E VERIFICAR SEU ARQUIVAMENTO E DESTINO SE PARA DESCARTE', 'AUDITORIA DE 5S', '2022-11-30', 'ANGELICA', 'PLAN', 'Concluído', 0, '2026-03-12 13:06:10'),
(500, 1, 'COLOCAR FAIXA INDICATIVA NO BLINDEX SALA DE REUNIÃO', 'PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO', 'COMPRAR FAIXA PRA APLICAÇÃO NO BLINDEX', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(501, 1, 'ORGANIZAR FIAÇÃO DE COMPUTADORES SOBRE AS MESAS', 'PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS', 'UTILIZANDO ASPIRAL PLASTICO PARA EMBUTIR OS FIOS', 'AUDITORIA DE 5S', '2022-11-30', 'CESAR - TI', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(502, 1, 'FALTANDO ESPELHO NAS TOMADAS EM ALGUMAS SALAS', 'MELHORAR ASPECTO VISUAL DA SALA', 'COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS', 'AUDITORIA DE 5S', '2022-11-30', 'CARLUCIO', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(503, 1, 'MANGEIRA JOGADA NO JARDIM', 'PARA MANTER ORGANIZADO A AREA EXTERNA E VIDA UTIL DO PRODUTO SEM DANIFICA-LO COM O USO INADEQUANDO', 'COMPRAR SUPORTE PARA MANTER A MANGEIRA ENROLADA ', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 0, '2026-03-12 13:06:10'),
(504, 1, 'MANUAL DE IDENTIFICAÇÃO CORPORATIVA E MOBILIZAÇÃO', 'FALTA DE ESTRUTURA NO INICIO E PADRONIZAÇÃO DA OBRA', 'ELABORANDO MANUAL COM AS DIRETRIZES ORGANIZACIONAIS E COMPORTAMENTAIS DA EMPRESA', 'AUDITORIA DE 5S', '2026-03-26', 'ALEX', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:06:10'),
(505, 1, 'SALA DE ARQUIVO MORTO', 'ORGANIZAR CAIXAS E DESCARTAR MATERIAL DESNECESSARIO', 'REALIZAR A SELEÇÃO E DESCARTE DOS ITENS DA AREA', 'AUDITORIA DE 5S', '2022-12-20', 'TODOS', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(506, 1, 'SALA DO RH/DEPARTAMENTO PESSOAL', 'PARA RETORNO AO PRESENCIAL E MELHORAR OS PROCESSO DO SETOR', 'DEFINIR ESPAÇO/SALA E COMPRAR MOVEIS PARA AMBIENTE DE TRABALHO', 'AUDITORIA DE 5S', '2022-11-30', 'RONALDO  / GUSTAVO', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(507, 1, 'MUROS E FAXADA DO PREDIO', 'PARA MELHOR IDENTIFICAÇÃO DA EMPRESA', 'REALIZAR PINTURA GERAL', 'AUDITORIA DE 5S', '2022-12-20', 'GUSTAVO', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(508, 1, 'FALTANDO ARMARIO PARA ORGANIZAR PEÇAS', 'PARA CONDICIONAR PEÇAS E EQUIPAMENTOS DO ALMOXARIFADO', 'REALIZAR O PROCESSO DE COMPRAS DAS PRATELEIRAS', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(509, 1, 'FALTA DE ORGANIZA DA AREA DE MANUTENÇÃO DA OFICINA', 'CONCEITO DE 5S NÃO ESTÃO SENDO APLICADO NA AREA', 'APLICAR O PROCESSO DE DESCARTE, ORGANIZAÇÃO E LIMPEZA DA AREA DEFINIDO OS RESPONSAVEIS POR ESSA AÇÃO', 'AUDITORIA DE 5S', '2022-11-30', 'DRUMOND', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(511, 1, 'ESTRUTURA PARA MEDIÇÃO DO TOURNOVER - RH ', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE ', 'CRIANDO PLANILHA TENDO COMO BASE OS DESLIGAMENTOS DO MÊS', 'AUDITORIA DE 5S', '2022-11-03', 'PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:06:10'),
(514, 1, 'CALENDARIO DE PROGRAMAS SOCIAIS', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE ', 'ACOMPANHAR PROCESSOS CUMPRIMENTO DO CALENDÁRIO', 'AUDITORIA DE 5S', '2022-12-20', 'RONALDO / PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:06:10'),
(516, 1, 'MODELO DE GESTÃO POR INDICADORES', 'FALTA DE INFORMAÇÃO PARA GESTORES TOMAREM DECISÕES', 'ALINHAR PLANILHA DE ORÇAMENTO COM PLANILHA E BI DE ANÁLISE MENSAL DIVIDIDO EM 3 DRE DIFERENTES - 3A, MONJOLINHO E VETORIAL.', 'REUNIÃO ALINHAMENTO', '2023-04-27', 'TIAGO / IGOR', 'PLAN', 'Concluído', 100, '2026-03-12 13:06:10'),
(527, 1, 'Alto volume de compras urgentes e não planejadas', 'Existem alguns serviços que pela complexidade são realizados com fornecedores especificos, não sendo possivel 3 cotações para tudo', 'Criar um processo de fornecedores homologados para alinhamento deste processo. Atualizar os manuais e treinar as equipes.', 'APRESENTAÇÃO AUDITORIAS', '2026-03-30', 'Ramiro / João Pedro', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:06:10'),
(529, 1, 'IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS', 'PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS', 'UTILIZANDO PLACAS DE ACETATO COM OS NOMES (PRODUTOS)', 'AUDITORIA DE 5S', '2022-11-30', 'TODOS', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(530, 1, 'CAIXAS BOX NA RECEPÇÃO E MOVEIS SEM UTILIZAÇÃO', 'INTENS DESNECESSARIOS NA AREA DA RECEPÇÃO', 'PARA AS CAIXAS BOX VALIDAR DOCUMENTOS E VERIFICAR SEU ARQUIVAMENTO E DESTINO SE PARA DESCARTE', 'AUDITORIA DE 5S', '2022-11-30', 'ANGELICA', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(531, 1, 'COLOCAR FAIXA INDICATIVA NO BLINDEX SALA DE REUNIÃO', 'PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO', 'COMPRAR FAIXA PRA APLICAÇÃO NO BLINDEX', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(532, 1, 'ORGANIZAR FIAÇÃO DE COMPUTADORES SOBRE AS MESAS', 'PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS', 'UTILIZANDO ASPIRAL PLASTICO PARA EMBUTIR OS FIOS', 'AUDITORIA DE 5S', '2022-11-30', 'CESAR - TI', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(533, 1, 'FALTANDO ESPELHO NAS TOMADAS EM ALGUMAS SALAS', 'MELHORAR ASPECTO VISUAL DA SALA', 'COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS', 'AUDITORIA DE 5S', '2022-11-30', 'CARLUCIO', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(534, 1, 'MANGEIRA JOGADA NO JARDIM', 'PARA MANTER ORGANIZADO A AREA EXTERNA E VIDA UTIL DO PRODUTO SEM DANIFICA-LO COM O USO INADEQUANDO', 'COMPRAR SUPORTE PARA MANTER A MANGEIRA ENROLADA ', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(535, 1, 'MANUAL DE IDENTIFICAÇÃO CORPORATIVA E MOBILIZAÇÃO', 'FALTA DE ESTRUTURA NO INICIO E PADRONIZAÇÃO DA OBRA', 'ELABORANDO MANUAL COM AS DIRETRIZES ORGANIZACIONAIS E COMPORTAMENTAIS DA EMPRESA', 'AUDITORIA DE 5S', '2026-04-30', 'ALEX', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44'),
(536, 1, 'SALA DE ARQUIVO MORTO', 'ORGANIZAR CAIXAS E DESCARTAR MATERIAL DESNECESSARIO', 'REALIZAR A SELEÇÃO E DESCARTE DOS ITENS DA AREA', 'AUDITORIA DE 5S', '2022-12-20', 'TODOS', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(537, 1, 'SALA DO RH/DEPARTAMENTO PESSOAL', 'PARA RETORNO AO PRESENCIAL E MELHORAR OS PROCESSO DO SETOR', 'DEFINIR ESPAÇO/SALA E COMPRAR MOVEIS PARA AMBIENTE DE TRABALHO', 'AUDITORIA DE 5S', '2022-11-30', 'RONALDO  / GUSTAVO', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(538, 1, 'MUROS E FAXADA DO PREDIO', 'PARA MELHOR IDENTIFICAÇÃO DA EMPRESA', 'REALIZAR PINTURA GERAL', 'AUDITORIA DE 5S', '2022-12-20', 'GUSTAVO', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(539, 1, 'FALTANDO ARMARIO PARA ORGANIZAR PEÇAS', 'PARA CONDICIONAR PEÇAS E EQUIPAMENTOS DO ALMOXARIFADO', 'REALIZAR O PROCESSO DE COMPRAS DAS PRATELEIRAS', 'AUDITORIA DE 5S', '2022-11-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(540, 1, 'FALTA DE ORGANIZA DA AREA DE MANUTENÇÃO DA OFICINA', 'CONCEITO DE 5S NÃO ESTÃO SENDO APLICADO NA AREA', 'APLICAR O PROCESSO DE DESCARTE, ORGANIZAÇÃO E LIMPEZA DA AREA DEFINIDO OS RESPONSAVEIS POR ESSA AÇÃO', 'AUDITORIA DE 5S', '2022-11-30', 'DRUMOND', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(541, 1, 'DIVULVAÇÃO DA NOVA ESTRURURA DA QUALIDADE NA EMPRESA', 'PARA QUE OS PROCESSOS NÃO SE PERCAM NESTA TRANSIÇÃO ENTRE OS DEPARTAMENTOS', 'FORMALIZANDO VIA E-MAIL PARA OS GESTORES E LIDERES OS RESPONSAVEIS PELO COMITE DA QUALIDADE E DO RH', 'AUDITORIA DE 5S', '2022-10-27', 'RONALDO / PATRICIA', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(542, 1, 'ESTRUTURA PARA MEDIÇÃO DO TOURNOVER - RH', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE', 'CRIANDO PLANILHA TENDO COMO BASE OS DESLIGAMENTOS DO MÊS', 'AUDITORIA DE 5S', '2022-11-04', 'PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(543, 1, 'PESQUISA DE SATISFAÇÃO DO CLIENTE', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE ', 'REALIZARA A PESQUIZA NO MÊS DE NOVEMBRO NA UNIDADE CORUMBA', 'AUDITORIA DE 5S', '2022-11-04', 'ALEX', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(544, 1, 'LEVANTAMENTO DE INDICES DE INCIDENTES/ACIDENTES', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE', 'RALIZAR LEVANTAMENTO DE INCIDENTES E ACIDENTES DA UNIDADE NOS ULTIMOS 90DIAS', 'AUDITORIA DE 5S', '2022-11-03', 'PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(545, 1, 'CALENDARIO DE PROGRAMAS SOCIAIS', 'PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE ', 'ACOMPANHAR PROCESSOS CUMPRIMENTO DO CALENDÁRIO', 'AUDITORIA DE 5S', '2022-12-20', 'RONALDO / PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(546, 1, 'MELHORAR REDES SOCIAIS DINEX', 'DIVULGAÇÃO DA EMPRESA', 'REALIZAR REUNIÃO COM EMPRESA DE MARKETING, PARA ALINHAR ESTRATÉGIA DE INSTAGRAM, LINKEDIN E SITE.', 'QUALIDADE', '2023-01-10', 'PATRICIA / GUSTAVO', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(548, 1, 'PROCESSO DE FEEDBACK DA EQUIPE', 'DESENVOLVER AS EQUIPES TÉCNICAMENTE E COMPORTAMENTALMENTE', 'REALIZAR FEEDBACK COM COLABORADORES, PATRICIA, IGOR, ADMILTON E RENAN E PASSAR RELATÓRIO PARA COACH REALIZAR O PROCESSO INDIVIDUAL.', 'REUNIÃO DE ALINHAMENTO', '2023-01-30', 'RONALDO', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(549, 1, 'FALTA DE CALENDÁRIO DE TREINAMENTOS DA QUALIDADE E DO SESMT', 'MELHORAR GERENCIAMENTO DO PLANO DE DESENVOLVIMENTO INDIVIDUAL', 'IMPLANTAR DE FORMA PADRONIZADA O CALENDÁRIO DE TREINAMENTO DE TODAS AS UNIDADES DA DINEX COM FOCO NA ÁREA DE DESENVOLVIMENTO TÉCNICA E COMPORTAMENTAL.', 'VISITA MENSAL', '2023-03-30', 'PATRICIA', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(550, 1, 'FALTA DE CALENDÁRIO DE AUDITORIAS DE 5´S PELA PATRICIA', 'MANTER A ORGANIZAÇÃO DAS ÁREAS', 'CRIAR E IMPLEMENTAR CALENDÁRIOS DE AUDITORIAS DE 5´S DE TODAS AS UNIDADES DA DINEX', 'VISITA MENSAL', '2023-03-30', 'PATRICIA', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(551, 1, 'DEFINIÇÃO DE % DE PREMIO PARA CORUMBÁ', 'FALTA DE METODOLOGIA PARA PAGAMENTO DOS PRÊMIOS DE CORUMBÁ', 'REALIZAR ALINHAMENTO DOS % DE PAGAMENTO DE PRÊMIO PARA IMPLANTAÇÃO DA METODOLOGIA', 'VISITA MENSAL', '2023-02-20', 'ADMILTON / TIAGO', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(552, 1, 'FALTA DE MÉTODO DO PROCESSO DE COMPRAS', 'FALTA DE ALINHAMENTO DO MANUAL COM AS EQUIPES', 'TREINAR TODAS AS UNDIDADE EM RELAÇÃO AS DIRETRIZES DE COMPRAS', 'VISITA MENSAL', '2023-03-30', 'BRUNO BORGES', 'PLAN', 'Concluído', 0, '2026-03-12 13:07:44'),
(554, 1, 'MELHORAR CAPACITAÇÃO TÉCNICA DA PATRICIA EM RELAÇÃO A QUALIDADE', 'FALTA DE EXPERIÊNCIA ANTERIOR', 'REALIZAR TREINAMENTO DE ISO9001', 'VISITA MENSAL - MARÇO', '2023-03-30', 'PATRICIA', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(555, 1, 'REUNIÁO DE AVALIAÇÃO MENSAL DO PAINEL DO PROGRAMA DE QUALIDADE', 'CRIAR MODELO DE GESTÃO A PARTIR DO NIVEL ESTRATÉGICO', 'MENSALMENTE REUNIR COM LIDERES E GESTORES DOS CONTRATOS PARA ALINHAMENTO DOS INDICADORES ESTRATÁGICOS DA QUALIDADE', 'VISITA MENSAL - ABRIL', '2023-05-15', 'RENAN, IGOR E PATRICIA', 'PLAN', 'Concluído', 100, '2026-03-12 13:07:44'),
(556, 1, 'ALTA ROTATIVIDADE DA EQUIPE DE MARACAS / COLOCAR COMO META 1% DE TOUNOUVER', 'Falta de processo de desenvolvimento profissional para gestores reterem suas equipes', 'Realizar processo com Paulo Cezar para avaliar a efetividade sobre a retenção das equipes. Incluir esta aboradagem em nossos treinamentos.', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-02-25', 'Alex Doná / Patricia', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44');
INSERT INTO `pdca_tasks` (`id`, `id_cliente`, `titulo`, `descricao`, `meta_valor`, `meta_unidade`, `prazo`, `responsavel`, `fase`, `status`, `progresso`, `created_at`) VALUES
(558, 1, 'Alto volume de compras urgentes e não planejadas', 'Existem alguns serviços que pela complexidade são realizados com fornecedores especificos, não sendo possivel 3 cotações para tudo', 'Criar um processo de fornecedores homologados para alinhamento deste processo. Atualizar os manuais e treinar as equipes.', 'APRESENTAÇÃO AUDITORIAS', '2026-03-30', 'Ramiro / João Pedro', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44'),
(559, 1, 'Processo de viagens com várias oportunidades de melhorias', 'Feito de forma manual', 'Reescrever o processo de viagens utilizando o novo sistema, e definir o responsável pelo processo como um todo.', 'APRESENTAÇÃO AUDITORIAS', '2026-03-30', 'Leonardo /  Alex Doná / João Pedro', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44'),
(560, 1, 'Dificuldade de comunicação entre as áreas', 'Não há um padrão de comunicação ideal entre as áreas', 'Desenvolver um Treinamento de comunicação com foco no Fluxo de comunicação / cada um usa uma ferramenta. Comunicação assertiva.Fluxo ideal de resolução de problemas', 'APRESENTAÇÃO AUDITORIAS', '2026-04-30', 'Alex Doná', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44'),
(561, 1, 'Falta de controle de orçamento para equipamentos que serão reformados', 'Custos sendo gerados sem controle orçamentário', 'Elaborar um orçamento para definição de equipamentos que serão reformados e teto de custos para as unidades.', 'REUNIÃO FEVEREIRO', '2026-03-15', 'Alex Doná', 'PLAN', 'Em Andamento', 0, '2026-03-12 13:07:44'),
(562, 2, 'FALTA DE CONTROLE DE HORAS EXTRAS', 'MELHORAR PROCESSO DE ACOMPANHAMENTO DE HORAS EXTRAS', 'IMPLEMENTAR CONTROLE DE HORAS EXTRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'Admilton', 'DO', 'Concluído', 100, '2026-03-12 11:48:25'),
(563, 2, 'FALTA DE CONTROLE DE HORAS EXTRAS', 'MELHORAR PROCESSO DE ACOMPANHAMENTO DE HORAS EXTRAS', 'IMPLEMENTAR CONTROLE DE HORAS EXTRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'Admilton', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(564, 2, 'FALTA DE CONTROLE DAS LOCAÇÕES DE EQUIPE', 'FALTA DE CONTROLE DAS LOCAÇÕES DAS PESSOAS NAS OBRAS', 'CRIAR PROCESSO DE VALIDAÇÃO DAS LOCAÇÕES DOS COLABORADORES NAS OBRAS', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(565, 2, 'FALTA DE METODOLOGIA PARA PAGAMENTO DE PREMIO', 'MELHORAR ENGAJAMENTO DOS COLABORADORES', 'CRIAR OS INDICADORES DE MEDIÇÃO PARA PAGAMENTO DE PREMIO', 'AVALIÇÃO  MENSAL / nov', '2022-12-30', 'FÁBIO', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(566, 2, 'FALTA DE ÁREA DE RECEBIMENTO E ORGANIZAÇÃO DO 5´S', 'FALTA DE ORGANIZAÇÃO E 5´S ', 'REORGANIZAÇÃO DAS ÁREAS, ESTOQUE E ANEXOS.', 'AUDITORIA DE ESTOQUE', '2023-12-20', 'PEDRO', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(567, 2, 'Água no bebedouro ADM', 'Manter água disponivel', 'Criar rotina de abastecimento', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(568, 2, 'Conserto do plug da porta de entrada', 'Manter a porta aberta sem bater', 'Instala plug de tranca porta', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(569, 2, 'Colocar sabonete liquido no banheiro masculino', 'Manter sabonete liquido disponivel no banheiro', 'Repor refil', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(570, 2, 'Mato no pátio e pontaletes quebrados no estacionamento', 'Manter pátio limpo e organizado', 'Roçar mato e trocar pontaletes', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(571, 2, 'Cone na portaria ', '', 'Orçar cancela', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(572, 2, 'Arrumação e pintura portaria', 'Correção / Padronização', 'Fazer a pintura e arrumação', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(573, 2, 'Container Anexo', 'Desorganização e sujeira', 'Limpeza e arrumação', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(574, 2, 'Desorganização e sujeito tambores e pontaletes (Monj.)', '', 'Organização e arrumação', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(575, 2, 'Banheiro (Monj.)', 'Padronização / organização e limpeza', 'Limpeza / colocar acrilico entre os mictorios', 'AUDITORIA DE 5´S', '2023-01-30', 'Carolina', 'PLAN', 'Concluído', 0, '2026-03-12 12:01:16'),
(576, 1, 'ELABORAR E APRESENTAR POLITICA DE PREMIAÇÃO/PLR PARA 2026', 'Falta de organização de uma politica de premiação para 2026', 'Criar um modelo de politica de remuneração com base em um modelo de PLR para implementação nos cargos de liderança', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'Patricia, Alex e Renan', 'DO', 'Planejado', 0, '2026-03-26 17:45:57'),
(577, 1, 'CRIAR UM RELATORIO  PREVIO PARA O MOTORISTA RELATAR AO MECANICO A CONDIÇÃO GERAL DO EQUIPAMENTO DE FORMA A GERAR INTERAÇÃO ENTRE AS AREAS', 'Falta de processo de entrega dos equipamentos na manutenção', 'Atualizar os manuais da manutenção, incluindo o item de recebimento do equipamento na oficina', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'Patricia / João Pedro', 'DO', 'Planejado', 0, '2026-03-26 17:48:11'),
(578, 1, 'COM A AUTOMAÇÃO DAS NOVAS PERFURATRIZES PARA MELHORAR A MÃO DE OBRA, INCLUIR NO PROJETO DA ESCOLINHA A INCLUSÃO FEMININA NA OPERAÇÃO DESTES EQUIPAMENTOS', 'Falta de treinamento de operação dos equipamentos', 'Buscar treinamento de operadores para perfuratriz para atualização de conhecimento das equipes', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'Patrícia / André', 'DO', 'Planejado', 0, '2026-03-26 17:48:35'),
(579, 1, 'CANDIDATOS A ESCOLINHA DE MANUTENÇÃO E OPERAÇÃO, DEVERÃO PASSAR POR UM DIAGNOSTICO PREVIO PARA MAPEAR A PREDILEÇÃO DO CANDIDATO AO FUTURO CARGO', 'Falta de mão de obra de manutenção', 'Promover a escola de mecânicos para desenvolvimento profissionais de futuros técnicos para nossa manutenção', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'André', 'DO', 'Planejado', 0, '2026-03-26 17:48:56'),
(580, 1, 'CRIAR CARGO DE TRAINNE DE CANDIDATOS RECEM FORMADO EM NIVEL SUPERIOR COM OBJETIVO DE FORMAR EQUIPES PREPARADAS PARA  SUCESSÃO DE SETORES ESTRATÉGICOS ESTABELECENDO PROCESSO DE ACOMPANHAMENTO JUNTAMENTE COM O GESTOR/COORDENADOR', 'Falta de sucessor para lideres', 'Criar um processo de desenvolvimento de lideres para assumir futuras vagas de liderança na empresa', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'Alex / Patricia', 'DO', 'Planejado', 0, '2026-03-26 17:49:20'),
(581, 1, 'ACRESCENTAR EM ACORDO COLETIVO CLAUSULA QUE POSSIBILITE GERAR AS HE\'s EM DIAS DE FOLGA PARA TREINAMENTOS MINISTRADO PELA EMPRESA E OU TERCEIROS PARA O COLABORADOR', 'Dificuldade de conseguir legaizar as horas extras da unidade e Maracás', 'Alinhar acordo coletivo para autorização de horas extras.', 'REUNIÃO RESUMO ANUAL 2025 RONALDO', '2026-04-30', 'Patricia / Mirian', 'DO', 'Planejado', 0, '2026-03-26 17:50:01'),
(582, 1, 'Finalização de ordens de compra sem a compra de todos os itens da manutenção.', 'Não há um retorno sobre as peças que não foram compradas faltando informação para a unidade sobre os itens que não foram comprados em um único lote', 'Criar um processo de comunicação para quando houver itens pendentes para a programação interna da unidade', 'REUNIÃO TIMÓTEO', '2026-04-30', 'Thiago Silva', 'DO', 'Planejado', 0, '2026-03-26 17:51:01'),
(583, 1, 'Falta de validação técnica dos itens que serão comprados pela área de suprimentos', 'Não estão validando os itens que serão comprados com a obra, antes de fechar os pedidos', 'Cumprir o processo de compras, validando as propostas técnicas quando forem realizadas ordens de compra', 'REUNIÃO TIMÓTEO', '2026-04-30', 'Thiago Silva', 'DO', 'Planejado', 0, '2026-03-26 17:51:26'),
(584, 1, 'Falta de informação na reunião mensal', 'Não utilizado o modelo sugerido', 'Criar um modelo de apresentação e temas para serem tratados na reunião mensal', 'REUNIÃO MARACÁS', '2026-04-30', 'Alex / Patricia', 'DO', 'Planejado', 0, '2026-03-26 17:51:47'),
(585, 1, 'Falta de comunicação do portão com a cozinha no inicio do turno em BH', 'Falta de extensão do interfone', 'Instalar uma extensão do interfone na cozinha', 'Ronaldo', '2026-03-30', 'Gustavo', 'DO', 'Planejado', 0, '2026-03-26 17:53:08'),
(586, 1, 'Falta de proteção nas janelas do banheiro para evitar molhar', 'Banheiros sendo molhados na chuva e janelas expostas ao prédio vizinho', 'Instalação de toldos nas janelas do banheiro', 'Ronaldo', '2026-04-30', 'Gustavo', 'DO', 'Planejado', 0, '2026-03-26 17:54:41'),
(587, 1, 'Porta do banheiro com problemas na massaneta', 'Falta de manutenção', 'Realizar reparo nas portas do banheiro e armários femininos', 'Manutenção', '2026-04-30', 'Gustavo', 'DO', 'Planejado', 0, '2026-03-26 17:56:06'),
(588, 4, 'Falta de informações na apresentação mensal', 'Falta de informações relavantes do dia-a-dia', 'Incluir os pontos mais relevantes da reuniões mensais para que tenhamos mais assertividade na reuniões (manutenção, RH, compras, equipamentos em operação, equipe disponivel).', 'REUNIÃO FEVEREIRO', '2026-04-30', 'Patricia / Renan / Alex / Tiago', 'DO', 'Concluído', 0, '2026-03-26 18:51:36'),
(589, 2, 'Falta de alinhamento da tratativa do valor fixo de 588.172,00', 'Não realizado a cobrança do valor fixo do contrato', 'Realizar o alinhamento da cobrança via suprimentos dos valores que ainda não foram cobrados', 'REUNIÃO MARÇO', '2026-04-20', 'Admilton / Igor', 'DO', 'Planejado', 0, '2026-03-26 19:25:46'),
(590, 2, 'Possibilidade de melhorar a quantidade de furos', 'Melhor rendimento operacional', 'Realizar uma tratativa junto ao cliente para influenciar a melhora na quantidade de furos e metros perfurados no cliente, pois hoje temos uma limitação de 4.000 metros. Tratar com Valter e Cairon.', 'REUNIÃO MARÇO', '2026-04-20', 'Admilton / Harlle', 'DO', 'Planejado', 0, '2026-03-26 19:26:34'),
(591, 2, 'Conceito incorreto de aplicação de indicadores de DF e utilização', 'Falta de alinhamento de conceito de indicadores que estamos utilizando para as medições', 'Apresentar as bases de calculos dos indicadores de DF e utilização', 'REUNIÃO MARÇO', '2026-04-20', 'Admilton / Vinicius', 'DO', 'Planejado', 0, '2026-03-26 19:27:14'),
(592, 2, 'Dificuldade de controlar os materiais que estão sendo enviados da matriz', 'Materiais sendo enviados em prazos fora do prazo dificultando o acompanhamento das compras do mês', 'Monitorar de forma mais dinâmica as compras da unidade para não impactar no resultado geral do mês', 'REUNIÃO MARÇO', '2026-04-20', 'Admilton', 'DO', 'Planejado', 0, '2026-03-26 19:27:49'),
(594, 5, 'Falta de postagens de turmas de treinamentos no insta da Doná', 'Falta de postagens', 'Finalizar postagens das turmas realizadas e ainda não postadas no insta', 'Reunião Mensal', '2026-04-30', 'João Pedro', 'DO', 'Em Andamento', 0, '2026-03-26 20:02:46'),
(595, 5, 'Treinamentos não finalizados', 'Falta de finalização dos scripts de treinamentos', 'Finalizar os scripts de treinamentos de nossa esteira', 'Reunião Mensal', '2026-06-30', 'Alex / Laura', 'DO', 'Planejado', 0, '2026-03-26 20:06:24'),
(596, 5, 'Falta de manuais da Doná', 'Falta de finalização dos manuais', 'Finalizar a produção dos manuais de processos da Doná', 'Reunião Mensal', '2026-04-30', 'João Pedro / Alex', 'DO', 'Planejado', 0, '2026-03-26 20:07:19'),
(597, 5, 'Falta de metodologia para retenção de alunos', 'Falta de ajuste na aplicação dos 6Ds', 'Revisar metodologia 6D em nossos treinamentos', 'Reunião Mensal', '2026-04-30', 'Alex Doná', 'DO', 'Planejado', 0, '2026-03-26 22:14:39'),
(598, 5, 'Melhorar processo comercial', 'Falta de organização comercial para relacionamento', 'OMERCIAL - Realizar lista de contato para ação de no mínimo 4 contatos por semana para fechar 3 atendimentos de constelação por sábado - Melhorar nossa área comercial para ser mais estatégica, colando Laura para participar do processo', 'Reunião Mensal', '2026-04-10', 'Alex Doná / Laura', 'DO', 'Planejado', 0, '2026-03-26 22:15:41'),
(599, 5, 'Falta de treinamento para entregar como bonus a nossos clientes', 'Falta de gravação de conteudo', 'MKT - gravar curso do Eneagrama que montamos com Jessica a base.', 'Reunião Mensal', '2026-05-30', 'Alex Doná', 'DO', 'Planejado', 0, '2026-03-26 22:16:23'),
(600, 5, 'Finalizar manuais de processos Dinex e Madeplant', 'Focar em implementação', 'Finalizar os manuais pendentes da Madeplant e Dinex', 'Reunião Mensal', '2026-04-30', 'Alex Doná', 'DO', 'Planejado', 0, '2026-03-26 22:19:53'),
(601, 5, 'Falta de organização comercial por falta de APP', 'Falta de utilização do CRM', 'Definir qual sistema utilizaremos para realização do nosso CRM', 'Reunião Mensal', '2026-04-10', 'Alex Doná', 'DO', 'Planejado', 0, '2026-03-26 22:20:34'),
(602, 1, 'Adequação NR01', 'Processo será obrigatório a partir de 01/05', 'Avaliar como encaixar os itens da NR01 em relação as ações do Viva+ para adequação as norma com o minimo de inclusão de itens na rotina da empresa.', 'Reunião Mensal', '2026-04-30', 'Patricia / Alex Doná', 'DO', 'Planejado', 0, '2026-03-27 13:23:27'),
(603, 1, 'Falta de processo para o caminhão da Dinex', 'Falta de processo desenvolvido', 'Criar manual de processo para o caminhão da Dinex', 'Reunião Ronaldo', '2026-04-30', 'Patricia / João Pedro', 'DO', 'Planejado', 0, '2026-03-27 13:28:02'),
(604, 1, 'Modelagem de reunião com cronograma dos participantes', 'Evitar perca de tempo dos participantes', 'Criar um cronograma que seja viavel para todos da reunião.', 'Reunião Ronaldo', '2026-04-16', 'Alex / Patricia', 'DO', 'Planejado', 0, '2026-03-27 13:45:24'),
(605, 1, 'Falta de controle dos afastados', 'Falta de controle de colaboradores afastados', 'Revisar auditorias do DP sobre o controle dos afastados. Com pagamento conforme acordo coletivos.', 'Reunião Ronaldo', '2026-04-30', 'Patricia / João Pedro', 'DO', 'Planejado', 0, '2026-03-27 13:59:21'),
(606, 1, 'Revisão de ferramenta para desenvolvimento da reunião de gestão', 'Melhorar analise do relatório', '- Implantar curva ABC para os itens de análise dos relatórios;\r\n- Criar painel de plano de fundo da área de trabalho para acompanhar os indicadores principais nas unidades;\r\n- incluir tv de gestão de indicadores e campanhas nas unidades;\r\n- Painel de equipamentos parados para avaliar os equipamentos que não estão sendo utilizados;\r\n- Equipamentos parados a tantos dias mostrar na tela dos gestores;', 'Reunião Ronaldo', '2026-04-30', 'Patricia', 'DO', 'Planejado', 0, '2026-03-27 14:31:18'),
(607, 6, 'Equipamentos espalhados na frente de serviço', 'Por segurança e limpeza do ambiente', 'Cobrar o terceiro de retirar esses equipamentos \"Sucata\"', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves e Fabiano', 'DO', 'Planejado', 0, '2026-03-30 17:41:02'),
(608, 6, 'Ferramentas desorganizadas na area de vivencia', 'Para garantir a integridade das ferramentas', 'Pedir para o mecânico manter organizado e limpo.', 'Auditoria 01', '2026-04-30', 'Fabiano Tamanho', 'DO', 'Planejado', 0, '2026-03-30 17:43:27'),
(609, 6, 'Banheiro sem condições de uso', 'Para segurança dos colaboradores', 'Ativar os banheiros nos ônibus', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho, Hélcio e Carlos', 'DO', 'Planejado', 0, '2026-03-30 17:46:10'),
(610, 6, 'Oléo, graça, baldes e latões.', 'Porque estava contaminando o solo', 'Realizar o descarte dos itens e a limpeza do solo', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho e Hélcio', 'DO', 'Planejado', 0, '2026-03-30 17:49:57'),
(611, 6, 'Material a ser descartado nas frentes', 'Porque está prejudicando o solo e a organização', 'F4000 irá passar pelas frentes recolhendo todos os materiais', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho, Odair Tonin, Hélcio', 'DO', 'Planejado', 0, '2026-03-30 17:52:07'),
(612, 6, 'Filtros não embalados corretamente', 'Sujeira prejudicando o uso correto', 'Organizar o estoque e proteger os filtros', 'Auditoria 01', '2026-04-30', 'Fabiano Tamanho, Odair Gonçalves e Hélcio', 'DO', 'Planejado', 0, '2026-03-30 17:56:05'),
(613, 6, 'Afiador sujo', 'Falta de orientação', 'Cobrar o afiador de manter o afiador sempre limpo e com a manutenção em dia', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho e Hélcio', 'DO', 'Planejado', 0, '2026-03-30 18:01:15'),
(614, 6, 'Falta de Conhecimento', 'Todos os afiadores novatos', 'Organizar um treinamento com a DRV', 'Auditoria 01', '2026-04-30', 'Odair Toninho', 'DO', 'Planejado', 0, '2026-03-30 18:02:49'),
(615, 6, 'Teto do trailer do afiador', 'Despencou', 'Travar o forro com parafuso', 'Auditoria 01', '2026-04-30', 'Hélcio', 'DO', 'Planejado', 0, '2026-03-30 18:07:38'),
(616, 6, 'Comboio com vazamento e pára-brisa trincado', 'Não conforme', 'Revisar o caminho comboio', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Em Andamento', 0, '2026-03-30 18:10:13'),
(617, 6, 'Não temos a programação do microplanejamento', 'Para garantir', 'Realizar o microplanejamento e disponibilizar aos supervisores e lideres', 'Auditoria 01', '2026-04-30', 'Odair Tonin', 'DO', 'Planejado', 0, '2026-03-30 18:12:36'),
(618, 6, 'Checklist não está sendo preenchido antes do turno', 'Por que não foi implementado o checklist', 'Reunir os operadores e ensinar a utilizar o checklist de manutenção', 'Auditoria 01', '2026-04-20', 'FABIO KUKIEL e Heribelton', 'DO', 'Concluído', 0, '2026-03-30 18:17:02'),
(619, 6, 'Equipamento com antena de dados avariada', 'Não tem comunicação', 'Realizar reparo na starlink', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Concluído', 0, '2026-03-30 18:23:52'),
(620, 6, 'Operador informou que não tinha vidias reservas na operação', 'Ter vidia em disponibilidade.', '1 jogo de vidia por feller no estoque central', 'Auditoria 01', '2026-04-30', 'Paulo Correa', 'DO', 'Planejado', 0, '2026-03-30 18:32:01'),
(621, 6, 'Equipamento com contador de arvores cortadas avariado', 'Não está contando as arvores', 'Realizar a manutenção no equipamento MC252', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Concluído', 0, '2026-03-30 18:36:06'),
(622, 5, 'Pneu fora de condições de uso', 'Risco de segurança', 'Trocar os pneu do skidder', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Planejado', 0, '2026-03-30 18:53:15'),
(623, 5, 'Picador sem parafuso', '', 'Providenciar os parafusos.', 'Auditoria 01', '2026-04-30', 'Fabiano Tamanho', 'DO', 'Planejado', 0, '2026-03-30 19:00:24'),
(624, 5, 'Picador sem parafuso', '', 'Providenciar os parafusos.', 'Auditoria 01', '2026-04-30', 'Fabiano Tamanho', 'DO', 'Planejado', 0, '2026-03-30 19:00:24'),
(625, 5, 'Falta de Padronização em lubrificação', 'Não tem um plano de Lubrificação', 'Criar o Plano de Lubrificação', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Planejado', 0, '2026-03-30 19:01:36'),
(626, 5, 'Desgaste e vazamento de oléo das bielas nas garras', '', 'Substituir as garras por novas', 'Auditoria 01', '2026-04-30', 'FABIO KUKIEL', 'DO', 'Planejado', 0, '2026-03-30 19:06:28'),
(627, 5, 'Latarias amassadas', 'Estão fora do padrão', 'Cobrar a Manutenção de realizar as correções na latarias', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho e Hélcio', 'DO', 'Planejado', 0, '2026-03-30 19:08:00'),
(628, 5, 'Equipe subindo em cima do cavado', 'Risco de segurança', 'Conversar com a equipe para não subir mais nas pilhas de cavaco', 'Auditoria 01', '2026-04-30', 'Odair Gonçalves, Fabiano Tamanho e Hélcio', 'DO', 'Planejado', 0, '2026-03-30 19:11:19'),
(629, 5, 'Não está realizando o Enlonamento após o carregamento', 'Cavaco caindo na estrada', 'Realizar orientação correta aos motoristas, para realizar o enlonamento.', 'Auditoria 01', '2026-04-30', 'Gustavo Lobo e Bruna', 'DO', 'Planejado', 0, '2026-03-30 19:22:30'),
(630, 6, 'Indicadores de DF com conceito com dúvidas', 'Contas não batem as horas de oficina com horas de manutenção', 'Avaliar melhor com Alex Galeano como o indicador esta sendo gerado', 'REUNIÃO MANUTENÇÃO', '2026-04-20', 'FÁBIO KUKIEL', 'DO', 'Planejado', 0, '2026-04-10 13:50:45'),
(631, 6, 'Custo de manutenção elevado devido a mobilização dos equipamentos', 'Lançamento incorreto das conta de mobilização na conta de manutenção', 'Revisar lançamento de contas de mobilização dos equipamentos que foram comprados', 'REUNIÃO MANUTENÇÃO', '2026-04-20', 'FÁBIO KUKIEL', 'DO', 'Planejado', 0, '2026-04-10 14:07:32'),
(632, 6, 'Relatório de manutenção com base incorreta', 'Relatório esta vindo das ordens de compra, e não da entrada de nfs', 'Passar a buscar valores de custo com base nas entradas de nfs', 'REUNIÃO MANUTENÇÃO', '2026-04-20', 'FÁBIO KUKIEL', 'DO', 'Planejado', 0, '2026-04-10 14:14:10'),
(633, 6, 'Falha no processo de custo com pneu', 'Esta sendo lançado o custo por ordem de serviço e não por tipo de pneu (novo ou recapado)', 'Revisar processo de controle de manutenção de pneus para o controle ser feito por pneu dentro do NR', 'REUNIÃO MANUTENÇÃO', '2026-04-30', 'FÁBIO KUKIEL', 'DO', 'Em Andamento', 0, '2026-04-10 14:18:08'),
(634, 6, 'Falta de controle de pneu no sistema', 'Falta de implantação do processo de pneus', 'Priorizar implantação do processo de controle de pneus', 'REUNIÃO DE MANUTENÇÃO', '2026-05-30', 'Fábio Kukeil / Orlando', 'DO', 'Planejado', 0, '2026-04-10 14:27:57'),
(635, 1, 'Falta de braço administrativo no comercial', 'Igor esta sozinho na área comercial, tendo que fazer a parte burocratica do processo', 'Avaliar necessidade de contratar um adm para o comercial', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Igor', 'DO', 'Planejado', 0, '2026-04-16 13:36:36'),
(636, 1, 'Não atingimento de meta de faturamento por conta de desalinhamento dentro do cliente', 'Falta de alinhamento internamento no cliente prejudicando nosso faturamento', 'Apresentar para Marcos da Bemisa o quadro de faturamento atual da Dinex, que esta prejudicado por conta do cliente', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Ronaldo / Renan', 'DO', 'Planejado', 0, '2026-04-16 13:58:35'),
(637, 3, 'Falta de indicador de relação tonelada desmontada por metro perfurada', 'Falta de padrão de medição no indicador', 'Criar um indicador de tonelada gerada por metros perfurados para identificar onde estamos sendo eficientes', 'REUNIÃO ESTRATÉGICA', '2026-01-30', 'Marcus', 'DO', 'Planejado', 0, '2026-04-16 14:21:54'),
(638, 3, 'Falta de alinhamento de acompanhamento de produção pela diretoria', 'Melhorar acompanhamento do Ronaldo em relação a produção', 'Criar rotina de envio de produção das unidades para a diretoria', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Marcus', 'DO', 'Planejado', 0, '2026-04-16 14:31:40'),
(639, 3, 'Horas extras sendo geradas mesmo com baixa produtividade', 'Falta de área para operar durante a semana, tendo que operar em horas extras', 'Estudar um modelo de cobrança de horas extras quando o cliente pedir que seja feita perfuração no final de semana ou com horas extras noturnas', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Marcus / Renan', 'DO', 'Planejado', 0, '2026-04-16 14:54:24'),
(640, 3, 'Escala de trabalho gerando horas extras por não trabalhar aos finais de semana', 'Utilizando turno comercial para produção, gerando horas extras', 'Realizar um estudo mais detalhado sobre a alteração de turno no modelo 4x4', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Renan / Marcus', 'DO', 'Planejado', 0, '2026-04-16 14:57:16'),
(641, 2, 'Estudar outras possibilidades de material de perfuração de outras marcas', 'Encontrar outros fornecedores', 'Realizar teste de performance com a marca INDELBROM', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton', 'DO', 'Planejado', 0, '2026-04-16 17:39:10'),
(642, 3, 'Estudar outras possibilidades de material de perfuração de outras marcas', 'Encontrar outros fornecedores', 'Realizar teste de performance com a marca INDELBROM', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Marcus', 'DO', 'Planejado', 0, '2026-04-16 17:39:40'),
(643, 2, 'Melhorar relatório de desgaste do kit CT67', 'Falta de valores no relatório', 'Incluir no relatório de viabilidade do CT67 o custo por metro perfurado com material de desgaste', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton', 'DO', 'Planejado', 0, '2026-04-16 17:49:25'),
(644, 1, 'Dificuldade de deslocamento das perfuratrizes para manutenção e lavagem', 'Não temos disponível prancha para deslocar o equipamento', 'Estudar a viabilidade de termos prancha ou plataforma para deslocar equipamentos para manutenção', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Renan / Drumond', 'DO', 'Planejado', 0, '2026-04-16 17:58:53'),
(645, 2, 'Continuar testes com CT67', 'Testes parados', 'Continuar com testes do CT 67 até terminar as hastes disponíveis', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admiton', 'DO', 'Planejado', 0, '2026-04-16 18:05:37'),
(646, 2, 'Plano orçamentário superdimensionado para 300.000t', 'Equipe superdimensionado a pedido do cliente', 'Revisar plano orçamentário para 200.000t', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton / Igor', 'DO', 'Planejado', 0, '2026-04-16 18:33:22'),
(647, 2, 'Equipe superdimencionada', 'Turnos de 8 horas, sendo necessário um grande número de colaboradores', 'Realizar um estudo para alteração do regime de horas trabalhadas, reduzindo a quantidade de operadores', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton / Renan / Ronaldo', 'DO', 'Planejado', 0, '2026-04-16 18:42:28'),
(648, 2, 'Produção abaixo do orçado devido ao cliente segurar a produção', 'Não atingimento de meta por conta do cliente', 'Pleitear o resultado negativo realizado por não poder produzir por conta do cliente, gerando um déficit de faturamento de R$ 256.559,85', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton', 'DO', 'Planejado', 0, '2026-04-16 18:48:54'),
(649, 2, 'Excesso de peso nas viagens de ROM', 'Carregamento médio de 51t', 'Reduzir peso médio de carga para 48t', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton', 'DO', 'Planejado', 0, '2026-04-16 19:02:35'),
(650, 2, 'Equipamentos parados na obra não gerando faturamento', 'Equipamentos desmobilizados estacionados na obra', 'Equipamento desmobilizados na obra de Corumbá', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Admilton', 'DO', 'Planejado', 0, '2026-04-16 19:31:24'),
(651, 4, 'Dúvidas na classificação dos incidentes', 'Falta de clareza nos critérios de classificação', 'Alinhar junto ao cliente sobre os critérios de classificação dos PG dos incidentes e acidentes', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Paulo Cezar', 'DO', 'Planejado', 0, '2026-04-16 20:04:43'),
(652, 4, 'Médico do trabalho sem a especialização necessária', 'Médico não especifico', 'Finalizar análise do médico da unidade se ele pode estar na função na unidade', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Paulo Cezar', 'DO', 'Planejado', 0, '2026-04-16 20:08:55'),
(653, 4, 'Capacidade de produção limitada pelo cliente', 'Orçamento limitado pelo cliente', 'Realizar uma reavaliação orçamentária avaliando pessoas e equipamentos para ajustar o custo da operação da unidade', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Paulo Cezar', 'DO', 'Planejado', 0, '2026-04-16 20:21:42'),
(654, 4, 'Falta de padrão de relatório diário de lavra', 'Falta de padronização de modelo', 'Padronizar RDL para todas as unidades da Dinex. (relatório diário de lavra)', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Alex Doná / Patricia', 'DO', 'Planejado', 0, '2026-04-16 21:39:23'),
(655, 4, 'Processo de segurança da mina não padronizado de acordo com a NR22', 'Falta de alinhamento do manual com a NR22', 'Revisar manuais de produção em relação a NR22', 'REUNIÃO ESTRATÉGICA', '2026-05-30', 'Alex Doná / Patricia', 'DO', 'Planejado', 0, '2026-04-16 21:43:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pilares`
--

CREATE TABLE `pilares` (
  `id` int NOT NULL,
  `nome` varchar(80) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pilares`
--

INSERT INTO `pilares` (`id`, `nome`) VALUES
(1, 'Processos'),
(2, 'Gestão'),
(3, 'Pessoas'),
(4, 'Trilha Capacitação');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planoacao_history`
--

CREATE TABLE `planoacao_history` (
  `id` int NOT NULL,
  `item_type` enum('task','action') NOT NULL,
  `item_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` enum('create','update','delete') NOT NULL,
  `changes_json` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `planoacao_history`
--

INSERT INTO `planoacao_history` (`id`, `item_type`, `item_id`, `user_id`, `action_type`, `changes_json`, `created_at`) VALUES
(1, 'task', 1, 1, 'delete', '[]', '2026-02-24 20:27:19'),
(2, 'task', 2, 1, 'create', '{\"id_cliente\":7,\"titulo\":\"Criar módulo do Pilar de Pessoas\",\"descricao\":\"Criar o módulo para incluir as informações de gestão de pessoas\",\"meta_valor\":\"1\",\"meta_unidade\":\"un\",\"prazo\":\"2026-03-05\",\"responsavel\":\"Ozuna\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-24 21:10:39'),
(3, 'task', 3, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Valor considerado para depreciação no orçamento gerou um alto custo da operação.\",\"descricao\":\"POR QUE?\\r\\nAvaliação inicial definido que seria em 36 meses, porém houve um impacto negativo no orçamento gerencial\\r\\nCOMO? (SOLUÇÃO)\\r\\nEstudar qual modelo de depreciação será utilizada para o orçamento gerencial\",\"meta_valor\":\"0\",\"meta_unidade\":\"0\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-24 21:59:13'),
(4, 'task', 3, 4, 'update', '{\"descricao\":{\"old\":\"POR QUE?\\r\\nAvaliação inicial definido que seria em 36 meses, porém houve um impacto negativo no orçamento gerencial\\r\\nCOMO? (SOLUÇÃO)\\r\\nEstudar qual modelo de depreciação será utilizada para o orçamento gerencial\",\"new\":\"Avaliação inicial definido que seria em 36 meses, porém houve um impacto negativo no orçamento gerencial\"},\"meta_valor\":{\"old\":\"0.00\",\"new\":\"Estudar qual modelo de depreciação será utilizada para o orçamento gerencial\"},\"meta_unidade\":{\"old\":\"0\",\"new\":\"Reunião Mensal Fevereiro\"}}', '2026-02-25 13:57:46'),
(5, 'task', 4, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Melhorar interpretação dos relatórios\",\"descricao\":\"Falta de alinhamento das informações que serão apresentadas\",\"meta_valor\":\"Gustavo solicitou que a controladoria sente com todos os responsáveis das áreas para alinhamento e tirar dúvidas dos números que serão apresentados.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 13:59:52'),
(6, 'task', 5, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Custo com serviço de destoca lançamento em conta incorreta\",\"descricao\":\"Conceito de lançamento incorreto\",\"meta_valor\":\"Incluir uma linha para serviços de produção de terceiros para identificar os custos com esses prestadores\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:00:27'),
(7, 'task', 6, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Falta de refenrencia dos valores realizados no mês\",\"descricao\":\"Orçamento traz apenas o realizado, e não tem o orçado\",\"meta_valor\":\"Incluir as colunas dos valores orçados, ajustado e realizado.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:01:09'),
(8, 'task', 7, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Classes de faturamento mescladas no mesmo item\",\"descricao\":\"Faturamento apresentado de modo geral\",\"meta_valor\":\"Detalhar quais classes de faturamento estão dentro da receita bruta.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:01:51'),
(9, 'task', 8, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Muitas variantes em relação ao custo de madeira\",\"descricao\":\"Falta de diretriz para lançamento deste custo\",\"meta_valor\":\"Reavaliar como será lançado o custo com madeira para adequar o custo que esta lançado no orçamento de janeiro.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:02:27'),
(10, 'task', 9, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"DRE Criada por frente, dificultando as análises dos projetos\",\"descricao\":\"Melhorar a divisão dos projetos para facilitar as análises\",\"meta_valor\":\"Criar DRE por projetos para identificação dos pontos que são específicos de cada tipo de operação.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:09:06'),
(11, 'task', 10, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Falta de avaliação do fluxo de caixa para avaliação\",\"descricao\":\"Relatórios foram construídos com base no orçamento gerencial, também vamos precisar de um relatório para avaliação do fluxo de caixa\",\"meta_valor\":\"Criar uma outra análise baseado no fluxo de caixa para identificar qual o resultado liquido necessário que a operação deve gerar para a saúde financeira do negócio.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:09:56'),
(12, 'task', 11, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Custo da logística dentro do DRE sendo lançado como frete e não como custo\",\"descricao\":\"Ajustar para custo e não como receita de transporte\",\"meta_valor\":\"Ajustar a apresentação dos números de receita de logística para custo e não receita. Utilizar mês anterior ao mês de fechamento.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:10:46'),
(13, 'task', 12, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"RMS não trás encargos para avaliação gerencial em relação ao mês anterior\",\"descricao\":\"Falta de pedido de ajuste do sistema\",\"meta_valor\":\"Estudar como o RMS pode gerar relatório financeiro do mês anterior, pois hoje precisa fazer um fechamento manual.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO \\/ GUSTAVO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:15:00'),
(14, 'task', 13, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Melhorar visualização dos relatórios das áreas\",\"descricao\":\"Relatório muito misturados com modelos de medição diferentes\",\"meta_valor\":\"Fazer os painéis por pilar Produção, Logística e Administrativo e criar um painel consolidado para avaliação geral.\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"RODRIGO\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:15:42'),
(15, 'task', 14, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Receita da logística superdimensionada devido a lançamento incorreto no sistema\",\"descricao\":\"Lançamento sendo realizado do valor total da tonelada e não fracionado\",\"meta_valor\":\"Ajustar no RMS a receita da tonada produzida, considerando somente a fatia de receita referente ao transporte e não da receita total por tonelada.\",\"meta_unidade\":\"Bruna\",\"prazo\":\"2026-03-10\",\"responsavel\":\"BRUNA\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:16:36'),
(16, 'task', 15, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Falta de controle da gestão de folga, por estarmos utilizando planilhas para este controle\",\"descricao\":\"Controle de folgas sendo realizado de forma manual\",\"meta_valor\":\"Criar processo de gestão de folga utilizando RMS para uma gestão eficiente de gestão de folga\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"ALEX CRISTALDO \\/ Supervisores\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:17:33'),
(17, 'task', 16, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Custo com corretiva lançadas junto com as manutenções preventivas\",\"descricao\":\"Não esta sendo lançado de forma separada as classes de quebras\",\"meta_valor\":\"Separar do custo de manutenção as falhas que são operacionais para essas serem suportadas pela área de produção\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:18:22'),
(18, 'task', 17, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Alto volume de horas extras pontuais nos departamentos\",\"descricao\":\"Melhorar avaliação das horas extras\",\"meta_valor\":\"Realizar uma avaliação detalhada dos custos com horas extras dentro das áreas\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"GUSTAVO \\/ MARLI \\/ RODRIGO \\/ FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:20:05'),
(19, 'task', 18, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Renan trouxe a demanda de avaliação de ponto ideal de troca de equipamentos, baseado em DF, custo de manutenção e investimento para um novo equipamento.\",\"descricao\":\"Melhorar relatório de ponto de troca de equipamento\",\"meta_valor\":\"Realizar uma avaliação de ponto de ideal de troca de equipamentos\",\"meta_unidade\":\"Reunião Mensal Fevereiro\",\"prazo\":\"2026-03-10\",\"responsavel\":\"FABIO KUKIEL \\/ ODAIR\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-25 14:21:01'),
(20, 'task', 2, 1, 'update', '{\"status\":{\"old\":\"A Fazer\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-02-25 17:24:42'),
(21, 'task', 18, 4, 'update', '{\"status\":{\"old\":\"A Fazer\",\"new\":\"Em Andamento\"}}', '2026-02-26 22:27:36'),
(22, 'task', 18, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"A Fazer\"}}', '2026-02-26 22:27:58'),
(23, 'task', 19, 1, 'create', '{\"id_cliente\":7,\"titulo\":\"Plano de melhoria\",\"descricao\":\"melhorar dados\",\"meta_valor\":\"atuando\",\"meta_unidade\":\"controladoria\",\"prazo\":\"2026-02-28\",\"responsavel\":\"Ozuna\",\"fase\":\"DO\",\"status\":\"A Fazer\",\"progresso\":0}', '2026-02-26 23:01:37'),
(24, 'task', 19, 5, 'update', '{\"status\":{\"old\":\"A Fazer\",\"new\":\"Concluído\"}}', '2026-02-26 23:07:15'),
(25, 'task', 19, 1, 'update', '{\"prazo\":{\"old\":\"2026-02-28\",\"new\":\"2026-01-28\"},\"status\":{\"old\":\"Concluído\",\"new\":\"A Fazer\"}}', '2026-02-26 23:07:19'),
(26, 'task', 4, 5, 'update', '{\"status\":{\"old\":\"A Fazer\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-06 13:08:23'),
(27, 'task', 304, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-03-12 18:00:32'),
(28, 'task', 302, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-03-12 18:00:37'),
(29, 'task', 333, 5, 'update', '{\"descricao\":{\"old\":\"Está vazando lama no acesso \",\"new\":\"Está vazando lama no acesso\"},\"meta_valor\":{\"old\":\"Esudar uma solução e implementa-la \",\"new\":\"Esudar uma solução e implementa-la\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-12 18:00:49'),
(30, 'task', 335, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-12 18:01:01'),
(31, 'task', 540, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:15:57'),
(32, 'task', 533, 5, 'update', '{\"meta_valor\":{\"old\":\"COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS \",\"new\":\"COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:21:54'),
(33, 'task', 537, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:07'),
(34, 'task', 539, 5, 'update', '{\"titulo\":{\"old\":\"FALTANDO ARMARIO PARA ORGANIZAR PEÇAS \",\"new\":\"FALTANDO ARMARIO PARA ORGANIZAR PEÇAS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:17'),
(35, 'task', 529, 5, 'update', '{\"titulo\":{\"old\":\"IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS \",\"new\":\"IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS\"},\"descricao\":{\"old\":\"PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS \",\"new\":\"PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:24'),
(36, 'task', 531, 5, 'update', '{\"descricao\":{\"old\":\"PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO \",\"new\":\"PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:32'),
(37, 'task', 532, 5, 'update', '{\"descricao\":{\"old\":\"PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS \",\"new\":\"PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:43'),
(38, 'task', 509, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:22:52'),
(39, 'task', 508, 5, 'update', '{\"titulo\":{\"old\":\"FALTANDO ARMARIO PARA ORGANIZAR PEÇAS \",\"new\":\"FALTANDO ARMARIO PARA ORGANIZAR PEÇAS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:02'),
(40, 'task', 506, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:09'),
(41, 'task', 502, 5, 'update', '{\"meta_valor\":{\"old\":\"COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS \",\"new\":\"COMPRAR PARA TROCAR OU RECOLOCAR OS ESPELHOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:14'),
(42, 'task', 501, 5, 'update', '{\"descricao\":{\"old\":\"PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS \",\"new\":\"PARA MELHORAR A ORGANIZAÇÃO DOS OBJETOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:19'),
(43, 'task', 500, 5, 'update', '{\"descricao\":{\"old\":\"PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO \",\"new\":\"PARA EVITAR ACIDENTES ONDE PODE SE BATER NO VIDRO\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:27'),
(44, 'task', 498, 5, 'update', '{\"titulo\":{\"old\":\"IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS \",\"new\":\"IDENTIFICAR AS PORTAS DE ARMARIOS E GAVETAS\"},\"descricao\":{\"old\":\"PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS \",\"new\":\"PARA MELHORAR A IDENTIFICAÇÃO DOS OBJETOS\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:42'),
(45, 'task', 538, 5, 'update', '{\"descricao\":{\"old\":\"PARA MELHOR IDENTIFICAÇÃO DA EMPRESA \",\"new\":\"PARA MELHOR IDENTIFICAÇÃO DA EMPRESA\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:23:49'),
(46, 'task', 535, 5, 'update', '{\"prazo\":{\"old\":\"2022-12-20\",\"new\":\"2026-04-30\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:24:18'),
(47, 'task', 536, 5, 'update', '{\"titulo\":{\"old\":\"SALA DE ARQUIVO MORTO \",\"new\":\"SALA DE ARQUIVO MORTO\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:24:30'),
(48, 'task', 504, 5, 'update', '{\"prazo\":{\"old\":\"2022-12-20\",\"new\":\"2026-03-26\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:25:16'),
(49, 'task', 507, 5, 'update', '{\"descricao\":{\"old\":\"PARA MELHOR IDENTIFICAÇÃO DA EMPRESA \",\"new\":\"PARA MELHOR IDENTIFICAÇÃO DA EMPRESA\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:25:27'),
(50, 'task', 505, 5, 'update', '{\"titulo\":{\"old\":\"SALA DE ARQUIVO MORTO \",\"new\":\"SALA DE ARQUIVO MORTO\"},\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:25:33'),
(51, 'task', 546, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:25:48'),
(52, 'task', 515, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:25:58'),
(53, 'task', 510, 5, 'delete', '[]', '2026-03-26 17:26:26'),
(54, 'task', 513, 5, 'delete', '[]', '2026-03-26 17:26:39'),
(55, 'task', 512, 5, 'delete', '[]', '2026-03-26 17:26:51'),
(56, 'task', 549, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:27:20'),
(57, 'task', 553, 5, 'delete', '[]', '2026-03-26 17:27:31'),
(58, 'task', 554, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:27:47'),
(59, 'task', 523, 5, 'delete', '[]', '2026-03-26 17:27:55'),
(60, 'task', 522, 5, 'delete', '[]', '2026-03-26 17:28:16'),
(61, 'task', 520, 5, 'delete', '[]', '2026-03-26 17:28:27'),
(62, 'task', 515, 5, 'delete', '[]', '2026-03-26 17:28:39'),
(63, 'task', 517, 5, 'delete', '[]', '2026-03-26 17:28:49'),
(64, 'task', 521, 5, 'delete', '[]', '2026-03-26 17:29:01'),
(65, 'task', 518, 5, 'delete', '[]', '2026-03-26 17:29:24'),
(66, 'task', 519, 5, 'delete', '[]', '2026-03-26 17:29:44'),
(67, 'task', 547, 5, 'delete', '[]', '2026-03-26 17:29:57'),
(68, 'task', 524, 5, 'delete', '[]', '2026-03-26 17:30:57'),
(69, 'task', 516, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:31:15'),
(70, 'task', 555, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 17:31:28'),
(71, 'task', 556, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:31:41'),
(72, 'task', 525, 5, 'delete', '[]', '2026-03-26 17:31:49'),
(73, 'task', 526, 5, 'delete', '[]', '2026-03-26 17:32:02'),
(74, 'task', 440, 5, 'delete', '[]', '2026-03-26 17:32:13'),
(75, 'task', 528, 5, 'delete', '[]', '2026-03-26 17:32:26'),
(76, 'task', 557, 5, 'delete', '[]', '2026-03-26 17:33:16'),
(77, 'task', 561, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:33:54'),
(78, 'task', 497, 5, 'delete', '[]', '2026-03-26 17:34:08'),
(79, 'task', 558, 5, 'update', '{\"meta_unidade\":{\"old\":\"APRESENTAÇÃO AUDITORIAS \",\"new\":\"APRESENTAÇÃO AUDITORIAS\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:34:33'),
(80, 'task', 559, 5, 'update', '{\"titulo\":{\"old\":\"Procsso de viagens com várias oportunidades de melhorias\",\"new\":\"Processo de viagens com várias oportunidades de melhorias\"},\"meta_valor\":{\"old\":\"Reescrever o processo de viagens utilizando o novo sistema, e definir o responsável pelo processo como um todo. \",\"new\":\"Reescrever o processo de viagens utilizando o novo sistema, e definir o responsável pelo processo como um todo.\"},\"meta_unidade\":{\"old\":\"APRESENTAÇÃO AUDITORIAS \",\"new\":\"APRESENTAÇÃO AUDITORIAS\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:34:47'),
(81, 'task', 527, 5, 'update', '{\"meta_unidade\":{\"old\":\"APRESENTAÇÃO AUDITORIAS \",\"new\":\"APRESENTAÇÃO AUDITORIAS\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:34:52'),
(82, 'task', 560, 5, 'update', '{\"meta_unidade\":{\"old\":\"APRESENTAÇÃO AUDITORIAS \",\"new\":\"APRESENTAÇÃO AUDITORIAS\"},\"status\":{\"old\":\"\",\"new\":\"Em Andamento\"}}', '2026-03-26 17:35:00'),
(83, 'task', 576, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"ELABORAR E APRESENTAR POLITICA DE PREMIAÇÃO\\/PLR PARA 2026\",\"descricao\":\"Falta de organização de uma politica de premiação para 2026\",\"meta_valor\":\"Criar um modelo de politica de remuneração com base em um modelo de PLR para implementação nos cargos de liderança\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:45:57'),
(84, 'task', 577, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"CRIAR UM RELATORIO  PREVIO PARA O MOTORISTA RELATAR AO MECANICO A CONDIÇÃO GERAL DO EQUIPAMENTO DE FORMA A GERAR INTERAÇÃO ENTRE AS AREAS\",\"descricao\":\"Falta de processo de entrega dos equipamentos na manutenção\",\"meta_valor\":\"Atualizar os manuais da manutenção, incluindo o item de recebimento do equipamento na oficina\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:48:11'),
(85, 'task', 578, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"COM A AUTOMAÇÃO DAS NOVAS PERFURATRIZES PARA MELHORAR A MÃO DE OBRA, INCLUIR NO PROJETO DA ESCOLINHA A INCLUSÃO FEMININA NA OPERAÇÃO DESTES EQUIPAMENTOS\",\"descricao\":\"Falta de treinamento de operação dos equipamentos\",\"meta_valor\":\"Buscar treinamento de operadores para perfuratriz para atualização de conhecimento das equipes\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:48:35'),
(86, 'task', 579, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"CANDIDATOS A ESCOLINHA DE MANUTENÇÃO E OPERAÇÃO, DEVERÃO PASSAR POR UM DIAGNOSTICO PREVIO PARA MAPEAR A PREDILEÇÃO DO CANDIDATO AO FUTURO CARGO\",\"descricao\":\"Falta de mão de obra de manutenção\",\"meta_valor\":\"Promover a escola de mecânicos para desenvolvimento profissionais de futuros técnicos para nossa manutenção\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:48:56'),
(87, 'task', 580, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"CRIAR CARGO DE TRAINNE DE CANDIDATOS RECEM FORMADO EM NIVEL SUPERIOR COM OBJETIVO DE FORMAR EQUIPES PREPARADAS PARA  SUCESSÃO DE SETORES ESTRATÉGICOS ESTABELECENDO PROCESSO DE ACOMPANHAMENTO JUNTAMENTE COM O GESTOR\\/COORDENADOR\",\"descricao\":\"Falta de sucessor para lideres\",\"meta_valor\":\"Criar um processo de desenvolvimento de lideres para assumir futuras vagas de liderança na empresa\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:49:20'),
(88, 'task', 581, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"ACRESCENTAR EM ACORDO COLETIVO CLAUSULA QUE POSSIBILITE GERAR AS HE\'s EM DIAS DE FOLGA PARA TREINAMENTOS MINISTRADO PELA EMPRESA E OU TERCEIROS PARA O COLABORADOR\",\"descricao\":\"Dificuldade de conseguir legaizar as horas extras da unidade e Maracás\",\"meta_valor\":\"Alinhar acordo coletivo para autorização de horas extras.\",\"meta_unidade\":\"REUNIÃO RESUMO ANUAL 2025 RONALDO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:50:01'),
(89, 'task', 582, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Finalização de ordens de compra sem a compra de todos os itens da manutenção.\",\"descricao\":\"Não há um retorno sobre as peças que não foram compradas faltando informação para a unidade sobre os itens que não foram comprados em um único lote\",\"meta_valor\":\"Criar um processo de comunicação para quando houver itens pendentes para a programação interna da unidade\",\"meta_unidade\":\"REUNIÃO TIMÓTEO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Thiago Silva\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:51:01'),
(90, 'task', 583, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de validação técnica dos itens que serão comprados pela área de suprimentos\",\"descricao\":\"Não estão validando os itens que serão comprados com a obra, antes de fechar os pedidos\",\"meta_valor\":\"Cumprir o processo de compras, validando as propostas técnicas quando forem realizadas ordens de compra\",\"meta_unidade\":\"REUNIÃO TIMÓTEO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Thiago Silva\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:51:26'),
(91, 'task', 584, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de informação na reunião mensal\",\"descricao\":\"Não utilizado o modelo sugerido\",\"meta_valor\":\"Criar um modelo de apresentação e temas para serem tratados na reunião mensal\",\"meta_unidade\":\"REUNIÃO MARACÁS\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Alex \\/ Patricia\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:51:47'),
(92, 'task', 585, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de comunicação do portão com a cozinha no inicio do turno em BH\",\"descricao\":\"Falta de extensão do interfone\",\"meta_valor\":\"Instalar uma extensão do interfone na cozinha\",\"meta_unidade\":\"Ronaldo\",\"prazo\":\"2026-03-30\",\"responsavel\":\"Gustavo\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:53:08'),
(93, 'task', 586, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de proteção nas janelas do banheiro para evitar molhar\",\"descricao\":\"Banheiros sendo molhados na chuva e janelas expostas ao prédio vizinho\",\"meta_valor\":\"Instalação de toldos nas janelas do banheiro\",\"meta_unidade\":\"Ronaldo\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Gustavo\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:54:41'),
(94, 'task', 587, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Porta do banheiro com problemas na massaneta\",\"descricao\":\"Falta de manutenção\",\"meta_valor\":\"Realizar reparo nas portas do banheiro e armários femininos\",\"meta_unidade\":\"Manutenção\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Gustavo\",\"fase\":\"DO\",\"status\":\"Pendente\",\"progresso\":0}', '2026-03-26 17:56:06'),
(95, 'task', 581, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"Patricia \\/ Mirian\"}}', '2026-03-26 17:57:05'),
(96, 'task', 580, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"Alex \\/ Patricia\"}}', '2026-03-26 17:57:17'),
(97, 'task', 579, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"André\"}}', '2026-03-26 17:57:34'),
(98, 'task', 578, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"Patrícia \\/ André\"}}', '2026-03-26 17:57:46'),
(99, 'task', 577, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"Patricia \\/ João Pedro\"}}', '2026-03-26 17:57:59'),
(100, 'task', 576, 5, 'update', '{\"responsavel\":{\"old\":\"\",\"new\":\"Patricia, Alex e Renan\"}}', '2026-03-26 17:58:09'),
(101, 'task', 585, 1, 'update', '{\"status\":{\"old\":\"Pendente\",\"new\":\"Planejado\"}}', '2026-03-26 18:04:39'),
(102, 'task', 419, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:46:34'),
(103, 'task', 424, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:46:55'),
(104, 'task', 425, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:47:04'),
(105, 'task', 426, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:47:14'),
(106, 'task', 428, 5, 'update', '{\"descricao\":{\"old\":\"Diminuição de custos \",\"new\":\"Diminuição de custos\"},\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:47:26'),
(107, 'task', 433, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:48:04'),
(108, 'task', 410, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:48:18'),
(109, 'task', 436, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:48:25'),
(110, 'task', 430, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:48:30'),
(111, 'task', 427, 5, 'update', '{\"titulo\":{\"old\":\"Aplicar TAG nas caçambas \",\"new\":\"Aplicar TAG nas caçambas\"},\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:48:39'),
(112, 'task', 418, 5, 'update', '{\"descricao\":{\"old\":\"Melhorar apresentação \",\"new\":\"Melhorar apresentação\"},\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:48:46'),
(113, 'task', 416, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:48:53'),
(114, 'task', 411, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:48:57'),
(115, 'task', 434, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:49:04'),
(116, 'task', 437, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:50:15'),
(117, 'task', 438, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Pendente\"}}', '2026-03-26 18:50:21'),
(118, 'task', 439, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Concluído\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 18:50:29'),
(119, 'task', 588, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Falta de informações na apresentação mensal\",\"descricao\":\"Falta de informações relavantes do dia-a-dia\",\"meta_valor\":\"Incluir os pontos mais relevantes da reuniões mensais para que tenhamos mais assertividade na reuniões (manutenção, RH, compras, equipamentos em operação, equipe disponivel).\",\"meta_unidade\":\"REUNIÃO FEVEREIRO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Patricia \\/ Renan \\/ Alex \\/ Tiago\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 18:51:36'),
(120, 'task', 361, 5, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Pendente\"}}', '2026-03-26 18:57:35'),
(121, 'task', 377, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Planejado\"}}', '2026-03-26 18:59:07'),
(122, 'task', 378, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Planejado\"}}', '2026-03-26 18:59:11'),
(123, 'task', 379, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Planejado\"}}', '2026-03-26 18:59:14'),
(124, 'task', 380, 5, 'update', '{\"status\":{\"old\":\"\",\"new\":\"Planejado\"}}', '2026-03-26 18:59:17'),
(125, 'task', 589, 6, 'create', '{\"id_cliente\":2,\"titulo\":\"Falta de alinhamento da tratativa do valor fixo de 588.172,00\",\"descricao\":\"Não realizado a cobrança do valor fixo do contrato\",\"meta_valor\":\"Realizar o alinhamento da cobrança via suprimentos dos valores que ainda não foram cobrados\",\"meta_unidade\":\"REUNIÃO MARÇO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"Admilton \\/ Igor\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 19:25:46'),
(126, 'task', 590, 6, 'create', '{\"id_cliente\":2,\"titulo\":\"Possibilidade de melhorar a quantidade de furos\",\"descricao\":\"Melhor rendimento operacional\",\"meta_valor\":\"Realizar uma tratativa junto ao cliente para influenciar a melhora na quantidade de furos e metros perfurados no cliente, pois hoje temos uma limitação de 4.000 metros. Tratar com Valter e Cairon.\",\"meta_unidade\":\"REUNIÃO MARÇO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"Admilton \\/ Harlle\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 19:26:34'),
(127, 'task', 591, 6, 'create', '{\"id_cliente\":2,\"titulo\":\"Conceito incorreto de aplicação de indicadores de DF e utilização\",\"descricao\":\"Falta de alinhamento de conceito de indicadores que estamos utilizando para as medições\",\"meta_valor\":\"Apresentar as bases de calculos dos indicadores de DF e utilização\",\"meta_unidade\":\"REUNIÃO MARÇO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"Admilton \\/ Vinicius\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 19:27:14'),
(128, 'task', 592, 6, 'create', '{\"id_cliente\":2,\"titulo\":\"Dificuldade de controlar os materiais que estão sendo enviados da matriz\",\"descricao\":\"Materiais sendo enviados em prazos fora do prazo dificultando o acompanhamento das compras do mês\",\"meta_valor\":\"Monitorar de forma mais dinâmica as compras da unidade para não impactar no resultado geral do mês\",\"meta_unidade\":\"REUNIÃO MARÇO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 19:27:49'),
(129, 'task', 593, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de postagens de turmas de treinamentos no insta da Doná\",\"descricao\":\"Falta de postagens\",\"meta_valor\":\"Finalizar postagens das turmas realizadas e ainda não postadas no insta\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"João Pedro\",\"fase\":\"DO\",\"status\":\"Em Andamento\",\"progresso\":0}', '2026-03-26 20:02:41'),
(130, 'task', 594, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de postagens de turmas de treinamentos no insta da Doná\",\"descricao\":\"Falta de postagens\",\"meta_valor\":\"Finalizar postagens das turmas realizadas e ainda não postadas no insta\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"João Pedro\",\"fase\":\"DO\",\"status\":\"Em Andamento\",\"progresso\":0}', '2026-03-26 20:02:46'),
(131, 'task', 593, 5, 'delete', '[]', '2026-03-26 20:03:11'),
(132, 'task', 595, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Treinamentos não finalizados\",\"descricao\":\"Falta de finalização dos scripts de treinamentos\",\"meta_valor\":\"Finalizar os scripts de treinamentos de nossa esteira\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-06-30\",\"responsavel\":\"Alex \\/ Laura\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 20:06:24'),
(133, 'task', 596, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de manuais da Doná\",\"descricao\":\"Falta de finalização dos manuais\",\"meta_valor\":\"Finalizar a produção dos manuais de processos da Doná\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"João Pedro \\/ Alex\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 20:07:19'),
(134, 'task', 541, 1, 'update', '{\"prazo\":{\"old\":\"2022-10-25\",\"new\":\"2022-10-27\"},\"progresso\":{\"old\":0,\"new\":100}}', '2026-03-26 20:17:39'),
(135, 'task', 542, 6, 'update', '{\"titulo\":{\"old\":\"ESTRUTURA PARA MEDIÇÃO DO TOURNOVER - RH \",\"new\":\"ESTRUTURA PARA MEDIÇÃO DO TOURNOVER - RH\"},\"descricao\":{\"old\":\"PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE \",\"new\":\"PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE\"},\"prazo\":{\"old\":\"2022-11-03\",\"new\":\"2022-11-04\"}}', '2026-03-26 20:20:14'),
(136, 'task', 597, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de metodologia para retenção de alunos\",\"descricao\":\"Falta de ajuste na aplicação dos 6Ds\",\"meta_valor\":\"Revisar metodologia 6D em nossos treinamentos\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Alex Doná\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 22:14:39'),
(137, 'task', 598, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Melhorar processo comercial\",\"descricao\":\"Falta de organização comercial para relacionamento\",\"meta_valor\":\"OMERCIAL - Realizar lista de contato para ação de no mínimo 4 contatos por semana para fechar 3 atendimentos de constelação por sábado\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-10\",\"responsavel\":\"Alex Doná \\/ Laura\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 22:15:41'),
(138, 'task', 599, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de treinamento para entregar como bonus a nossos clientes\",\"descricao\":\"Falta de gravação de conteudo\",\"meta_valor\":\"MKT - gravar curso do Eneagrama que montamos com Jessica a base.\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Alex Doná\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 22:16:23'),
(139, 'task', 598, 5, 'update', '{\"meta_valor\":{\"old\":\"OMERCIAL - Realizar lista de contato para ação de no mínimo 4 contatos por semana para fechar 3 atendimentos de constelação por sábado\",\"new\":\"OMERCIAL - Realizar lista de contato para ação de no mínimo 4 contatos por semana para fechar 3 atendimentos de constelação por sábado - Melhorar nossa área comercial para ser mais estatégica, colando Laura para participar do processo\"}}', '2026-03-26 22:17:10'),
(140, 'task', 600, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Finalizar manuais de processos Dinex e Madeplant\",\"descricao\":\"Focar em implementação\",\"meta_valor\":\"Finalizar os manuais pendentes da Madeplant e Dinex\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Alex Doná\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 22:19:53'),
(141, 'task', 601, 5, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de organização comercial por falta de APP\",\"descricao\":\"Falta de utilização do CRM\",\"meta_valor\":\"Definir qual sistema utilizaremos para realização do nosso CRM\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-10\",\"responsavel\":\"Alex Doná\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-26 22:20:34'),
(142, 'task', 544, 6, 'update', '{\"descricao\":{\"old\":\"PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE \",\"new\":\"PARA ATENDIMENTO DO PROCESSO DA GESTÃO DE QUALIDADE\"},\"meta_valor\":{\"old\":\"RALIZAR LEVANTAMENTO DE INCIDENTES E ACIDENTES DA UNIDADE NOS ULTIMOS 90DIAS \",\"new\":\"RALIZAR LEVANTAMENTO DE INCIDENTES E ACIDENTES DA UNIDADE NOS ULTIMOS 90DIAS\"}}', '2026-03-27 11:52:44'),
(143, 'task', 602, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Adequação NR01\",\"descricao\":\"Processo será obrigatório a partir de 01\\/05\",\"meta_valor\":\"Avaliar como encaixar os itens da NR01 em relação as ações do Viva+ para adequação as norma com o minimo de inclusão de itens na rotina da empresa.\",\"meta_unidade\":\"Reunião Mensal\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Patricia \\/ Alex Doná\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-27 13:23:27'),
(144, 'task', 603, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de processo para o caminhão da Dinex\",\"descricao\":\"Falta de processo desenvolvido\",\"meta_valor\":\"Criar manual de processo para o caminhão da Dinex\",\"meta_unidade\":\"Reunião Ronaldo\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Patricia \\/ João Pedro\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-27 13:28:02'),
(145, 'task', 604, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Modelagem de reunião com cronograma dos participantes\",\"descricao\":\"Evitar perca de tempo dos participantes\",\"meta_valor\":\"Criar um cronograma que seja viavel para todos da reunião.\",\"meta_unidade\":\"Reunião Ronaldo\",\"prazo\":\"2026-04-16\",\"responsavel\":\"Alex \\/ Patricia\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-27 13:45:24'),
(146, 'task', 605, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de controle dos afastados\",\"descricao\":\"Falta de controle de colaboradores afastados\",\"meta_valor\":\"Revisar auditorias do DP sobre o controle dos afastados. Com pagamento conforme acordo coletivos.\",\"meta_unidade\":\"Reunião Ronaldo\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Patricia \\/ João Pedro\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-27 13:59:21'),
(147, 'task', 606, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Revisão de ferramenta para desenvolvimento da reunião de gestão\",\"descricao\":\"Melhorar analise do relatório\",\"meta_valor\":\"- Implantar curva ABC para os itens de análise dos relatórios;\\r\\n- Criar painel de plano de fundo da área de trabalho para acompanhar os indicadores principais nas unidades;\\r\\n- incluir tv de gestão de indicadores e campanhas nas unidades;\\r\\n- Painel de equipamentos parados para avaliar os equipamentos que não estão sendo utilizados;\\r\\n- Equipamentos parados a tantos dias mostrar na tela dos gestores;\",\"meta_unidade\":\"Reunião Ronaldo\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Patricia\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-27 14:31:18'),
(148, 'task', 607, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Equipamentos espalhados na frente de serviço\",\"descricao\":\"Por segurança e limpeza do ambiente\",\"meta_valor\":\"Cobrar o terceiro de retirar esses equipamentos \\\"Sucata\\\"\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves e Fabiano\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:41:02'),
(149, 'task', 608, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Ferramentas desorganizadas na area de vivencia\",\"descricao\":\"Para garantir a integridade das ferramentas\",\"meta_valor\":\"Pedir para o mecânico manter organizado e limpo.\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Fabiano Tamanho\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:43:27'),
(150, 'task', 609, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Banheiro sem condições de uso\",\"descricao\":\"Para segurança dos colaboradores\",\"meta_valor\":\"Ativar os banheiros nos ônibus\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho, Hélcio e Carlos\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:46:10'),
(151, 'task', 610, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Oléo, graça, baldes e latões.\",\"descricao\":\"Porque estava contaminando o solo\",\"meta_valor\":\"Realizar o descarte dos itens e a limpeza do solo\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho e Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:49:57'),
(152, 'task', 611, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Material a ser descartado nas frentes\",\"descricao\":\"Porque está prejudicando o solo e a organização\",\"meta_valor\":\"F4000 irá passar pelas frentes recolhendo todos os materiais\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho, Odair Tonin, Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:52:07'),
(153, 'task', 612, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Filtros não embalados corretamente\",\"descricao\":\"Sujeira prejudicando o uso correto\",\"meta_valor\":\"Organizar o estoque e proteger os filtros\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Fabiano Tamanho, Odair Gonçalves e Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 17:56:05'),
(154, 'task', 613, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Afiador sujo\",\"descricao\":\"Falta de orientação\",\"meta_valor\":\"Cobrar o afiador de manter o afiador sempre limpo e com a manutenção em dia\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho e Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:01:15'),
(155, 'task', 614, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Falta de Conhecimento\",\"descricao\":\"Todos os afiadores novatos\",\"meta_valor\":\"Organizar um treinamento com a DRV\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Toninho\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:02:49'),
(156, 'task', 615, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Teto do trailer do afiador\",\"descricao\":\"Despencou\",\"meta_valor\":\"Travar o forro com parafuso\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:07:38'),
(157, 'task', 616, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Comboio com vazamento e pára-brisa trincado\",\"descricao\":\"Não conforme\",\"meta_valor\":\"Revisar o caminho comboio\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:10:13'),
(158, 'task', 617, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Não temos a programação do microplanejamento\",\"descricao\":\"Para garantir\",\"meta_valor\":\"Realizar o microplanejamento e disponibilizar aos supervisores e lideres\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Tonin\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:12:36'),
(159, 'task', 618, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Checklist não está sendo preenchido antes do turno\",\"descricao\":\"Por que não foi implementado o checklist\",\"meta_valor\":\"Reunir os operadores e ensinar a utilizar o checklist de manutenção\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-20\",\"responsavel\":\"FABIO KUKIEL e Heribelton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:17:02'),
(160, 'task', 619, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Equipamento com antena de dados avariada\",\"descricao\":\"Não tem comunicação\",\"meta_valor\":\"Realizar reparo na starlink\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:23:52'),
(161, 'task', 620, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Equipamento com contador de arvores cortadas avariado\",\"descricao\":\"Ter vidia em disponibilidade.\",\"meta_valor\":\"1 jogo de vidia por feller no estoque central\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Paulo Correa\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:32:01'),
(162, 'task', 621, 4, 'create', '{\"id_cliente\":6,\"titulo\":\"Equipamento com contador de arvores cortadas avariado\",\"descricao\":\"Não está contando as arvores\",\"meta_valor\":\"Realizar a manutenção no equipamento MC252\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:36:06'),
(163, 'task', 620, 4, 'update', '{\"titulo\":{\"old\":\"Equipamento com contador de arvores cortadas avariado\",\"new\":\"Operador informou que não tinha vidias reservas na operação\"}}', '2026-03-30 18:44:32'),
(164, 'task', 622, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Pneu fora de condições de uso\",\"descricao\":\"Risco de segurança\",\"meta_valor\":\"Trocar os pneu do skidder\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 18:53:15'),
(165, 'task', 623, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Picador sem parafuso\",\"descricao\":\"\",\"meta_valor\":\"Providenciar os parafusos.\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Fabiano Tamanho\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:00:24'),
(166, 'task', 624, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Picador sem parafuso\",\"descricao\":\"\",\"meta_valor\":\"Providenciar os parafusos.\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Fabiano Tamanho\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:00:24'),
(167, 'task', 625, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Falta de Padronização em lubrificação\",\"descricao\":\"Não tem um plano de Lubrificação\",\"meta_valor\":\"Criar o Plano de Lubrificação\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:01:36'),
(168, 'task', 626, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Desgaste e vazamento de oléo das bielas nas garras\",\"descricao\":\"\",\"meta_valor\":\"Substituir as garras por novas\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FABIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:06:28'),
(169, 'task', 627, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Latarias amassadas\",\"descricao\":\"Estão fora do padrão\",\"meta_valor\":\"Cobrar a Manutenção de realizar as correções na latarias\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho e Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:08:00'),
(170, 'task', 628, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Equipe subindo em cima do cavado\",\"descricao\":\"Risco de segurança\",\"meta_valor\":\"Conversar com a equipe para não subir mais nas pilhas de cavaco\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Odair Gonçalves, Fabiano Tamanho e Hélcio\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:11:19'),
(171, 'task', 629, 4, 'create', '{\"id_cliente\":5,\"titulo\":\"Não está realizando o Enlonamento após o carregamento\",\"descricao\":\"Cavaco caindo na estrada\",\"meta_valor\":\"Realizar orientação correta aos motoristas, para realizar o enlonamento.\",\"meta_unidade\":\"Auditoria 01\",\"prazo\":\"2026-04-30\",\"responsavel\":\"Gustavo Lobo e Bruna\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-03-30 19:22:30');
INSERT INTO `planoacao_history` (`id`, `item_type`, `item_id`, `user_id`, `action_type`, `changes_json`, `created_at`) VALUES
(172, 'task', 630, 5, 'create', '{\"id_cliente\":6,\"titulo\":\"Indicadores de DF com conceito com dúvidas\",\"descricao\":\"Contas não batem as horas de oficina com horas de manutenção\",\"meta_valor\":\"Avaliar melhor com Alex Galeano como o indicador esta sendo gerado\",\"meta_unidade\":\"REUNIÃO MANUTENÇÃO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"FÁBIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-10 13:50:45'),
(173, 'task', 631, 5, 'create', '{\"id_cliente\":6,\"titulo\":\"Custo de manutenção elevado devido a mobilização dos equipamentos\",\"descricao\":\"Lançamento incorreto das conta de mobilização na conta de manutenção\",\"meta_valor\":\"Revisar lançamento de contas de mobilização dos equipamentos que foram comprados\",\"meta_unidade\":\"REUNIÃO MANUTENÇÃO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"FÁBIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-10 14:07:32'),
(174, 'task', 632, 5, 'create', '{\"id_cliente\":6,\"titulo\":\"Relatório de manutenção com base incorreta\",\"descricao\":\"Relatório esta vindo das ordens de compra, e não da entrada de nfs\",\"meta_valor\":\"Passar a buscar valores de custo com base nas entradas de nfs\",\"meta_unidade\":\"REUNIÃO MANUTENÇÃO\",\"prazo\":\"2026-04-20\",\"responsavel\":\"FÁBIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-10 14:14:10'),
(175, 'task', 633, 5, 'create', '{\"id_cliente\":6,\"titulo\":\"Falha no processo de custo com pneu\",\"descricao\":\"Esta sendo lançado o custo por ordem de serviço e não por tipo de pneu (novo ou recapado)\",\"meta_valor\":\"Revisar processo de controle de manutenção de pneus para o controle ser feito por pneu dentro do NR\",\"meta_unidade\":\"REUNIÃO MANUTENÇÃO\",\"prazo\":\"2026-04-30\",\"responsavel\":\"FÁBIO KUKIEL\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-10 14:18:08'),
(176, 'task', 634, 5, 'create', '{\"id_cliente\":6,\"titulo\":\"Falta de controle de pneu no sistema\",\"descricao\":\"Falta de implantação do processo de pneus\",\"meta_valor\":\"Priorizar implantação do processo de controle de pneus\",\"meta_unidade\":\"REUNIÃO DE MANUTENÇÃO\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Fábio Kukeil \\/ Orlando\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-10 14:27:57'),
(177, 'task', 340, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 18:41:31'),
(178, 'task', 367, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 18:46:49'),
(179, 'task', 370, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 18:47:15'),
(180, 'task', 368, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 18:47:27'),
(181, 'task', 369, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 18:48:26'),
(182, 'task', 588, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 18:57:27'),
(183, 'task', 375, 4, 'update', '{\"meta_valor\":{\"old\":\"Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\",\"new\":\"Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\\r\\n\\r\\n##CANCELADO##\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 19:03:00'),
(184, 'task', 358, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 19:03:31'),
(185, 'task', 365, 4, 'update', '{\"meta_valor\":{\"old\":\"Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\",\"new\":\"Criar ponto de apoio com torre de apoio e gerador para iluminação da nova área de manutenção da perfuratriz\\r\\n##CANCELADO##\"},\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 19:04:06'),
(186, 'task', 364, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 19:04:36'),
(187, 'task', 374, 4, 'update', '{\"status\":{\"old\":\"Em Andamento\",\"new\":\"Concluído\"}}', '2026-04-15 19:04:47'),
(188, 'task', 344, 4, 'delete', '[]', '2026-04-15 19:06:43'),
(189, 'task', 379, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:08:24'),
(190, 'task', 378, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:09:16'),
(191, 'task', 380, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:11:25'),
(192, 'task', 343, 4, 'delete', '[]', '2026-04-15 19:11:49'),
(193, 'task', 14, 4, 'update', '{\"meta_valor\":{\"old\":\"Ajustar no RMS a receita da tonada produzida, considerando somente a fatia de receita referente ao transporte e não da receita total por tonelada.\",\"new\":\"Ajustar no RMS a receita da tonelada produzida, considerando somente a fatia de receita referente ao transporte e não da receita total por tonelada.\"}}', '2026-04-15 19:46:42'),
(194, 'task', 13, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:53:09'),
(195, 'task', 12, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Pendente\"}}', '2026-04-15 19:54:30'),
(196, 'task', 11, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:54:47'),
(197, 'task', 10, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-15 19:55:29'),
(198, 'task', 9, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-15 19:55:56'),
(199, 'task', 8, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:56:29'),
(200, 'task', 7, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-15 19:57:57'),
(201, 'task', 6, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-15 19:58:13'),
(202, 'task', 5, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:58:32'),
(203, 'task', 3, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 19:59:05'),
(204, 'task', 17, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-15 20:00:57'),
(205, 'task', 15, 4, 'update', '{\"prazo\":{\"old\":\"2026-03-10\",\"new\":\"2026-06-01\"}}', '2026-04-15 20:54:29'),
(206, 'task', 14, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-15 21:30:29'),
(207, 'task', 635, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Falta de braço administrativo no comercial\",\"descricao\":\"Igor esta sozinho na área comercial, tendo que fazer a parte burocratica do processo\",\"meta_valor\":\"Avaliar necessidade de contratar um adm para o comercial\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Igor\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 13:36:36'),
(208, 'task', 18, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-16 13:45:53'),
(209, 'task', 633, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-16 13:50:49'),
(210, 'task', 621, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-16 13:51:31'),
(211, 'task', 619, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-16 13:51:58'),
(212, 'task', 616, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Em Andamento\"}}', '2026-04-16 13:52:12'),
(213, 'task', 618, 4, 'update', '{\"status\":{\"old\":\"Planejado\",\"new\":\"Concluído\"}}', '2026-04-16 13:53:23'),
(214, 'task', 636, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Não atingimento de meta de faturamento por conta de desalinhamento dentro do cliente\",\"descricao\":\"Falta de alinhamento internamento no cliente prejudicando nosso faturamento\",\"meta_valor\":\"Apresentar para Marcos da Bemisa o quadro de faturamento atual da Dinex, que esta prejudicado por conta do cliente\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Ronaldo \\/ Renan\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 13:58:35'),
(215, 'task', 637, 5, 'create', '{\"id_cliente\":3,\"titulo\":\"Falta de indicador de relação tonelada desmontada por metro perfurada\",\"descricao\":\"Falta de padrão de medição no indicador\",\"meta_valor\":\"Criar um indicador de tonelada gerada por metros perfurados para identificar onde estamos sendo eficientes\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-01-30\",\"responsavel\":\"Marcus\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 14:21:54'),
(216, 'task', 638, 5, 'create', '{\"id_cliente\":3,\"titulo\":\"Falta de alinhamento de acompanhamento de produção pela diretoria\",\"descricao\":\"Melhorar acompanhamento do Ronaldo em relação a produção\",\"meta_valor\":\"Criar rotina de envio de produção das unidades para a diretoria\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Marcus\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 14:31:40'),
(217, 'task', 639, 5, 'create', '{\"id_cliente\":3,\"titulo\":\"Horas extras sendo geradas mesmo com baixa produtividade\",\"descricao\":\"Falta de área para operar durante a semana, tendo que operar em horas extras\",\"meta_valor\":\"Estudar um modelo de cobrança de horas extras quando o cliente pedir que seja feita perfuração no final de semana ou com horas extras noturnas\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Marcus \\/ Renan\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 14:54:24'),
(218, 'task', 640, 5, 'create', '{\"id_cliente\":3,\"titulo\":\"Escala de trabalho gerando horas extras por não trabalhar aos finais de semana\",\"descricao\":\"Utilizando turno comercial para produção, gerando horas extras\",\"meta_valor\":\"Realizar um estudo mais detalhado sobre a alteração de turno no modelo 4x4\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Renan \\/ Marcus\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 14:57:16'),
(219, 'task', 641, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Estudar outras possibilidades de material de perfuração de outras marcas\",\"descricao\":\"Encontrar outros fornecedores\",\"meta_valor\":\"Realizar teste de performance com a marca INDELBROM\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 17:39:10'),
(220, 'task', 642, 5, 'create', '{\"id_cliente\":3,\"titulo\":\"Estudar outras possibilidades de material de perfuração de outras marcas\",\"descricao\":\"Encontrar outros fornecedores\",\"meta_valor\":\"Realizar teste de performance com a marca INDELBROM\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Marcus\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 17:39:40'),
(221, 'task', 643, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Melhorar relatório de desgaste do kit CT67\",\"descricao\":\"Falta de valores no relatório\",\"meta_valor\":\"Incluir no relatório de viabilidade do CT67 o custo por metro perfurado com material de desgaste\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 17:49:25'),
(222, 'task', 644, 5, 'create', '{\"id_cliente\":1,\"titulo\":\"Dificuldade de deslocamento das perfuratrizes para manutenção e lavagem\",\"descricao\":\"Não temos disponível prancha para deslocar o equipamento\",\"meta_valor\":\"Estudar a viabilidade de termos prancha ou plataforma para deslocar equipamentos para manutenção\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Renan \\/ Drumond\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 17:58:53'),
(223, 'task', 645, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Continuar testes com CT67\",\"descricao\":\"Testes parados\",\"meta_valor\":\"Continuar com testes do CT 67 até terminar as hastes disponíveis\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admiton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 18:05:37'),
(224, 'task', 646, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Plano orçamentário superdimensionado para 300.000t\",\"descricao\":\"Equipe superdimensionado a pedido do cliente\",\"meta_valor\":\"Revisar plano orçamentário para 200.000t\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton \\/ Igor\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 18:33:22'),
(225, 'task', 647, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Equipe superdimencionada\",\"descricao\":\"Turnos de 8 horas, sendo necessário um grande número de colaboradores\",\"meta_valor\":\"Realizar um estudo para alteração do regime de horas trabalhadas, reduzindo a quantidade de operadores\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton \\/ Renan \\/ Ronaldo\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 18:42:28'),
(226, 'task', 648, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Produção abaixo do orçado devido ao cliente segurar a produção\",\"descricao\":\"Não atingimento de meta por conta do cliente\",\"meta_valor\":\"Pleitear o resultado negativo realizado por não poder produzir por conta do cliente, gerando um déficit de faturamento de R$ 256.559,85\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 18:48:54'),
(227, 'task', 649, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Excesso de peso nas viagens de ROM\",\"descricao\":\"Carregamento médio de 51t\",\"meta_valor\":\"Reduzir peso médio de carga para 48t\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 19:02:35'),
(228, 'task', 650, 5, 'create', '{\"id_cliente\":2,\"titulo\":\"Equipamentos parados na obra não gerando faturamento\",\"descricao\":\"Equipamentos desmobilizados estacionados na obra\",\"meta_valor\":\"Equipamento desmobilizados na obra de Corumbá\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Admilton\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 19:31:24'),
(229, 'task', 651, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Dúvidas na classificação dos incidentes\",\"descricao\":\"Falta de clareza nos critérios de classificação\",\"meta_valor\":\"Alinhar junto ao cliente sobre os critérios de classificação dos PG dos incidentes e acidentes\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Paulo Cezar\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 20:04:43'),
(230, 'task', 652, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Médico do trabalho sem a especialização necessária\",\"descricao\":\"Médico não especifico\",\"meta_valor\":\"Finalizar análise do médico da unidade se ele pode estar na função na unidade\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Paulo Cezar\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 20:08:55'),
(231, 'task', 653, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Capacidade de produção limitada pelo cliente\",\"descricao\":\"Orçamento limitado pelo cliente\",\"meta_valor\":\"Realizar uma reavaliação orçamentária avaliando pessoas e equipamentos para ajustar o custo da operação da unidade\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Paulo Cezar\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 20:21:42'),
(232, 'task', 654, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Falta de padrão de relatório diário de lavra\",\"descricao\":\"Falta de padronização de modelo\",\"meta_valor\":\"Padronizar RDL para todas as unidades da Dinex. (relatório diário de lavra)\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Alex Doná \\/ Patricia\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 21:39:23'),
(233, 'task', 655, 5, 'create', '{\"id_cliente\":4,\"titulo\":\"Processo de segurança da mina não padronizado de acordo com a NR22\",\"descricao\":\"Falta de alinhamento do manual com a NR22\",\"meta_valor\":\"Revisar manuais de produção em relação a NR22\",\"meta_unidade\":\"REUNIÃO ESTRATÉGICA\",\"prazo\":\"2026-05-30\",\"responsavel\":\"Alex Doná \\/ Patricia\",\"fase\":\"DO\",\"status\":\"Planejado\",\"progresso\":0}', '2026-04-16 21:43:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int NOT NULL,
  `nome` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `departamento_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `setores`
--

INSERT INTO `setores` (`id`, `nome`, `departamento_id`) VALUES
(2, 'ADMINISTRATIVO', 1),
(9, 'COMPRAS', 1),
(4, 'CONTABILIDADE', 1),
(7, 'CORPORATIVO', 1),
(1, 'DEPARTAMENTO PESSOAL', 1),
(5, 'FINANCEIRO', 1),
(12, 'GERAL', 1),
(10, 'PEÇAS', 1),
(11, 'QUALIDADE', 1),
(8, 'TI', 1),
(3, 'MANUTENÇÃO', 2),
(6, 'PRODUÇÃO', 2),
(13, 'SESMT', 2),
(14, 'CRIAÇÃO', 3),
(16, 'Arraste', 4),
(18, 'Carregamento', 4),
(15, 'Colheita', 4),
(19, 'Estrutura de apoio', 4),
(17, 'Picagem', 4),
(23, 'Arraste', 5),
(27, 'Carregamento', 5),
(21, 'Colheita', 5),
(25, 'Estrutura de apoio', 5),
(29, 'Picagem', 5),
(22, 'Arraste', 6),
(26, 'Carregamento', 6),
(20, 'Colheita', 6),
(24, 'Estrutura de apoio', 6),
(28, 'Picagem', 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `setor_metricas`
--

CREATE TABLE `setor_metricas` (
  `id` int NOT NULL,
  `setor_id` int NOT NULL,
  `ano_mes` char(7) NOT NULL,
  `total_validas` int NOT NULL DEFAULT '0',
  `total_conforme` int NOT NULL DEFAULT '0',
  `pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `setor_metricas`
--

INSERT INTO `setor_metricas` (`id`, `setor_id`, `ano_mes`, `total_validas`, `total_conforme`, `pct`, `created_at`, `updated_at`) VALUES
(1, 16, '2026-03', 72, 44, 64.29, '2026-03-28 19:05:34', '2026-03-30 00:16:21'),
(6, 19, '2026-03', 30, 2, 5.71, '2026-03-29 21:47:48', '2026-03-29 23:29:21'),
(12, 15, '2026-03', 27, 17, 62.16, '2026-03-29 23:42:25', '2026-03-30 00:10:28'),
(14, 17, '2026-03', 30, 21, 70.00, '2026-03-29 23:49:26', '2026-03-30 00:32:00'),
(15, 18, '2026-03', 23, 12, 51.52, '2026-03-29 23:51:14', '2026-03-30 00:35:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_acesso` enum('instituto','cliente','cliente_admin','reader','consultor') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cliente',
  `id_cliente` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `tipo_acesso`, `id_cliente`) VALUES
(1, 'Admin', 'admin@agencialester.com.br', '$2y$10$lf4BMcVTxLP.XhChjGlRMOb6dT.X4qI6v6cUOiTzOcdR1t3lRoKGy', 'instituto', NULL),
(4, 'João Pedro', 'joao.pedro@donaconsultorias.com.br', '$2y$12$TlMnx2FN6nmQSNaJmyxt4./b9BqQHvKMj9BXYaqhmlbGIY11auy6S', 'instituto', NULL),
(5, 'Alex Doná', 'alex.dona@donaconsultorias.com.br', '$2y$12$pMHqliKRi60ZsJ7VpeMRa.6fFY/0gJxFxngvaO2BYC7eTFX7WcR5u', 'instituto', NULL),
(6, 'Patricia Germana Loureiro', 'patricia@dinex.com.br', '$2y$12$HG3.YX1CKUxujIWyUDzcJeno07YCP8aFYMfAAkdCbDmh1bng0vo9.', 'cliente', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_empresas`
--

CREATE TABLE `usuario_empresas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `origem` enum('direto','herdado') NOT NULL DEFAULT 'direto',
  `permitido` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuario_empresas`
--

INSERT INTO `usuario_empresas` (`id`, `usuario_id`, `cliente_id`, `origem`, `permitido`, `created_at`) VALUES
(17, 6, 1, 'direto', 1, '2026-03-27 11:50:42'),
(18, 6, 2, 'direto', 1, '2026-03-27 11:50:42'),
(19, 6, 3, 'direto', 1, '2026-03-27 11:50:42'),
(20, 6, 4, 'direto', 1, '2026-03-27 11:50:42');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aplicacao_arquivos`
--
ALTER TABLE `aplicacao_arquivos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `aplicacao_colaboradores`
--
ALTER TABLE `aplicacao_colaboradores`
  ADD PRIMARY KEY (`aplicacao_id`,`colaborador_id`);

--
-- Índices de tabela `aplicacao_funcoes`
--
ALTER TABLE `aplicacao_funcoes`
  ADD PRIMARY KEY (`aplicacao_id`,`funcao_id`);

--
-- Índices de tabela `aplicacao_updates`
--
ALTER TABLE `aplicacao_updates`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `aplicacoes`
--
ALTER TABLE `aplicacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_apl_cliente` (`id_cliente`),
  ADD KEY `fk_apl_metodologia` (`id_metodologia`),
  ADD KEY `idx_aplicacoes_id_cliente` (`id_cliente`);

--
-- Índices de tabela `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditorias_cliente` (`cliente_id`),
  ADD KEY `idx_auditorias_setor` (`setor_id`),
  ADD KEY `idx_auditorias_nome` (`nome_auditoria`),
  ADD KEY `idx_auditorias_responsavel` (`responsavel_id`),
  ADD KEY `idx_auditorias_data` (`data_auditoria`);

--
-- Índices de tabela `auditoria_arquivos`
--
ALTER TABLE `auditoria_arquivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aud_arquivos_auditoria` (`auditoria_id`),
  ADD KEY `idx_aud_arquivos_questao` (`questao_id`);

--
-- Índices de tabela `auditoria_avaliacoes`
--
ALTER TABLE `auditoria_avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_auditoria_questao` (`auditoria_id`,`questao_id`),
  ADD KEY `idx_auditoria_avaliacoes_auditoria` (`auditoria_id`),
  ADD KEY `fk_auditoria_avaliacoes_questao` (`questao_id`);

--
-- Índices de tabela `auditoria_avaliacoes_log`
--
ALTER TABLE `auditoria_avaliacoes_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aud_av_log_aud` (`auditoria_id`),
  ADD KEY `idx_aud_av_log_q` (`questao_id`);

--
-- Índices de tabela `auditoria_questoes`
--
ALTER TABLE `auditoria_questoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_questoes_auditoria` (`auditoria_id`);

--
-- Índices de tabela `auditoria_relatorios`
--
ALTER TABLE `auditoria_relatorios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_relatorios_auditoria` (`auditoria_id`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_avaliacoes_cliente_id` (`cliente_id`);

--
-- Índices de tabela `avaliacoes_publicas`
--
ALTER TABLE `avaliacoes_publicas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `uq_avaliacao_publica_avaliacao` (`avaliacao_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clientes_matriz_id` (`matriz_id`);

--
-- Índices de tabela `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `consultores`
--
ALTER TABLE `consultores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_cons_usuario` (`usuario_id`);

--
-- Índices de tabela `cronogramas`
--
ALTER TABLE `cronogramas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cronograma_eventos`
--
ALTER TABLE `cronograma_eventos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dep_unique` (`cliente_id`,`nome`);

--
-- Índices de tabela `funcoes`
--
ALTER TABLE `funcoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `func_unique` (`setor_id`,`nome`);

--
-- Índices de tabela `indicadores`
--
ALTER TABLE `indicadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_indicadores_cliente_id` (`cliente_id`);

--
-- Índices de tabela `metodologias`
--
ALTER TABLE `metodologias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_met_pilar` (`id_pilar`);

--
-- Índices de tabela `pdca_actions`
--
ALTER TABLE `pdca_actions`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pdca_checks`
--
ALTER TABLE `pdca_checks`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pdca_metrics`
--
ALTER TABLE `pdca_metrics`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pdca_tasks`
--
ALTER TABLE `pdca_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pdca_tasks_id_cliente` (`id_cliente`);

--
-- Índices de tabela `pilares`
--
ALTER TABLE `pilares`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `planoacao_history`
--
ALTER TABLE `planoacao_history`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setor_unique` (`departamento_id`,`nome`);

--
-- Índices de tabela `setor_metricas`
--
ALTER TABLE `setor_metricas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_setor_ano_mes` (`setor_id`,`ano_mes`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usr_cliente` (`id_cliente`),
  ADD KEY `idx_usuarios_id_cliente` (`id_cliente`);

--
-- Índices de tabela `usuario_empresas`
--
ALTER TABLE `usuario_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_cliente` (`usuario_id`,`cliente_id`),
  ADD KEY `idx_usuario_empresas_usuario` (`usuario_id`),
  ADD KEY `idx_usuario_empresas_cliente` (`cliente_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aplicacao_arquivos`
--
ALTER TABLE `aplicacao_arquivos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aplicacao_updates`
--
ALTER TABLE `aplicacao_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `aplicacoes`
--
ALTER TABLE `aplicacoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `auditoria_arquivos`
--
ALTER TABLE `auditoria_arquivos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=277;

--
-- AUTO_INCREMENT de tabela `auditoria_avaliacoes`
--
ALTER TABLE `auditoria_avaliacoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1271;

--
-- AUTO_INCREMENT de tabela `auditoria_avaliacoes_log`
--
ALTER TABLE `auditoria_avaliacoes_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `auditoria_questoes`
--
ALTER TABLE `auditoria_questoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=486;

--
-- AUTO_INCREMENT de tabela `auditoria_relatorios`
--
ALTER TABLE `auditoria_relatorios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `avaliacoes_publicas`
--
ALTER TABLE `avaliacoes_publicas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `colaboradores`
--
ALTER TABLE `colaboradores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=770;

--
-- AUTO_INCREMENT de tabela `consultores`
--
ALTER TABLE `consultores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cronogramas`
--
ALTER TABLE `cronogramas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cronograma_eventos`
--
ALTER TABLE `cronograma_eventos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `funcoes`
--
ALTER TABLE `funcoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de tabela `indicadores`
--
ALTER TABLE `indicadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `metodologias`
--
ALTER TABLE `metodologias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pdca_actions`
--
ALTER TABLE `pdca_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pdca_checks`
--
ALTER TABLE `pdca_checks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pdca_metrics`
--
ALTER TABLE `pdca_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pdca_tasks`
--
ALTER TABLE `pdca_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=656;

--
-- AUTO_INCREMENT de tabela `pilares`
--
ALTER TABLE `pilares`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `planoacao_history`
--
ALTER TABLE `planoacao_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `setor_metricas`
--
ALTER TABLE `setor_metricas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuario_empresas`
--
ALTER TABLE `usuario_empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aplicacoes`
--
ALTER TABLE `aplicacoes`
  ADD CONSTRAINT `fk_apl_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_apl_metodologia` FOREIGN KEY (`id_metodologia`) REFERENCES `metodologias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `auditoria_avaliacoes`
--
ALTER TABLE `auditoria_avaliacoes`
  ADD CONSTRAINT `fk_auditoria_avaliacoes_auditoria` FOREIGN KEY (`auditoria_id`) REFERENCES `auditorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_auditoria_avaliacoes_questao` FOREIGN KEY (`questao_id`) REFERENCES `auditoria_questoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `auditoria_questoes`
--
ALTER TABLE `auditoria_questoes`
  ADD CONSTRAINT `fk_auditoria_questoes_auditoria` FOREIGN KEY (`auditoria_id`) REFERENCES `auditorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `avaliacoes_publicas`
--
ALTER TABLE `avaliacoes_publicas`
  ADD CONSTRAINT `fk_avaliacoes_publicas_avaliacao` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_matriz` FOREIGN KEY (`matriz_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `consultores`
--
ALTER TABLE `consultores`
  ADD CONSTRAINT `fk_cons_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `indicadores`
--
ALTER TABLE `indicadores`
  ADD CONSTRAINT `fk_ind_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `metodologias`
--
ALTER TABLE `metodologias`
  ADD CONSTRAINT `fk_met_pilar` FOREIGN KEY (`id_pilar`) REFERENCES `pilares` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usr_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuario_empresas`
--
ALTER TABLE `usuario_empresas`
  ADD CONSTRAINT `fk_usuario_empresas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usuario_empresas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
