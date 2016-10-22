
--- Main screen module
select
@ecash3_system_id := s.system_id as system_id,
@ecash_section_id := s.section_id as section_id,
@new_level := s.level+1 as new_level,
@new_sequence := max(s2.sequence_no) as last_sequence
from section s
inner join section s2 on (s2.section_parent_id = s.section_id)
inner join system sys on (s.system_id = sys.system_id)
where sys.name_short = 'ecash3_0'
and s.level = 1
and s2.sequence_no < 1000
group by new_level
;


INSERT INTO section (date_modified, date_created, active_status, system_id, name, description, section_parent_id,sequence_no, level, read_only_option)
values(now(), now(), 'active', @ecash3_system_id, 'main_screen', 'Main Screen', @ecash_section_id, @last_sequence+5, @new_level, 0);

delete from flag_type
where name_short in ('csr', '2hr_email', '4hr_email', 'ty_email', 'ach_email', 'raf_notice');


insert into flag_type 
(date_modified, date_created, active_status, name, name_short)
values
(now(), now(), 'active', 'OFAC', 'ofac'),
(now(), now(), 'active', 'SafeScan', 'safescan'),
(now(), now(), 'active', 'Suspect Fraud', 'suspect_fraud'),
(now(), now(), 'active', 'Deceased Notification', 'deceased_notification'),
(now(), now(), 'active', 'Bankruptcy Notification', 'bankruptcy_notification'),
(now(), now(), 'active', 'Cease & Desist', 'cease_desist'),
(now(), now(), 'active', 'SSCRA', 'sscra'),
(now(), now(), 'active', 'Arrangements Failed', 'arrangements_failed')
;

/* maybes
(now(), now(), 'active', 'Pending Verification', 'approved'),
(now(), now(), 'active', 'No Electronic Payments', 'arrangements_hold'),
(now(), now(), 'active', '(Collections) Contact', 'collections_contact'),
(now(), now(), 'active', '(Collections) Contact Follow Up', 'collections_contact_follow_up'),
(now(), now(), 'active', 'Pending Adverse Action', 'declined'),
(now(), now(), 'active', 'Waiting E-Sign', 'pending'),
(now(), now(), 'active', 'Electronic Payments Stopped', 'servicing_hold'),
(now(), now(), 'active', 'Adverse Action', 'adverse_action'),
(now(), now(), 'active', 'Credit Counseling Agency', 'credit_counseling_agency'),
(now(), now(), 'active', 'Rescind', 'rescind'),
;
*/

/*

*root
	prospect
	applicant
	customer
	external_collections
*/

-- top level changes

select 
@root_id := as.application_status_id
from application_status `as`
where name_short = '*root';

update application_status `as`
set active_status = 'inactive'
where application_status_parent_id = @root_id
and name_short = 'applicant';

-- void nodes

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Void', 'void', @root_id, 1);

set @void_id := last_insert_id();

update application_status `as`
set application_status_parent_id = @void_id
where name_short in ('withdrawn', 'confirm_declined', 'disagree', 'fraud', 'funding_failed');

update
application_status `as`,
application_status `asp`
set as.active_status = 'inactive'
where as.application_status_parent_id = asp.application_status_id
and asp.name_short = 'fraud';

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Rescind', 'rescind', @void_id, 2);


-- prospect nodes

update application_status `as`
set name = 'Pending'
where name_short = 'prospect';

update 
	application_status `as`,
	application_status `asp`
set as.active_status = 'inactive'
where as.application_status_parent_id = asp.application_status_id
and asp.name_short = 'prospect'
and as.name_short in ('in_process', 'pending', 'preact_agree', 'preact_confirmed', 'preact_pending');

select 
@prospect_id := as.application_status_id
from application_status `as`
where name_short = 'prospect';

insert into application_status
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Approved', 'prospect_approved', @prospect_id, 2);

update application_status
set application_status_parent_id = @prospect_id
where name_short in ('denied', 'approved');

update 
	application_status `as`,
	application_status `asp`
set as.name = 'Confirmed'
where as.application_status_parent_id = asp.application_status_id
and as.name_short = 'confirmed'
and asp.name_short = 'prospect';

-- paid off nodes

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Paid Off', 'paid_off', @root_id, 1);

set @paid_off_id := last_insert_id();

update application_status
set application_status_parent_id = @paid_off_id
where name_short = 'paid';


-- customer nodes

update application_status
set name = 'Active'
where name_short = 'customer';

select 
@customer_id := as.application_status_id
from application_status `as`
where name_short = 'customer';

update application_status
set application_status_parent_id = @customer_id
where name_short in ('active', 'past_due', 'verified');

update 
	application_status `as`,
	application_status `asp`
set as.application_status_parent_id = @customer_id
where as.application_status_parent_id = asp.application_status_id
and as.name_short = 'hold'
and asp.name_short = 'servicing';

update application_status `as`
set active_status = 'inactive'
where application_status_parent_id = @customer_id
and name_short in ('servicing', 'collections');

-- external collections nodes

update application_status `as`
set as.name = 'Sold'
where as.name_short = 'external_collections';


update 
	application_status `as`,
	application_status `asp`
set as.active_status = 'inactive'
where as.application_status_parent_id = asp.application_status_id
and asp.name_short = 'external_collections';

-- charge off nodes

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Charge Off', 'charge_off', @root_id, 1);


-- inactive nodes

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Inactive', 'inactive', @root_id, 1);

set @inactive_id := last_insert_id();

insert into application_status 
(date_modified, date_created, active_status, name, name_short, application_status_parent_id, level)
values
(now(), now(), 'active', 'Deceased', 'deceased', @inactive_id, 2);
