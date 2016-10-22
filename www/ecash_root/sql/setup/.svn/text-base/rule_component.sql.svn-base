CREATE TABLE IF NOT EXISTS `rule_component` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `rule_component_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(30) NOT NULL default '',
  `grandfathering_enabled` enum('no','yes') NOT NULL default 'no',
  PRIMARY KEY  (`rule_component_id`),
  UNIQUE KEY `idx_rule_comp_name_short` (`name_short`)
);
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-10 22:39:23','2005-08-10 22:37:42','active',4,'Return Transaction Fee','return_transaction_fee','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-19 23:59:46','2005-08-10 22:41:00','active',5,'Failed Payment Next Attempt Date','failed_pmnt_next_attempt_date','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-10 22:45:28','2005-08-10 22:45:28','active',6,'Past Due Status','past_due_status','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-25 05:09:36','2005-08-10 22:46:12','active',7,'Max Failures Before Collections','max_svc_charge_failures','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-20 00:22:41','2005-08-10 22:47:02','active',8,'Max Interest Only Payments','max_svc_charge_only_pmts','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-10 22:47:34','2005-08-10 22:47:34','active',9,'Principal Payment Amount','principal_payment_amount','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-20 00:11:28','2005-08-10 22:48:15','active',10,'Interest Percentage','svc_charge_percentage','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-09-10 00:20:17','2005-08-10 22:52:22','active',13,'Re-Activate Loan Amount Increase','react_amount_increase','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-20 00:22:41','2005-08-10 22:53:02','active',14,'Max Re-Activate Loan Amount','max_react_loan_amount','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-13 06:59:02','2005-08-13 06:59:02','active',15,'Max Contact Attempts','max_contact_attempts','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2006-04-26 21:05:00','2005-08-13 07:01:16','active',16,'Max Arranged Payments','max_num_arr_payments','no');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-13 07:07:27','2005-08-13 07:07:27','active',18,'Arrangements Met Discount','arrangements_met_discount','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-25 07:31:47','2005-08-13 07:16:13','active',19,'Automated E-Mail','automated_email','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2006-04-26 21:06:28','2005-08-14 02:24:24','active',20,'Max Arranged Payments After Failure','max_num_arr_payment_failed','no');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-08-25 07:27:02','2005-08-25 06:40:44','active',21,'Max ACH Fee Charges Per Loan','max_ach_fee_chrg_per_loan','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-11-02 08:35:26','2005-08-25 07:27:02','active',22,'Debit Frequency','debit_frequency','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-09-15 23:00:41','2005-09-15 23:00:41','active',23,'New Loan Amount','new_loan_amount','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-10-28 23:18:29','2005-10-28 23:18:29','active',24,'Bankruptcy Notified','bankruptcy_notified','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2005-12-17 03:05:26','2005-12-17 03:05:26','active',25,'Cancelation Delay','cancelation_delay','yes');
INSERT  IGNORE INTO `rule_component` VALUES ('2006-01-14 06:16:58','2006-01-14 06:16:58','active',26,'Watch Status Time Period','watch_period','no');
INSERT  IGNORE INTO `rule_component` VALUES ('2006-03-18 04:35:36','2006-03-17 02:53:47','active',27,'Grace period before first payment','grace_period','no');
