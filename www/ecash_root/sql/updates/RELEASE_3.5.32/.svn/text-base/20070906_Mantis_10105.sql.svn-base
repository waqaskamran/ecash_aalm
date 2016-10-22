-- Failed Payment Next Attempt Date
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'failed_pmnt_next_attempt_date';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Max Re-Activate Loan Amount
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'max_react_loan_amount';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Re-Activate Loan Amount Increase
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'react_amount_increase';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Max Contact Attempts
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'max_contact_attempts';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Automated E-Mail
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'automated_email';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Max ACH Fee Charges Per Loan
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'max_ach_fee_chrg_per_loan';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;

-- Bankruptcy Notified
SELECT @rule_component_id := rule_component_id FROM rule_component WHERE name_short = 'bankruptcy_notified';
DELETE FROM rule_set_component WHERE rule_component_id = @rule_component_id;
DELETE FROM rule_set_component_parm_value WHERE rule_component_id = @rule_component_id;
