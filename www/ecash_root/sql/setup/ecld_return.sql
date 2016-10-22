CREATE TABLE IF NOT EXISTS `ecld_return` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign Key: Convenience",
  `ecld_return_id` int(10) unsigned NOT NULL COMMENT "Primary Key",
  `return_file_content` longtext COMMENT "Contents of the file retrieved",
  `return_status` enum('received','processed','failed') NOT NULL default 'received' COMMENT "Status of the row, should be started at the 'received' stage",
  PRIMARY KEY  (`ecld_return_id`)
) ENGINE=InnoDB COMMENT="When we get returns from QuickChecks, this contains the batch files" ;
