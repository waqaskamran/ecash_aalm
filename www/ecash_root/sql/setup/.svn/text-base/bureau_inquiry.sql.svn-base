CREATE TABLE IF NOT EXISTS `bureau_inquiry` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign key, Convenience",
  `application_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign key, Convenience",
  `bureau_inquiry_id` int(10) unsigned NOT NULL COMMENT "Primary Key",
  `bureau_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign key, primary join",
  `inquiry_type` varchar(20) NOT NULL default '' COMMENT "Remote query type",
  `sent_package` text COMMENT "What string we sent",
  `received_package` blob COMMENT "What string we received",
  `outcome` varchar(20) default NULL COMMENT "Retrieved from parsing",
  `trace_info` varchar(255) default NULL COMMENT "Retrieved from parsing",
  `error_condition` enum('other','timeout','malformed request') default NULL COMMENT "If not NULL, what problem occurred",
  PRIMARY KEY  (`bureau_inquiry_id`),
  KEY `idx_bureau_inq_app_type_dt` (`application_id`,`inquiry_type`,`date_modified`)
) ENGINE=InnoDB COMMENT="This file contains communication with the remote server" ;
