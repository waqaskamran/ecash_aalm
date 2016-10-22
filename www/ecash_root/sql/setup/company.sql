CREATE TABLE IF NOT EXISTS `company` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `active_status` enum('active','inactive') NOT NULL default 'active' COMMENT "Deprecated?",
  `company_id` int(10) unsigned NOT NULL COMMENT "PRIMARY KEY",
  `name` varchar(100) NOT NULL default '' COMMENT "Human-readable name",
  `name_short` varchar(5) NOT NULL default '' COMMENT "Computer short-code... but why are we using it?",
  `ecash_process_type` enum('1','2') default NULL COMMENT "Verification vs. Underwriting for queue selection",
  `property_id` int(10) unsigned NOT NULL default '0' COMMENT "Used in statpro.2",
  PRIMARY KEY  (`company_id`),
  UNIQUE KEY `name_short` (`name_short`)
) ENGINE=InnoDB COMMENT="A reference table composed of the client's sub-companies";
INSERT IGNORE INTO `company` VALUES (NOW(),NOW(),'active',1,'Sample Corporation','sampl',NULL,'0');
