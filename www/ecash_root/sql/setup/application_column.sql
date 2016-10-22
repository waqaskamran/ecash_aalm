CREATE TABLE IF NOT EXISTS `application_column` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign Key, Convenience",
  `application_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign Key, Primary join",
  `table_name` varchar(100) NOT NULL default '' COMMENT "Deprecated: Only contains 'application'",
  `column_name` varchar(100) NOT NULL default '' COMMENT "Column name from application table. WARNING: Not direct mapping, set by code!",
  `bad_info` enum('off','on') NOT NULL default 'off' COMMENT "If the column in question is considered 'bad'",
  `do_not_contact` enum('off','on') NOT NULL default 'off' COMMENT "If the column in question is a point of contact the applicant does *not* want to be reached at",
  `best_contact` enum('off','on') NOT NULL default 'off' COMMENT "If the column in question is the best point of contact for the applicant (direct opposition to do_not_contact",
  `do_not_market` enum('off','on') NOT NULL default 'off' COMMENT "If the column in question should not be used for marketing",
  `do_not_loan` enum('off','on') NOT NULL COMMENT "WARNING: This flag does not mean what it seems.  This means do not loan to this entire applicant, regardless of which column this is attached to",
  PRIMARY KEY  (`application_id`,`table_name`,`column_name`)
) ENGINE=InnoDB COMMENT="This stores flags regarding the disposition of different data fields" ;
