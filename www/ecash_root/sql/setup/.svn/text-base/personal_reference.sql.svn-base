CREATE TABLE IF NOT EXISTS `personal_reference` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `personal_reference_id` int(10) unsigned NOT NULL,
  `name_full` varchar(50) NOT NULL default '',
  `phone_home` varchar(10) NOT NULL default '',
  `relationship` varchar(30) NOT NULL default '',
  `reference_verified` enum('unverified','verified') NOT NULL default 'unverified',
  `contact_pref` enum('do not contact','ok to contact') NOT NULL default 'do not contact',
  PRIMARY KEY  (`personal_reference_id`),
  KEY `idx_pers_ref_app` (`application_id`,`personal_reference_id`)
);
