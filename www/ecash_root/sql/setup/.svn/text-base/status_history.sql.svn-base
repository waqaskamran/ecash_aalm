CREATE TABLE IF NOT EXISTS `status_history` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "When the person changed the status",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Convenience column",
  `application_id` int(10) unsigned NOT NULL default '0' COMMENT "Which application was changed",
  `status_history_id` int(10) unsigned NOT NULL COMMENT "Primary key",
  `agent_id` int(10) unsigned NOT NULL default '0' COMMENT "Who performed the change",
  `application_status_id` int(10) unsigned NOT NULL default '0' COMMENT "What the NEW status is after the change",
  PRIMARY KEY  (`status_history_id`),
  KEY `idx_sts_hist_app_date` (`application_id`,`date_created`),
  KEY `idx_sts_hist_date_co_app` (`date_created`,`company_id`,`application_id`),
  KEY `idx_sts_hist_app_sts_date` (`application_id`,`application_status_id`,`date_created`)
) ENGINE=InnoDB COMMENT="This table is automatically filled in by a trigger";
