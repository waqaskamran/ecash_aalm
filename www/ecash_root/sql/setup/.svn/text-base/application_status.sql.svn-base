CREATE TABLE IF NOT EXISTS `application_status` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT "CONVENIENCE",
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT "CONVENIENCE",
  `active_status` enum('active','inactive') NOT NULL default 'active' COMMENT "Inactive statuses may have data, but are no longer used in the FSM models",
  `application_status_id` int(10) unsigned NOT NULL COMMENT "PRIMARY KEY",
  `name` varchar(100) NOT NULL default '' COMMENT "Human-readable name. NOTE: Non-unique!!!",
  `name_short` varchar(25) NOT NULL default '' COMMENT "Computer-readable name. NOTE: Only unique withing parent strata",
  `application_status_parent_id` int(10) unsigned default NULL COMMENT "Used to turn this into a tree",
  `level` tinyint(3) unsigned NOT NULL default '0' COMMENT "Make sure you set this correctly.  It represents how far down the tree the value is. Counts from zero",
  PRIMARY KEY  (`application_status_id`),
  UNIQUE KEY `idx_appsts_name_short_parent` (`name_short`,`application_status_parent_id`)
) ENGINE=InnoDB COMMENT="These statuses describe the life-path of an application";
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',1,'*root','*root',NULL,0);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',5,'Agree','agree',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',6,'Disagree','disagree',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-06-09 14:44:53','0000-00-00 00:00:00','active',7,'Prospect Confirmed','confirmed',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',8,'Confirmed','dequeued',106,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',9,'Confirmed','queued',106,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 16:49:52','0000-00-00 00:00:00','active',10,'Approved','queued',107,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 16:49:52','0000-00-00 00:00:00','active',11,'Approved','dequeued',107,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',12,'Duplicate','duplicate',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-04 19:31:52','0000-00-00 00:00:00','active',13,'Confirmed Followup','follow_up',106,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-04 19:31:41','0000-00-00 00:00:00','active',14,'Approved Followup','follow_up',107,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',15,'Confirm Declined','confirm_declined',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',16,'Pending','pending',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:59','0000-00-00 00:00:00','inactive',17,'In Process','in_process',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','0000-00-00 00:00:00','active',18,'Denied','denied',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:59','0000-00-00 00:00:00','active',19,'Withdrawn','withdrawn',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:59','0000-00-00 00:00:00','active',20,'Active','active',108,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:59','0000-00-00 00:00:00','inactive',21,'Cashline','cashline',102,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:59','0000-00-00 00:00:00','inactive',22,'Pending Approval','pending_approval',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',23,'In Fraud','dequeued',105,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',24,'Fraud Queued','queued',105,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',25,'Fraud Follow Up','follow_up',105,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',26,'Fraud Confirmed','confirmed',105,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-17 18:16:03','2005-11-17 18:16:03','active',27,'Declined','declined',100,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',100,'Prospect','prospect',1,1);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',101,'Applicant','applicant',1,1);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',102,'Customer','customer',1,1);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',103,'External Collections','external_collections',1,1);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',104,'Cashline','cashline',1,1);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','2005-10-27 01:00:58','inactive',105,'Fraud','fraud',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',106,'Verification','verification',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',107,'Underwriting','underwriting',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',108,'Servicing','servicing',102,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',109,'Inactive (Paid)','paid',102,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',110,'Collections','collections',102,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-02 16:25:00','2005-10-27 01:00:58','active',111,'Second Tier (Pending)','pending',103,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-02 16:25:11','2005-10-27 01:00:58','active',112,'Second Tier (Sent)','sent',103,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',113,'Inactive (Recovered)','recovered',103,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',114,'Pending Transfer','pending_transfer',104,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',115,'Conversion Queued','queued',104,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-22 18:57:50','2005-10-27 01:00:58','active',116,'In Conversion Queue','dequeued',104,2);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',117,'Arrangements','arrangements',110,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',118,'Bankruptcy','bankruptcy',110,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',119,'Contact','contact',110,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-10 01:00:50','2005-10-27 01:00:58','active',120,'Quick Check','quickcheck',110,3);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-09 21:50:08','2005-10-27 01:00:58','active',121,'Pre-Fund','approved',108,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',122,'Servicing Hold','hold',108,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',123,'Past Due','past_due',108,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',124,'Funding Failed','funding_failed',108,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',125,'Made Arrangements','current',117,4);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-14 17:07:56','2005-10-27 01:00:58','active',126,'Arngmnt Failed','arrangements_failed',117,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',127,'Arrangements Hold','hold',117,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-10 18:16:48','2005-10-27 01:00:58','inactive',128,'Bankruptcy Notification','dequeued',118,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-10 18:16:48','2005-10-27 01:00:58','inactive',129,'Bankruptcy Notification','queued',118,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',130,'Bankruptcy Notification','unverified',118,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-10-27 01:00:58','2005-10-27 01:00:58','active',131,'Bankruptcy Verified','verified',118,4);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-04 15:13:55','2005-10-27 01:00:58','active',132,'Collections Contact','dequeued',119,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-18 22:46:43','2005-10-27 01:00:58','active',133,'Contact Followup','follow_up',119,4);
INSERT  IGNORE INTO `application_status` VALUES ('2006-05-04 15:13:55','2005-10-27 01:00:58','active',134,'Collections Contact','queued',119,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-18 00:22:12','2005-10-31 18:51:36','active',135,'QC Ready','ready',120,4);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-18 00:22:34','2005-10-31 18:51:36','active',136,'QC Sent','sent',120,4);
INSERT  IGNORE INTO `application_status` VALUES ('2006-03-03 18:22:18','2006-03-03 18:22:18','active',137,'Collections New','new',110,3);
-- missing, added by JRF
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-18 00:22:12','2005-10-31 18:51:36','active',138,'Collections (Dequeued)','indef_dequeue',110,3);
INSERT  IGNORE INTO `application_status` VALUES ('2005-11-18 00:22:34','2005-10-31 18:51:36','active',139,'QC Returned','return',120,4);
INSERT  IGNORE INTO `application_status` VALUES ('2006-03-03 18:22:18','2006-03-03 18:22:18','active',140,'QC Arrangements','arrangements',120,4);
-- PUT PREACT SHIT HERE
-- for fraud/risk module
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','2005-10-27 01:00:58','active',146,'High Risk','high_risk',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',147,'In High Risk','dequeued',146,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',148,'High Risk Queued','queued',146,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',149,'High Risk Follow Up','follow_up',146,2);
INSERT  IGNORE INTO `application_status` VALUES (current_timestamp, current_timestamp, 'active', 150, 'Amortization', 'amortization', 118, 4);
