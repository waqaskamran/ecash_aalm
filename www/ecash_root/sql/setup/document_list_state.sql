CREATE TABLE IF NOT EXISTS `document_list_state` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY",
  `document_list_id` int unsigned not null default 0 COMMENT "FOREIGN KEY",
  `state` char(2) NOT NULL default '' COMMENT "FOREIGN KEY",
  primary key (`document_list_id`,`state`)
  ) ENGINE=InnoDB COMMENT="Links documents to the states they're allowed to be sent in/to";
