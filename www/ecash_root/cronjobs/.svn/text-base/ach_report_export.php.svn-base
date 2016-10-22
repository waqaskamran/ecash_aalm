<?php
/**
 * Utility to dump a return file in the database to a file on disk
 */

function main($argv)
{
	
	if((empty($argv[4])) || empty($argv[5]))
	{
		die("Must pass report_id and filename parameters!\n");
	}

	$report_id = $argv[4];
	$filename  = $argv[5];

	$db = ECash::getSlaveDb();
	
	$query = "
	SELECT remote_response
	FROM ach_report
	WHERE ach_report_id = {$report_id}";

	$result = $db->Query($query);

	if($result->rowCount() == 0) {
		return false;
	}

	while ($row = $result->fetch(PDO::FETCH_OBJ)) 
	{
		$data = $row->remote_response;
	}
	
	$fp = fopen($filename, 'w');
	fwrite($fp, $data);
	fclose($fp);

}
