-- This is an offline processing rule
SELECT @loan_type_id := loan_type_id FROM loan_type WHERE name_short = 'offline_processing';

SELECT @rule_set_id := MAX(rule_set_id) FROM rule_set rs where name like '%nightly task%';

-- Create 2nd tier rule component
INSERT INTO rule_component (name,name_short) VALUES ('Second Tier rules','second_tier');
-- Create 2nd tier schedule rule component
INSERT INTO rule_component (name,name_short) VALUES ('Second Tier Schedule','second_tier_schedule');


-- Get the ID.
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'second_tier';
SELECT @schedule_rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'second_tier_schedule';

-- second_tier belongs to Nightly Task Schedule.
INSERT INTO rule_set_component SELECT NOW(), NOW(), 'active', rule_set_id, @rule_component_id, (SELECT MAX(sequence_no) FROM rule_set_component WHERE rule_set_id = rs.rule_set_id group by rule_set_id) FROM rule_set rs WHERE name LIKE '%nightly task%';

-- As does the schedule
INSERT INTO rule_set_component SELECT NOW(), NOW(), 'active', rule_set_id, @schedule_rule_component_id, (SELECT MAX(sequence_no) FROM rule_set_component WHERE rule_set_id = rs.rule_set_id group by rule_set_id) FROM rule_set rs WHERE name LIKE '%nightly task%';

-- Set limit of days until 2nd tier collections kicks in
INSERT INTO rule_component_parm (rule_component_id, parm_name, display_name, parm_type, user_configurable, input_type, presentation_type, value_min, value_max, value_increment, description)
VALUES (@rule_component_id, 'second_tier_limit','Days until sent to 2nd-tier','numeric','yes','select','scalar','1','120','1','The amount of days without a completed payment until the application is sent to second tier collections');
-- get the ID
SELECT @rule_component_parm_id := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @rule_component_id AND parm_name = 'second_tier_limit';

-- Set the values
    INSERT INTO rule_set_component_parm_value
    SELECT NOW(), NOW(), 0, rule_set_id, @rule_component_id, @rule_component_parm_id, (SELECT MAX(sequence_no) FROM rule_set_component WHERE rule_set_id = rs.rule_set_id GROUP BY rule_set_id)
    from rule_set rs where name like '%nightly task%';

INSERT INTO rule_component_parm
SELECT NOW(), NOW(), 'active', NULL, @schedule_rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern 
FROM rule_component_parm where parm_name LIKE '%day%' group by parm_name;

SELECT @rule_set_id := MAX(rule_set_id) FROM rule_set rs where name like '%nightly task%';
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, @rule_set_id, @schedule_rule_component_id, rule_component_parm_id, 'Yes' FROM rule_component_parm WHERE rule_component_id = @schedule_rule_component_id and parm_name like '%day%';
