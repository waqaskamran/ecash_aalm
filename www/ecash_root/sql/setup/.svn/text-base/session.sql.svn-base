CREATE TABLE IF NOT EXISTS `session` (
  `session_id` varchar(32) NOT NULL default '' COMMENT "MD5 sum for uniqueness",
  `date_created` datetime default '0000-00-00 00:00:00' COMMENT "For logging / cleanup",
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "For logging / cleanup",
  `date_locked` datetime NOT NULL default '0000-00-00 00:00:00' COMMENT "WARNING: If not zero, code waits to update.",
  `session_open` tinyint(4) NOT NULL default '0' COMMENT "Boolean: If date_locked not zero?",
  `compression` enum('none','gz','bz') NOT NULL default 'none' COMMENT "Source code is providing compression, not database",
  `session_info` mediumblob NOT NULL COMMENT "The actual serialized data",
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB COMMENT="Where apache sessions go";
