SET autocommit=0;

-- Add the Rule Component
INSERT INTO rule_component(date_modified, date_created, active_status, rule_component_id, name, name_short, grandfathering_enabled)
  VALUES(NOW(), NOW(), 'active', NULL, 'Require Bank Account Information', 'require_bank_account', 'no');
SET @component_id := LAST_INSERT_ID();

-- Add the Rule Component Parameter
INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_parm_id, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', NULL, @component_id, 'require_bank_account_enable', NULL, '0', 'Require Bank Account Information', 'Allows the requirement for bank account information to be changed for a loan type.', 'string', 'yes', 'select', 'scalar', 'Enabled', '0', 0, 2, 1, '2', '0', 'On, Off', NULL);
SET @component_parm_id := LAST_INSERT_ID();

-- Add the rule to Payday and Title loan types
INSERT INTO rule_set_component
SELECT NOW(), NOW(), 'active', rs.rule_set_id, @component_id, max(rsc.sequence_no)+1
FROM rule_set as rs
JOIN rule_set_component AS rsc ON (rsc.rule_set_id = rs.rule_set_id)
WHERE NAME LIKE '%Payday%'
OR NAME LIKE '%Title%'
GROUP BY rule_set_id;

-- Insert the values into the rule set component values for Payday Loans
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rs.rule_set_id, @component_id, @component_parm_id, 'On'
FROM rule_set AS rs
WHERE rs.name LIKE '%Payday%'
GROUP BY rule_set_id;

-- Insert the values into the rule set component values for Title Loans
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), 0, rs.rule_set_id, @component_id, @component_parm_id, 'Off'
FROM rule_set AS rs
WHERE rs.name LIKE '%Title%'
GROUP BY rule_set_id;

COMMIT;
