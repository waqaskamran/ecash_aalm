update loan_actions set type= 'FUND_DENIED,FUND_WITHDRAW,CS_WITHDRAW' where status ='ACTIVE' AND name_short ='fd_fraud_on_list';
update loan_actions set status= 'INACTIVE' where status ='ACTIVE' AND name_short ='withdrawn_app_fraud_alert_list';

update loan_actions set type= 'FUND_DENIED,FUND_WITHDRAW,CS_WITHDRAW' where status ='ACTIVE' AND name_short ='fd_high_risk_on_list';
update loan_actions set status= 'INACTIVE' where status ='ACTIVE' AND name_short ='winthdrawn_app_high_risk';

update loan_actions set type= 'FUND_APPROVE,FUND_WITHDRAW' where status ='ACTIVE' AND name_short ='a_eu_contact_work_invalid';
update loan_actions set status= 'INACTIVE' where status ='ACTIVE' AND name_short ='w_eu_contact_at_work_inval';

update loan_actions set type= 'FUND_DENIED,FUND_WITHDRAW' where status ='ACTIVE' AND name_short ='d_wn_No_work_phone';
update loan_actions set status= 'INACTIVE' where status ='ACTIVE' AND name_short ='w_wn_customer_work_phone';
