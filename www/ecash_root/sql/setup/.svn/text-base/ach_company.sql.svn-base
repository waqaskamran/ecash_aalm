CREATE TABLE IF NOT EXISTS `ach_company` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "Convenience",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "Convenience",
  `company_id` int(10) unsigned NOT NULL default '0' COMMENT "Convenience",
  `ach_id` int(10) unsigned NOT NULL COMMENT "Foreign key, Primary Key",
  `ach_batch_id` int(10) unsigned NOT NULL default '0' COMMENT "Foreign key",
  `ach_report_id` int(10) unsigned default NULL COMMENT "Foreign key",
  `ach_date` date NOT NULL default '0000-00-00' COMMENT "Effective date of transaction",
  `amount` decimal(7,2) NOT NULL default '0.00' COMMENT "Total absolute value",
  `ach_type` enum('debit','credit') NOT NULL default 'debit' COMMENT "Which direction the money is flowing",
  `bank_aba` varchar(9) NOT NULL default '' COMMENT "Where the money is actually going",
  `bank_account` varchar(17) NOT NULL default '' COMMENT "Where the money is actually going",
  `bank_account_type` enum('checking','savings') NOT NULL default 'checking' COMMENT "Where the money is actually going",
  `ach_status` enum('created','batched','returned','processed') NOT NULL default 'created' COMMENT "Each status should only ever be hit once",
  `ach_return_code_id` int(10) unsigned default NULL COMMENT "Foreign key",
  `ach_trace_number` varchar(15) NOT NULL default '' COMMENT "What number *they* use for this transaction",
  PRIMARY KEY  (`ach_id`),
  KEY `idx_ach_company_co_dt` (`company_id`,`ach_date`)
) ENGINE=InnoDB COMMENT="This table contains details about an ACH transaction";
