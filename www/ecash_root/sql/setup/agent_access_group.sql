CREATE TABLE IF NOT EXISTS `agent_access_group` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `access_group_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`agent_id`,`access_group_id`)
);
