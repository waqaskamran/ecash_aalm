CREATE TABLE IF NOT EXISTS `contact_history` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `application_id` int(10) unsigned NOT NULL default '0',
  `contact_history_id` int(10) unsigned NOT NULL,
  `contact_type_id` int(10) unsigned NOT NULL default '0',
  `contact_outcome_id` int(10) unsigned NOT NULL default '0',
  `date_next_contact` timestamp NULL default NULL,
  `agent_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`contact_history_id`),
  KEY `idx_contact_history_app` (`application_id`,`date_created`)
);
