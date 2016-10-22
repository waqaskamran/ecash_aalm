<?php
	/**
	 * Event Type, Transaction Type, Event <-> Transaction Map Data Dump
	 * 
	 * Use this script against an existing reference database to generate a new
	 * SQL script that can be used either on a new database or for a new company.
	 * 
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */

	$company_id = $argv[1];
	if($company_id === NULL || ! ctype_digit((string)$company_id))
	{
		die("Must supply a company_id to pull reference data from!\n");
	}

	require_once(dirname(__FILE__)."/../www/config.php");
	require_once("mysqli.1.php");
	
	$mysqli = new MySQLi_1(SLAVE_DB_HOST, SLAVE_DB_USER, SLAVE_DB_PASS, SLAVE_DB_NAME, SLAVE_DB_PORT);
	
	$sql = "
	select  et.name_short as et_name_short, 
	        et.name as et_name,
        	tt.name_short as tt_name_short, 
	        tt.name as tt_name, 
	        tt.clearing_type as tt_clearing_type, 
	        tt.affects_principal as tt_affects_principal, 
	        tt.pending_period as tt_pending_period, 
	        tt.end_status as tt_end_status, 
	        tt.period_type as tt_period_type
	from event_transaction evt
	join event_type as et using (event_type_id)
	join transaction_type as tt using (transaction_type_id)
	where et.active_status = 'active'
	and   tt.active_status = 'active'
	AND company_id = {$company_id}";
	
	
	$events = array();
	$transactions = array();

	echo "--- SQL Script to generate event and transaction types and the event <-> transaction map\n";
	echo "--- Change the company_id below to whichever company you want to create the data for.\n";
	echo "\nSELECT @company_id := $company_id;\n";
	
	$result = $mysqli->Query($sql);
	while($r = $result->Fetch_Object_Row())
	{
		if(!in_array($r->et_name_short, $events))
		{
			echo "\n-- New Event Type :: {$r->et_name}\n";
			
			echo "INSERT INTO `event_type` (date_modified, date_created, active_status, company_id, name_short, name)\n";
			echo "VALUES (NOW(),NOW(),'active',@company_id,'{$r->et_name_short}','{$r->et_name}');\n";
			echo "SELECT @event_type_id := LAST_INSERT_ID();\n";
			$events[] = $r->et_name_short;
		}
		
		echo "INSERT INTO transaction_type (date_modified, date_created, company_id, name_short, name, clearing_type, affects_principal, pending_period, end_status, period_type)\n";
		echo "VALUES (NOW(), NOW(), @company_id, '{$r->tt_name_short}', '{$r->tt_name}', '{$r->tt_clearing_type}', '{$r->tt_affects_principal}', '{$r->tt_pending_period}', '{$r->tt_end_status}', '{$r->tt_period_type}');\n";
		echo "SELECT @transaction_type_id := LAST_INSERT_ID();\n";

		echo "INSERT INTO event_transaction (date_modified, date_created, active_status, company_id, event_type_id, transaction_type_id)\n";
		echo "VALUES (NOW(), NOW(), 'active', @company_id, @event_type_id, @transaction_type_id);\n";
	}

?>