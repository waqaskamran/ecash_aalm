//This needs to be run on Impact and IIC to enable email queues.


UPDATE section set active_status = 'active' where name = 'servicing_email_queue';

INSERT INTO document_list 
SELECT NOW(), NOW(), 'active', company_id, NULL, 'Incoming Email Document', 'other_email_document', 'yes', 'no', 3, 'email', 'condor', null, null, 'yes' FROM company WHERE active_status = 'active';

