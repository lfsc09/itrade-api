-- Table structure for table `cf__conta`
--

DROP TABLE IF EXISTS `cf__conta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cf__conta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `banco` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banco` (`banco`,`numero`,`local`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__ativo`
--

DROP TABLE IF EXISTS `rv__ativo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__ativo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `custo` decimal(20,2) NOT NULL,
  `valor_tick` decimal(20,2) NOT NULL,
  `pts_tick` decimal(20,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`,`id_usuario`) USING BTREE,
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__cenario`
--

DROP TABLE IF EXISTS `rv__cenario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__cenario` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_dataset` int(10) unsigned NOT NULL,
  `nome` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`,`id_dataset`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__cenario_obs`
--

DROP TABLE IF EXISTS `rv__cenario_obs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__cenario_obs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_cenario` int(10) unsigned NOT NULL,
  `ref` int(10) unsigned NOT NULL,
  `nome` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_cenario_2` (`id_cenario`,`ref`),
  UNIQUE KEY `id_cenario` (`id_cenario`,`nome`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__dataset`
--

DROP TABLE IF EXISTS `rv__dataset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__dataset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario_criador` int(10) unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` datetime NOT NULL DEFAULT current_timestamp(),
  `situacao` tinyint(4) NOT NULL,
  `tipo` tinyint(4) NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_usuario_criador` (`id_usuario_criador`,`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__dataset__usuario`
--

DROP TABLE IF EXISTS `rv__dataset__usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__dataset__usuario` (
  `id_dataset` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  UNIQUE KEY `id_arcabouco` (`id_dataset`,`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__gerenciamento`
--

DROP TABLE IF EXISTS `rv__gerenciamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__gerenciamento` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`,`id_usuario`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `rv__operacoes`
--

DROP TABLE IF EXISTS `rv__operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rv__operacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_dataset` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  `sequencia` int(10) unsigned NOT NULL,
  `gerenciamento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `ativo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `op` int(10) unsigned NOT NULL,
  `cts` int(10) unsigned NOT NULL,
  `hora` time NOT NULL,
  `erro` tinyint(3) unsigned NOT NULL,
  `resultado` decimal(20,2) NOT NULL COMMENT 'Bruto',
  `retorno_risco` decimal(10,2) NOT NULL,
  `cenario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ref',
  `ativo_custo` decimal(20,2) NOT NULL,
  `ativo_valor_tick` decimal(20,2) NOT NULL,
  `ativo_pts_tick` decimal(20,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_arcabouco` (`id_dataset`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `token`
--

DROP TABLE IF EXISTS `token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `criado_em` datetime NOT NULL,
  `expira_em` datetime NOT NULL,
  `token` varchar(1000) NOT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  `ip` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`) USING HASH,
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
