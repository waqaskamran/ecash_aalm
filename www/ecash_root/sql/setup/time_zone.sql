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
-- Table structure for table `time_zone`
--

CREATE TABLE IF NOT EXISTS `time_zone` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `time_zone_id` int(10) unsigned NOT NULL,
  `gmt_offset` decimal(4,2) NOT NULL default '0.00',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`time_zone_id`)
);

--
-- Dumping data for table `time_zone`
--

INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',1,'-5.00','eastern');
INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',2,'-6.00','central');
INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',3,'-7.00','mountain');
INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',4,'-8.00','pacific');
INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',5,'-9.00','alaska');
INSERT IGNORE INTO `time_zone` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',6,'-10.00','hawaii');
INSERT IGNORE INTO `time_zone` VALUES ('2004-12-11 00:13:34','2004-12-11 00:13:34','active',7,'-4.00','atlantic');
INSERT IGNORE INTO `time_zone` VALUES ('2004-12-11 00:13:39','2004-12-11 00:13:39','active',8,'10.00','guam');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

