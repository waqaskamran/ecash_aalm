-- MySQL dump 10.10
--
-- Host: monster.tss    Database: ldb_mls
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl

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
-- Table structure for table `n_queue`
--

DROP TABLE IF EXISTS `n_queue`;
CREATE TABLE `n_queue` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` datetime NOT NULL,
  `queue_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned default NULL,
  `queue_group_id` int(10) unsigned default NULL,
  `escalate_queue_id` int(10) unsigned default NULL,
  `section_id` int(10) unsigned default NULL,
  `name_short` varchar(253) default NULL,
  `name` varchar(30) NOT NULL,
  `display_name` varchar(16) NOT NULL,
  `sort_order` varchar(60) NOT NULL default 'priority desc, date_available asc',
  `control_class` varchar(20) NOT NULL default 'BasicQueue',
  `is_system_queue` tinyint(4) NOT NULL,
  PRIMARY KEY  (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_queue_entry`
--

DROP TABLE IF EXISTS `n_queue_entry`;
CREATE TABLE `n_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '0',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_time_sensitive_queue_entry`
--

DROP TABLE IF EXISTS `n_time_sensitive_queue_entry`;
CREATE TABLE `n_time_sensitive_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '100',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  `start_hour` tinyint(3) NOT NULL,
  `end_hour` tinyint(3) NOT NULL,
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_escalated_queue_entry`
--

DROP TABLE IF EXISTS `n_escalated_queue_entry`;
CREATE TABLE `n_escalated_queue_entry` (
  `queue_entry_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `agent_id` int(10) unsigned default NULL,
  `related_id` int(10) unsigned NOT NULL,
  `source_queue_id` int(10) unsigned NOT NULL,
  `date_queued` datetime NOT NULL,
  `date_available` datetime NOT NULL,
  `date_expire` datetime default NULL,
  `priority` tinyint(3) NOT NULL default '0',
  `dequeue_count` tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (`queue_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_queue_config`
--

DROP TABLE IF EXISTS `n_queue_config`;
CREATE TABLE `n_queue_config` (
  `queue_config_id` int(10) unsigned NOT NULL auto_increment,
  `queue_id` int(10) unsigned NOT NULL,
  `config_key` varchar(32) NOT NULL,
  `config_value` varchar(64) NOT NULL,
  PRIMARY KEY  (`queue_config_id`),
  UNIQUE KEY `uni_config_queue` (`queue_id`,`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_queue_group`
--

DROP TABLE IF EXISTS `n_queue_group`;
CREATE TABLE `n_queue_group` (
  `queue_group_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL,
  `name_short` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY  (`queue_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `n_queue_history`
--

DROP TABLE IF EXISTS `n_queue_history`;
CREATE TABLE `n_queue_history` (
  `queue_history_id` int(10) unsigned NOT NULL auto_increment,
  `date_queued` datetime NOT NULL,
  `date_removed` datetime NOT NULL,
  `queue_entry_id` int(10) unsigned NOT NULL,
  `queue_id` int(10) unsigned NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `original_agent_id` int(10) unsigned NOT NULL,
  `removal_agent_id` int(10) unsigned NOT NULL,
  `dequeue_count` int(10) unsigned NOT NULL default '0',
  `removal_reason` enum('queue','group','manual','expired') NOT NULL,
  PRIMARY KEY  (`queue_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-01-14 23:14:53
