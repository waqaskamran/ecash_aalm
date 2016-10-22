## Inserting missing event_types for arranged payments fees & principal[josefn] and the event_transaction entries[richardb] 

INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '1', 'payment_arranged_fees', 'Arranged Fees Payment');
INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '1', 'payment_arranged_princ', 'Arranged Principal Payment');


INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '2', 'payment_arranged_fees', 'Arranged Fees Payment');
INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '2', 'payment_arranged_princ', 'Arranged Principal Payment');

INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '3', 'payment_arranged_fees', 'Arranged Fees Payment');
INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '3', 'payment_arranged_princ', 'Arranged Principal Payment');

INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '4', 'payment_arranged_fees', 'Arranged Fees Payment');
INSERT INTO event_type (date_created, active_status, company_id, name_short, name) VALUES (NOW(), 'active', '4', 'payment_arranged_princ', 'Arranged Principal Payment');

insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',1, (select event_type_id from event_type where company_id=1 and name_short = 'payment_arranged_fees'),(select transaction_type_id from transaction_type where company_id=1 and name_short = 'payment_arranged_fees')); 
insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',1, (select event_type_id from event_type where company_id=1 and name_short = 'payment_arranged_princ'),(select transaction_type_id from transaction_type where company_id=1 and name_short = 'payment_arranged_princ'));

insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',2, (select event_type_id from event_type where company_id=2 and name_short = 'payment_arranged_fees'),(select transaction_type_id from transaction_type where company_id=2 and name_short = 'payment_arranged_fees')); 
insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',2, (select event_type_id from event_type where company_id=2 and name_short = 'payment_arranged_princ'),(select transaction_type_id from transaction_type where company_id=2 and name_short = 'payment_arranged_princ'));

insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',3, (select event_type_id from event_type where company_id=3 and name_short = 'payment_arranged_fees'),(select transaction_type_id from transaction_type where company_id=3 and name_short = 'payment_arranged_fees')); 
insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',3, (select event_type_id from event_type where company_id=3 and name_short = 'payment_arranged_princ'),(select transaction_type_id from transaction_type where company_id=3 and name_short = 'payment_arranged_princ'));

insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',4, (select event_type_id from event_type where company_id=4 and name_short = 'payment_arranged_fees'),(select transaction_type_id from transaction_type where company_id=4 and name_short = 'payment_arranged_fees')); 
insert into event_transaction (date_modified,date_created,active_status,company_id,event_type_id,transaction_type_id) values (now(),now(),'active',4, (select event_type_id from event_type where company_id=4 and name_short = 'payment_arranged_princ'),(select transaction_type_id from transaction_type where company_id=4 and name_short = 'payment_arranged_princ'));

