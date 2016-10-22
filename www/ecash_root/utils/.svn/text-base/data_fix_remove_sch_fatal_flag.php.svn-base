<?php
/**
php data_fix_remove_sch_fatal_flag.php
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

//Create array:
$app_array = array();

$not_reprocessed = array();

echo "Started...\n";

	if (count($app_array) > 0)
	{
		foreach ($app_array as $application_id)
		{
			echo $application_id, "\n";
			try
			{
				Remove_Unregistered_Events_From_Schedule($application_id);
				
				$app = 	ECash::getApplicationByID($application_id);
				$flags = $app->getFlags();
				if(!$flags->get('has_fatal_ach_failure'))
				{
					$flags->set('has_fatal_ach_failure');
				}

			}
			catch(Exception $e)
			{
				echo $e, "\n";
				$not_reprocessed[] = $application_id;
				continue;
			}
		}
	}

	echo "not processed: \n";
	var_dump($not_reprocessed);
?>

