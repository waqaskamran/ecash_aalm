CREATE TABLE IF NOT EXISTS `control_option` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `control_option_id` int(10) unsigned NOT NULL,
  `name_short` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` enum('field','button','queue') NOT NULL,
  PRIMARY KEY  (`control_option_id`)
);
INSERT  IGNORE INTO `control_option` VALUES ('2006-02-03 16:04:40','2006-02-03 16:04:40',1,'bank_account','Disable Bank Account','This is used to disable the bank account number in the application.','field');
INSERT  IGNORE INTO `control_option` VALUES ('2006-02-04 09:55:22','2006-02-04 09:55:22',2,'bank_account_type','Disable Bank Type','This is used to disable the bank account type in the application.','field');
INSERT  IGNORE INTO `control_option` VALUES ('2006-02-04 09:56:27','2006-02-04 09:56:27',3,'bank_name','Disable Bank Name','This is used to disable the bank account name in the application.','field');
INSERT  IGNORE INTO `control_option` VALUES ('2006-02-04 09:57:28','2006-02-04 09:57:28',4,'bank_aba','Disable Bank ABA','This is used to disable the bank account ABA number in the application.','field');
INSERT  IGNORE INTO `control_option` VALUES ('2006-02-15 08:37:03','2006-02-15 08:37:03',5,'populate_collections_agent','Populate Collections Agent','The agents associated with this feature will be visible as a collections agent.','field');
