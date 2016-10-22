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
	sreport_data_id,
    sreport_data
    
    FROM
    sreport_data
;

	";
	$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$sreport_data_id = $row['sreport_data_id'];
		$sreport_data = $row['sreport_data'];
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
$query = "UPDATE sreport_data SET 
		sreport_data_oldkey = ?,
		sreport_data_iv = ?
		WHERE sreport_data_id = ?
			";


		$st = $db->queryPrepared($query, array($sreport_data, md5($sreport_data), $sreport_data_id));

		$i++;
		
		
	}
	echo "\nUpdate $i records\n\n";
}



?>
