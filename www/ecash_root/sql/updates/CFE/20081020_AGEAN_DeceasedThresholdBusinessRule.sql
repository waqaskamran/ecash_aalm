/* This adds a business rule for deceased unverified days threshold */
/* After x amount of business days, the application will revert its status from deceased unverified */
-- This is an offline processing rule
SELECT @loan_type_id := loan_type_id FROM loan_type WHERE name_short = 'offline_processing';

SELECT @rule_set_id := MAX(rule_set_id) FROM rule_set rs where name like '%nightly task%';

-- Create 2nd tier rule component
INSERT INTO rule_component (name,name_short) VALUES ('Deceased Verification Rules','deceased_verification');
-- Create 2nd tier schedule rule component
INSERT INTO rule_component (name,name_short) VALUES ('Deceased Verification Schedule','deceased_verification_schedule');


-- Get the ID.
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'deceased_verification';
SELECT @schedule_rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'deceased_verification_schedule';

-- second_tier belongs to Nightly Task Schedule.
INSERT INTO rule_set_component SELECT NOW(), NOW(), 'active', rule_set_id, @rule_component_id, (SELECT MAX(sequence_no) FROM rule_set_component WHERE rule_set_id = rs.rule_set_id group by rule_set_id) FROM rule_set rs WHERE name LIKE '%nightly task%';

-- As does the schedule
INSERT INTO rule_set_component SELECT NOW(), NOW(), 'active', rule_set_id, @schedule_rule_component_id, (SELECT MAX(sequence_no) FROM rule_set_component WHERE rule_set_id = rs.rule_set_id group by rule_set_id) FROM rule_set rs WHERE name LIKE '%nightly task%';

-- Set limit of days until reverting deceased unverified statuses
INSERT INTO rule_component_parm (rule_component_id, parm_name, display_name, parm_type, user_configurable, input_type, presentation_type, value_min, value_max, value_increment, description)
VALUES (@rule_component_id, 'deceased_verification_limit','Deceased Unverified Days','numeric','yes','select','scalar','1','365','1','The amount of calendar days until an application moves from deceased unverified to its previous status.');
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
