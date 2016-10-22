<?php

/**
 * send out reminder letters to those who have made payment arrangements
 * 3 days prior to activation of said arrangement
 */
function Send_Arrangement_Reminder_Letters($server)
{
	$mssql_db = ECash::getAppSvcDB();
	$query = "CALL sp_arrangement_reminder_letter_app_ids";
	$result = $mssql_db->query($query);
	$app_ids = array();

	if (!empty($result))
	{
		while($row = $result->fetch())
		{
			$app_ids[] = $row['application_id'];
		}
	}

	if (!empty($app_ids))
	{
		$company_id = $server->company_id;
		$db = ECash::getMasterDb();
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$accounts = array();

		// The following query pulls up all accounts that have a debit scheduled 3 days from now.
		$tbd = $pdc->Get_Business_Days_Forward(date("Y-m-d"), 3);

		$in_stmt = implode(',', $app_ids);
		$query = "
		SELECT application_id
		FROM event_schedule
		WHERE date_effective = '{$tbd}'
			AND company_id = {$company_id}
			AND (context = 'arrangement' OR context = 'partial' )
			AND application_id IN ({$in_stmt})
		";
		$accounts = $db->querySingleColumn($query);

		foreach ($accounts as $account_id) 
		{
			ECash::getLog()->Write("Sending Arrangement Reminder Letter for account {$account_id}");
			ECash_Documents_AutoEmail::Queue_For_Send($account_id, 'ARRANGEMENTS_MADE');
		}

		ECash_Documents_AutoEmail::Send_Queued_Documents();
	}
}

/*                 MAIN processing code                */

function Main()
{
	global $server;
	
	require_once(COMMON_LIB_DIR."pay_date_calc.3.php");
	require_once(LIB_DIR."common_functions.php");


	Send_Arrangement_Reminder_Letters($server);

}
