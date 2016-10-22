<?php

function Main()
{
	global $server;
	global $_BATCH_XEQ_MODE;
	global $co;       

	echo __FILE__. " : Executing Backfill of Cashline IDs to newly funded apps. [Mode: {$_BATCH_XEQ_MODE}] [Company: {$co}]";
	
	$db = ECash::getMasterDb();
	// Get all the apps that are in the standby table for backfilling	
	//mantis:7357 - filter company	
	$query = "SELECT st.* 
		  FROM standby st
		  JOIN application ap ON (st.application_id = ap.application_id)
		  WHERE st.process_type = 'backfill'
		     AND
			ap.company_id = {$server->company_id}
		 ";

	$result = $db->query($query);
	$apps = array();

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) 
	{		
		$query = "
SELECT application_id, name_last, name_first, ssn
FROM application
WHERE application_id = {$row['application_id']}";
		$result2 = $db->query($query);
		if ($row2 = $result2->fetch(PDO::FETCH_ASSOC)) 
		{
			$ln = strtoupper($row2['name_last']);
			$fn = strtoupper($row2['name_first']);
			if (!isset($apps[$ln])) $apps[$ln] = array();
			    $apps[$ln][$fn] = $row2['application_id'];
		}		
	}

	echo "Candidate apps:\n",print_r($apps, true). "\n";

	// Now read in the CSV from serenity
	$infile = file_get_contents(BASE_DIR."cronjobs/funded-".date("Y-m-d", strtotime("-1 day")).".csv");
	$infile = explode("\n", $infile);

	$ldb = $db;
	$ldb->query("set autocommit=1");

	// And backfill...
	// As we go, make sure to remove the row from the standby table
	foreach ($infile as $line) 
	{
		try 
		{			
			$data = explode(",", $line);
			$ln = substr($data[1], 1, strlen($data[1])-2);
			$fn = substr($data[2], 1, strlen($data[2])-2);
			$aci = substr($data[3], 1, strlen($data[3])-2);
			$id = $apps[$ln][$fn];

			if (isset($id)) {
				$ssn_res = $ldb->query("SELECT ssn from application where application_id = {$id}");
				$ssn = $ssn_res->fetch(PDO::FETCH_OBJ)->ssn;
				$ldb->exec("UPDATE application set archive_cashline_id = NULL where ssn = {$ssn}
                                            and company_id = {$server->company_id}");
				$query = "
UPDATE application
SET archive_cashline_id = {$aci}
WHERE application_id = {$id}
AND company_id = {$server->company_id}
";
				$ldb->exec($query);

				$query = "
DELETE FROM standby
WHERE application_id = {$id}
AND process_type = 'backfill'";
				$ldb->exec($query);
				echo "Associated Cashline ID {$aci} to eCash ID {$id}\n";
			} 
			else 
			{
				echo "Failed to associate imported line {$line}, names not found.\n";
			}
		} 
		catch (Exception $e) 
		{
			 echo "Failed to associate imported line {$line} (EXCEPTION:".$e->getMessage()."\n";
		}
	}
       
}

?>
