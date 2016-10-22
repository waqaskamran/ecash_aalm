CREATE TABLE IF NOT EXISTS `ecld_file` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign Key, Convenience",
  `ecld_file_id` int(10) unsigned NOT NULL COMMENT "Primary Key",
  `ecld_file_content` longtext NOT NULL COMMENT "What is actually stored in the file remotely",
  `remote_filename` varchar(50) NOT NULL default '' COMMENT "Where the file gets stored. NOTE: Maximum column width",
  `file_status` enum('created','sent','failed') NOT NULL default 'created' COMMENT "Send status, row should always start from 'created' stage",
  `client_identifier` varchar(38) NOT NULL default '' COMMENT "?: External id",
  PRIMARY KEY  (`ecld_file_id`)
) ENGINE=InnoDB COMMENT="QuickChecks are handled via direct file transfers. This table contains the files sent";
