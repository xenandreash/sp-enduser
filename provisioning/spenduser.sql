-- MySQL dump 10.13  Distrib 5.5.40, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: spenduser
-- ------------------------------------------------------
-- Server version	5.5.40-0+wheezy1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bwlist`
--

DROP TABLE IF EXISTS `bwlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bwlist` (
  `access` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`access`,`type`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bwlist`
--

LOCK TABLES `bwlist` WRITE;
/*!40000 ALTER TABLE `bwlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `bwlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messagelog`
--

DROP TABLE IF EXISTS `messagelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messagelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(300) DEFAULT NULL,
  `owner_domain` varchar(300) DEFAULT NULL,
  `msgts0` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msgts` int(11) DEFAULT NULL,
  `msgid` varchar(100) DEFAULT NULL,
  `msgactionid` int(11) DEFAULT NULL,
  `msgaction` varchar(50) DEFAULT NULL,
  `msglistener` varchar(100) DEFAULT NULL,
  `msgtransport` varchar(100) DEFAULT NULL,
  `msgsasl` varchar(300) DEFAULT NULL,
  `msgfromserver` varchar(300) DEFAULT NULL,
  `msgfrom` varchar(300) DEFAULT NULL,
  `msgfrom_domain` varchar(300) DEFAULT NULL,
  `msgto` varchar(300) DEFAULT NULL,
  `msgto_domain` varchar(300) DEFAULT NULL,
  `msgsubject` text,
  `score_rpd` decimal(10,5) DEFAULT NULL,
  `score_sa` decimal(10,5) DEFAULT NULL,
  `scores` text,
  `msgdescription` text,
  `serialno` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ind_owner` (`owner`),
  KEY `ind_owner_domain` (`owner_domain`),
  KEY `ind_msgfromserver` (`msgfromserver`),
  KEY `ind_msgfrom` (`msgfrom`),
  KEY `ind_msgfrom_domain` (`msgfrom_domain`),
  KEY `ind_msgto` (`msgto`),
  KEY `ind_msgto_domain` (`msgto_domain`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messagelog`
--

LOCK TABLES `messagelog` WRITE;
/*!40000 ALTER TABLE `messagelog` DISABLE KEYS */;
INSERT INTO `messagelog` (`id`, `owner`, `owner_domain`, `msgts0`, `msgts`, `msgid`, `msgactionid`, `msgaction`, `msglistener`, `msgtransport`, `msgsasl`, `msgfromserver`, `msgfrom`, `msgfrom_domain`, `msgto`, `msgto_domain`, `msgsubject`, `score_rpd`, `score_sa`, `scores`, `msgdescription`, `serialno`) VALUES (1,'admin@example.local','example.local','2014-10-31 09:29:15',1414751584,'d30a4794-60e0-11e4-ac7b-0050569ab446',1,'DELIVER','mailserver:1','mailtransport:1','','10.2.0.151','admin@example.local','example.local','admin@example.local','example.local','Test 1',0.00000,-0.90000,'{\"sa\":{\"ALL_TRUSTED\":\"-1\"},\"rpd\":\"str=0001.0A0B0204.5453572E.0004,ss=1,re=0.000,recu=0.000,reip=0.000,cl=1,cld=1,fgs=0\",\"rpdav\":\"0\",\"kav\":\"\",\"clam\":\"\"}','2.0.0 Ok: queued as B672CFF6E2','25945435'),(2,'admin@example.local','example.local','2014-10-31 09:29:20',1414751589,'ebf0b205-60e0-11e4-ac7b-0050569ab446',1,'DELIVER','mailserver:1','mailtransport:1','','10.2.0.151','admin@example.local','example.local','admin@example.local','example.local','Test 2',0.00000,-0.90000,'{\"sa\":{\"ALL_TRUSTED\":\"-1\"},\"rpd\":\"str=0001.0A0B020B.54535753.0281,ss=1,re=0.000,recu=0.000,reip=0.000,cl=1,cld=1,fgs=0\",\"rpdav\":\"0\",\"kav\":\"\",\"clam\":\"\"}','2.0.0 Ok: queued as B83CDFF6E2','25945435'),(3,'admin@example.local','example.local','2014-10-31 09:29:22',1414751591,'ed273154-60e0-11e4-ac7b-0050569ab446',1,'DELIVER','mailserver:1','mailtransport:1','','10.2.0.151','admin@example.local','example.local','admin@example.local','example.local','Test 3',0.00000,-0.90000,'{\"sa\":{\"ALL_TRUSTED\":\"-1\"},\"rpd\":\"str=0001.0A0B020B.54535755.01EC,ss=1,re=0.000,recu=0.000,reip=0.000,cl=1,cld=1,fgs=0\",\"rpdav\":\"0\",\"kav\":\"\",\"clam\":\"\"}','2.0.0 Ok: queued as AF497FF6E2','25945435'),(4,'admin@example.local','example.local','2014-10-31 09:29:44',1414751613,'fabd17d3-60e0-11e4-ac7b-0050569ab446',1,'DELIVER','mailserver:1','mailtransport:1','','10.2.0.151','admin@example.local','example.local','admin@example.local','example.local','Test 4',0.00000,-0.90000,'{\"sa\":{\"ALL_TRUSTED\":\"-1\"},\"rpd\":\"str=0001.0A0B020B.5453576C.0169,ss=1,re=0.000,recu=0.000,reip=0.000,cl=1,cld=1,fgs=0\",\"rpdav\":\"0\",\"kav\":\"\",\"clam\":\"\"}','2.0.0 Ok: queued as 1445CFF6E2','25945435');
/*!40000 ALTER TABLE `messagelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `username` varchar(128) NOT NULL DEFAULT '',
  `password` text,
  `reset_password_token` text,
  `reset_password_timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_relations`
--

DROP TABLE IF EXISTS `users_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_relations` (
  `username` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `access` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`username`,`type`,`access`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_relations`
--

LOCK TABLES `users_relations` WRITE;
/*!40000 ALTER TABLE `users_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_relations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-10-31 10:34:33
