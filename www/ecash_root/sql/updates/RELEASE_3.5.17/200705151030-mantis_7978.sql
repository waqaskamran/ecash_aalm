/* Add Email Queue Section to Loan Servicing */
INSERT INTO section (date_created, active_status, system_id,name,description,
 section_parent_id,level,sequence_no,read_only_option)
VALUES 
 (NOW(), 'active', 3, 'manager_email_queue', 'Manager Email Queue', 1106, 3, 30, 0);
