CREATE TABLE IF NOT EXISTS `return_reason` (
  `id` int(10) unsigned NOT NULL,
  `return_reason_code` char(3) NOT NULL default '',
  `is_fatal` enum('no','yes') NOT NULL default 'no',
  `human_readable` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `return_reason_code` (`return_reason_code`),
  KEY `is_fatal` (`is_fatal`)
);
INSERT  IGNORE INTO `return_reason` VALUES (1,'P-N','no','NSF / Insufficient Funds');
INSERT  IGNORE INTO `return_reason` VALUES (2,'P-E','no','Endorsement');
INSERT  IGNORE INTO `return_reason` VALUES (3,'P-R','no','Refer to Maker');
INSERT  IGNORE INTO `return_reason` VALUES (4,'P-X','no','Other');
INSERT  IGNORE INTO `return_reason` VALUES (5,'P-A','yes','Account Closed');
INSERT  IGNORE INTO `return_reason` VALUES (6,'P-S','yes','Stop Payment');
INSERT  IGNORE INTO `return_reason` VALUES (7,'P-U','yes','Unable to Locate');
INSERT  IGNORE INTO `return_reason` VALUES (8,'P-F','yes','Forged');
