-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/07/2023 às 03:32
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `empresas`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `nome` varchar(255) DEFAULT NULL,
  `cnpj` varchar(14) DEFAULT NULL,
  `contato` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `quantidade_por_dia` int(11) NOT NULL DEFAULT 30,
  `hora_inicio` time NOT NULL DEFAULT '08:00:00',
  `hora_fim` time NOT NULL DEFAULT '17:30:00',
  `intervalo_minimo` int(11) NOT NULL DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `quantidade_por_dia`, `hora_inicio`, `hora_fim`, `intervalo_minimo`) VALUES
(1, 30, '08:30:00', '16:30:00', 60);

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_envio`
--

CREATE TABLE `logs_envio` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `cnpj` varchar(14) NOT NULL,
  `numero` varchar(13) DEFAULT NULL,
  `id_mensagem` int(11) DEFAULT NULL,
  `hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `enviado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagem`
--

CREATE TABLE `mensagem` (
  `id` int(11) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `formal` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `mensagem`
--

INSERT INTO `mensagem` (`id`, `mensagem`, `formal`) VALUES
(1, 'E aí, tudo certo? Quer economizar nas ligações de longa distância e mandar muito bem na comunicação com seus clientes? Nosso 0800 e fixo VoIP pode te ajudar nisso! Vamos marcar uma demo gratuita?', 0),
(2, 'Oi, tudo bem? Temos uma solução irada de 0800 e fixo VoIP que vai fazer sua empresa economizar em telefonia e melhorar a comunicação com os clientes. Bora bater um papo sobre isso?', 0),
(3, 'Ei, beleza? Se sua empresa tá precisando economizar em ligações e manter a qualidade, nosso serviço de 0800 e fixo VoIP é a solução perfeita. Vamos marcar uma reunião?', 0),
(4, 'Opa, tudo tranquilo? Oferecemos um serviço de 0800 e fixo VoIP com chamadas de alta qualidade e redução de custos em telefonia. Bora conversar sobre como podemos te ajudar?', 0),
(5, 'E aí, meu consagrado? Seu negócio pode ficar ainda mais show com nosso 0800 e fixo VoIP, oferecendo qualidade e economia nas ligações. Bora marcar uma demo pra você ver como funciona?', 0),
(6, 'Fala, meu rei! Se você tá procurando melhorar a comunicação com os clientes e ainda economizar em telefonia, nosso serviço de 0800 e fixo VoIP é a escolha certa. Topa marcar uma reunião pra bater um papo sobre isso?', 0),
(7, 'E aí, beleza pura? Com nosso serviço de 0800 e fixo VoIP, sua empresa tem chamadas de alta qualidade e redução de custos em telefonia. Bora conversar sobre como podemos ajudar?', 0),
(8, 'Ei, tudo certo por aí? Quer deixar a comunicação da sua empresa ainda mais top e economizar em telefonia? Nosso serviço de 0800 e fixo VoIP é a resposta. Topa uma demo grátis?', 0),
(9, 'Oi, tudo bem contigo? Nosso serviço de 0800 e fixo VoIP é perfeito pra quem quer economizar em telefonia sem perder qualidade nas ligações. Vamos conversar sobre como podemos ajudar?', 0),
(10, 'E aí, tudo beleza? Oferecemos soluções personalizadas de 0800 e fixo VoIP pra atender as necessidades específicas da sua empresa. Topa marcar uma reunião pra entendermos melhor suas necessidades?', 0),
(11, 'Olá! Você sabia que nosso serviço de 0800 e fixo VoIP pode ajudar a sua empresa a economizar em chamadas de longa distância e melhorar a comunicação com os seus clientes? Podemos agendar uma demonstração gratuita para mostrar como podemos ajudar.', 1),
(12, 'Olá! Estamos oferecendo uma solução completa de 0800 e fixo VoIP para sua empresa. Com nossos serviços, você terá uma comunicação mais eficiente, economizando em seus custos de telefonia. Vamos conversar mais sobre isso?', 1),
(13, 'Olá! Nosso serviço de 0800 e fixo VoIP é ideal para empresas que buscam economia e qualidade nas ligações. Podemos agendar uma reunião para entender melhor suas necessidades e oferecer uma solução personalizada.', 1),
(14, 'Olá! Gostaria de apresentar nosso serviço de 0800 e fixo VoIP para sua empresa. Com ele, você terá chamadas de alta qualidade, além de reduzir seus custos em telefonia. Podemos conversar mais sobre isso?', 1),
(15, 'Olá! Seu negócio pode se beneficiar de nosso serviço de 0800 e fixo VoIP, oferecendo qualidade e economia em suas ligações. Podemos agendar uma demonstração para mostrar como podemos ajudar.', 1),
(16, 'Olá! Sabemos que a comunicação é essencial para qualquer empresa e nosso serviço de 0800 e fixo VoIP é a solução perfeita para melhorar a comunicação com seus clientes, reduzindo seus custos em telefonia. Vamos conversar mais sobre isso?', 1),
(17, 'Olá! Temos a solução perfeita para sua empresa em termos de telefonia. Nosso serviço de 0800 e fixo VoIP é a solução ideal para reduzir custos em chamadas de longa distância e melhorar a comunicação com seus clientes. Podemos agendar uma reunião para discutir isso?', 1),
(18, 'Olá! Com nosso serviço de 0800 e fixo VoIP, sua empresa pode desfrutar de chamadas de alta qualidade, além de economizar em custos de telefonia. Podemos agendar uma demonstração gratuita para que você possa ver como isso funciona na prática.', 1),
(19, 'Olá! Se você está procurando uma maneira de economizar em custos de telefonia sem comprometer a qualidade, nosso serviço de 0800 e fixo VoIP é a escolha certa para sua empresa. Podemos conversar mais sobre isso?', 1),
(20, 'Olá! Sabemos que cada empresa tem necessidades diferentes em termos de telefonia, por isso oferecemos soluções personalizadas de 0800 e fixo VoIP para atender às suas necessidades específicas. Podemos agendar uma reunião para entender melhor suas necessidades e oferecer uma solução sob medida para você.', 1),
(26, 'Impulsione suas vendas com Whaticket, VoIP fixo e 0800. Está pronto para comunicar-se de maneira mais eficaz e conquistar mais clientes?', 1),
(27, 'Transforme seus leads em vendas! Whaticket, VoIP fixo e 0800 - as ferramentas que você precisa. Podemos ajudar a melhorar sua comunicação?', 0),
(28, 'Melhore suas vendas com Whaticket para WhatsApp, VoIP fixo e 0800. Pronto para comunicar melhor e vender mais?', 1),
(29, 'Vendas superiores começam com comunicação superior. Que tal experimentar Whaticket, VoIP fixo e 0800 para alcançar seus clientes de maneira mais eficaz?', 1),
(30, 'Acelere suas vendas com Whaticket para WhatsApp, VoIP fixo e 0800. Pronto para melhorar a comunicação e impulsionar seu negócio?', 1),
(31, 'Alcance seus objetivos de vendas com Whaticket, VoIP fixo e 0800. Pronto para melhorar a comunicação e obter melhores resultados?', 1),
(32, 'Maximize suas vendas com Whaticket para WhatsApp, VoIP fixo e 0800. Pronto para comunicar de maneira mais eficaz e vender mais', 1),
(33, 'Otimize suas vendas com Whaticket, VoIP fixo e 0800. Que tal melhorar sua comunicação para um melhor desempenho de vendas?', 1),
(34, 'Aumente suas vendas com Whaticket para WhatsApp, VoIP fixo e 0800. Pronto para uma comunicação superior e resultados superiores?', 1),
(35, 'Impulsione suas vendas com Whaticket, VoIP fixo e 0800. Está pronto para melhorar sua comunicação e ver seus números de vendas dispararem?', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagemservico`
--

CREATE TABLE `mensagemservico` (
  `id_mensagem` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `mensagemservico`
--

INSERT INTO `mensagemservico` (`id_mensagem`, `id_servico`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 2),
(4, 1),
(5, 1),
(5, 2),
(6, 2),
(6, 1),
(7, 2),
(7, 1),
(8, 1),
(8, 2),
(9, 1),
(9, 2),
(10, 1),
(10, 2),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(13, 2),
(14, 1),
(14, 2),
(15, 1),
(15, 2),
(16, 1),
(16, 2),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(19, 1),
(19, 2),
(20, 1),
(20, 2),
(26, 1),
(26, 2),
(26, 4),
(27, 1),
(27, 2),
(27, 4),
(28, 1),
(28, 2),
(28, 4),
(29, 1),
(29, 2),
(29, 4),
(30, 1),
(30, 2),
(30, 4),
(31, 1),
(31, 2),
(31, 4),
(32, 1),
(32, 2),
(32, 4),
(33, 1),
(33, 2),
(33, 4),
(34, 1),
(34, 2),
(34, 4),
(35, 1),
(35, 2),
(35, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `servico`
--

CREATE TABLE `servico` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `servico`
--

INSERT INTO `servico` (`id`, `descricao`) VALUES
(1, 'Fixo VoIP'),
(2, '0800'),
(3, 'PABX'),
(4, 'Plataforma WhatsApp');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs_envio`
--
ALTER TABLE `logs_envio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cnpj_numero` (`cnpj`,`numero`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `logs_envio`
--
ALTER TABLE `logs_envio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
