update loan_actions set status='INACTIVE' where name_short='does_not_want_loan';
update loan_actions set type='FUND_WITHDRAW,CS_WITHDRAW' where name_short='w_cw_customer_not';

update loan_actions set status='INACTIVE' where name_short='w_eu_contact_no_work';
update loan_actions set type='FUND_APPROVE,FUND_WITHDRAW' where name_short='a_eu_contact_no_work';

update loan_actions set status='INACTIVE' where name_short='w_eu_no_contact';
update loan_actions set type='FUND_APPROVE,FUND_WITHDRAW' where name_short='a_eu_no_contact';

update loan_actions set status='INACTIVE' where name_short='w_pu_unable_verify';
update loan_actions set type='FUND_APPROVE,FUND_WITHDRAW' where name_short='e_pu_unable_verify';