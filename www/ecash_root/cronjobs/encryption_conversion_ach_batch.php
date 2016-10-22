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
	ach_batch_id,
    ach_file_outbound
    
    FROM
    ach_batch
;

	";
	$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$ach_batch_id = $row['ach_batch_id'];
		$ach_file_outbound = $row['ach_file_outbound'];
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
$query = "UPDATE ach_batch SET 
		ach_file_outbound_oldkey = ?,
		ach_file_outbound_iv = ?
		WHERE ach_batch_id = ?
			";


		$st = $db->queryPrepared($query, array($ach_file_outbound, md5($ach_file_outbound), $ach_batch_id));

		$i++;
		
		
	}
	echo "\nUpdate $i records\n\n";
}



?>
