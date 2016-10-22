
-- Fraud Cron Runtimes --

-- DELETE (in reverse order) FOR RE-RUN --

delete ignore rcp
from rule_component_parm rcp
inner join rule_set_component rsc on (rsc.rule_component_id = rcp.rule_component_id)
inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
where rc.name_short = 'fraud_reminder';

delete ignore rsc
from rule_set_component rsc
inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
where rc.name_short = 'fraud_reminder';

delete ignore rc
from rule_component rc
where rc.name_short = 'fraud_reminder';

-- END DELETE --
-- START INSERT --

set @ecash_support = 	(select agent_id from agent a inner join system s on (s.system_id = a.system_id) where s.name_short = 'ecash3_0' and login = 'ecash_support');
set @loan_type_id := (select loan_type_id from loan_type where name_short = 'offline_processing' and company_id in (select company_id from company where name_short = 'ufc'));
set @rule_set_id := (select rule_set_id from rule_set where name = 'Nightly Task Schedule' and loan_type_id = @loan_type_id);

INSERT INTO `rule_component` 
(date_modified, date_created, active_status, rule_component_id, name, name_short, grandfathering_enabled)
VALUES 
(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active', NULL,'Fraud Reminder','fraud_reminder','no');

set @rule_component_id := last_insert_id();
set @next_component_sequence := (select max(sequence_no)+1 from rule_set_component where rule_set_id = @rule_set_id);

INSERT INTO `rule_set_component`
(date_modified, date_created, active_status, rule_set_id, rule_component_id, sequence_no)
VALUES
(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', @rule_set_id, @rule_component_id, @next_component_sequence);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Sunday',			-- 5
	1,					-- 6
	'Sunday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Monday',			-- 5
	2,					-- 6
	'Monday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Tuesday',			-- 5
	3,					-- 6
	'Tuesday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Wednesday',			-- 5
	4,					-- 6
	'Wednesday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Thursday',			-- 5
	5,					-- 6
	'Thursday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Friday',			-- 5
	6,					-- 6
	'Friday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Saturday',			-- 5
	7,					-- 6
	'Saturday',			-- 7
	'This rule determines which days a given task will run in the Nightly cron.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Holidays',			-- 5
	8,					-- 6
	'Holidays',			-- 7
	'This rule determines whether or not a given task will run the night before a holiday.',
	'string',			-- 9
	'no',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Run Task',			-- 13
	'Yes, No'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'Yes'				-- 7
);


-- Fraud Rule Defaults --

-- DELETE (in reverse order) FOR RE-RUN --

delete ignore rscpv
from rule_set_component_parm_value rscpv
inner join rule_component_parm rcp on (rcp.rule_component_parm_id = rscpv.rule_component_parm_id)
inner join rule_set_component rsc on (rsc.rule_component_id = rcp.rule_component_id)
inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
where rc.name_short = 'fraud_settings';


delete ignore rcp
from rule_component_parm rcp
inner join rule_set_component rsc on (rsc.rule_component_id = rcp.rule_component_id)
inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
where rc.name_short = 'fraud_settings';

delete ignore rsc
from rule_set_component rsc
inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
where rc.name_short = 'fraud_settings';

delete ignore rc
from rule_component rc
where rc.name_short = 'fraud_settings';

-- END DELETE --
-- START INSERT --

set @loan_type_id := (select loan_type_id from loan_type where name_short = 'company_level' and company_id in (select company_id from company where name_short = 'ufc'));
set @rule_set_id := (select rule_set_id from rule_set where name = 'Initial Name Set' and loan_type_id = @loan_type_id);

INSERT INTO `rule_component` 
(date_modified, date_created, active_status, rule_component_id, name, name_short, grandfathering_enabled)
VALUES 
(CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'active', NULL,'Fraud Module Settings','fraud_settings','no');

set @rule_component_id := last_insert_id();
set @next_component_sequence := (select max(sequence_no)+1 from rule_set_component where rule_set_id = @rule_set_id);

INSERT INTO `rule_set_component`
(date_modified, date_created, active_status, rule_set_id, rule_component_id, sequence_no)
VALUES
(CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', @rule_set_id, @rule_component_id, @next_component_sequence);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Fraud Exp. Warning Time',			-- 5
	1,					-- 6
	'Fraud Exp. Warning Time',			-- 7
	'Determines how many hours before a fraud rule expires should a warning email be sent',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Hours',			-- 13
	'24, 48, 72'			-- 14
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'48'				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'High Risk Exp. Warning Time',			-- 5
	2,					-- 6
	'High Risk Exp. Warning Time',			-- 7
	'Determines how many hours before a high risk rule expires should a warning email be sent',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Hours',			-- 13
	'24, 48, 72'			-- 14
);

set @rule_component_parm_id := last_insert_id();


INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'48'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Fraud Exp. Warning Email',			-- 5
	3,					-- 6
	'Fraud Exp. Warning Email',			-- 7
	'Who receives warnings about fraud rules that are about to expire (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 13
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'High Risk Exp. Warning Email',			-- 5
	4,					-- 6
	'High Risk Exp. Warning Email',			-- 7
	'Who receives warnings about high risk rules that are about to expire (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 13
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Fraud Expired Email',			-- 5
	5,					-- 6
	'Fraud Expired Email',			-- 7
	'Who receives emails about expired fraud rules (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 1
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'High Risk Expired Email',			-- 5
	6,					-- 6
	'High Risk Expired Email',			-- 7
	'Who receives emails about expired high risk rules (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 13
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'New Fraud Rule Email',			-- 5
	7,					-- 6
	'Rule Activated Email',			-- 7
	'Who receives emails when rules are activated (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 13
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
   length_min,
   length_max
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'New Fraud Proposition Email',			-- 5
	8,					-- 6
	'New Fraud Proposition Email',			-- 7
	'Who receives emails when new fraud propositions are created (comma-seperated email addressses)',
	'string',			-- 9
	'yes',				-- 10
	'text',			-- 11
	'array',			-- 12
	'Email Addresses',			-- 13
	'3',
	'1024'
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	''				-- 7
);

-- forward selection limits for fraud/high risk

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'New Fraud Rule Expiration',			-- 5
	8,					-- 6
	'New Fraud Rule Expiration',			-- 7
	'Default number of days after a new Fraud Rule is created that it expires',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Days',			-- 13
	'30, 45, 60'			-- 14
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'45'				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'Fraud Rule Extension Limit',			-- 5
	9,					-- 6
	'Fraud Rule Extension Limit',			-- 7
	'Number of days the Fraud Rule expiration date can be extended',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Days',			-- 13
	'30, 45, 60'			-- 14
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'45'				-- 7
);

INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'New High Risk Rule Expiration',			-- 5
	10,					-- 6
	'New High Risk Rule Expiration',			-- 7
	'Default number of days after a new High Risk Rule is created that it expires',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Days',			-- 13
	'7, 14, 30'			-- 14
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'7'				-- 7
);


INSERT INTO `rule_component_parm` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
  `active_status`,		-- 3
  `rule_component_id`,	-- 4
  `parm_name`,			-- 5
  `sequence_no`,		-- 6
  `display_name`,		-- 7
  `description`,		-- 8
  `parm_type`,			-- 9
  `user_configurable`,	-- 10
  `input_type`,			-- 11
  `presentation_type`,	-- 12
  `value_label`,		-- 13
  `enum_values`			-- 15
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	'active',			-- 3
	@rule_component_id,	-- 4
	'High Risk Rule Extension Limit',			-- 5
	11,					-- 6
	'High Risk Rule Extension Limit',			-- 7
	'Number of days the High Risk Rule expiration date can be extended',
	'string',			-- 9
	'yes',				-- 10
	'select',			-- 11
	'array',			-- 12
	'Days',			-- 13
	'7, 14, 30'			-- 14
);

set @rule_component_parm_id := last_insert_id();

INSERT INTO `rule_set_component_parm_value` 
( 
  `date_modified`,		-- 1
  `date_created`, 		-- 2
   agent_id,			-- 3
   rule_set_id,			-- 4
  `rule_component_id`,	-- 5
  `rule_component_parm_id`,	-- 6
  `parm_value`			-- 7
)
VALUES 
(
	CURRENT_TIMESTAMP,	-- 1
	CURRENT_TIMESTAMP,	-- 2
	@ecash_support,
	@rule_set_id,		-- 4
	@rule_component_id,	-- 5
	@rule_component_parm_id, -- 6
	'7'				-- 7
);
