SET AUTOCOMMIT=0;

INSERT INTO cfe_variable
(
	date_modified,
	date_created,
	name,
	name_short
)
VALUES
(
	NOW(),
	NOW(),
	'Last Document Received',
	'last_document_received'
);

COMMIT;
