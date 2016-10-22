DROP VIEW IF EXISTS `report_ach_details`;

CREATE
	VIEW report_ach_details AS 
SELECT
	application.company_id                              AS _company_id,
	application.application_status_id                   AS _application_status_id,
    application.application_id                          AS application_id,
    application.date_created                            AS date_created,
    ach.ach_status                                      AS ach_status,
    ach.amount                                          AS ach_amount,
    ach.ach_date                                        AS ach_date,
    ach.date_created                                    AS ach_date_created,
    ach.ach_trace_number                                AS ach_trace_number,
    ach.ach_type                                        AS ach_type,
    ach_return_code.name                                AS ach_return_code_desc,
    ach_return_code.name_short                          AS ach_return_code_name
FROM
    ((
    application
    LEFT JOIN ach                                                       ON(((1 = 1)
    AND	(application.application_id = ach.application_id))))
    LEFT JOIN ach_return_code                                           ON(((1 = 1)
    AND	(ach.ach_return_code_id = ach_return_code.ach_return_code_id))));