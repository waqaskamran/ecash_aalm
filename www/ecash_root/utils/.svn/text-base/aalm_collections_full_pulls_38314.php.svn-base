<?php
/**
	38314
 */

function Main()
{
	$db = ECash::getSlaveDb();
	global $server;
	$limit = 30;
	$company_id = ECash::getCompany()->company_id;
	//Because of the crazy amount of datafixes that have been performed on applications that have full pulls, and have
	//failed full pulls, I am ONLY going to address applications that have a scheduled full pull reattempt.  All others
	//Will be left in their current status.

	//get applications
	$sql = "
			SELECT app.*, 

(SELECT COUNT(es.event_schedule_id)
FROM event_schedule AS es
JOIN event_transaction AS et ON et.event_type_id = es.event_type_id
JOIN transaction_type AS tt ON tt.transaction_type_id = et.transaction_type_id
WHERE es.application_id = app.application_id
AND es.event_status = 'scheduled'
AND tt.clearing_type = 'ach'
AND es.context IN ('generated', 'reattempt')
) as num_scheduled
 FROM application_status
JOIN application app ON app.application_status_id = application_status.application_status_id
WHERE application_id IN (SELECT application_id FROM event_schedule WHERE event_type_id = 13 AND event_schedule.context = 'reattempt' AND event_status = 'scheduled') 
;"
;
	
	$app_count = 0;
	echo "'$sql'\n\n";
	echo "reattempted FULL PULLS FOR ".strtoupper(ECash::getCompany()->name_short);
	$result = $db->query($sql);
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$app_count++;
		// Generate a full pull event
		//Remove_Unregistered_Events_From_Schedule($application_id);
		
		$application_id = $row['application_id'];
		// Remove it from all queues
		//Update_Status(NULL,$application_id,190);
		
//		}
	echo"\n{$application_id}";
	}
		
	
	echo "\n\n{$app_count} apps with canceled full pulls";
}



?>
