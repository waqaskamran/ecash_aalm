DROP VIEW IF EXISTS `report_transaction_details`;

CREATE
	VIEW report_transaction_details AS 
SELECT
	application.company_id                              AS _company_id,
	application.application_status_id                   AS _application_status_id,
    transaction_register.date_created                   AS transaction_date_created,
    transaction_register.amount                         AS transaction_amount,
    transaction_register.date_effective                 AS transaction_date_effective,
    transaction_register.transaction_status             AS transaction_status,
    transaction_type.name                               AS transaction_name,
    transaction_type.clearing_type                      AS transaction_clearing_type
FROM
    ((
    application
    LEFT JOIN transaction_register                                      ON(((1 = 1) 
    AND (application.application_id = transaction_register.application_id))))
    LEFT JOIN transaction_type                                          ON(((1 = 1) 
    AND (transaction_register.transaction_type_id = transaction_type.transaction_type_id))))