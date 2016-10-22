CREATE TABLE `follow_up` (
  `follow_up_id` int(10) unsigned NOT NULL auto_increment,
  `follow_up_type_id` int(10) unsigned NOT NULL default '0',
  `follow_up_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `comment_id` int(10) unsigned NOT NULL,
  `status` enum('pending','complete') NOT NULL default 'pending',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`follow_up_id`),
  KEY `idx_followup_app_time_status` (`application_id`, `follow_up_time`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `follow_up_type` (
  `follow_up_type_id` int(10) unsigned NOT NULL auto_increment,
  `name_short` varchar(25) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`follow_up_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
