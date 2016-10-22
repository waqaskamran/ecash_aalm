CREATE TABLE IF NOT EXISTS `loan_type` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `loan_type_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`loan_type_id`),
  UNIQUE KEY `idx_loan_type_name_short` (`company_id`,`name_short`)
);
INSERT IGNORE INTO `loan_type` VALUES (NOW(),NOW(),'active',1,1,'Standard Payday Loan','standard');
