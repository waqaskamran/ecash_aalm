-- MySQL dump 10.11
--
-- Host: writer.ecashimpact.ept.tss    Database: ldb_impact
-- ------------------------------------------------------
-- Server version	5.0.17-pro-gpl-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `loan_actions`
--

TRUNCATE loan_actions;

--
-- Dumping data for table `loan_actions`
--

INSERT INTO `loan_actions` VALUES (1,'no_answer_at_work','No Answer at Work#','INACTIVE','');
INSERT INTO `loan_actions` VALUES (2,'does_not_work_here','Contact at work# and \"Doesn\'t Work Here\"','INACTIVE','');
INSERT INTO `loan_actions` VALUES (3,'work_answers_hello','Work# Answer \"Hello\"','INACTIVE','');
INSERT INTO `loan_actions` VALUES (4,'work_invalid','Work# Invalid  (not company, etc)','INACTIVE','');
INSERT INTO `loan_actions` VALUES (5,'work_disconnected','Work# Disconnected','INACTIVE','');
INSERT INTO `loan_actions` VALUES (6,'self_or_unemployed','Self Employed or Unemployed','INACTIVE','');
INSERT INTO `loan_actions` VALUES (7,'no_work_number','Customer can\'t provide updated Work Phone','INACTIVE','');
INSERT INTO `loan_actions` VALUES (8,'self_employed','Customer Self Employed','INACTIVE','');
INSERT INTO `loan_actions` VALUES (9,'does_not_want_loan','Customer does not want loan - Loan Withdrawn','ACTIVE','CS_WITHDRAW');
INSERT INTO `loan_actions` VALUES (10,'resides_in_ks_ga','Customer resides in KS or GA - Unable to Fund','INACTIVE','');
INSERT INTO `loan_actions` VALUES (11,'three_assoc_account','3+ SSNs/People associated with Bank Acct','INACTIVE','');
INSERT INTO `loan_actions` VALUES (12,'datax_perform_fail','DataX IDV and/or Performance Failure','INACTIVE','');
INSERT INTO `loan_actions` VALUES (13,'no_contact','No Contact','INACTIVE','');
INSERT INTO `loan_actions` VALUES (14,'work_unsure','Contact at work# and \"not sure if works there\"','INACTIVE','');
INSERT INTO `loan_actions` VALUES (15,'unable_to_contact','Unable to contact Customer','INACTIVE','');
INSERT INTO `loan_actions` VALUES (16,'answered_name_company','Answered with name and company','INACTIVE','');
INSERT INTO `loan_actions` VALUES (17,'name_company_voicemail','Name and Company in Voicemail','INACTIVE','');
INSERT INTO `loan_actions` VALUES (18,'super_verify','H/R or Co-Worker Verification','INACTIVE','');
INSERT INTO `loan_actions` VALUES (19,'talked_with_person','Talked with Applicant/Customer','INACTIVE','');
INSERT INTO `loan_actions` VALUES (20,'check_load_bearing','Customer Checking Account is load bearing card','INACTIVE','');
INSERT INTO `loan_actions` VALUES (21,'account_no_debied','Checking Account cannot be debited','INACTIVE','');
INSERT INTO `loan_actions` VALUES (22,'contacted_at_work','Contacted Customer at Work - Verified Paydates','INACTIVE','');
INSERT INTO `loan_actions` VALUES (23,'contacted_at_home','Contacted Customer at Home - Verified Paydates','INACTIVE','');
INSERT INTO `loan_actions` VALUES (24,'unable_verify_paydates','Unable to Verify Paydates','INACTIVE','');
INSERT INTO `loan_actions` VALUES (25,'specify_other','Other [Specify Reason]','ACTIVE','PRESCRIPTION,FUND_DENIED,FUND_WITHDRAW,FUND_APPROVE,CS_VERIFY,CS_REVERIFY');
INSERT INTO `loan_actions` VALUES (26,'talked_no_home_phone','Talked with Applicant - Does not have home phone','INACTIVE','');
INSERT INTO `loan_actions` VALUES (27,'approve_three_ssn','3+ SSNs/People associated with checking account','INACTIVE','');
INSERT INTO `loan_actions` VALUES (28,'VERIFY_SAME_WH','Work and home phone are the same.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (29,'ABA_CHECK','More than three social security numbers have been used with the specified bank account and routing number.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (30,'VERIFY_MIN_INCOME','Self-reported monthly income is below  1,300.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (31,'VERIFY_WORK_BIZ','Work phone may be a residential number.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (32,'VERIFY_WORK_CELL','Work phone may be a cellular number.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (33,'LIST_VERIFY_BANK_ABA_1','Bank ABA must be verified.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (34,'cs_correct_work_number','Customer gave correct work number','ACTIVE','CS_VERIFY');
INSERT INTO `loan_actions` VALUES (35,'cs_correct_home_number','Customer gave correct home number','ACTIVE','CS_VERIFY');
INSERT INTO `loan_actions` VALUES (36,'cs_verified_income','Customer verified income','ACTIVE','CS_VERIFY');
INSERT INTO `loan_actions` VALUES (37,'cs_verified_paydays','Customer verified paydays','ACTIVE','CS_VERIFY');
INSERT INTO `loan_actions` VALUES (38,'cs_verified_employer','Customer verified employer','ACTIVE','CS_VERIFY');
INSERT INTO `loan_actions` VALUES (39,'w_cu_all_unable','Unable to contact Customer','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (40,'w_cw_all_customer_not','Customer does not want loan - Loan Withdrawn','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (41,'w_eu_all_no_contact','No Contact','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (42,'w_eu_all_contact_no_work','Contact at work# and \"not sure if works there\"','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (43,'w_ew_all_no_answer_work','No Answer at Work#','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (44,'w_ew_all_answer_work_hello','Work# Answer \"Hello\"','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (45,'w_ew_all_work_invalid','Work# Invalid (not company, etc)','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (46,'w_ew_all_work_disconnect','Work# Disconnected','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (47,'w_en_aml_self_employ','Self Employed - bank statement','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (48,'w_eu_aml_contact_at_work_inval','Contact at work# & uses invalid verification method (the work #) - paystub','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (49,'w_pu_aml_unable_verify','Unable to Verify Paydates - paystub','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (50,'w_wn_aml_customer_work_phone','Customer can\'t provide updated Work Phone','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (51,'w_wn_aml_waiting_Loan_doc','Waiting on updated Loan Document (Changed amount or date)','ACTIVE','FUND_WITHDRAW');
INSERT INTO `loan_actions` VALUES (52,'d_en_all_self_employed','Self Employed or Unemployed','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (53,'d_en_all_contact_work_not_work','Contact at work# and \"Doesn\'t Work Here\"','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (54,'d_wn_all_No_work_phone','Customer can\'t provide updated Work Phone ','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (55,'d_on_all_customer_ks_ga','Customer resides in KS or GA - Unable to Fund','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (56,'d_on_all_3_ssn','3+ SSNs associated with Bank Acct','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (57,'d_on_all_datax_FAIL','DataX IDV and/or Performance Failure','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (58,'d_an_all_checking_load','Customer Checking Account is load bearing card','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (59,'d_an_all_checking_not_debit','Checking Account cannot be debited','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (60,'a_ep_alll_answered','Answered with name and company','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (61,'a_ep_all_voicemail','Name and Company in Voicemail','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (62,'a_ep_all_hr_verify','H/R or Co-Worker Verification','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (63,'a_ep_all_talked_work','Talked with Applicant/Customer at Work','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (64,'a_ep_all_talked_home','Talked with Applicant/Customer at Home','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (65,'a_ep_all_verified_military','Verifed employment with military.com','INACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (66,'a_ep_all_verified_em_line','Verifed employment with employment verification line','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (67,'a_ep_all_talked_applicant','Talked with Applicant - Does not have home phone','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (68,'a_eu_all_contact_work_invalid','Contact at work# & uses invalid verification method (the work #)','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (69,'a_eu_all_no_contact','No Contact','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (70,'a_eu_all_contact_not_sure_work','Contact at work# and \"not sure if works there\"','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (71,'a_pp_all_contact_work_verfied','Contacted Customer at Work - Verified Paydates','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (72,'a_pp_all_contact_home_verfied','Contacted Customer at Home - Verified Paydates','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (73,'e_pu_all_unable_verrfiy','Unable to Verify Paydates ','ACTIVE','FUND_APPROVE');
INSERT INTO `loan_actions` VALUES (74,'LIST_VERIFY_BANK_ABA_15','ABA Watch List 11-2-2005','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (75,'LIST_VERIFY_EMPLOYER_NAME_14','Employer Watch List 11-2-2005','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (76,'DATAX_PERF','Could not complete verification due to a missing or invalid DataX response.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (77,'VERIFY_PAYDATES','Pay dates are within 5 days of each other.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (78,'cu_invalid_home_number','Home phone number is invalid','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (79,'cu_invalid_work_number','Work phone number is invalid','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (80,'fd_fraud_on_list','Fraud, On List','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (81,'fd_high_risk_on_list','High Risk, On List','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (82,'fd_sent_to_fraud_by_underwriti','Sent to Fraud by Underwriting','ACTIVE','FUND_DENIED');
INSERT INTO `loan_actions` VALUES (83,'no_datax_call','DataX call failed','ACTIVE',NULL);
INSERT INTO `loan_actions` VALUES (84,'VERIFY_W_TOLL_FREE','Work phone is a toll free number.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (85,'VERIFY_WH_AREA','Home and work area code mismatch.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (86,'VERIFY_W_PHONE','Unverified work phone.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (87,'VERIFY_SAME_CR_W_PHONE','Cell or residential phone in work phone.','INACTIVE','PRESCRIPTION');
INSERT INTO `loan_actions` VALUES (88,'cs_reverify_qualified','Customer does not qualify for loan amount','ACTIVE','CS_REVERIFY');
INSERT INTO `loan_actions` VALUES (89,'cs_reverify_payday','Due date does not fall on a payday','ACTIVE','CS_REVERIFY');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-03-31 22:16:02
