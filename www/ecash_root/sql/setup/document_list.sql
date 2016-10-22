CREATE TABLE IF NOT EXISTS `document_list` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `active_status` enum('active','inactive') NOT NULL default 'active' COMMENT "Have we turned this off in favor of another?",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Convenience",
  `document_list_id` int(10) unsigned NOT NULL COMMENT "Primary key",
  `name` varchar(255) NOT NULL default '' COMMENT "Human readable name",
  `name_short` varchar(50) NOT NULL default '' COMMENT "Name found in source code",
  `required` enum('yes','no') NOT NULL default 'yes' COMMENT "Document required for loan to be approved",
  `esig_capable` enum('no','yes') NOT NULL default 'no' COMMENT "If this document may have an electronic signature",
  `system_id` int(10) unsigned default NULL COMMENT "Which version of the code is this in reference to?",
  `send_method` set('email','fax') default NULL COMMENT "By what means must this document be sent?",
  PRIMARY KEY  (`document_list_id`),
  UNIQUE KEY `idx_document_co_name_short` (`company_id`,`system_id`,`name_short`)
) ENGINE=InnoDB COMMENT="This is the list of documents *possible* to send";

/* Alter table */
ALTER TABLE `document_list` ADD COLUMN `document_api` enum('condor','copia') DEFAULT 'copia';
