-- MySQL dump 10.11
--
-- Host: writer.ecashaalm.ept.tss    Database: ldb_mls
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cfe_event`
--

TRUNCATE cfe_event;

--
-- Dumping data for table `cfe_event`
--

INSERT INTO `cfe_event` VALUES ('2008-01-25 09:48:04','2008-01-25 09:48:04',1,'Application','APPLICATION');
INSERT INTO `cfe_event` VALUES ('2008-01-25 09:48:04','2008-01-25 09:48:04',2,'Fund','FUND');
INSERT INTO `cfe_event` VALUES ('2008-01-25 09:48:04','2008-01-25 09:48:04',3,'Application Status Change','APPLICATION_STATUS');
INSERT INTO `cfe_event` VALUES ('2008-01-25 09:48:04','2008-01-25 09:48:04',4,'Add Follow up','FOLLOW_UP');
INSERT INTO `cfe_event` VALUES ('2008-02-06 14:19:57','0000-00-00 00:00:00',5,'Accepted','ACCEPT');
INSERT INTO `cfe_event` VALUES ('2008-02-12 08:24:22','2008-02-12 08:24:22',6,'DeQueued','DEQUEUED');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-04-03 15:16:34
