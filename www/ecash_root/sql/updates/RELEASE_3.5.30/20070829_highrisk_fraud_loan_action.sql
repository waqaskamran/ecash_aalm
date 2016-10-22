INSERT INTO loan_actions (name_short,description,status,type)
VALUES
	('confirmed_fraud','Confirmed Fraud','ACTIVE','FRAUD'),
	('confirmed_not_fraud', 'Not Found to be Fraud','ACTIVE','FRAUD'),
	('confirmed_high_risk','Confirmed High Risk','ACTIVE','HIGH_RISK'),
	('confirmed_not_high_risk','Not Found to be High Risk','ACTIVE','HIGH_RISK');
