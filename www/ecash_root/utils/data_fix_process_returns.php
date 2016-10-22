<?php
/**
php data_fix_process_returns.php

DELETE DFA in utils dir

CHECK FORMAT
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SERVER_CODE_DIR . 'comment.class.php');

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

echo "Started...\n";

$sql = "
SELECT ach_report_id,remote_response
FROM ach_report
WHERE report_status='received'
-- AND ach_report_id IN (1781)
ORDER BY ach_report_id ASC
";
$result = $db->query($sql);
while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	$app_es_array = array();
	$es_array = array();

	$ach_report_id = $row->ach_report_id;
	$return_array = array();
	$return_array = explode("\n", $row->remote_response);
	//var_dump($return_array);
	if (count($return_array) > 0)
	{
		echo count($return_array), " records in the report", "\n";
		foreach ($return_array as $return_record)
		{
			$record_array = array();
			$record_array = explode(",", $return_record);
			//var_dump($record_array);break;
			echo count($record_array), " fields in the record for the report id ", $ach_report_id, "\n";
			if (count($record_array) > 18)
			{
				$ach_id = str_replace('"', '', $record_array[3]);
				$ach_status = strtolower(str_replace('"', '', $record_array[14]));
				$returned_code = str_replace('"', '', $record_array[15]);
				
				if (!empty($ach_id) && $ach_status == "returned")
				{
					$sql1 = "
					SELECT application_id,event_schedule_id,transaction_status
					FROM transaction_register
					WHERE ach_id = {$ach_id}
					";
					try
					{
						$result1 = $db->query($sql1);
						$row1 = $result1->fetch(PDO::FETCH_OBJ);
						if (!empty($row1))
						{
							$transaction_status = $row1->transaction_status;
							if ($transaction_status != 'failed')
							{
								$application_id = $row1->application_id;
								$event_schedule_id = $row1->event_schedule_id;

								if (!in_array($event_schedule_id, $es_array))
								{
									$es_array[] = $event_schedule_id;
									$app_es_array[$event_schedule_id] = $application_id;
								}
								
								$sql2 = "
									SELECT ach_return_code_id
									FROM ach_return_code
									WHERE name_short = '{$returned_code}'
								";
								$result2 = $db->query($sql2);
								$row2 = $result2->fetch(PDO::FETCH_OBJ);
								$ach_return_code_id = $row2->ach_return_code_id;
								echo $ach_report_id, ", ", $ach_id, ", ", $ach_status, ", ", $returned_code, ", ", $ach_return_code_id, "\n";
			
								$sql3 = "
									UPDATE ach
									SET ach_status = 'returned',
									ach_return_code_id = {$ach_return_code_id},
									ach_report_id = {$ach_report_id}
									WHERE ach_id = {$ach_id}
								";
								$db->query($sql3);
							}
							else
							{
								echo "Transaction already failed \n";
							}
						}
					}
					catch(Exception $e)
					{
						continue;
					}
				}
				else
				{
					echo "Ach status in the record is not Returned \n";
				}
			}
			else
			{
				echo "Few number of fields in the record \n";
			}
		}
	}

	if (count($app_es_array) > 0)
	{
		ksort($app_es_array);
		foreach ($app_es_array as $event_schedule_id => $application_id)
		{
			echo $event_schedule_id, ", ", $application_id, "\n";
			try
			{
				Record_Event_Failure($application_id, $event_schedule_id);
			
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $server;
				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				$comment = new Comment();
				$comment->Add_Comment($company_id, $application_id, $agent_id,
						      "Assembla 36, Processed return of event id " . $event_schedule_id, "standard"
				);
			}
			catch(Exception $e)
			{
				continue;
			}
		}
	}
}

?>

