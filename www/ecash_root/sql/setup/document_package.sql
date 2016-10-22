CREATE TABLE IF NOT EXISTS `document_package` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `active_status` enum('active','inactive') not null default 'active' COMMENT "If this is still available to end users",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY",
  `document_package_id` int unsigned not null auto_increment COMMENT "PRIMARY KEY",
  `name` varchar(255) not null default '' COMMENT "Human readable package name",
  `name_short` varchar(50) not null default '' COMMENT "Source-code readable name",
  primary key (`document_package_id`),
  unique `name_short` (`name_short`)
  ) ENGINE=InnoDB COMMENT="A group of documents";
