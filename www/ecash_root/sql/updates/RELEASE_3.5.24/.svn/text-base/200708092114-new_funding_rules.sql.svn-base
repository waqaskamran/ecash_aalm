/*
 * These business rule changes are to make the way schedules are created more dynamic.
 * This obviously means there have to be code changes to go with these rule changes
 * and additions.
 */

/*
 * Rename the Principal Payment Amount component to Principal Payment and then 
 * add a bunch of other rule components underneath it.
 */
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name = 'Principal Payment Amount';
UPDATE rule_component SET name = 'Principal Payment', name_short = 'principal_payment' WHERE rule_component_id = @rule_component_id;
-- Reorder the principal payment amount rule
UPDATE rule_component_parm SET sequence_no = '2' WHERE parm_name = 'principal_payment_amount' AND rule_component_id = @rule_component_id;

-- Insert new Payment Type rule
INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', @rule_component_id, 'principal_payment_type', '', '1', 'Principal Payment Type', 'This rule determines whether the principal amount should be based on a fixed dollar amount or a percentage of the original loan amount.  If fixed is selected, the Principal Payment Amount rule defines the amount.  If Percentage is selected the Principal P', 'string', 'yes', 'select', 'scalar', 'Payment Type', '', 0, 2, 1, '0', '0', 'Fixed, Percentage', '');
SET @payment_type_parm_id := LAST_INSERT_ID();

-- Insert the values
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), agent_id, rule_set_id, @rule_component_id, @payment_type_parm_id, 'Percentage'
FROM rule_set_component_parm_value 
WHERE rule_component_id = @rule_component_id
GROUP BY rule_set_id;

-- Insert new Payment Percentage rule
INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', @rule_component_id, 'principal_payment_percentage', '', '3', 'Principal Payment Percentage', 'This is the percentage of the original loan amount to pay for an automatically scheduled principal payment.', 'integer', 'yes', 'select', 'array', 'Percentage', 'Percentage', 1, 100, 1, '1', '3', '', '');
SET @payment_percentage_parm_id := LAST_INSERT_ID();

-- Insert the values
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), agent_id, rule_set_id, @rule_component_id, @payment_percentage_parm_id, '100'
FROM rule_set_component_parm_value 
WHERE rule_component_id = @rule_component_id
GROUP BY rule_set_id;

-- Find the component id
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name = 'Principal Payment';

-- Insert new Minimum Payment Percentage rule
INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', @rule_component_id, 'min_renew_prin_pmt_prcnt', '', '3', 'Minimum Principal Payment Percentage', 'This is the percentage of the original loan amount to pay for a manually renewed principal payment.', 'integer', 'yes', 'select', 'array', 'Percentage', 'Percentage', 1, 100, 1, '1', '3', '', '');
SET @min_renew_prin_pmt_prcnt_parm_id := LAST_INSERT_ID();

-- Insert the values
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), agent_id, rule_set_id, @rule_component_id, @min_renew_prin_pmt_prcnt_parm_id, '10'
FROM rule_set_component_parm_value 
WHERE rule_component_id = @rule_component_id
GROUP BY rule_set_id;

/*
 * Move Service Charge Type under Service Charge Percentage and rename
 * the Service Charge Percentage component name to Service Charge.
 */
SELECT @svc_charge_type_component_id := rule_component_id FROM rule_component WHERE name_short = 'svc_charge_type';
SELECT @svc_charge_percent_component_id := rule_component_id FROM rule_component WHERE name_short = 'svc_charge_percentage';
SELECT @svc_charge_type_parm_id := rule_component_parm_id FROM rule_component_parm WHERE parm_name = 'svc_charge_type';
SELECT @svc_charge_percent_parm_id := rule_component_parm_id FROM rule_component_parm WHERE parm_name = 'service_charge_percent';

-- Rename the Percentage component
UPDATE rule_component SET name = 'Service Charge', name_short = 'service_charge' WHERE rule_component_id = @svc_charge_percent_component_id;
-- Move the Type component upder Service Charge Percentage Rule Component
UPDATE rule_component_parm SET rule_component_id = @svc_charge_percent_component_id WHERE rule_component_parm_id = @svc_charge_type_parm_id;
-- Rename rule because previously it took the rule_components name
UPDATE rule_component_parm SET parm_name = 'svc_charge_percentage' WHERE rule_component_parm_id = @svc_charge_percent_parm_id;
-- Move the parm values as well
UPDATE rule_set_component_parm_value SET rule_component_id = @svc_charge_percent_component_id WHERE rule_component_id = @svc_charge_type_component_id AND rule_component_parm_id = @svc_charge_type_parm_id;

DELETE from rule_set_component WHERE rule_component_id = @svc_charge_type_component_id;
DELETE from rule_component WHERE rule_component_id = @svc_charge_type_component_id;

/*
 * Move 'Max Service Charge Only Payments' under Service Charge
 */
SELECT @max_svc_charge_component_id := rule_component_id FROM rule_component WHERE name_short = 'max_svc_charge_only_pmts';
SELECT @service_charge_component_id := rule_component_id FROM rule_component WHERE name_short = 'service_charge';
SELECT @max_svc_charge_only_pmts_parm_id := rule_component_parm_id FROM rule_component_parm WHERE parm_name = 'max_svc_charge_only_pmts';

-- Move the 'Max Service Charge Only Payments' component parm upder Service Charge Rule Component
UPDATE rule_component_parm SET rule_component_id = @service_charge_component_id WHERE rule_component_parm_id = @max_svc_charge_only_pmts_parm_id;
-- Move the parm values as well
UPDATE rule_set_component_parm_value SET rule_component_id = @service_charge_component_id WHERE rule_component_id = @max_svc_charge_component_id AND rule_component_parm_id = @max_svc_charge_only_pmts_parm_id;

DELETE from rule_set_component WHERE rule_component_id = @max_svc_charge_component_id;
DELETE from rule_component WHERE rule_component_id = @max_svc_charge_component_id;

-- Reorder the list
UPDATE rule_component_parm SET sequence_no = 1 WHERE rule_component_id = @service_charge_component_id and parm_name = 'svc_charge_type';
UPDATE rule_component_parm SET sequence_no = 2 WHERE rule_component_id = @service_charge_component_id and parm_name = 'svc_charge_percentage';
UPDATE rule_component_parm SET sequence_no = 3 WHERE rule_component_id = @service_charge_component_id and parm_name = 'max_svc_charge_only_pmts';

/*
 * New rule for number of service charge only payments when manually renewed.
 */
SELECT @service_charge_component_id := rule_component_id FROM rule_component WHERE name_short = 'service_charge';

INSERT INTO rule_component_parm(date_modified, date_created, active_status, rule_component_id, parm_name, parm_subscript, sequence_no, display_name, description, parm_type, user_configurable, input_type, presentation_type, value_label, subscript_label, value_min, value_max, value_increment, length_min, length_max, enum_values, preg_pattern)
  VALUES(NOW(), NOW(), 'active', @service_charge_component_id, 'max_renew_svc_charge_only_pmts', '', '4', 'Max Renew Service Charge Only Payments', 'When a loan is manually renewed per customer request, this is the maximum number of times a service charge will be applied to an account before principal payments are applied.', 'integer', 'yes', 'select', 'scalar', 'Number of times', NULL, 0, 10, 1, '0', '0', '', '');
SET @max_renew_svc_charge_only_pmts_parm_id := LAST_INSERT_ID();

-- Insert the values
INSERT INTO rule_set_component_parm_value
SELECT NOW(), NOW(), agent_id, rule_set_id, @service_charge_component_id, @max_renew_svc_charge_only_pmts_parm_id, '4'
FROM rule_set_component_parm_value 
WHERE rule_component_id = @service_charge_component_id
GROUP BY rule_set_id;
