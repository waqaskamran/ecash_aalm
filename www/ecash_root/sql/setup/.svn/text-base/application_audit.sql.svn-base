CREATE TABLE IF NOT EXISTS `application_audit` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "The date the change occurred",
  `audit_log_id` int(10) unsigned NOT NULL COMMENT "Primary Key, not normally used/needed",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY",
  `application_id` int(10) unsigned NOT NULL default '0' COMMENT "FOREIGN KEY - Which app was altered",
  `table_name` varchar(100) NOT NULL default '' COMMENT "Deprecated: Always contains 'application'",
  `column_name` varchar(100) NOT NULL default '' COMMENT "Contains the column name which was changed",
  `value_before` mediumtext COMMENT "Just what it says, the value of the column before the change",
  `value_after` mediumtext COMMENT "Just what it says, the value of the column after the change",
  `update_process` varchar(50) NOT NULL default '' COMMENT "An odd string, currently one of: 'php::trigger::application','mysql::trigger:app_updt'",
  `agent_id` int(10) unsigned NOT NULL default '0' COMMENT "Who MySQL thought was logged in at the time, if inserted by the trigger",
  PRIMARY KEY  (`audit_log_id`)
) ENGINE=InnoDB COMMENT="Contains an audit log of changes to individual columns in the application table" ;
