CREATE TABLE IF NOT EXISTS `acl` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `access_group_id` int(10) unsigned NOT NULL default '0',
  `section_id` int(10) unsigned NOT NULL default '0',
  `acl_mask` varchar(255) default NULL,
  `read_only` tinyint(4) NOT NULL,
  PRIMARY KEY  (`access_group_id`,`section_id`)
);
