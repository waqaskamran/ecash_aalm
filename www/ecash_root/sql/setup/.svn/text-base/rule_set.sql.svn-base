CREATE TABLE IF NOT EXISTS `rule_set` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `rule_set_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `loan_type_id` int(10) unsigned NOT NULL default '0',
  `date_effective` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`rule_set_id`),
  UNIQUE KEY `idx_rule_set_loan_type_effdate` (`loan_type_id`,`date_effective`)
);
INSERT  IGNORE INTO `rule_set` VALUES ('2006-02-14 07:22:40','2006-02-14 07:22:40','active',249,'Initial Rule Set',2,'2006-02-14 07:22:40');
INSERT  IGNORE INTO `rule_set` VALUES ('2006-03-27 15:52:45','2006-03-27 15:52:45','active',250,'Initial Rule Set 03-27-2006 07:52:45',2,'2006-03-27 15:52:45');
INSERT  IGNORE INTO `rule_set` VALUES ('2006-03-27 22:15:10','2006-03-27 22:15:10','active',251,'Initial Rule Set  03-27-2006 14:15:10',2,'2006-03-27 22:15:10');
INSERT  IGNORE INTO `rule_set` VALUES ('2006-04-17 17:50:17','2006-04-17 17:50:17','active',252,'Initial Rule Set   04-17-2006 10:50:17',2,'2006-04-17 17:50:17');
INSERT  IGNORE INTO `rule_set` VALUES ('2006-05-08 20:16:24','2006-05-08 20:16:24','active',253,'Initial Rule Set',6,'2006-05-08 20:16:24');
