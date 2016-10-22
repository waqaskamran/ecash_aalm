<?php
/**
 * This script should be used to run applications through rescheduling code.
 * 
 * Application IDs should be passed on newlines through the command line. An 
 * empty line will halt the application.
 */
chdir(dirname(__FILE__));
require_once "../www/config.php";
require_once './mini-server.class.php';
require_once LIB_DIR.'common_functions.php';
require_once SQL_LIB_DIR.'util.func.php';

Main();

function Main()
{
	$args = GetArgs();
	if (empty($args['company_id']))
	{
		DisplayHelp();
		exit(1);
	}
	
	$server = SetupServer($args['company_id']);
	
	require_once SQL_LIB_DIR.'scheduling.func.php';
	$results = getAffectedAccounts();
	while ($row = $results->Fetch_Array_Row())
	{
		try
		{
			Set_Payout($row['application_id']);
		}
		catch (Exception $e)
		{
			echo "Error [{$application_id}]: {$e->getMessage()}\n";
		}
	}
}

function GetArgs()
{
	$args = getopt('c:');
	return array(
		'company_id' => $args['c']
	);
}

function getAffectedAccounts()
{
	$query = "
SELECT
 application_id
FROM
  cl_pending_data pd
  JOIN cl_customer USING (application_id)
  JOIN cl_transaction t USING (customer_id)
  JOIN application USING (application_id)
  JOIN application_status aps USING (application_status_id)
WHERE
  application_id BETWEEN 11200000 AND 11700000 AND
  pd.cashline_status = 'active' AND
  t.transaction_id = (
    SELECT
      transaction_id
    FROM
      cl_transaction
    WHERE
      customer_id = t.customer_id AND
      transaction_type = 'service charge'
    ORDER BY
      transaction_date DESC, transaction_type = 'service charge' DESC
    LIMIT 1
  ) AND
  t.transaction_payment_amount = 0 AND
  t.transaction_effective_date = '0000-00-00' AND
  t.transaction_next_due_date = '0000-00-00' AND
  t.transaction_type = 'service charge' AND
  t.transaction_amount <> 15 AND
  t.transaction_amount_paid = 0 AND
  t.transaction_due_date > '2007-05-30' AND
  NOT EXISTS (
    SELECT 1 FROM event_schedule WHERE application_id = pd.application_id AND event_type_id = 63
  ) AND
  application_status_id = 20
ORDER BY application_id
	";
	return get_mysqli('SLAVE_DB_')->query($query);
}

function DisplayHelp()
{
	echo 'Usage: php '.basename(__FILE__).' -c <company_id>'."\n";
}

function SetupServer($company_id)
{
	$server = new Server(get_log('repair'), MySQLi_1e::Get_Instance(), $company_id);
	Set_Company_Constants($server->company);
}

