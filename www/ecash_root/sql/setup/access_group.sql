CREATE TABLE IF NOT EXISTS `access_group` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `active_status` enum('active','inactive') NOT NULL default 'active' COMMENT "All should be active, this is deprecated but not yet removable",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "FOREIGN KEY: Necessary. Each access group is PER COMPANY",
  `system_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY: Which version of the software is using this",
  `access_group_id` int(10) unsigned NOT NULL COMMENT "PRIMARY KEY",
  `name` varchar(100) NOT NULL default '' COMMENT "Human-readable name, not used in code",
  PRIMARY KEY  (`access_group_id`),
  UNIQUE KEY `idx_access_group_co_sys_name` (`company_id`,`system_id`,`name`)
) ENGINE=InnoDB COMMENT="This table defines groups for user access allowances";
