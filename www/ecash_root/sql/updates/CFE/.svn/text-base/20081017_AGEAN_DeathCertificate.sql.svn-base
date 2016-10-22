/* This adds the death certificate receivable file for eCash */
SET AUTOCOMMIT=0;

/* Insert an entry for every company */
INSERT INTO document_list
(
	date_modified,
	date_created,
	active_status,
	company_id,
	name,
	name_short,
	required,
	esig_capable,
	system_id,
	send_method,
	document_api,
	doc_send_order,
	doc_receive_order,
	only_receivable
)
SELECT
	NOW(),
	NOW(),
	'active',
	company.company_id,
	'Death Certificate',
	'death_certificate',
	'yes',
	'no',
	'3',
	'email,fax',
	'condor',
	NULL,
	NULL,
	'yes'
FROM
	company;



COMMIT;
