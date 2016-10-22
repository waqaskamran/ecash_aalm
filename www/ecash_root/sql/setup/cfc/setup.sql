DELETE FROM document_list WHERE name NOT LIKE 'Letter%';

update rule_set set name = 'CFC Default Rule Set' where name = 'CFC Payday Rule Set';

UPDATE rule_set SET name = 'CFC Default Rule Set -Gold' where
name = 'CFC Default Rule Set'; -- 281

UPDATE rule_set SET name = 'CFC Default Rule Set -Classic' where
name = 'CFC Title Loan Rule Set'; -- 284

update rule_set rs, rule_set_component rsc
set rsc.active_status = 'inactive'
where rs.rule_set_id = rsc.rule_set_id
and rs.name = 'CFC Default Rule Set -Classic'; -- 284 inactivate components

update rule_set rs, rule_set_component rsc
set rsc.active_status = 'inactive'
where rs.rule_set_id = rsc.rule_set_id
and rs.name = 'CFC Default Rule Set -Gold'; -- 281 inactivate components


UPDATE rule_set_component rsc, rule_component rc, rule_set rs
SET rsc.active_status = 'inactive'
where rsc.rule_component_id = rc.rule_component_id
and rsc.rule_set_id = rs.rule_set_id
and rs.name = 'CFC Company Level Rule Set'
and rc.name_short = 'fraud_settings';

UPDATE rule_component_parm rcp, rule_component rc
SET active_status = 'inactive'
WHERE rcp.rule_component_id = rc.rule_component_id
and rc.name_short in ('recycle_limit', 'queue_timeout')
AND rcp.parm_name != 'verification';

UPDATE rule_component_parm rcp, rule_component rc
SET sequence_no = 1
WHERE rcp.rule_component_id = rc.rule_component_id
and rc.name_short in ('recycle_limit', 'queue_timeout')
AND parm_name = 'verification';

UPDATE rule_set_component rsc, rule_set rs
SET rsc.active_status = 'inactive'
WHERE rsc.rule_set_id = rs.rule_set_id
and rs.name = 'CFC Nightly Task Schedule';

-- rename some statuses to be used (and move others out of the way)
update application_status set name = 'Self Declined' where name = 'Declined';
update application_status set name = 'Declined' where name = 'Denied';
update application_status set name = 'Prospect Pending' where name = 'Pending';
update application_status set name = 'Pending' where name = 'Confirmed';
update application_status set name = 'Underwriting Approved' where name = 'Approved';
update application_status set name = 'Approved' where name = 'Pre-fund';

INSERT INTO loan_actions (name_short, description, status, type)
VALUES 
('IDVSSNFAIL', 'IDV SSN Fail', 'ACTIVE', 'FUND_DENIED'),
('IDVCONTACTFAIL', 'IDV Address or Phone Fail', 'ACTIVE', 'FUND_DENIED'),
('FACT', 'FACT Act Hit', 'INACTIVE', 'FUND_DENIED'),
('TUNOHIT', 'No Credit File', 'ACTIVE', 'FUND_DENIED'),
('AUTO60', 'Auto Delinquent', 'ACTIVE', 'FUND_DENIED'),
('MORT60', 'Mortgage Delinquent', 'ACTIVE', 'FUND_DENIED'),
('CFCNOCHKAC', 'No Checking Account', 'ACTIVE', 'FUND_DENIED'),
('CFCDUPE90', '90 Day Duplicate', 'ACTIVE', 'FUND_DENIED'),
('AGE', 'Not 18 Years Old', 'ACTIVE', 'FUND_DENIED'),
('RES', 'Not in an area serviced by CFC (eg NY)', 'ACTIVE', 'FUND_DENIED'),
('VERRESFAIL', 'Cannot Verify Residence', 'ACTIVE', 'FUND_DENIED'),
('VERIDFAIL', 'Unable to Verify Information', 'ACTIVE', 'FUND_DENIED'),
('CFCCHKACZZ', 'Blank Checking Account', 'ACTIVE', 'FUND_DENIED'),
('VNTGTD', 'Low Vantage Score', 'ACTIVE', 'FUND_DENIED'),
('BANKRUPTCY', 'Open Bankruptcy', 'ACTIVE', 'FUND_DENIED'),
('VNTGUNAVL', 'No Vantage Score', 'ACTIVE', 'FUND_DENIED'),
('DUPE', 'Duplicate Account', 'ACTIVE', 'FUND_DENIED'),
('CFCDUPE180', '180 Day Duplicate', 'ACTIVE', 'FUND_DENIED'),
('FROZEN', 'Security Freeze', 'ACTIVE', 'FUND_DENIED'),
('Letter_Approval_Classic_FBD', 'Approval for Classic Only', 'ACTIVE', ''),
('Letter_Approval_Downsell_FBD', 'Approval for Classic from Downsell', 'ACTIVE', ''),
('Letter_Approval_Gold_FDB', 'Approval for Gold', 'ACTIVE', ''),
('Letter_Pending_FBD', 'Pending', 'ACTIVE', ''),
('IFA','FACT Act Hit - IFA','ACTIVE','FUND_DENIED'),
('EFA','FACT Act Hit - EFA','ACTIVE','FUND_DENIED'),
('TNF','FACT Act Hit - TNF','ACTIVE','FUND_DENIED'),
('SSA','FACT Act Hit - SSA','ACTIVE','FUND_DENIED'),
('ADFA','FACT Act Hit - ADFA','ACTIVE','FUND_DENIED'),
('OFAC','FACT Act Hit - OFAC','ACTIVE','FUND_DENIED'),
('VNTGFRDTD','FACT Act Hit - VNTGFRDTD','ACTIVE','FUND_DENIED');

INSERT INTO loan_actions (name_short, description, status, type)
VALUES 
('CFCIDV', 'IDV General Fail', 'ACTIVE', 'FUND_DENIED'),
('CFCHOMEPH', 'Home Phone', 'ACTIVE', 'FUND_DENIED');



select
@ecash3_system_id := s.system_id as system_id,
@verification_section_id := s.section_id as new_section_parent_id,
@new_level := s.level+1 as new_level,
@new_sequence := max(s2.sequence_no) as new_sequence
from section s
inner join section s2 on (s2.section_parent_id = s.section_id)
inner join system sys on (s.system_id = sys.system_id)
where sys.name_short = 'ecash3_0'
and s.name = 'verification'
group by new_section_parent_id;

insert into section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, 
sequence_no, level, read_only_option)
values(now(), now(), 'active', @ecash3_system_id, 'reprocess', 'Reprocess', @verification_section_id, @new_sequence+5, 
@new_level, 0);

insert into section (date_modified, date_created, active_status, system_id, name, description, section_parent_id, 
sequence_no, level, read_only_option)
values(now(), now(), 'active', @ecash3_system_id, 'batch_mgmt', 'Batch Management', @verification_section_id, 
@new_sequence+10, @new_level, 0);

UPDATE section SET description = 'Approval' WHERE name = 'funding';

UPDATE section SET active_status = 'inactive' where name = 'holidays';

UPDATE section s1, section s2
SET s1.active_status = 'active',
  s1.description = "Decisioning"
where s1.section_parent_id = s2.section_id
and s1.name = 'loan_actions'
and s2.name = 'verification';


UPDATE section s1, section s2, section s3
SET s1.active_status = 'inactive'
where s1.section_parent_id = s2.section_id
and s2.section_parent_id = s3.section_id
and s1.name = 'application_history'
and s2.name = 'application'
and s3.name = 'verification';


DELETE FROM application_field_attribute 
WHERE field_name IN ("high_risk","do_not_loan","fraud");

DELETE FROM rule_set_component WHERE rule_component_id = (SELECT rule_component_id FROM rule_component WHERE name_short = 'loan_type_model');


DELETE FROM rule_set_component_parm_value WHERE rule_component_id = (SELECT rule_component_id FROM rule_component WHERE name_short = 'loan_type_model');


//TODO fix these
update section set active_status = 'inactive'
where name in ('dda', 'dnl_audit', 'payment_arrangement_history', 
'personal_references', 'vehicle_data', 'loan_actions', 'transactions', 
'underwriting', 'react', 'react_review', 'tiffing', 'id_recheck',
'addl_verification', 'pending_expiration_queue', 'hotfile_queue',
'watch', 'new_application', 'report_dda_history', 'collections',
'loan_servicing', 'fraud', 'myapps');
