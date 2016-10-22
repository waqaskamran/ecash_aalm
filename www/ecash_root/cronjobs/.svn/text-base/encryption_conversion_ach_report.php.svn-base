<?php
/**
ADDING FATAL ACH FLAGS!
 */

function Main()
{
	$db =  ECash::getMasterDb();
	global $server;
	
	
	
	//get applications
	$sql = "			SELECT 
	ach_report_id,
    remote_response
    
    FROM
    ach_report
;

	";
	$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$ach_batch_id = $row['ach_report_id'];
		$remote_response = $row['remote_response'];
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
$query = "UPDATE ach_report SET 
		remote_response_oldkey = ?,
		remote_response_iv = ?
		WHERE ach_report_id = ?
			";


		$st = $db->queryPrepared($query, array($remote_response, md5($remote_response), $ach_batch_id));

		$i++;
		
		
	}
	echo "\nUpdate $i records\n\n";
}



?>
