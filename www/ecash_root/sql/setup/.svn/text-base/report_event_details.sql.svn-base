DROP VIEW IF EXISTS `report_event_details`;

CREATE
	VIEW report_event_details AS 
SELECT
	application.company_id                              AS _company_id,
	application.application_status_id                   AS _application_status_id,
    application.application_id                          AS application_id,
    application.date_created                            AS date_created,
    dc.company_name                                     AS debt_company_name,
    dc.address_1                                        AS debt_company_address1,
    dc.address_2                                        AS debt_company_address2,
    dc.city                                             AS debt_company_city,
    dc.state                                            AS debt_company_state,
    dc.zip_code                                         AS debt_company_zip,
    dc.contact_phone                                    AS debt_company_phone,
    event_schedule.amount_principal                     AS event_amount_principal,
    event_schedule.amount_non_principal                 AS event_amount_non_principal,
    event_schedule.context                              AS event_context,
    event_schedule.date_created                         AS event_date_created,
    event_schedule.date_event                           AS event_date_event,
    event_schedule.date_effective                       AS event_date_effective,
    event_type.name                                     AS event_name,    
    event_schedule.event_status                         AS event_status
FROM
    ((((
    application
    LEFT JOIN event_schedule                                            ON(((1 = 1)
    AND (application.application_id = event_schedule.application_id))))
    LEFT JOIN event_type                                                ON(((1 = 1)
    AND (event_schedule.event_type_id = event_type.event_type_id))))
    LEFT JOIN debt_company_event_schedule as dbces                      ON(((1 = 1)                
    AND	(event_schedule.event_schedule_id = dbces.event_schedule_id))))
    LEFT JOIN debt_company as dc                                        ON(((1 = 1)                
    AND	(dc.company_id = dbces.company_id))));
