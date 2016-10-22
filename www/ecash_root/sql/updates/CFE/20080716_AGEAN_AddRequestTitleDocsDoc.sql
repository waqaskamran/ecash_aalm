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
	c.company_id,
	'Request Title Docs',
	'request_title_docs',
	'no',
	'no',
	3,
	'email',
	'condor',
	NULL,
	NULL,
	'no'
FROM company c;
