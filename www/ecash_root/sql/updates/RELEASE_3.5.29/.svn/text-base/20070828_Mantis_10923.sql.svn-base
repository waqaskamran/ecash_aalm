/*
 * New rule for interest accrual limit
 */
SELECT @component_id := rule_component_id FROM rule_component WHERE name_short = 'service_charge';

INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_parm_id, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', NULL, @component_id, 'interest_accrual_limit', '', '0', 'Interest Accrual Limit', 'The maximum number of days of allowed interest accrual.', 'integer', 'yes', 'text', 'scalar', '', '', 0, 0, 0, '0', '0', '', '');
SET @component_parm_id := LAST_INSERT_ID();

-- Insert the values
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), agent_id, rule_set_id, @component_id, @component_parm_id, '60'
FROM rule_set_component_parm_value
WHERE rule_component_id = @component_id
GROUP BY rule_set_id;
