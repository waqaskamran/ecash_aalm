CREATE TABLE IF NOT EXISTS `ach_report` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `date_request` DATE NOT NULL DEFAULT '0000-00-00' COMMENT 'You should find this as the sdate in the ach_report_request',
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign key, Convenience",
  `ach_report_id` int(10) unsigned NOT NULL COMMENT "Primary key",
  `ach_report_request` text NOT NULL COMMENT "What we sent them to get the report",
  `remote_response` longtext COMMENT "What they sent back in return",
  `report_status` enum('received','processed','failed') NOT NULL default 'received' COMMENT "What we've done with the data",
  PRIMARY KEY  (`ach_report_id`)
) ENGINE=InnoDB COMMENT="Retrieval of information from the host";
