--
-- Inserts new event and transaction types for cashline returns.
-- This script must be ran once for every company that wants to 
-- run cashline returns.
--

SET @company_id := 2;

INSERT INTO event_type
VALUES
  (NOW(), NOW(), 'active', @company_id, NULL, 'cashline_return', 'Cashline Return'),
  (NOW(), NOW(), 'active', @company_id, NULL, 'h_fatal_cashline_return', 'Fatal Cashline Return'),
  (NOW(), NOW(), 'active', @company_id, NULL, 'h_nfatal_cashline_return', 'Non-Fatal Cashline Return')
;

SET @event_type_id := (SELECT LAST_INSERT_ID());

INSERT INTO transaction_type
VALUES
  (NOW(), NOW(), 'active', @company_id, NULL, 'cashline_return', 'Cashline Return', 'adjustment', 'yes', 1, 'complete', 'business'),
  (NOW(), NOW(), 'active', @company_id, NULL, 'h_fatal_cashline_return', 'Fatal Cashline Return', 'ach', 'yes', 1, 'complete', 'business'),
  (NOW(), NOW(), 'active', @company_id, NULL, 'h_nfatal_cashline_return', 'Non-Fatal Cashline Return', 'ach', 'yes', 1, 'complete', 'business')
;

SET @transaction_type_id := (SELECT LAST_INSERT_ID());

INSERT INTO event_transaction
VALUES
  (NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id, NULL, NULL, NULL, NULL, NULL),
  (NOW(), NOW(), 'active', @company_id, @event_type_id + 1, @transaction_type_id + 1, NULL, NULL, NULL, NULL, NULL),
  (NOW(), NOW(), 'active', @company_id, @event_type_id + 2, @transaction_type_id + 2, NULL, NULL, NULL, NULL, NULL)
;
