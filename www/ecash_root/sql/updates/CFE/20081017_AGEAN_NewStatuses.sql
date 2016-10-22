SET AUTOCOMMIT=0;

SELECT @application_parent_id := application_status_id FROM application_status_flat asf WHERE asf.level0_name = 'Customer';

INSERT INTO application_status
(
	date_modified,
	date_created,
	active_status,
	name,
	name_short,
	application_status_parent_id,
	level
)
VALUES
(
	NOW(),
	NOW(),
	'active',
	'Write-Off',
	'write_off',
	@application_parent_id, 
	2
);


COMMIT;
