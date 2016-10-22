SELECT @new_loan_amount := rule_component_id FROM rule_component WHERE name_short = 'new_loan_amount';


SELECT @1000 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '1000';
SELECT @1200 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '1200';
SELECT @1700 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '1700';
SELECT @2000 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '2000';
SELECT @2500 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '2500';
SELECT @5000 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '5000';


INSERT INTO rule_component_parm
SELECT NOW(), NOW(), active_status, null, @new_loan_amount, '2300', parm_subscript, sequence_no + 1,'2300', description, parm_type, user_configurable, input_type, presentation_type, value_label, 'Monthly Income: 2300', value_min,value_max, value_increment, length_min, length_max, enum_values, preg_pattern
FROM rule_component_parm WHERE rule_component_parm_id = @2000;
 
UPDATE rule_component_parm SET parm_name = '1800', display_name = '1800', subscript_label = 'Monthly Income: 1800' WHERE rule_component_parm_id = @1700;
SELECT @2300 := LAST_INSERT_ID();
-- SELECT @2300 := rule_component_parm_id FROM rule_component_parm WHERE rule_component_id = @new_loan_amount AND parm_name = '2300';
UPDATE rule_component_parm SET sequence_no = sequence_no+1 WHERE rule_component_id = @new_loan_amount AND parm_name = '2500';

UPDATE rule_component_parm SET sequence_no = sequence_no+1 WHERE rule_component_id = @new_loan_amount AND parm_name = '5000';

UPDATE rule_set_component_parm_value SET parm_value = '200' WHERE rule_component_id = @new_loan_amount AND rule_component_parm_id = @1000;

UPDATE rule_set_component_parm_value SET parm_value = '200' WHERE rule_component_id = @new_loan_amount AND rule_component_parm_id = @1200;

UPDATE rule_set_component_parm_value SET parm_value = '200' WHERE rule_component_id = @new_loan_amount AND rule_component_parm_id = @1700;

UPDATE rule_set_component_parm_value SET parm_value = '250' WHERE rule_component_id = @new_loan_amount AND rule_component_parm_id = @2000;


INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rule_set_id, @new_loan_amount, @2300, '250' FROM rule_set_component_parm_value WHERE rule_component_id = @new_loan_amount AND rule_component_parm_id = @2000;
 
SELECT * from rule_component_parm WHERE rule_component_id = @new_loan_amount;

SELECT * FROM rule_set_component_parm_value WHERE rule_component_id = @new_loan_amount;