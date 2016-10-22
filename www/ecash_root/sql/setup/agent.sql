CREATE TABLE IF NOT EXISTS `agent` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `system_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL,
  `name_last` varchar(50) NOT NULL default '',
  `name_first` varchar(50) NOT NULL default '',
  `name_middle` varchar(50) default NULL,
  `email` varchar(100) default NULL,
  `phone` varchar(10) default NULL,
  `login` varchar(50) NOT NULL default '',
  `crypt_password` varchar(255) NOT NULL default '',
  `date_expire_account` date default NULL,
  `date_expire_password` date default NULL,
  PRIMARY KEY  (`agent_id`),
  UNIQUE KEY `idx_agent_login_sys` (`login`,`system_id`)
);
