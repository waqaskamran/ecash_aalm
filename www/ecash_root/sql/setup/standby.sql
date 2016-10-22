CREATE TABLE IF NOT EXISTS `standby` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "No update or delete, it seems",
  `application_id` int(10) unsigned NOT NULL COMMENT "This is the 'primary key' for this table, but not unique",
  `process_type` varchar(100) NOT NULL COMMENT "Which process (nightly, for instance) touched this app",
  KEY `idx_app_id` (`application_id`)
) ENGINE=InnoDB COMMENT="This appears to be a log of when different processes touched each application";
