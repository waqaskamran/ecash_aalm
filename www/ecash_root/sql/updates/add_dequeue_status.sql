set autocommit=0;

insert into application_status
values (current_timestamp, current_timestamp, 'active', null,
	'Collections (Dequeued)', 'indef_dequeue', 110, 3);

update application
set application_status_id = (select application_status_id 
				from application_status
				where name_short = 'indef_dequeue')
where application_status_id = (select application_status_id
				from application_status_flat
				where level0 = 'follow_up'
				and level1 = 'contact'
				and level2 = 'collections')
and date_next_contact = '2010-01-01';

commit;