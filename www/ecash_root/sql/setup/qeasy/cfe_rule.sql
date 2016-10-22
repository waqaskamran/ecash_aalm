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
-- Table structure for table `cfe_rule`
--


TRUNCATE cfe_rule;

--
-- Dumping data for table `cfe_rule`
--

INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2974,1,'add_follow_up_servicing_hold',4,42);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2975,1,'add_follow_up_pre_fund',4,43);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2976,1,'add_follow_up_amortization',4,44);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2977,1,'Default Queue Insert',5,33);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2978,1,'Status_Change_inactive',3,0);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2979,1,'add_follow_up_past_due',4,40);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2980,1,'add_follow_up_collections1',4,4);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2981,1,'add_follow_up_funding_failed',4,41);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2982,1,'add_follow_up_underwriting_react',4,2);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2983,1,'Status_Change_high_risk',3,6);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2984,1,'add_follow_up_collections2',4,1);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2985,1,'Default Queue Insert react',5,35);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2986,1,'DO NOT DELETE THIS RULE, OR ELSE!',1,32);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2987,1,'Status_change_funding_failed',3,5);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2988,1,'Status_Change_PastDue',3,12);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2989,1,'add_follow_up_underwriting',4,3);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2990,1,'Status_Change_verification_nonreact',3,7);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2991,1,'Status_Change_collections_new',3,13);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2992,1,'Status_Change_verification_react',3,8);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2993,1,'Status_Change_collections_contact',3,11);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2994,1,'Status_Change_fraud',3,9);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2995,1,'Status_Change_underwriting_nonreact',3,14);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2996,1,'Status_Change_underwriting_react',3,15);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2997,1,'Status_change_sent_quickchecks',3,16);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2998,1,'Status_change_unverified_bankruptcy',3,20);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',2999,1,'Status_change_active',3,21);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3000,1,'Status_change_verified_bankruptcy',3,23);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3001,1,'Status_change_sent_external',3,19);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3002,1,'add_follow_up_active',4,31);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3003,1,'add_follow_up_verification',4,27);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3004,1,'Status_change_denied',3,25);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3005,1,'Status_change_bankruptcy_dequeued',3,18);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3006,1,'Status_change_pre_fund',3,26);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3007,1,'Status_change_withdrawn',3,24);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3008,1,'Status_change_ready_quickchecks',3,17);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3009,1,'add_follow_up_verification_react',4,28);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3010,1,'Status_change_bankruptcy_queued',3,22);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3011,1,'Accepted_approved_react',5,37);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3012,1,'Accepted_approved_followup_react',5,39);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3013,1,'Status_Change_made_arrangments',3,29);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3014,1,'Accepted_approved_followup',5,38);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3015,1,'Accepted_approved',5,36);
INSERT INTO `cfe_rule` VALUES ('2008-02-27 10:17:48','2008-02-27 10:17:47',3016,1,'add_follow_up_agree',4,45);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-04-03 15:16:57
