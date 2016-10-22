
DROP VIEW IF EXISTS `report_extcol_details`;

CREATE
    VIEW report_extcol_details AS
SELECT
	application.company_id                              AS _company_id,
	application.application_status_id                   AS _application_status_id,
    application.application_id                          AS application_id,
    application.date_created                            AS date_created,
    extcol.current_balance                              AS extcol_current_blance,
    extcol.date_created                                 AS extcol_date_created,
    extcol.ext_collections_batch_id                     AS extcol_batch_id,
    extcol_batch.date_created                           AS extcol_batch_date_created,
    extcol_batch.batch_status                           AS extcol_batch_status,
    extcol_batch.ext_collections_co                     AS extcol_batch_company
FROM
    ((
    application
    LEFT JOIN ext_collections as extcol                                 ON(((1 = 1)
    AND (application.application_id = extcol.application_id)))) 
    LEFT JOIN ext_collections_batch as extcol_batch                     ON(((1 = 1)
    AND (extcol_batch.ext_collections_batch_id = extcol.ext_collections_batch_id))));