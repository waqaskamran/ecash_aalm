

alter table loan_actions MODIFY COLUMN `type` set('PRESCRIPTION','FUND_DENIED','FUND_WITHDRAW','FUND_APPROVE','CS_WITHDRAW','CS_VERIFY','DEQUEUE', 'FRAUD', 'HIGH_RISK');

INSERT  IGNORE INTO `loan_actions` (name_short, description, status, `type`)
VALUES 
('ready_2_fund','Ready to fund','ACTIVE','FRAUD'),
('verify_emp','Call employer and verify employment','ACTIVE','FRAUD'),
('verify_home','Call home number and verify residence','ACTIVE','FRAUD'),
('specify_other','Other','ACTIVE','FRAUD'),

('ready_2_fund','Ready to fund','ACTIVE','HIGH_RISK'),
('verify_emp','Call employer and verify employment','ACTIVE','HIGH_RISK'),
('verify_home','Call home number and verify residence','ACTIVE','HIGH_RISK'),
('specify_other','Other','ACTIVE','HIGH_RISK')

;

/*
Ready to fund
Call employer and verify employment
Call home number and verify residence
Other
*/