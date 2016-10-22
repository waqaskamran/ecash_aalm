-- MySQL dump 10.11
--
-- Host: monster.tss    Database: ldb_lcs
-- ------------------------------------------------------
-- Server version	5.0.44sp1-enterprise-gpl
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `transaction_type`
--

TRUNCATE `transaction_type`;

--
-- Dumping data for table `transaction_type`
--

INSERT INTO `transaction_type` VALUES ('2008-03-19 21:35:39','2007-04-03 17:52:54','active',1,1,'loan_disbursement','ACH Disbursement','ach','yes',1,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:54','active',1,2,'repayment_principal','Principal Payment','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:54','active',1,3,'payment_service_chg','Interest Payment','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-09-25 06:58:33','2007-04-03 17:52:54','active',1,4,'assess_service_chg','Interest','accrued charge','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:54','2007-04-03 17:52:54','active',1,5,'assess_fee_ach_fail','ACH Fee','accrued charge','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:54','active',1,6,'payment_fee_ach_fail','ACH Fee Payment','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:54','2007-04-03 17:52:54','active',1,7,'writeoff_fee_ach_fail','ACH Fee Writeoff','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:54','2007-04-03 17:52:54','active',1,8,'converted_principal_bal','Principal Balance Forward','adjustment','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-09-25 06:58:33','2007-04-03 17:52:54','active',1,9,'converted_service_chg_bal','Interest Balance Forward','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:54','2007-04-03 17:52:54','active',1,10,'adjustment_internal_fees','Internal Adjustment (Fees)','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:54','2007-04-03 17:52:54','active',1,11,'adjustment_internal_princ','Internal Adjustment (Principal)','adjustment','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:54','active',1,12,'payment_arranged_fees','Arranged Payment (Fees)','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:55','active',1,13,'payment_arranged_princ','Arranged Payment (Principal)','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,14,'payment_manual_fees','Manual Payment (Fees)','external','no',3,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,15,'payment_manual_princ','Manual Payment (Principal)','external','yes',3,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:55','active',1,16,'full_balance','Full Balance Pull','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,17,'quickcheck','QuickCheck','quickcheck','yes',60,'complete','calendar');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,18,'debt_writeoff_princ','Bad Debt Writeoff (Principal)','adjustment','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,19,'debt_writeoff_fees','Bad Debt Writeoff (Fees)','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,20,'ext_recovery_princ','Second Tier Recovery (Principal)','adjustment','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:55','2007-04-03 17:52:55','active',1,21,'ext_recovery_fees','Second Tier Recovery (Fees)','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,22,'money_order_fees','Money Order (Fees)','external','no',13,'failed','calendar');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,23,'money_order_princ','Money Order (Principal)','external','yes',13,'failed','calendar');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,24,'moneygram_fees','Moneygram (Fees)','external','no',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,25,'moneygram_princ','Moneygram (Principal)','external','yes',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,26,'western_union_fees','Western Union (Fees)','external','no',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2007-11-20 19:08:58','2007-04-03 17:52:55','active',1,27,'western_union_princ','Western Union (Principal)','external','yes',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2008-03-18 20:56:27','2007-04-03 17:52:55','active',1,28,'credit_card_fees','Credit Card (Fees)','external','no',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2008-03-18 20:56:27','2007-04-03 17:52:55','active',1,29,'credit_card_princ','Credit Card (Principal)','external','yes',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:55','active',1,30,'personal_check_fees','Personal Check (Fees)','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:55','active',1,31,'personal_check_princ','Personal Check (Principal)','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-09-25 06:58:33','2007-04-03 17:52:55','active',1,32,'converted_sc_event','Converted Interest Transaction','external','no',0,'complete','calendar');
INSERT INTO `transaction_type` VALUES ('2008-03-20 22:44:48','2007-04-03 17:52:55','active',1,33,'refund_3rd_party_princ','3rd Party Refund (Principal)','ach','yes',1,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-03-20 22:44:48','2007-04-03 17:52:55','active',1,34,'refund_3rd_party_fees','3rd Party Refund (Fees)','ach','no',1,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,35,'paydown','Paydown','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:56','2007-04-03 17:52:56','active',1,36,'cancel_fees','Cancel (Fees)','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,37,'cancel_principal','Cancel (Principal)','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,38,'payout_fees','Payout (Fees)','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,39,'payout_principal','Payout (Principal)','ach','yes',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:56','2007-04-03 17:52:56','active',1,40,'payment_debt_fees','Debt Consolidation (Fees)','external','no',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:56','2007-04-03 17:52:56','active',1,41,'payment_debt_principal','Debt Consolidation (Principal)','external','yes',3,'failed','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:56','2007-04-03 17:52:56','active',1,42,'ext_recovery_reversal_pri','Second Tier Recovery Reversal (Principal)','adjustment','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-04-03 17:52:56','2007-04-03 17:52:56','active',1,43,'ext_recovery_reversal_fee','Second Tier Recovery Reversal (Fees)','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-03-20 22:44:38','2007-04-03 17:52:56','active',1,44,'refund_princ','Refund (Principal)','ach','yes',1,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-03-20 22:44:38','2007-04-03 17:52:56','active',1,45,'refund_fees','Refund (Fees)','ach','no',1,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,46,'chargeback','Chargeback','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-04-03 17:52:56','active',1,47,'chargeback_reversal','Chargeback Reversal','ach','no',5,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 15:01:05','2007-05-09 14:57:12','active',1,236,'assess_fee_delivery','Delivery Fee','accrued charge','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-11 22:39:25','2007-05-09 14:57:20','active',1,237,'payment_fee_delivery','Delivery Fee Payment','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 15:01:05','2007-05-09 14:57:26','active',1,238,'writeoff_fee_delivery','Delivery Fee Writeoff','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 20:29:35','2007-05-09 20:29:35','active',1,251,'assess_fee_transfer','Wire Transfer Fee','accrued charge','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-11 22:39:25','2007-05-09 20:29:35','active',1,252,'payment_fee_transfer','Wire Transfer Fee Payment','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 20:29:35','2007-05-09 20:29:35','active',1,253,'writeoff_fee_transfer','Wire Transfer Fee Writeoff','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 21:37:31','2007-05-09 21:37:31','active',1,266,'assess_fee_lien','Lien Fee','accrued charge','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-11 22:39:25','2007-05-09 21:37:31','active',1,267,'payment_fee_lien','Lien Fee Payment','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-09 21:37:31','2007-05-09 21:37:31','active',1,268,'writeoff_fee_lien','Lien Fee Writeoff','adjustment','no',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-05-14 16:17:57','2007-05-14 16:17:57','active',1,281,'moneygram_disbursement','Moneygram Disbursement','external','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2007-08-28 16:26:48','2007-08-28 16:26:48','active',1,286,'check_disbursement','Check Disbursement','external','yes',0,'complete','business');
INSERT INTO `transaction_type` VALUES ('2008-02-06 00:28:37','2007-11-27 02:16:50','active',1,291,'payment_imga_fee','Adjustment Fee Payment','ach','no',5,'complete','business');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-03-21 21:01:47
