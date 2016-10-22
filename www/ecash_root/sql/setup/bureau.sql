CREATE TABLE IF NOT EXISTS `bureau` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `bureau_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(20) NOT NULL default '',
  `url_live` varchar(255) NOT NULL default '',
  `port_live` smallint(5) unsigned default NULL,
  `url_test` varchar(255) default NULL,
  `port_test` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`bureau_id`),
  UNIQUE KEY `idx_bureau_name_short` (`name_short`)
);
INSERT  IGNORE INTO `bureau` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','inactive',1,'CLVerify','clverify','https://63.230.21.36:443/clv/clvxml',443,'https://63.230.21.36:443/clv/clvxml',443);
INSERT  IGNORE INTO `bureau` VALUES ('2005-06-28 23:17:33','2005-04-21 06:01:09','active',2,'DataX','datax','',0,'',0);
INSERT  IGNORE INTO `bureau` VALUES ('2004-08-28 07:00:00','2004-08-28 07:00:00','active',3,'Satori','satori',' tcp://mailroom1.satorisoftware.com',5150,' tcp://mailroom1.satorisoftware.com',5150);
