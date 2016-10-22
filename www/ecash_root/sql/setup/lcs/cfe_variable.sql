-- MySQL dump 10.11
--
-- Host: writer.ecashaalm.ept.tss    Database: ldb_mls
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cfe_variable`
--

TRUNCATE cfe_variable;

--
-- Dumping data for table `cfe_variable`
--

INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:33:46','2008-01-29 01:33:46',1,'Age','age','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:34:00','2008-01-29 01:34:00',2,'Application Status','application_status','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:34:18','2008-01-29 01:34:18',3,'Last Loan Date','last_loan_date','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:35:17','2008-01-29 01:35:17',4,'Previous Loan Count','prev_loan_count','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:35:36','2008-01-29 01:35:36',5,'Last Transaction Date','last_trans_date','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:36:13','2008-01-29 01:36:13',6,'Last Transaction Fail Date','last_trans_fail_date','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:36:36','2008-01-29 01:36:36',7,'Number of Service Charges','num_service_charges','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:37:01','2008-01-29 01:37:01',8,'Number of Scheduled Events','num_events_scheduled','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:37:55','2008-01-29 01:37:55',9,'Number of Failed Returns','num_failed_returns','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:38:12','2008-01-29 01:38:12',10,'Number of Quick Checks','num_quick_checks','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:39:22','2008-01-29 01:38:33',11,'Balance Principal','balance_principal','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:38:55','2008-01-29 01:38:55',12,'Balance Fees','balance_fees','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',13,'Application Date Modified','date_modified','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',14,'Application Date Created','date_created','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',15,'Application Company Id','company_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',16,'Application Application Id','application_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',17,'Application Customer Id','customer_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',18,'Application Archive DB2 Id','archive_db2_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',19,'Application Archive MySQL Id','archive_mysql_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',20,'Application Archive Cashline Id','archive_cashline_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',21,'Application Login Id','login_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',22,'Application Is React','is_react','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',23,'Application Loan Type Id','loan_type_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',24,'Application Rule Set Id','rule_set_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:17','0000-00-00 00:00:00',25,'Application Enterprise Site Id','enterprise_site_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',26,'Application Application Status Id','application_status_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',27,'Application Date Application Status Set','date_application_status_set','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',28,'Application Date Next Contact','date_next_contact','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',29,'Application Ip Address','ip_address','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',30,'Application Application Type','application_type','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',31,'Application Bank Name','bank_name','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',32,'Application Bank ABA','bank_aba','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',33,'Application Bank Account','bank_account','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',34,'Application Bank Account Type','bank_account_type','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',35,'Application Date Fund Estimated','date_fund_estimated','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',36,'Application Date Fund Actual','date_fund_actual','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',37,'Application Date First Payment','date_first_payment','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',38,'Application Fund Requested','fund_requested','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',39,'Application Fund Qualified','fund_qualified','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',40,'Application Fund Actual','fund_actual','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',41,'Application Finance Charge','finance_charge','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',42,'Application Payment Total','payment_total','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:18','0000-00-00 00:00:00',43,'Application APR','apr','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:51:20','0000-00-00 00:00:00',44,'Application Income Monthly','income_monthly','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',45,'Application Income Source','income_source','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',46,'Application Income Direct Deposit','income_direct_deposit','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',47,'Application Income Frequency','income_frequency','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',48,'Application Income Date Soap 1','income_date_soap_1','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',49,'Application Income Date Soap 2','income_date_soap_2','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:55','0000-00-00 00:00:00',50,'Application Paydate Model','paydate_model','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',51,'Application Day of the Week','day_of_week','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',52,'Application Last Paydate','last_paydate','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',53,'Application Day of the Month 1','day_of_month_1','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',54,'Application Day of the Month 2','day_of_month_2','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',55,'Application Week 1','week_1','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',56,'Application Week 2','week_2','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',57,'Application Track ID','track_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',58,'Application Agent ID','agent_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',59,'Application Agent ID Callcenter','agent_id_callcenter','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',60,'Application Date of Birth','dob','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',61,'Application SSN','ssn','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',62,'Application Legal ID Number','legal_id_number','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',63,'Application Legal ID State','legal_id_state','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',64,'Application Legal ID Type','legal_id_type','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',65,'Application Identity Verified','identity_verified','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',66,'Application Email','email','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',67,'Application Email Verified','email_verified','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',68,'Application Name Last','name_last','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',69,'Application Name First','name_first','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',70,'Application Name Middle','name_middle','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:56','0000-00-00 00:00:00',71,'Application Name Suffix','name_suffix','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',72,'Application Street','street','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',73,'Application Unit','unit','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',74,'Application City','city','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',75,'Application State','state','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',76,'Application Zip Code','zip_code','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',77,'Application Tenancy Type','tenancy_type','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',78,'Application Phone Home','phone_home','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',79,'Application Phone Cell','phone_cell','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',80,'Application Phone Fax','phone_fax','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',81,'Application Call Time Pref','call_time_pref','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',82,'Application Contact Method Pref','contact_method_pref','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',83,'Application Marketing Contact Pref','marketing_contact_pref','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',84,'Application Employer Name','employer_name','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',85,'Application Job Title','job_title','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',86,'Application Supervisor','supervisor','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',87,'Application Shift','shift','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',88,'Application Date Hire','date_hire','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',89,'Application Job Tenure','job_tenure','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',90,'Application Phone Work','phone_work','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',91,'Application Phone Work Ext','phone_work_ext','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',92,'Application Work Address 1','work_address_1','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',93,'Application Work Address 2','work_address_2','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',94,'Application Work City','work_city','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',95,'Application Work State','work_state','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',96,'Application Work Zip Code','work_zip_code','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',97,'Application Employment Verified','employment_verified','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',98,'Application PWADVID','pwadvid','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',99,'Application OLP Process','olp_process','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',100,'Application Is Watched','is_watched','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',101,'Application Schedule Model ID','schedule_model_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',102,'Application Modifying Agent Id','modifying_agent_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:57','0000-00-00 00:00:00',103,'Application County','county','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:58','0000-00-00 00:00:00',104,'Application CFE Rule Set Id','cfe_rule_set_id','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:58','2008-01-29 01:58:58',105,'Application Status Level1','status_level_1','');
INSERT INTO `cfe_variable` VALUES ('2008-01-29 01:58:58','2008-01-29 01:58:58',106,'Application Status Level2','status_level_2','');
INSERT INTO `cfe_variable` VALUES ('2008-02-12 08:24:22','2008-02-12 08:24:22',107,'Application Status Old','application_status_old','');
INSERT INTO `cfe_variable` VALUES ('2008-02-12 08:24:22','2008-02-12 08:24:22',108,'Queue From','Queue','');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-04-03 15:18:04
