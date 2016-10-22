/* This data fix will take place in 2 steps.
 * Step 1. Update all applications currently in withdrawn status to be in collections new status
 * Step 2. Insert them into the queue taking time zones into consideration
 *
 * NOTE: THIS WILL NOT DO ANYTHING WITH THE DENIED APPS WHICH WERE ALSO LISTED IN THE TICKET
 */


/* Step 1. Update all applications currently in withdrawn status to be in collections new status */
UPDATE 
	application 
SET 
	application_status_id=137,
	date_modified = NOW()
WHERE 
	application_id in (
		103668983,
		103669147,
		106841209
	)
AND
	application_status_id='19';

/* Step 2. Insert them into the queue taking time zones into consideration */
INSERT INTO n_time_sensitive_queue_entry
(
	queue_id,
	agent_id,
	related_id,
	date_queued,
	date_available,
	date_expire,
	priority,
	dequeue_count,
	start_hour,
	end_hour
)
select  '9', /* queue id 9 = Collections New */ 
		'4', /* agent id 4 = ecash support   */ 
		a.application_id,
		NOW(),
		NOW(),
		NULL,
		100,
		0,
		(if(dst='Y',z.tz - 1, z.tz) - 7) + 8,
		(if(dst='Y',z.tz - 1, z.tz) - 7) + 20
FROM
	application a
JOIN 
	zip_tz z ON (z.zip_code=a.zip_code)
WHERE 
	a.application_id IN (
		103668983,
		103669147,
		106841209
	)
AND
	application_status_id='137';

