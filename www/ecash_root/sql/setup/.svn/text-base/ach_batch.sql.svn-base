CREATE TABLE IF NOT EXISTS `ach_batch` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Convenience",
  `ach_batch_id` int(10) unsigned NOT NULL COMMENT "Primary Key",
  `ach_file_outbound` longtext NOT NULL COMMENT "The generated batch file being sent",
  `remote_response` text COMMENT "Their response string",
  `batch_status` enum('created','sent','failed') NOT NULL default 'created' COMMENT "In case the operation is interrupted, where was it when that happened?",
  PRIMARY KEY  (`ach_batch_id`)
) ENGINE=InnoDB COMMENT="This represents the OUTBOUND portion of an ACH batch" ;
