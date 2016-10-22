-- MySQL dump 10.11
--
-- Host: monster.tss    Database: ldb_qeasy
-- ------------------------------------------------------
-- Server version	5.0.44sp1-enterprise-gpl
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `rule_component`
--

TRUNCATE rule_component;

--
-- Dumping data for table `rule_component`
--

INSERT INTO `rule_component` VALUES ('2007-05-08 21:50:59','2005-08-10 22:37:42','active',4,'Fee Return Transaction','return_transaction_fee','yes');
INSERT INTO `rule_component` VALUES ('2005-08-19 23:59:46','2005-08-10 22:41:00','active',5,'Failed Payment Next Attempt Date','failed_pmnt_next_attempt_date','yes');
INSERT INTO `rule_component` VALUES ('2005-08-25 05:09:36','2005-08-10 22:46:12','active',7,'Max Failures Before Collections','max_svc_charge_failures','yes');
INSERT INTO `rule_component` VALUES ('2007-08-15 05:58:51','2005-08-10 22:47:34','active',9,'Principal Payment','principal_payment','yes');
INSERT INTO `rule_component` VALUES ('2007-08-15 06:00:09','2005-08-10 22:48:15','active',10,'Service Charge','service_charge','yes');
INSERT INTO `rule_component` VALUES ('2005-09-10 00:20:17','2005-08-10 22:52:22','active',13,'Re-Activate Loan Amount Increase','react_amount_increase','yes');
INSERT INTO `rule_component` VALUES ('2005-08-20 00:22:41','2005-08-10 22:53:02','active',14,'Max Re-Activate Loan Amount','max_react_loan_amount','yes');
INSERT INTO `rule_component` VALUES ('2005-08-13 06:59:02','2005-08-13 06:59:02','active',15,'Max Contact Attempts','max_contact_attempts','yes');
INSERT INTO `rule_component` VALUES ('2006-04-26 21:05:00','2005-08-13 07:01:16','active',16,'Max Arranged Payments','max_num_arr_payments','no');
INSERT INTO `rule_component` VALUES ('2005-08-13 07:07:27','2005-08-13 07:07:27','active',18,'Arrangements Met Discount','arrangements_met_discount','yes');
INSERT INTO `rule_component` VALUES ('2005-08-25 07:31:47','2005-08-13 07:16:13','active',19,'Automated E-Mail','automated_email','yes');
INSERT INTO `rule_component` VALUES ('2006-04-26 21:06:28','2005-08-14 02:24:24','active',20,'Max Arranged Payments After Failure','max_num_arr_payment_failed','no');
INSERT INTO `rule_component` VALUES ('2005-08-25 07:27:02','2005-08-25 06:40:44','active',21,'Max ACH Fee Charges Per Loan','max_ach_fee_chrg_per_loan','yes');
INSERT INTO `rule_component` VALUES ('2005-11-02 08:35:26','2005-08-25 07:27:02','active',22,'Debit Frequency','debit_frequency','yes');
INSERT INTO `rule_component` VALUES ('2005-09-15 23:00:41','2005-09-15 23:00:41','active',23,'New Loan Amount','new_loan_amount','yes');
INSERT INTO `rule_component` VALUES ('2005-10-28 23:18:29','2005-10-28 23:18:29','active',24,'Bankruptcy Notified','bankruptcy_notified','yes');
INSERT INTO `rule_component` VALUES ('2007-06-11 23:36:02','2005-12-17 03:05:26','active',25,'Cancellation Delay','cancelation_delay','yes');
INSERT INTO `rule_component` VALUES ('2006-01-14 06:16:58','2006-01-14 06:16:58','active',26,'Watch Status Time Period','watch_period','no');
INSERT INTO `rule_component` VALUES ('2006-03-18 04:35:36','2006-03-17 02:53:47','active',27,'Grace period before first payment','grace_period','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',28,'Resolve Flash Report','resolve_flash_report','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',29,'Resolve DDA History Report','resolve_dda_history_report','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',30,'Resolve Payments Due Report','resolve_payments_due_report','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',31,'Resolve Open Advances Report','resolve_open_advances_report','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',32,'Nightly Transactions Update','nightly_transactions_update','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',33,'Resolve Past Due to Active','resolve_past_due_to_active','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',34,'Resolve Collections New to Active','resolve_collections_new_to_act','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',35,'Move Bankruptcy to Collections','move_bankruptcy_to_collections','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',36,'Set Completed Accounts to Inactive','completed_accounts_to_inactive','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',37,'Set QC Returns to 2nd Tier','set_qc_to_2nd_tier','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',38,'Expire Watched Accounts','expire_watched_accounts','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',39,'Reschedule Held Apps','reschedule_held_apps','no');
INSERT INTO `rule_component` VALUES ('2006-10-18 03:04:16','2006-10-18 03:04:16','active',40,'Move Dequeued Collections to QC Ready','deq_coll_to_qc_ready','no');
INSERT INTO `rule_component` VALUES ('2006-11-20 23:04:52','2006-11-20 23:04:52','active',41,'Complete Agent Affiliation Expiration Actions','cmp_aff_exp_actions','no');
INSERT INTO `rule_component` VALUES ('2006-12-12 00:42:55','2006-12-12 00:42:55','active',42,'Report Export','report_export','no');
INSERT INTO `rule_component` VALUES ('2007-01-04 17:01:11','2007-01-04 17:01:11','active',43,'Arrangements Follow Ups','set_arr_followup','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',44,'Company Start Time','company_start_time','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',45,'Company Close Time','company_close_time','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',46,'Company Lunch Time','company_lunch_time','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',47,'Company Lunch Duration','company_lunch_duration','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',48,'Company Default Queue Timeout','company_default_queue_timeout','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:10:05','2007-01-17 01:10:05','active',49,'Company Queue Recycle Limit','company_queue_recycle_limit','no');
INSERT INTO `rule_component` VALUES ('2007-01-17 01:38:25','2007-01-17 01:38:25','active',51,'Recycle Limit','recycle_limit','no');
INSERT INTO `rule_component` VALUES ('2007-02-07 22:54:31','0000-00-00 00:00:00','active',52,'Amortization Start Period','amortization_start_period','no');
INSERT INTO `rule_component` VALUES ('2007-02-07 23:25:02','0000-00-00 00:00:00','active',53,'Amortization Payment Period','amortization_payment_period','no');
INSERT INTO `rule_component` VALUES ('2007-02-07 23:25:33','0000-00-00 00:00:00','active',54,'Amortization Start Period Expiration Notify List','amortization_start_expiration_','no');
INSERT INTO `rule_component` VALUES ('2007-02-07 23:25:50','0000-00-00 00:00:00','active',55,'Amortization Payment Period Expiration Notify List','amortization_payment_expiratio','no');
INSERT INTO `rule_component` VALUES ('2007-03-06 01:58:49','2007-03-06 01:58:49','active',56,'Fraud Reminder','fraud_reminder','no');
INSERT INTO `rule_component` VALUES ('2007-03-06 01:58:49','2007-03-06 01:58:49','active',57,'Fraud Module Settings','fraud_settings','no');
INSERT INTO `rule_component` VALUES ('2007-03-14 02:10:29','0000-00-00 00:00:00','active',58,'PBX Server Type','pbx_server_type','no');
INSERT INTO `rule_component` VALUES ('2007-03-14 02:11:17','0000-00-00 00:00:00','active',59,'PBX Asterisk Host','pbx_asterisk_host','no');
INSERT INTO `rule_component` VALUES ('2007-03-14 02:11:58','0000-00-00 00:00:00','active',60,'PBX Asterisk Username','pbx_asterisk_username','no');
INSERT INTO `rule_component` VALUES ('2007-03-14 02:12:24','0000-00-00 00:00:00','active',61,'PBX Asterisk Password','pbx_asterisk_password','no');
INSERT INTO `rule_component` VALUES ('2007-03-14 02:13:01','0000-00-00 00:00:00','active',62,'PBX Asterisk Context','pbx_asterisk_context','no');
INSERT INTO `rule_component` VALUES ('2007-03-22 15:26:58','2007-03-22 15:26:58','active',63,'Queue Timeout','queue_timeout','no');
INSERT INTO `rule_component` VALUES ('2007-04-30 16:16:20','2007-04-30 16:16:20','active',64,'Loan Percentage','loan_percentage','yes');
INSERT INTO `rule_component` VALUES ('2007-04-30 18:18:12','2007-04-30 18:18:12','active',65,'Loan Amount Increment','loan_amount_increment','yes');
INSERT INTO `rule_component` VALUES ('2007-04-30 18:29:54','2007-04-30 18:29:54','active',66,'Loan Cap','loan_cap','yes');
INSERT INTO `rule_component` VALUES ('2007-04-30 21:20:43','2007-04-30 21:20:43','active',67,'Loan Percentage','ca_loan_percentage','yes');
INSERT INTO `rule_component` VALUES ('2007-04-30 21:20:55','2007-04-30 21:20:55','active',68,'Loan Amount Increment','ca_loan_amount_increment','yes');
INSERT INTO `rule_component` VALUES ('2007-04-30 21:20:55','2007-04-30 21:20:55','active',69,'Loan Cap','ca_loan_cap','yes');
INSERT INTO `rule_component` VALUES ('2007-05-02 20:25:18','2007-05-02 20:25:18','active',70,'Loan Type Model','loan_type_model','yes');
INSERT INTO `rule_component` VALUES ('2007-05-08 21:51:45','2007-05-08 21:51:45','active',71,'Fee MoneyGram','moneygram_fee','yes');
INSERT INTO `rule_component` VALUES ('2007-05-08 21:51:59','2007-05-08 21:51:59','active',72,'Fee UPS Label','ups_label_fee','yes');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:37','2007-05-31 17:20:47','active',74,'Move Hotfiles To Pending Expired','move_hotfiles_to_pending_exp','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:37','2007-06-01 20:58:16','active',75,'Hotfile Expiration','hotfile_expiration','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:20:15','2007-06-01 21:11:58','active',76,'Additional Verification Expiration','addl_ver_expiration','no');
INSERT INTO `rule_component` VALUES ('2007-06-04 17:22:34','2007-06-04 17:22:34','active',77,'','','no');
INSERT INTO `rule_component` VALUES ('2007-06-04 17:22:53','2007-06-04 17:22:53','active',78,'Email Reminder Interval','email_reminder_interval','no');
INSERT INTO `rule_component` VALUES ('2007-06-05 14:35:36','2007-06-05 14:35:36','active',79,'Send Email Reminders','send_email_reminders','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:37','2007-06-05 14:44:50','active',80,'Move To Reminder Queues','move_to_reminder_queues','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:37','2007-06-05 15:09:52','active',81,'Reminder Queue Interval','reminder_queue_interval','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:37','2007-06-08 16:04:18','active',82,'Withdraw Soft Fax','withdraw_soft_fax','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:36:36','2007-06-08 16:47:15','active',83,'Soft Fax Expiration','softfax_expiration','no');
INSERT INTO `rule_component` VALUES ('2007-06-12 18:11:42','2007-06-12 17:59:17','active',84,'Move Arrangements To MyQueue','move_arrangements_to_myqueue','no');
INSERT INTO `rule_component` VALUES ('2007-06-12 18:13:46','2007-06-12 18:13:46','active',85,'Arrangements To MyQueue Interval','arrangements_to_myqueue_interv','no');
INSERT INTO `rule_component` VALUES ('2007-06-13 20:15:06','2007-06-13 20:15:06','active',86,'Max Schedule Adjustment','max_sched_adjust','no');
INSERT INTO `rule_component` VALUES ('2007-08-13 15:53:15','2007-08-13 15:53:15','active',87,'APR Change Notification','apr_change_notification','no');
INSERT INTO `rule_component` VALUES ('2007-08-28 15:46:13','2007-08-28 15:46:13','active',88,'Settlement Offer','settlement_offer','no');
INSERT INTO `rule_component` VALUES ('2007-12-13 21:17:13','0000-00-00 00:00:00','active',89,'IDV CALL','IDV_CALL','no');
INSERT INTO `rule_component` VALUES ('2007-08-30 23:00:12','2007-08-30 23:00:12','active',90,'Require Bank Account Information','require_bank_account','no');
INSERT INTO `rule_component` VALUES ('2007-08-31 16:18:07','2007-08-31 16:18:07','active',91,'Minimum Loan Amount','minimum_loan_amount','no');
INSERT INTO `rule_component` VALUES ('2007-11-08 17:26:58','2007-09-04 16:24:01','active',92,'Expire Additional Verification','expire_additional_verification','no');
INSERT INTO `rule_component` VALUES ('2007-10-10 21:58:06','0000-00-00 00:00:00','active',93,'Minimum Vehicle Year','minimum_vehicle_year','no');
INSERT INTO `rule_component` VALUES ('2008-02-15 22:08:14','0000-00-00 00:00:00','active',94,'One Time Arrangement grace period','one_time_arrangement_grace','no');
INSERT INTO `rule_component` VALUES ('2007-12-04 20:41:33','0000-00-00 00:00:00','active',96,'Interest Rounding','interest_rounding','no');
INSERT INTO `rule_component` VALUES ('2007-12-28 21:45:22','0000-00-00 00:00:00','active',97,'FUND UPDATE CALL','FUNDUPD_CALL','no');
INSERT INTO `rule_component` VALUES ('2008-02-15 22:27:08','0000-00-00 00:00:00','active',99,'One Time Arrangement Mininmun Payment','one_time_arrangement_min','no');
INSERT INTO `rule_component` VALUES ('2008-02-15 22:31:02','0000-00-00 00:00:00','active',100,'Notification After Partial Days','notification_after_partial','no');
INSERT INTO `rule_component` VALUES ('2008-02-19 17:52:00','0000-00-00 00:00:00','active',101,'Action After Partial','action_after_partial','no');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-03-28 14:18:16
