CREATE TABLE IF NOT EXISTS `site` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `site_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `license_key` varchar(100) default NULL,
  PRIMARY KEY  (`site_id`),
  KEY `idx_site_license_key` (`license_key`)
);
