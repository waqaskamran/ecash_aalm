<?php

/**
 * This script searches through all of the applications in the
 * database from 2009-01-01 until now.  If the account status is
 * 'Declined', we need to parse the perf calls in the bureau_inquiry
 * table display the reason code.
 * 
 * DataX does three different calls in a waterfall.  If the first call
 * passes, it moves on until either one of them fails or they all
 * pass.  This script will do the same sort of thing, reading through
 * all of the reasons until it finds a fail, and returns that for the
 * reason.
 * 
 * The results are output in a quoted comma delimited format for each
 * import to CSV File.
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

set_include_path(get_include_path() . ':/virtualhosts/libolution:/virtualhosts/lib');
require_once 'AutoLoad.1.php';
require_once 'minixml/minixml.inc.php';


function main()
{
	
	$total_records = 0;
	
	$file = fopen("AALM_accounts_200901_to_current.csv", "w");

	// AALM DB Config
	$db_conf = new DB_MySQLConfig_1('reader.ecashaalm.ept.tss','ecash', 'Hook6Zoh', 'ldb_mls', 3306 );
	$db = $db_conf->getConnection();
	
	
	// CSV HEADER
	//App ID, customer name, email address, campaign info, and loan status
	$columns = array('application_id' => '"Application ID"',
					 'name_last' => '"Last Name"',
					 'name_first' => '"First Name"', 
					 'email' => '"Email"',
					 'promo_id' => '"Promo ID"',
					 'promo_sub_code' => '"Sub Code"',
					 'campaign_name' => '"Campaign Name"',
					 'status' => '"Loan Status"');
	
	fputs($file, join(',', array_merge($columns, array('"Reason"'))) . "\n");

	/**
	 * EXAMPLES:
	 * a.application_id in (212103315, 
	 * 212101903,
	 * 212101659,
	 * 212100617,
	 * 212100277)
	 */
	$sql = "
			SELECT 	a.application_id,
					a.name_last,
					a.name_first,
					a.email,
					ci.promo_id,
					ci.promo_sub_code,
					ci.campaign_name,
					asf.name as status,
					UNCOMPRESS(bi.received_package) as received_package
			FROM application AS a
			LEFT JOIN bureau_inquiry AS bi ON a.application_id = bi.application_id
			JOIN application_status AS asf ON asf.application_status_id = a.application_status_id
			JOIN campaign_info ci ON ci.application_id = a.application_id
			WHERE a.date_created between '2009-01-01' and NOW()
			AND bi.inquiry_type = 'aalm-perf'
			AND (a.name_last NOT LIKE '%test%' AND a.name_first NOT LIKE '%test%')
			ORDER BY a.application_id ASC
			";
	
	$result = $db->query($sql);

	$total = $result->rowCount();
	$counter = 0;
	$last_app_id = NULL;
	
	echo "$total rows to process\n";
	
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{		
		$counter++;
		$percentage = intval(($counter/$total)*100);
		if(($percentage != $last_percentage) && (! ($percentage % 1))) 
		{
				echo "$percentage% complete, $counter of $total\n";
				$last_percentage = $percentage;
		}

		try
		{
			$mini = new MiniXMLDoc();
			$mini->fromString($row->received_package);
			$xml = $mini->toArray();
			$reason = '';

			//skip legit records
			if(isset($xml['DataxResponse']['Response']['Detail']['GlobalDecision']))
			{
				//kind of an ugly hack
				continue;
			}
			else if(isset($xml['DataxResponse']['Response']['ErrorCode']))
			{
				$reason =  "DataX Error: " . $xml['DataxResponse']['Response']['ErrorCode'] . " - " . $xml['DataxResponse']['Response']['ErrorMsg'];
			}
			else
			{
				echo "An unknown error occurred!\n";
				var_dump($xml);
			}

			if($last_app_id != $row->application_id)
			{
				$last_app_id = $row->application_id;
				$total_records++;
				//echo $reason, PHP_EOL;

				$row_array = array();
				foreach($columns as $column => $unused)
				{
					$row_array[] = '"' . $row->{$column} . '"';
				}
				$row_array[] = '"' . $reason . '"';
				fputs($file, join(',', $row_array) . "\n");
			}
			
			unset($xml);

		}
		catch ( Exception $e)
		{
			echo $e->getMessage();
			die();
		}
	}
	
	fclose($file);
	echo "\nTotal Unique Records: {$total_records}\n";
}

main();