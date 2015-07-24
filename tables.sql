-- MySQL dump 10.13  Distrib 5.5.9, for linux2.6 (x86_64)
--
-- Host: localhost    Database: virus
-- ------------------------------------------------------
-- Server version	5.5.9

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
-- Table structure for table `virus_queue`
--

DROP TABLE IF EXISTS `virus_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virus_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(2083) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` int(1) NOT NULL DEFAULT '0',
  `ipv4` int(10) unsigned NOT NULL,
  `email` varchar(254) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virus_queue`
--

LOCK TABLES `virus_queue` WRITE;
/*!40000 ALTER TABLE `virus_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `virus_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virus_queue_js`
--

DROP TABLE IF EXISTS `virus_queue_js`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virus_queue_js` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,
  `url` varchar(2083) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`,`status`),
  KEY `parent_2` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virus_queue_js`
--

LOCK TABLES `virus_queue_js` WRITE;
/*!40000 ALTER TABLE `virus_queue_js` DISABLE KEYS */;
/*!40000 ALTER TABLE `virus_queue_js` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virus_result`
--

DROP TABLE IF EXISTS `virus_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virus_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` int(11) NOT NULL,
  `virustotal` text NOT NULL,
  `avdetect` text NOT NULL,
  `domain` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virus_result`
--

LOCK TABLES `virus_result` WRITE;
/*!40000 ALTER TABLE `virus_result` DISABLE KEYS */;
/*!40000 ALTER TABLE `virus_result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virus_result_domain`
--

DROP TABLE IF EXISTS `virus_result_domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virus_result_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `domain` varchar(2083) NOT NULL,
  `avdetect` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domain` (`domain`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virus_result_domain`
--

LOCK TABLES `virus_result_domain` WRITE;
/*!40000 ALTER TABLE `virus_result_domain` DISABLE KEYS */;
/*!40000 ALTER TABLE `virus_result_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `virus_result_js`
--

DROP TABLE IF EXISTS `virus_result_js`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `virus_result_js` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` int(11) NOT NULL,
  `virustotal` text NOT NULL,
  `avdetect` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `virus_result_js`
--

LOCK TABLES `virus_result_js` WRITE;
/*!40000 ALTER TABLE `virus_result_js` DISABLE KEYS */;
/*!40000 ALTER TABLE `virus_result_js` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-07-24 14:15:11
