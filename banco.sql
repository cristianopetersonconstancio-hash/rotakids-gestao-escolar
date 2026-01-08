-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/01/2026 às 17:10
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `van_escolar`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `nome_aluno` varchar(100) NOT NULL,
  `escola` varchar(100) DEFAULT NULL,
  `turno` enum('Manhã','Tarde','Noite','Integral') DEFAULT 'Manhã',
  `nome_responsavel` varchar(100) DEFAULT NULL,
  `cpf_responsavel` varchar(20) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero_endereco` varchar(10) DEFAULT NULL,
  `telefone_responsavel` varchar(20) DEFAULT NULL,
  `status_financeiro` enum('em_dia','inadimplente') DEFAULT 'em_dia',
  `seg_ida` tinyint(1) DEFAULT 1,
  `seg_volta` tinyint(1) DEFAULT 1,
  `ter_ida` tinyint(1) DEFAULT 1,
  `ter_volta` tinyint(1) DEFAULT 1,
  `qua_ida` tinyint(1) DEFAULT 1,
  `qua_volta` tinyint(1) DEFAULT 1,
  `qui_ida` tinyint(1) DEFAULT 1,
  `qui_volta` tinyint(1) DEFAULT 1,
  `sex_ida` tinyint(1) DEFAULT 1,
  `sex_volta` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `valor_mensalidade` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos`
--

INSERT INTO `alunos` (`id`, `motorista_id`, `veiculo_id`, `nome_aluno`, `escola`, `turno`, `nome_responsavel`, `cpf_responsavel`, `cep`, `endereco`, `numero_endereco`, `telefone_responsavel`, `status_financeiro`, `seg_ida`, `seg_volta`, `ter_ida`, `ter_volta`, `qua_ida`, `qua_volta`, `qui_ida`, `qui_volta`, `sex_ida`, `sex_volta`, `data_cadastro`, `valor_mensalidade`) VALUES
(1, 1, 2, 'Anderson', 'Ludgero Braga', 'Noite', 'Marcelo', '123.456.789-12', '13560642', 'Rua Jesuíno de Arruda - São Carlos/SP', '200', '(11) 11111-1111', 'em_dia', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-01-08 10:25:13', 200.00),
(2, 1, 2, 'Ana', 'Ludgero Braga', 'Manhã', 'Paula', '555.555.555-55', '13564-331', 'Avenida Liberdade - São Carlos/SP', '56', '(22) 22222-2222', 'inadimplente', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-01-08 10:26:22', 200.00),
(3, 2, 3, 'Aline', 'Esterina Placo', 'Manhã', 'Celso', '777.777.777-77', '13564-331', 'Avenida Liberdade - São Carlos/SP', '17', '(56) 55441-4444', 'em_dia', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-01-08 10:58:53', 150.00),
(4, 5, 4, 'Marcos', 'Ludgero Braga', 'Manhã', 'Celso', '666.666.666-66', '13564-331', 'Avenida Liberdade - São Carlos/SP', '55', '(14) 22541-4211', 'em_dia', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-01-08 11:53:19', 150.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `bairros`
--

CREATE TABLE `bairros` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `bairros`
--

INSERT INTO `bairros` (`id`, `nome`, `motorista_id`, `ativo`) VALUES
(1, 'Jardim Nova Santa Paula', 1, 1),
(2, 'Jardim São Carlos', 1, 1),
(3, 'Jardim Nova Santa Paula', 2, 1),
(4, 'Jardim Nova Santa Paula', 5, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `classificados_vans`
--

CREATE TABLE `classificados_vans` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` int(11) NOT NULL,
  `lotacao` int(11) DEFAULT 0,
  `cor` varchar(50) DEFAULT NULL,
  `adesivado` tinyint(1) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `contato` varchar(20) NOT NULL,
  `valor` decimal(10,2) DEFAULT 0.00,
  `data_anuncio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `classificados_vans`
--

INSERT INTO `classificados_vans` (`id`, `motorista_id`, `modelo`, `ano`, `lotacao`, `cor`, `adesivado`, `observacoes`, `contato`, `valor`, `data_anuncio`) VALUES
(1, 2, 'Sprinter', 2020, 20, 'Branca', 1, 'Van em bom estado de conservação.', '15331421547', 60000.00, '2026-01-08 11:04:59'),
(2, 1, 'Ducato', 2019, 15, 'Preta', 0, 'Precisa de pequenos reparos na pintura, documentação em ordem, passa na vistoria.', '16991430134', 45000.00, '2026-01-08 11:06:50'),
(3, 5, 'Sprinter', 2019, 15, 'Branca', 0, 'Van em perfeito estado', '15221454874', 30000.00, '2026-01-08 11:47:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `escolas`
--

CREATE TABLE `escolas` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `motorista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escolas`
--

INSERT INTO `escolas` (`id`, `nome`, `motorista_id`) VALUES
(1, 'Dalila Galili', 1),
(2, 'Ludgero Braga', 1),
(3, 'Esterina Placo', 2),
(4, 'Esterina Placo', 1),
(5, 'Ludgero Braga', 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `motoristas`
--

CREATE TABLE `motoristas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motoristas`
--

INSERT INTO `motoristas` (`id`, `nome`, `email`, `telefone`, `senha`, `data_cadastro`) VALUES
(1, 'Cristiano', 'cris@cris.com', '16552143214', '$2y$10$Tx./XXzoRHgRAx2Gc71afuo7QxSb9or9vD4dcxHZMqxlDjc9lFTPe', '2026-01-06 22:29:50'),
(2, 'Carlos', 'carlos@carlos.com', '15331421547', '$2y$10$p8zeHgBdwYL/LTOEzMonwuqRIYTkN.pTGmRJKdjeFUBeG0Vz0zPRm', '2026-01-08 10:54:26'),
(3, 'Ana', 'ana@ana.com', '451112454', '$2y$10$iX7cAoPnRwYpet6b5UdcNOG/MWwsu3XuVNIkv06rsLC.qdFDL95ty', '2026-01-08 11:07:41'),
(4, 'Cleber Luciano', 'cleber@cleber.com', '12445784574', '$2y$10$hLuMc2dzH6tugwVCZbLuPePMyNhwORFZpzmwQ.1nT95g4rN1C3/dm', '2026-01-08 11:38:29'),
(5, 'Amanda', 'amanda@amanda.com', '15221454874', '$2y$10$jRwu/YneXxlajF0byj/c1OY98LFgOXR4l.17TZGhIzapR.qaSfcuG', '2026-01-08 11:42:10');

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista_bairros`
--

CREATE TABLE `motorista_bairros` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `bairro_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motorista_bairros`
--

INSERT INTO `motorista_bairros` (`id`, `motorista_id`, `veiculo_id`, `bairro_id`) VALUES
(12, 1, 1, 2),
(14, 2, 3, 3),
(15, 2, 3, 1),
(17, 5, 4, 4),
(18, 5, 4, 1),
(19, 1, 2, 1),
(20, 1, 2, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista_escolas`
--

CREATE TABLE `motorista_escolas` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `escola_id` int(11) NOT NULL,
  `manha` tinyint(1) DEFAULT 0,
  `tarde` tinyint(1) DEFAULT 0,
  `noite` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motorista_escolas`
--

INSERT INTO `motorista_escolas` (`id`, `motorista_id`, `escola_id`, `manha`, `tarde`, `noite`) VALUES
(5, 1, 1, 1, 0, 0),
(6, 1, 2, 1, 0, 0),
(7, 2, 3, 1, 1, 0),
(8, 1, 4, 1, 1, 0),
(9, 5, 5, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `rota_escolas`
--

CREATE TABLE `rota_escolas` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `escola_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `rota_escolas`
--

INSERT INTO `rota_escolas` (`id`, `motorista_id`, `veiculo_id`, `escola_id`) VALUES
(6, 1, 1, 1),
(8, 1, 2, 2),
(10, 2, 3, 3),
(11, 1, 2, 4),
(12, 5, 4, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `ano` int(4) DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `para_alugar` tinyint(1) DEFAULT 0,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `alugar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `veiculos`
--

INSERT INTO `veiculos` (`id`, `motorista_id`, `modelo`, `placa`, `ano`, `capacidade`, `para_alugar`, `data_cadastro`, `alugar`) VALUES
(1, 1, 'Ducato', 'SYH5M27', 2020, 15, 0, '2026-01-08 09:43:53', 1),
(2, 1, 'sprinter', 'DFD5G31', 2025, 20, 0, '2026-01-08 09:44:55', 0),
(3, 2, 'Ducato', 'FGD5G12', 2008, 15, 0, '2026-01-08 10:56:10', 0),
(4, 5, 'Ducato', 'DFS4R67', 2019, 15, 0, '2026-01-08 11:43:55', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alunos_motorista` (`motorista_id`),
  ADD KEY `fk_alunos_veiculo` (`veiculo_id`);

--
-- Índices de tabela `bairros`
--
ALTER TABLE `bairros`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `classificados_vans`
--
ALTER TABLE `classificados_vans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_classificados_motorista` (`motorista_id`);

--
-- Índices de tabela `escolas`
--
ALTER TABLE `escolas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `motoristas`
--
ALTER TABLE `motoristas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `motorista_bairros`
--
ALTER TABLE `motorista_bairros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mb_motorista` (`motorista_id`),
  ADD KEY `fk_mb_veiculo` (`veiculo_id`),
  ADD KEY `fk_mb_bairro` (`bairro_id`);

--
-- Índices de tabela `motorista_escolas`
--
ALTER TABLE `motorista_escolas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_me_motorista` (`motorista_id`),
  ADD KEY `fk_me_escola` (`escola_id`);

--
-- Índices de tabela `rota_escolas`
--
ALTER TABLE `rota_escolas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_re_motorista` (`motorista_id`),
  ADD KEY `fk_re_veiculo` (`veiculo_id`),
  ADD KEY `fk_re_escola` (`escola_id`);

--
-- Índices de tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_veiculos_motorista` (`motorista_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `bairros`
--
ALTER TABLE `bairros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `classificados_vans`
--
ALTER TABLE `classificados_vans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `escolas`
--
ALTER TABLE `escolas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `motoristas`
--
ALTER TABLE `motoristas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `motorista_bairros`
--
ALTER TABLE `motorista_bairros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `motorista_escolas`
--
ALTER TABLE `motorista_escolas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `rota_escolas`
--
ALTER TABLE `rota_escolas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `fk_alunos_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alunos_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `classificados_vans`
--
ALTER TABLE `classificados_vans`
  ADD CONSTRAINT `fk_classificados_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `motorista_bairros`
--
ALTER TABLE `motorista_bairros`
  ADD CONSTRAINT `fk_mb_bairro` FOREIGN KEY (`bairro_id`) REFERENCES `bairros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mb_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mb_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `motorista_escolas`
--
ALTER TABLE `motorista_escolas`
  ADD CONSTRAINT `fk_me_escola` FOREIGN KEY (`escola_id`) REFERENCES `escolas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_me_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `rota_escolas`
--
ALTER TABLE `rota_escolas`
  ADD CONSTRAINT `fk_re_escola` FOREIGN KEY (`escola_id`) REFERENCES `escolas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_re_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_re_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `veiculos`
--
ALTER TABLE `veiculos`
  ADD CONSTRAINT `fk_veiculos_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
