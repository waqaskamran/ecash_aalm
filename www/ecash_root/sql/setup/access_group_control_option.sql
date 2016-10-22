CREATE TABLE IF NOT EXISTS `access_group_control_option` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `access_group_id` int(10) unsigned NOT NULL COMMENT "FOREIGN KEY",
  `control_option_id` int(10) unsigned NOT NULL COMMENT "FOREIGN KEY",
  PRIMARY KEY  (`access_group_id`,`control_option_id`)
) ENGINE=InnoDB COMMENT="This maps extra control options to an access group";
