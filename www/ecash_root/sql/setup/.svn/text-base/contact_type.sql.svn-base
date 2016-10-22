CREATE TABLE IF NOT EXISTS `contact_type` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `contact_type_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`contact_type_id`),
  UNIQUE KEY `idx_contact_type_name_short` (`name_short`)
);
INSERT  IGNORE INTO `contact_type` VALUES ('2004-09-14 07:00:00','2004-09-14 07:00:00','active',1,'Prospect','prospect');
INSERT  IGNORE INTO `contact_type` VALUES ('2004-09-14 07:00:00','2004-09-14 07:00:00','active',2,'Drop','drop');
INSERT  IGNORE INTO `contact_type` VALUES ('2004-09-14 07:00:00','2004-09-14 07:00:00','active',3,'Decline','decline');
INSERT  IGNORE INTO `contact_type` VALUES ('2004-10-18 07:00:00','2004-10-18 07:00:00','active',4,'Confirmation','confirmation');
INSERT  IGNORE INTO `contact_type` VALUES ('2006-02-25 03:10:22','2006-02-25 03:10:22','active',6,'Drop E-Sig','drop_esig');
INSERT  IGNORE INTO `contact_type` VALUES ('2006-02-25 03:10:46','2006-02-25 03:10:46','active',7,'Drop Confirm','drop_confirm');
