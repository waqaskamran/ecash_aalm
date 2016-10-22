CREATE TABLE IF NOT EXISTS `demographics` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `has_income` enum('yes','no') default NULL,
  `has_minimum_income` enum('yes','no') default NULL,
  `has_checking` enum('yes','no') default NULL,
  `minimum_age` enum('yes','no') default NULL,
  `opt_in` enum('yes','no') default NULL,
  `us_citizen` enum('yes','no') default NULL,
  `ca_resident_agree` enum('yes','no') default NULL,
  `email_agent_created` enum('yes','no') default NULL,
  `tel_app_proc` enum('yes','no') default NULL,
  PRIMARY KEY  (`application_id`)
);
