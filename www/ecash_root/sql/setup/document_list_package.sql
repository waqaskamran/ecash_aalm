CREATE TABLE IF NOT EXISTS `document_list_package` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY",
  `document_package_id` int unsigned not null default 0 COMMENT "FOREIGN KEY",
  `document_list_id` int unsigned not null default 0 COMMENT "FOREIGN KEY",
  primary key (`document_list_id`,`document_package_id`)
  ) ENGINE=InnoDB COMMENT="A way of grouping documents, linking table";
