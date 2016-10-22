<?php
/**
gforge 38314
 */

function Main()
{
	$db = ECash::getSlaveDb();
	global $server;
	$company_id = ECash::getCompany()->company_id;

	//get applications
	$sql = "SELECT
app.application_id,
app.application_status_id,
aps.name_short,
(
SELECT COUNT(es.event_schedule_id)
FROM event_schedule AS es
JOIN event_transaction AS et ON et.event_type_id = es.event_type_id
JOIN transaction_type AS tt ON tt.transaction_type_id = et.transaction_type_id
WHERE es.application_id = app.application_id
AND es.event_status = 'scheduled'
AND tt.clearing_type = 'ach'
AND es.context IN ('generated', 'reattempt')
) as num_scheduled,
(
SELECT COUNT(es.event_schedule_id)
FROM event_schedule AS es
JOIN event_transaction AS et ON et.event_type_id = es.event_type_id
JOIN transaction_type AS tt ON tt.transaction_type_id = et.transaction_type_id
WHERE es.application_id = app.application_id
AND es.event_status = 'scheduled'
AND es.context NOT IN ('generated', 'reattempt')
) as num_arranged,
(SELECT application_status_id
FROM status_history WHERE application_id = app.application_id
ORDER BY date_created desc 
limit 1,1) as prev_status
FROM application app
join application_status aps ON aps.application_status_id = app.application_status_id
LEFT JOIN
application_flag flag ON app.application_id = flag.application_id AND flag.flag_type_id = 8
JOIN
ach ON (ach.application_id = app.application_id
AND ach.ach_status = 'returned'
AND ach.ach_type != 'credit'
AND ach.bank_aba = app.bank_aba
AND ach.bank_account = app.bank_account
)
WHERE app.application_status_id = 125
GROUP BY app.application_id
HAVING num_arranged = 0;";

	$result = $db->query($sql);
	$app_count = 0;
	echo "\n'$sql'\n";
	echo "Stuck in 'Made Arrangements'";
	echo "\nApplication id,Current application status_id, status being reverted to";
$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$i++;

		$application_id = $row['application_id'];
		$prev_status = $row['prev_status'];
		
		echo "\n{$application_id},{$row['application_status_id']}, {$prev_status}";
		//		Update_Status(NULL,$application_id,$prev_status);

		
		
	}
	echo "\nReverting {$i} apps\n\n";
	
}



?>
