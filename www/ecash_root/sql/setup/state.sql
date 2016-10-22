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
-- Table structure for table `state`
--

CREATE TABLE IF NOT EXISTS `state` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `state` char(2) NOT NULL default '',
  `name` varchar(20) NOT NULL default '',
  `business_allowed` enum('yes','no') NOT NULL default 'yes',
  `time_zone_id` int(10) unsigned NOT NULL default '0',
  `use_daylight_saving` enum('yes','no') NOT NULL default 'yes',
  PRIMARY KEY  (`state`)
);

--
-- Dumping data for table `state`
--

INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ak','Alaska','yes',5,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','al','Alabama','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ar','Arkansas','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','az','Arizona','yes',4,'no');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ca','California','yes',4,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','co','Colorado','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ct','Connecticut','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','dc','District of Columbia','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','de','Delaware','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','fl','Florida','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ga','Georgia','no',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-12-11 00:13:55','2004-12-11 00:13:55','active','gu','Guam','yes',8,'no');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','hi','Hawaii','yes',6,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ia','Iowa','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','id','Idaho','yes',4,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','il','Illinois','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','in','Indiana','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ks','Kansas','no',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ky','Kentucky','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','la','Louisiana','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ma','Massachusetts','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','md','Maryland','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','me','Maine','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','mi','Michigan','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','mn','Minnesota','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','mo','Missouri','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ms','Mississippi','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','mt','Montana','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nc','North Carolina','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nd','North Dakota','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ne','Nebraska','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nh','New Hampshire','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nj','New Jersey','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nm','New Mexico','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','nv','Nevada','yes',4,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ny','New York','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','oh','Ohio','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ok','Oklahoma','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','or','Oregon','yes',4,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','pa','Pennsylvania','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','pr','Puerto Rico','yes',7,'no');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ri','Rhode Island','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','sc','South Carolina','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','sd','South Dakota','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','tn','Tennessee','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','tx','Texas','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','ut','Utah','yes',3,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','va','Virginia','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-12-11 00:13:51','2004-12-11 00:13:51','active','vi','U.S. Virgin Islands','yes',7,'no');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','vt','Vermont','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','wa','Washington','yes',4,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','wi','Wisconsin','yes',2,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','wv','West Virginia','yes',1,'yes');
INSERT  IGNORE INTO `state` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active','wy','Wyoming','yes',3,'yes');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

