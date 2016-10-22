CREATE TABLE IF NOT EXISTS `loan_action_history` (
  `loan_action_history_id` int(10) unsigned NOT NULL,
  `loan_action_id` int(10) unsigned default NULL,
  `application_id` int(10) unsigned default NULL,
  `status_history_id` int(10) unsigned default NULL,
  `date_created` timestamp NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`loan_action_history_id`),
  KEY `idx_loan_action_id` (`loan_action_id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_date_created` (`date_created`)
);
