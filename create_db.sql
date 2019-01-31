--
-- Table structure for table `bwlist`
--

DROP TABLE IF EXISTS `bwlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bwlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field` char(1) NOT NULL COMMENT '4 = IPv4\n6 = IPv6\nH = hostname\nE = HELO-Name\nF = SMTP FROM\nT = SMTP TO',
  `matchtype` char(1) NOT NULL COMMENT 'P = "like" pattern\nR = regex pattern',
  `pattern` varchar(128) NOT NULL COMMENT 'Pattern for string matching',
  `patternnum` bigint(20) DEFAULT '0',
  `patternmask` bigint(20) DEFAULT '0',
  `action` set('B','W','C') DEFAULT NULL COMMENT 'B = Blacklisted\nW = Whitelisted',
  `prio` tinyint(3) unsigned DEFAULT '128',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `filter`
--

DROP TABLE IF EXISTS `filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filter` (
  `userid` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rcpt` varchar(128) DEFAULT NULL,
  `sender` varchar(128) DEFAULT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `headerf` varchar(128) DEFAULT NULL,
  `headerv` varchar(128) DEFAULT NULL,
  `rcpttype` char(1) DEFAULT 'P',
  `sendertype` char(1) DEFAULT 'P',
  `subjecttype` char(1) DEFAULT 'P',
  `headertype` char(1) DEFAULT 'P',
  `headervtype` char(1) DEFAULT NULL,
  `action` set('A','D','F') DEFAULT NULL,
  `resultcode` smallint(6) DEFAULT NULL,
  `xresultcode` char(10) DEFAULT NULL,
  `resultmsg` varchar(128) DEFAULT NULL,
  `prio` tinyint(3) unsigned DEFAULT '128',
  `endts` int(11) DEFAULT '0',
  `forward` varchar(128) DEFAULT '',
  `comment` varchar(1024) DEFAULT '',
  `filtercol` varchar(45) DEFAULT NULL,
  `tag` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`,`userid`),
  KEY `fk_filter_user_idx` (`userid`),
  CONSTRAINT `fk_filter_user` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `timestamp` int(11) NOT NULL,
  `userid` int(11) NOT NULL DEFAULT '0',
  `id` int(11) DEFAULT NULL COMMENT '>0: Personal Rules',
  `from` varchar(128) DEFAULT NULL,
  `to` varchar(128) DEFAULT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `action` char(1) DEFAULT NULL,
  `host` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcpts`
--

DROP TABLE IF EXISTS `rcpts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcpts` (
  `userid` int(11) NOT NULL,
  `rcpt` varchar(128) NOT NULL,
  `rand` int(11) DEFAULT NULL,
  PRIMARY KEY (`userid`,`rcpt`),
  UNIQUE KEY `rcpt_UNIQUE` (`rcpt`),
  CONSTRAINT `fk_rcpts_user1` FOREIGN KEY (`userid`) REFERENCES `user` (`userid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `status` set('A','U','I','D') DEFAULT NULL,
  PRIMARY KEY (`userid`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin','admin@localhost',sha256("admin",256),'A');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;


