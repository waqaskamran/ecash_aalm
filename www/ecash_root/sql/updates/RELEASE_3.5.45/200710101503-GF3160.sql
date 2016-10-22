#Reverify Reason updates

UPDATE loan_actions
SET
description = 'Customer does not qualify for loan amount',
name_short = 'cs_does_not_qualify'
where loan_action_id = 35;
UPDATE loan_actions
SET
description = 'Due date does not fall on a payday',
name_short = 'cs_duedate_not_on_payday'
where loan_action_id = 34;
UPDATE loan_actions
SET
status = 'INACTIVE'
WHERE loan_action_id IN (36,37,38);