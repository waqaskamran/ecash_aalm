CREATE TABLE IF NOT EXISTS `process_log` (
  `business_day` date NOT NULL default '0000-00-00' COMMENT 'Which day the process is being run FOR, not ON',
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT 'Which company is the process running for',
  `process_log_id` int(10) unsigned NOT NULL COMMENT 'Primary key',
  `step` varchar(50) default NULL COMMENT 'When you want a logical process with multiple stages, you may place the name of sub-stages here',
  `state` enum('started','completed','failed') NOT NULL default 'started' COMMENT 'Overall state, "started" = "running"',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'CONVENIENCE: Each time it gets modified',
  `date_started` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'When this actually started',
  PRIMARY KEY  (`process_log_id`),
  KEY `idx_proclog_dt_co_step_startdt` (`business_day`,`company_id`,`step`,`date_started`)
) ENGINE=InnoDB COMMENT='This is a tracking table for background processes' ;
