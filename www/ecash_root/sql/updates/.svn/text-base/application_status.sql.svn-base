
update `application_status`
set date_modified = now(),
	active_status = 'active'
where name = 'In Fraud';

update `application_status`
set date_modified = now(),
	active_status = 'active'
where name = 'Fraud Queued';

update `application_status`
set date_modified = now(),
	active_status = 'active'
where name = 'Fraud Follow Up';

update `application_status`
set date_modified = now(),
	active_status = 'active',
	name = 'Fraud Confirmed',
	name_short = 'confirmed'
where name = 'Fraud Verified';

update `application_status`
set date_modified = now(),
	active_status = 'active'
where name = 'Fraud';

INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','2005-10-27 01:00:58','active',146,'High Risk','high_risk',101,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',147,'In High Risk','dequeued',146,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',148,'High Risk Queued','queued',146,2);
INSERT  IGNORE INTO `application_status` VALUES ('2006-02-11 00:03:33','0000-00-00 00:00:00','active',149,'High Risk Follow Up','follow_up',146,2);
