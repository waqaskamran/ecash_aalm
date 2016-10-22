
## Added new type to loan_actions enumerated list of types to allow Reverify reasons to be limited to only what Impact expects to see. [#8707]
## Added new loan actions. [#8707]

ALTER TABLE loan_actions MODIFY type set('PRESCRIPTION','FUND_DENIED','FUND_WITHDRAW','FUND_APPROVE','CS_WITHDRAW','CS_VERIFY','DEQUEUE','FRAUD','HIGH_RISK', 'CS_REVERIFY');
INSERT INTO loan_actions (name_short, description, status, type) VALUES ('cs_reverify_qualified', 'Customer does not qualify for loan amount', 'ACTIVE', 'CS_REVERIFY');
INSERT INTO loan_actions (name_short, description, status, type) VALUES ('cs_reverify_payday', 'Due date does not fall on a payday', 'ACTIVE', 'CS_REVERIFY');