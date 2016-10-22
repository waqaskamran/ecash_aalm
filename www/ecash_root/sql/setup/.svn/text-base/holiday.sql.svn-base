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
-- Table structure for table `holiday`
--

CREATE TABLE IF NOT EXISTS `holiday` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `holiday_id` int(10) unsigned NOT NULL,
  `holiday` date NOT NULL default '0000-00-00',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`holiday_id`),
  KEY `idx_holiday_date` (`holiday`)
);

--
-- Dumping data for table `holiday`
--

INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',1,'2004-01-01','New Years Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',2,'2004-01-19','Martin Luther King, Jr');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',3,'2004-02-16','Washingtons Birthday');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',4,'2004-05-31','Memorial Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',5,'2004-07-05','Independence Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',6,'2004-09-06','Labor Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',7,'2004-10-11','Columbus Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',8,'2004-11-11','Veterans Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',9,'2004-11-25','Thanksgiving Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',10,'2004-12-25','Christmas Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',11,'2005-01-01','New Years Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',12,'2005-01-17','Martin Luther King, Jr');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',13,'2005-02-21','Washingtons Birthday');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',14,'2005-05-30','Memorial Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',15,'2005-07-04','Independence Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',16,'2005-09-05','Labor Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',17,'2005-10-10','Columbus Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',18,'2005-11-11','Veterans Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:11','2004-08-28 23:26:11','active',19,'2005-11-24','Thanksgiving Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',20,'2005-12-26','Christmas Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',21,'2006-01-02','New Years Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',22,'2006-01-16','Martin Luther King, Jr');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',23,'2006-02-20','Washingtons Birthday');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',24,'2006-05-29','Memorial Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',25,'2006-07-04','Independence Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',26,'2006-09-04','Labor Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',27,'2006-10-09','Columbus Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',28,'2006-11-10','Veterans Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',29,'2006-11-23','Thanksgiving Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',30,'2006-12-25','Christmas Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',31,'2007-01-01','New Years Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',32,'2007-01-15','Martin Luther King, Jr');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',33,'2007-02-19','Washingtons Birthday');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',34,'2007-05-28','Memorial Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',35,'2007-07-04','Independence Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',36,'2007-09-03','Labor Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',37,'2007-10-08','Columbus Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',38,'2007-11-11','Veterans Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',39,'2007-11-22','Thanksgiving Day');
INSERT  IGNORE INTO `holiday` VALUES ('2004-08-28 23:26:12','2004-08-28 23:26:12','active',40,'2007-12-25','Christmas Day');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

