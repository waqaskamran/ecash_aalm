CREATE TABLE IF NOT EXISTS `contact_outcome` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `contact_outcome_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(25) NOT NULL default '',
  `contact_interval_default` smallint(6) default NULL,
  PRIMARY KEY  (`contact_outcome_id`),
  UNIQUE KEY `idx_contact_outcome_name_short` (`name_short`)
);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2004-09-14 07:00:00','2004-09-14 07:00:00','active',1,'Contacted - Will Fax','contacted - will fax',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',2,'Contacted - Canceled','contacted - canceled',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',3,'Contacted - Applied','contacted - applied',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',4,'Contacted - Not Qualified','contacted - not qualified',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',5,'Never Registered','never registered',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',6,'No Answer / Busy','no answer / busy',120);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',7,'Voicemail','voicemail',120);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',8,'Call Back','call back',120);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',9,'Bad Phone Number','bad phone number',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',10,'Bad Name','bad name',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',11,'Do Not Call','do not call',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2005-06-28 23:17:34','2004-09-14 07:00:00','active',12,'Other','other',NULL);
INSERT  IGNORE INTO `contact_outcome` VALUES ('2004-10-18 07:00:00','2004-10-18 07:00:00','active',13,'Contacted - Confirmed','contacted - confirmed',NULL);
