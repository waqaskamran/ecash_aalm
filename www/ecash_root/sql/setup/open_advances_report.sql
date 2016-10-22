CREATE TABLE IF NOT EXISTS `open_advances_report` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `open_advances_report_id` int(10) unsigned NOT NULL,
  `report_date` date NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `company_name_short` varchar(5) NOT NULL,
  `status` varchar(100) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `total` decimal(10,2) NOT NULL default '0.00',
  `loan_type_id` varchar(25) NOT NULL,
  PRIMARY KEY  (`open_advances_report_id`),
  KEY `idx_report_date_company_name` (`report_date`,`company_name_short`)
);
