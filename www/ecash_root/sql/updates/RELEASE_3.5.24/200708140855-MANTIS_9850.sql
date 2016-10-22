-- normalizing messages

update loan_actions set description = 'FD - On Fraud List' where name_short in ('fd_fraud_on_list','withdrawn_app_fraud_alert_list');
update loan_actions set description = 'FD - On High Risk List' where name_short in ('fd_high_risk_on_list','winthdrawn_app_high_risk');
