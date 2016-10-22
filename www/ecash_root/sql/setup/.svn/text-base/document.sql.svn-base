CREATE TABLE IF NOT EXISTS `document` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "CONVENIENCE FOREIGN KEY",
  `application_id` int(10) unsigned NOT NULL default '0' COMMENT "FOREIGN KEY",
  `document_id` int(10) unsigned NOT NULL COMMENT "PRIMARY KEY",
  `document_list_id` int(10) unsigned NOT NULL default '0' COMMENT "FOREIGN KEY: What document this was generated from",
  `document_method` enum('copia_fax','copia_email','condor_esig','db2_print','db2_both','condor_email','condor_fax') NOT NULL default 'copia_fax' COMMENT "How the document was delivered",
  `document_event_type` enum('sent','received') NOT NULL default 'sent' COMMENT "Which direction the document went",
  `name_other` varchar(255) default NULL COMMENT "Comments entered regarding this entry",
  `document_id_ext` varchar(255) default NULL COMMENT "FOREIGN KEY Id used by delivery mechanism",
  `agent_id` int(10) unsigned default NULL COMMENT "FOREIGN KEY Who initiated this document, optional",
  `signature_status` enum('unsigned','esig','signed_other') NOT NULL default 'unsigned' COMMENT "For document requiring signatures, was this signed and if so how",
  `sent_to` VARCHAR(255) NULL DEFAULT NULL COMMENT "The actual address this was sent to, in case of difference from up-to-date address",
  PRIMARY KEY  (`document_id`),
  KEY `idx_document_app_date` (`application_id`,`date_created`,`document_list_id`),
  KEY `idx_document_app_extid` (`application_id`,`document_id_ext`)
) ENGINE=InnoDB COMMENT="A list of what exactly has been sent to each applicant";


/*Alter Table */;
ALTER TABLE `document` CHANGE COLUMN `document_method` `document_method_legacy` ENUM('copia_fax','copia_email','condor_esig','db2_print','db2_both') NOT NULL DEFAULT 'copia_fax', ADD COLUMN `document_method` ENUM('fax','email') NULL DEFAULT NULL COMMENT 'Document Type' , ADD COLUMN `transport_method` ENUM('condor','copia','ole') NULL DEFAULT NULL COMMENT 'Document Transport Method', ADD COLUMN `archive_id` INT NULL DEFAULT NULL COMMENT 'Based on transport_method';
