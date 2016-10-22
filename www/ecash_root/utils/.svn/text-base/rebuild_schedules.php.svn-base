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
			Complete_Schedule($row['application_id']);
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
		es.application_id,
		name,
		COUNT(DISTINCT
			IF (
				es2.event_type_id = 45,
				es2.event_schedule_id,
				NULL
			)
		) sc_events,
		COUNT(DISTINCT
			IF (
				es2.event_type_id = 62 AND
				es2.origin_id IS NULL AND
				es2.date_effective < (
					SELECT MIN(date_effective) FROM event_schedule WHERE event_type_id = 68 AND application_id = es2.application_id
				),
				es2.event_schedule_id,
				NULL
			)
		) service_charge_payments
	FROM
		event_schedule es
		JOIN application USING (application_id)
		JOIN application_status USING (application_status_id)
		LEFT JOIN event_schedule es2 USING (application_id)
	WHERE
		es.event_type_id IN (62, 68) AND
		es.company_id = 2 AND
		es.application_id BETWEEN 11200000 AND 11600000 AND
		es.event_status = 'scheduled'
	GROUP BY
		es.application_id
	HAVING
		(IF(sc_events > 4, 4, sc_events) + service_charge_payments) <> 4
	ORDER BY
		NULL
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

