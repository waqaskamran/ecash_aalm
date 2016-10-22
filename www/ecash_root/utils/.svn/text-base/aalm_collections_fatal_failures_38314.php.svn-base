<?php
/**
gforge 38316
 */

function Main()
{
	$db = ECash::getSlaveDb();
	global $server;
	$limit = 30;
	$company_id = ECash::getCompany()->company_id;

	//get applications
	$sql = "
SELECT
app.application_id,
app.bank_aba,
app.bank_account,
app.application_status_id,
ach.bank_aba,
ach.bank_account,
aps.name_short,
flag.flag_type_id,
achrc.name_short as return_code,
achrc.name,
achrc.is_fatal,
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
) as num_arranged
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
JOIN ach_return_code achrc ON achrc.ach_return_code_id = ach.ach_return_code_id
WHERE achrc.is_fatal = 'yes'
GROUP BY app.application_id
HAVING num_scheduled > 0";

	$result = $db->query($sql);
	$app_count = 0;
	echo "\n'$sql'\n";
	echo "With Fatal ACH returns";
	echo "\nApplication id,Current application status_id, fatal return code, Has fatal flag?";
$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
		
		$application_id = $row['application_id'];
		$application = ECash::getApplicationById($application_id);
		$flags = $application->getFlags();
		$has_flag = true;
		if(!$flags->get('has_fatal_ach_failure'))
		{$i++;
		//	echo "\nAdding fatal ach flag for {$row['application_id']} ";
		//	$flags->set('has_fatal_ach_failure');
		$has_flag = false;
		}
		echo "\n{$application_id},{$row['application_status_id']}, {$row['return_code']}, {$has_flag}";
		//		Remove_Unregistered_Events_From_Schedule($application_id);
		//		Update_Status(NULL,$application_id,190);

		
		
	}
	echo "\nAdding {$i} fatal ACH flags\n\n";
	
}



?>
