-- Add Check Disbursement for each company
-- Just change the company_id
SET @company_id := 1;

INSERT INTO event_type(date_modified, date_created, active_status, company_id, event_type_id, name_short, name)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'check_disbursement', 'Check Disbursement');
SET @event_type_id := LAST_INSERT_ID();

INSERT INTO transaction_type(date_modified, date_created, active_status, company_id, transaction_type_id, name_short, name, clearing_type, affects_principal, pending_period, end_status, period_type)
  VALUES(NOW(), NOW(), 'active', @company_id, NULL, 'check_disbursement', 'Check Disbursement', 'external', 'yes', '0', 'complete', 'business');
SET @transaction_type_id := LAST_INSERT_ID();

INSERT INTO event_transaction(date_modified, date_created, active_status, company_id, event_type_id, transaction_type_id, distribution_percentage, distribution_amount, spawn_percentage, spawn_amount, spawn_max_num)
  VALUES(NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id, NULL, NULL, NULL, NULL, NULL);

