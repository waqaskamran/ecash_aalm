<?php
include dirname(__FILE__).'/../../../www/config.php';
require_once(LIB_DIR . 'company_rules.class.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(LIB_DIR . 'mysqli.1e.php');
require_once(SQL_LIB_DIR.'queues.lib.php');


Set_Company_Constants($argv[1]);

$company_id = Fetch_Company_ID_by_Name(MySQLi_1e::Get_Instance(), $argv[1]);
$mysqli = MySQLi_1e::Get_Instance();

convertCurrentQueueStatusRows($mysqli, $company_id);
deleteQueueAffiliations($mysqli, $company_id);
updateAffiliationStatus($mysqli, $company_id);
updateAffiliationReason($mysqli, $company_id);
updateActualAffiliationExpirations($mysqli, $company_id);

function deleteQueueAffiliations(MySQLi_1e $mysqli, $company_id)
{
	$query = "
		DELETE FROM agent_affiliation
		WHERE
			affiliation_area = 'queue' AND
			company_id = {$mysqli->Intelligent_Escape($company_id)}
	";
	
	$mysqli->Query($query);
}

function updateAffiliationStatus(MySQLi_1e $mysqli, $company_id)
{
	$original_query = "
		SELECT
			(
				SELECT agent_affiliation_id
				FROM agent_affiliation
				WHERE
					application_id = aa.application_id AND
					affiliation_area = aa.affiliation_area AND
					affiliation_type = aa.affiliation_type
				ORDER BY date_created DESC
				LIMIT 1
			) affiliation_id
		FROM
			agent_affiliation aa
		WHERE
			aa.date_expiration IS NULL OR
			aa.date_expiration > NOW() AND
			aa.company_id = {$mysqli->Intelligent_Escape($company_id)}
		GROUP BY
			aa.application_id, aa.affiliation_area, aa.affiliation_type
	";
	
	$result = $mysqli->Query($original_query);
	
	while ($row = $result->Fetch_Array_Row())
	{
		$query = "
			UPDATE agent_affiliation
			SET affiliation_status = 'active'
			WHERE
				agent_affiliation_id = {$mysqli->Intelligent_Escape($row['affiliation_id'])}
		";
		
		$mysqli->Query($query);
	}
	
	$final_query = "
		UPDATE agent_affiliation
		SET affiliation_status = 'expired'
		WHERE 
			affiliation_status IS NULL AND
			company_id = {$mysqli->Intelligent_Escape($company_id)}
	";
	
	$mysqli->Query($final_query);
}

function updateAffiliationReason(MySQLi_1e $mysqli, $company_id)
{
	$query = "
		UPDATE agent_affiliation
		SET
			agent_affiliation_reason_id = (SELECT agent_affiliation_reason_id FROM agent_affiliation_reason WHERE name_short = 'other')
		WHERE
			company_id = {$mysqli->Intelligent_Escape($company_id)}
	";
	
	$mysqli->Query($query);
}

function updateActualAffiliationExpirations(MySQLi_1e $mysqli, $company_id)
{
	$query = "
		UPDATE agent_affiliation
		SET
			date_expiration_actual = date_expiration
		WHERE
			company_id = {$mysqli->Intelligent_Escape($company_id)}
	";
	
	$mysqli->Query($query);
}

function convertCurrentQueueStatusRows(MySQLi_1e $mysqli, $company_id)
{
	$results = retrieveCurrentQueueStatusRows($mysqli, $company_id);
	
	while ($row = $results->Fetch_Array_Row())
	{
		processCurrentQueueStatusRow($mysqli, $row, $company_id);
	}
	
}

/**
 * @param MySQLi_1e $mysqli
 * @return MySQLi_Result_1
 */
function retrieveCurrentQueueStatusRows(MySQLi_1e $mysqli, $company_id)
{
	$query = "
		SELECT
			cqs.application_id,
			cqs.queue_name,
			(
			SELECT
			FROM_UNIXTIME(MAX(date_removed))
			FROM
			queue_history
			WHERE
			key_value = cqs.application_id AND
			queue_name = cqs.queue_name
			) last_queue_pull
		FROM
			current_queue_status cqs
			JOIN application a USING (application_id)
		WHERE
			a.company_id = {$company_id}
		HAVING
			last_queue_pull IS NOT NULL
	";
	
	return $mysqli->Query($query);
}

function processCurrentQueueStatusRow(MySQLi_1e $mysqli, Array $row, $company_id)
{
	$timeout = getQueueRecycleTimeout($company_id, $row['queue_name']);
	
	$recycle_time = date('Y-m-d H:i:s', strtotime($row['last_queue_pull']) + $timeout);
	
	updateRecycleTime($mysqli, $row['application_id'], $row['queue_name'], $recycle_time);
}

function updateRecycleTime(MySQLi_1e $mysqli, $application_id, $queue_name, $recycle_time)
{
	$query = "
		UPDATE current_queue_status
		SET
			date_to_recycle = {$mysqli->Intelligent_Escape($recycle_time)}
		WHERE
			application_id = {$mysqli->Intelligent_Escape($application_id)} AND
			queue_name = {$mysqli->Intelligent_Escape($queue_name)}
	";
	
	$mysqli->Query($query);
}

function getQueueRecycleTimeout($company_id, $queue_name)
{
	$_SESSION['company_id'] = $company_id;
	return queue_get_config_timeout($queue_name) * 60;
}

?>