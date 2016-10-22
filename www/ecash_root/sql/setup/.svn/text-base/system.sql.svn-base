-- MySQL dump 10.10
--
-- Host: db3.clkonline.com    Database: ldb
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `system`
--

CREATE TABLE IF NOT EXISTS `system` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `system_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`system_id`),
  UNIQUE KEY `idx_system_name_short` (`name_short`)
);

--
-- Dumping data for table `system`
--

INSERT  IGNORE INTO `system` VALUES ('2005-10-27 00:38:55','2005-06-28 23:17:32','active',1,'eCash','ecash2_7');
INSERT  IGNORE INTO `system` VALUES ('2005-06-28 23:17:32','2005-06-28 23:17:32','active',2,'Teleweb','teleweb');
INSERT  IGNORE INTO `system` VALUES ('2006-05-05 19:48:03','0000-00-00 00:00:00','active',3,'eCash 3.0','ecash3_0');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

