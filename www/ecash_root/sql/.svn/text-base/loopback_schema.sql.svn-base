-- MySQL dump 10.10
--
-- Host: dev2.clkonline.com    Database: ach_loopback
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl-log

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
-- Table structure for table `batch_control`
--

DROP TABLE IF EXISTS `batch_control`;
CREATE TABLE `batch_control` (
  `batch_id` int(10) unsigned NOT NULL,
  `bc_id` int(10) unsigned NOT NULL auto_increment,
  `record_type_code` char(1) NOT NULL,
  `service_class_code` char(3) NOT NULL,
  `entry_addenda_count` char(6) NOT NULL,
  `entry_hash` char(10) NOT NULL,
  `total_debit_dollar_amount` decimal(12,2) NOT NULL,
  `total_credit_dollar_amount` decimal(12,2) NOT NULL,
  `company_identification` char(10) NOT NULL,
  `message_authentication_code` char(19) default NULL,
  `reserves` char(6) default NULL,
  `originating_dft_identification` char(8) default NULL,
  `batch_number` char(7) default NULL,
  PRIMARY KEY  (`bc_id`),
  KEY `idx_bc_batch` (`batch_id`,`bc_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `batch_reference`
--

DROP TABLE IF EXISTS `batch_reference`;
CREATE TABLE `batch_reference` (
  `batch_id` int(10) NOT NULL auto_increment,
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `complete` int(1) default '0',
  `source` varchar(255) NOT NULL,
  PRIMARY KEY  (`batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `company_batch_header`
--

DROP TABLE IF EXISTS `company_batch_header`;
CREATE TABLE `company_batch_header` (
  `batch_id` int(10) unsigned NOT NULL,
  `cbh_id` int(10) unsigned NOT NULL auto_increment,
  `record_type_code` char(1) NOT NULL,
  `service_class_code` char(3) NOT NULL,
  `company_name` varchar(16) NOT NULL,
  `company_discretionary_data` varchar(20) default NULL,
  `company_id` varchar(10) NOT NULL,
  `standard_entry_class_code` char(3) NOT NULL,
  `company_entry_description` varchar(10) NOT NULL,
  `company_descriptive_date` date default NULL,
  `effective_entry_date` date NOT NULL,
  `settlement_date` varchar(3) default NULL,
  `originator_status_code` char(1) NOT NULL,
  `originating_dfi_id` char(8) NOT NULL,
  `batch_number` char(7) NOT NULL,
  PRIMARY KEY  (`cbh_id`),
  KEY `idx_cbh_batch` (`batch_id`,`cbh_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `file_control`
--

DROP TABLE IF EXISTS `file_control`;
CREATE TABLE `file_control` (
  `batch_id` int(10) unsigned NOT NULL,
  `fc_id` int(10) unsigned NOT NULL auto_increment,
  `record_type_code` char(1) NOT NULL,
  `batch_count` char(6) NOT NULL,
  `block_count` char(6) NOT NULL,
  `entry_addenda_count` char(8) NOT NULL,
  `entry_hash` char(10) NOT NULL,
  `total_debit_dollar_amount` decimal(12,2) NOT NULL,
  `total_credit_dollar_amount` decimal(12,2) NOT NULL,
  `reserved` char(39) default NULL,
  PRIMARY KEY  (`fc_id`),
  KEY `idx_fc_batch` (`batch_id`,`fc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `header`
--

DROP TABLE IF EXISTS `header`;
CREATE TABLE `header` (
  `batch_id` int(10) unsigned NOT NULL default '0',
  `record_type_code` char(1) NOT NULL,
  `priority_code` char(2) NOT NULL,
  `immediate_destination` varchar(10) NOT NULL,
  `immediate_origin` varchar(10) NOT NULL,
  `file_creation_date` date default NULL,
  `file_creation_time` time default NULL,
  `file_id_modifier` char(1) NOT NULL,
  `record_size` char(3) NOT NULL,
  `blocking_factor` char(2) NOT NULL,
  `format_code` char(1) NOT NULL,
  `immediate_destination_name` varchar(23) default NULL,
  `immediate_origin_name` varchar(23) default NULL,
  `reference_code` varchar(8) default NULL,
  PRIMARY KEY  (`batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `ppd_addenda`
--

DROP TABLE IF EXISTS `ppd_addenda`;
CREATE TABLE `ppd_addenda` (
  `batch_id` int(10) unsigned NOT NULL,
  `ppd_id` int(10) unsigned NOT NULL,
  `ppd_addenda_id` int(10) unsigned NOT NULL auto_increment,
  `record_type_code` char(1) NOT NULL,
  `transaction_code` char(2) NOT NULL,
  `payment_related_info` char(80) default NULL,
  `addenda_seq_no` char(4) NOT NULL,
  `entry_detail_seq_no` char(7) NOT NULL,
  PRIMARY KEY  (`ppd_addenda_id`),
  KEY `idx_ppd_add_batch` (`batch_id`,`ppd_id`,`ppd_addenda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `ppd_entry`
--

DROP TABLE IF EXISTS `ppd_entry`;
CREATE TABLE `ppd_entry` (
  `batch_id` int(10) unsigned NOT NULL,
  `ppd_id` int(10) unsigned NOT NULL auto_increment,
  `record_type_code` char(1) NOT NULL,
  `transaction_code` char(2) NOT NULL,
  `receiving_dfi_id` char(8) NOT NULL,
  `check_digit` char(1) NOT NULL,
  `dfi_account_number` char(17) NOT NULL,
  `debit_amount` decimal(10,2) NOT NULL,
  `credit_amount` decimal(10,2) NOT NULL,
  `individual_id_num` char(15) NOT NULL,
  `individual_name` char(22) NOT NULL,
  `discretionary_date` char(2) default NULL,
  `addenda_record_indicator` char(1) default NULL,
  `trace_number` char(15) default NULL,
  PRIMARY KEY  (`ppd_id`),
  KEY `idx_ppd_batch` (`batch_id`,`ppd_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `return_batch`
--

DROP TABLE IF EXISTS `return_batch`;
CREATE TABLE `return_batch` (
  `return_batch_id` int(10) unsigned NOT NULL auto_increment,
  `date_created` datetime NOT NULL,
  PRIMARY KEY  (`return_batch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

