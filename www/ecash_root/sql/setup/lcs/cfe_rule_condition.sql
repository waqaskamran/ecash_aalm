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
-- Table structure for table `cfe_rule_condition`
--

TRUNCATE cfe_rule_condition;

--
-- Dumping data for table `cfe_rule_condition`
--

INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4022,2974,'equals','application_status',0,'hold::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4023,2975,'equals','application_status',0,'approved::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4024,2976,'equals','application_status',0,'amortization::bankruptcy::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4025,2977,'equals','application_status',0,'queued::verification::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4026,2977,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4027,2978,'equals','application_status',0,'paid::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4028,2979,'equals','application_status',0,'past_due::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4029,2980,'equals','status_level_1',0,'collections',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4030,2981,'equals','application_status',0,'funding_failed::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4031,2982,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4032,2982,'equals','status_level_2',0,'applicant',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4033,2982,'equals','status_level_1',0,'underwriting',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4034,2983,'equals','application_status',0,'queued::high_risk::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4035,2984,'equals','status_level_2',0,'collections',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4036,2985,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4037,2985,'equals','application_status',0,'queued::verification::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4038,2987,'equals','application_status',0,'funding_failed::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4039,2988,'equals','application_status',0,'past_due::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4040,2989,'equals','status_level_1',0,'underwriting',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4041,2989,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4042,2989,'equals','status_level_2',0,'applicant',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4043,2990,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4044,2990,'equals','application_status',0,'queued::verification::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4045,2991,'equals','application_status',0,'new::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4046,2992,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4047,2992,'equals','application_status',0,'queued::verification::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4048,2993,'equals','application_status',0,'queued::contact::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4049,2994,'equals','application_status',0,'queued::fraud::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4050,2995,'equals','application_status',0,'queued::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4051,2995,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4052,2996,'equals','application_status',0,'queued::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4053,2996,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4054,2997,'equals','application_status',0,'sent::quickcheck::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4055,2998,'equals','',0,'',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4056,2999,'equals','application_status',0,'active::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4057,3000,'equals','application_status',0,'verified::bankruptcy::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4058,3001,'equals','application_status',0,'sent::external_collections::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4059,3002,'equals','application_status',0,'active::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4060,3003,'equals','status_level_2',0,'applicant',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4061,3003,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4062,3003,'equals','status_level_1',0,'verification',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4063,3004,'equals','application_status',0,'denied::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4064,3005,'equals','application_status',0,'dequeued::bankruptcy::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4065,3006,'equals','application_status',0,'approved::servicing::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4066,3007,'equals','application_status',0,'withdrawn::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4067,3008,'equals','application_status',0,'sent::quickcheck::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4068,3009,'equals','status_level_1',0,'verification',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4069,3009,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4070,3009,'equals','status_level_2',0,'applicant',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4071,3010,'equals','application_status',0,'unverified::bankruptcy::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4072,3011,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4073,3011,'equals','application_status',0,'queued::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4074,3012,'equals','is_react',0,'yes',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4075,3012,'equals','application_status',0,'follow_up::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4076,3013,'equals','application_status',0,'current::arrangements::collections::customer::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4077,3014,'equals','application_status',0,'follow_up::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4078,3014,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4079,3015,'equals','is_react',0,'no',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4080,3015,'equals','application_status',0,'queued::underwriting::applicant::*root',1,1);
INSERT INTO `cfe_rule_condition` VALUES ('2008-02-27 10:17:48','0000-00-00 00:00:00',4081,3016,'equals','application_status',0,'agree::prospect::*root',1,1);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-04-03 15:17:30
