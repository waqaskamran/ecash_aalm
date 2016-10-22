-- Agent Actions required for Email Transfers
INSERT INTO action(date_modified, date_created, action_id, name, name_short)
  VALUES
        (NOW(), NOW(), NULL, 'request_react_from_email_queue', 'request_react_from_email_queue'),
        (NOW(), NOW(), NULL, 'request_new_loan_from_email_queue', 'request_new_loan_from_email_queue'),
        (NOW(), NOW(), NULL, 'request_pay_down_from_email_queue', 'request_pay_down_from_email_queue'),
        (NOW(), NOW(), NULL, 'request_pay_out_from_email_queue', 'request_pay_out_from_email_queue');
