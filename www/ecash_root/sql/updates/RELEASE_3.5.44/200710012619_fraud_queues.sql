INSERT INTO section (date_modified,date_created,active_status,system_id,name,description,section_parent_id,sequence_no,level,read_only_option)
VALUES
	(now(), now(), 'active', 3, 'fraud_queue',"Fraud Queue", 1085, 50, 3, 0), 
	(now(), now(), 'active', 3, 'high_risk_queue',"High Risk Queue", 1085, 50, 3, 0);