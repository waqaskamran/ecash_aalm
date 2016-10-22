CREATE TABLE IF NOT EXISTS `ach_return_code` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'CONVENIENCE',
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'CONVENIENCE',
  `active_status` enum('active','inactive') NOT NULL default 'active' COMMENT 'Deprecated?',
  `ach_return_code_id` int(10) unsigned NOT NULL COMMENT 'Primary key',
  `name_short` varchar(20) NOT NULL default '' COMMENT 'Actual return code from batch file',
  `name` varchar(150) NOT NULL COMMENT 'What this means to me',
  `is_fatal` enum('yes','no') NOT NULL default 'no' COMMENT 'If this is more of a warning or an error',
  PRIMARY KEY  (`ach_return_code_id`),
  UNIQUE KEY `idx_return_code_name` (`name_short`)
) ENGINE=InnoDB COMMENT='Stores a mapping of ACH return codes to human readable strings';
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:08','2005-01-07 06:32:08','active',1,'A1','Payment Cleared','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:08','2005-01-07 06:32:08','active',2,'A2','Paid','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:09','2005-01-07 06:32:09','active',3,'A3','Void Processing','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:09','2005-01-07 06:32:09','active',4,'CLEAR','Cleared','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:09','2005-01-07 06:32:09','active',5,'CPAY','Elecchk.com Customer Payment','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:09','2005-01-07 06:32:09','active',6,'N1','Returned NSF','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:09','2005-01-07 06:32:09','active',7,'PROC','Processing','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-01-07 06:32:09','active',8,'R01','Insufficient funds','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:09','active',9,'R02','Account closed','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-01-07 06:32:09','active',10,'R03','No account or unable to locate account','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-01-07 06:32:09','active',11,'R04','Invalid account number','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:09','active',12,'R05','UnAuthorized Debit to Consumer Account using Corporate SEC','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-01-07 06:32:09','active',13,'R06','Returned per ODFI\'s request','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:10','active',14,'R07','Authorization revoked by customer','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:10','active',15,'R08','Payment stopped or stop payment on item','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-01-07 06:32:10','active',16,'R09','Uncollected funds','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:10','active',17,'R16','Account frozen','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:10','active',18,'R51','Item is ineligible, notice not provided, signature not genuine, item altered, or amount of entry not accurately obtained from','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-01-07 06:32:10','active',19,'R52','Stop payment on item','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:10','2005-01-07 06:32:10','active',20,'R99','Do Not Redeposit','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-01-07 06:32:10','2005-01-07 06:32:10','active',21,'VOID','Check has been voided','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-08-12 00:23:17','active',22,'R10','Customer advises not authorized; item is ineligible, notice not provided, signatures not genuine, or item altered','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',23,'R11','Check truncation entry return or state law affecting acceptance of PPD debit entry constituting notice of presentment','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',24,'R12','Branch sold to another DFI','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',25,'R14','Representative payee deceased or unable to continue in that capacity','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',26,'R15','Befeficiary or account holder (other than a representative payee) deceased','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',27,'R17','File record edit criteria','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',28,'R20','Non-transaction account','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',29,'R21','Invalid company ID','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',30,'R22','Invalid Individual ID','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',31,'R23','Credit entry refused by receiver','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',32,'R24','Duplicate entry','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2005-08-12 00:23:17','active',33,'R29','Corporate customer advises not authorized','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',34,'R31','Permissible return entry (CCD & CTX)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',35,'R33','Return of XCK Entry','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',36,'R40','Return of ENR Entry by federal government agency (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',37,'R41','Invalid transaction code (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',38,'R42','Routing number / check digit error (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',39,'R43','Invalid DFI account number (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',40,'R44','Invalid Individual ID (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',41,'R45','Invalid Individual Name or company name (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',42,'R46','Invalid representative payee indicator (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',43,'R47','Duplicate enrollment (ENR only)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-08-12 00:23:17','2005-08-12 00:23:17','active',44,'R80','Cross Border Payment Coding Error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-08-12 00:23:17','2005-08-12 00:23:17','active',45,'R81','Non Participant in Cross Border Program','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-08-12 00:23:17','2005-08-12 00:23:17','active',46,'R82','Invalid Foreign Receiving DFI Identification','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-08-12 00:23:17','2005-08-12 00:23:17','active',47,'R83','Foreign Receiving DFI Unable To Settle','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2005-08-12 00:23:17','2005-08-12 00:23:17','active',48,'R84','Entry Not Processed by OGO','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',49,'R61','Misrouted return (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',50,'R62','Incorrect trace number (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',51,'R63','Incorrect dollar amount (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',52,'R64','Incorrect Individual ID (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',53,'R65','Incorrect transaction code (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',54,'R66','Incorrect company ID (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',55,'R67','Duplicate return (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',56,'R68','Untimely return (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',57,'R69','Multiple errors (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',58,'R70','Permissible return entry not accepted (Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',59,'R13','RDFI not qualified to participate','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',60,'R18','Improper effective entry date','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',61,'R19','Amount field error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',62,'R25','Addenda error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',63,'R26','Mandatory field error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',64,'R27','Trace number error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',65,'R28','Routing number check digit error','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',66,'R30','RDFI not a participant in check truncation','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',67,'R32','RDFI Non-Settlement','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',68,'R34','Limited Participation DFI','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',69,'R71','Misrouted dishonored return (Contested Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2005-08-12 00:23:17','active',70,'R72','Untimely dishonored return (Contested Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',71,'R35','Return of improper debit entry','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',72,'R36','Return of improper credit entry','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',73,'R37','Source Document Presented for Payment','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2006-03-01 00:20:33','active',74,'R38','Stop Payment on Source Document','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',75,'R50','State Law Affecting RCK Acceptance','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',76,'R53','Item and ACH Entry Presented for Payment','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',77,'R73','Timely original return (Contested Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-01 00:20:33','2006-03-01 00:20:33','active',78,'R74','Corrected return (Contested Dishonored return)','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-03-17 00:46:16','2006-03-17 00:46:16','active',79,'R39','Improper Source Document','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',80,'P-N','Insufficient Funds','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',81,'P-E','Endorsement','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',82,'P-X','Other','no');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',83,'P-A','Account Closed','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',84,'P-S','Stop Payment','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',85,'P-F','Forged','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',86,'P-U','Unable to locate','yes');
INSERT  IGNORE INTO `ach_return_code` VALUES ('2006-06-05 17:12:56','2006-06-05 17:12:56','active',87,'P-R','Refer to maker','yes');
