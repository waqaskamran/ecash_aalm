#!/usr/bin/php
<?php
/*  Usage example:  php -f ecash_data_utility.php  */

/*
 * Before using this utility, verify all of the defines below.  Only a few precautions 
 * are made to verify data is not modified on the wrong servers.  Be sure to have a full 
 * set of reference data loaded on the local DB before attempting to run the 'fund' command.
 */

$file_name = $_SERVER['argv'][1];
chdir(dirname(__FILE__));
require_once("../www/config.php");

require_once(COMMON_LIB_DIR."mysqli.1.php");

$log = new Applog(APPLOG_SUBDIRECTORY.'/repairs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
$mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->auto_commit(false);

if (!is_writable($file_name)) {
	die("Usage: php find_3rd_party_refunds.php <reverse_sql_filename>\n");
}

$refund_3rd_party_id = RetrieveEventTypeId('refund_3rd_party', $mysqli);
$refund_id = RetrieveEventTypeId('refund', $mysqli);

$refund_princ_id = RetrieveTransactionTypeId('refund_princ', $mysqli);
$refund_fees_id = RetrieveTransactionTypeId('refund_fees', $mysqli);
$refund_3rd_party_princ_id = RetrieveTransactionTypeId('refund_3rd_party_princ', $mysqli);
$refund_3rd_party_fees_id = RetrieveTransactionTypeId('refund_3rd_party_fees', $mysqli);

//First search for newer refunds that will be split
$search_query_non_balanced_accounts = <<<END_SQL
SELECT manual_app.application_id, manual_app.application_status_id, balance
	FROM (SELECT a.application_id, a.application_status_id
		FROM 
			event_schedule es
			LEFT JOIN event_type et ON es.event_type_id = et.event_type_id
			LEFT JOIN application a ON es.application_id = a.application_id
		WHERE 
			et.name_short LIKE 'refund%'
			AND es.amount_non_principal <> 0) as manual_app
	LEFT JOIN (SELECT	application_id,
			SUM(IF(tt.affects_principal = 'yes', amount, 0)) + SUM(IF(tt.affects_principal = 'no', amount, 0)) as balance
		FROM
		    event_schedule es
		    LEFT JOIN transaction_register tr USING (event_schedule_id)
		    LEFT JOIN event_type et ON es.event_type_id = et.event_type_id
		    LEFT JOIN transaction_type tt ON tr.transaction_type_id = tt.transaction_type_id
		WHERE
			(es.event_status = 'scheduled'
			OR (es.event_status = 'registered' AND tr.transaction_status != 'failed'))
			AND et.name_short <> 'refund_3rd_party'
		GROUP BY (application_id)) as app_balance on manual_app.application_id = app_balance.application_id
	GROUP BY manual_app.application_id
	HAVING balance <> 0 AND application_status_id = 109
END_SQL;
$result = $mysqli->Query($search_query_non_balanced_accounts);

echo "Please Review the following non-balanced accounts:\n\n";
flush();

while ($row = $result->Fetch_Object_Row()) {
	echo "\n APPLICATION #".$row->application_id." [" . $row->application_status_id ."] ($".$row->balance.")\n\n";
	flush();
	
	$search_query_refund_events= <<<END_SQL
SELECT 
		es.application_id, es.event_schedule_id,
		es.event_type_id, es.configuration_trace_data, 
		et.name_short
	FROM event_schedule es
	LEFT JOIN event_type et ON es.event_type_id = et.event_type_id 
	WHERE application_id = {$row->application_id} 
		AND et.name_short LIKE 'refund%'	
END_SQL;
	
	$result_refund_events = $mysqli->Query($search_query_refund_events);
	
	while ($row_re = $result_refund_events->Fetch_Object_Row()) {
		echo "\n    EVENT #".$row_re->event_schedule_id." [".$row_re->name_short."] ".$row_re->configuration_trace_data."\n\n";
		flush();
		
		Print_Schedule($row->application_id, $mysqli);
		
		
		$option = 0;
		$available_options = array(1, 2, 3);
		while (!in_array($option, $available_options)) {
			echo "[1] Convert    [2] Split     [3] Do Nothing\n";
			flush();
			$option = readline("Action: ");
		}
		
		switch ($option) {
			case 1:
				$newType = $row_re->name_short == 'refund'?'refund_3rd_party':'refund';
				echo "Converting to {$newType}\n";
				flush();

				$newType_id = $newType == 'refund'?$refund_id:$refund_3rd_party_id;
				$newType_trans_princ_id = $newType == 'refund'?$refund_princ_id:$refund_3rd_party_princ_id;
				$newType_trans_fees_id = $newType == 'refund'?$refund_fees_id:$refund_3rd_party_fees_id;
				
				$reverse_file = fopen($file_name, 'a');
			
				if (!($event = RetrieveEvent($row_re->event_schedule_id, $mysqli))) {
					$log->Write("!!ERROR: COULDN'T FIND EVENT $event_id");
					continue;
				}
				
				$mysqli->Start_Transaction();
				_doUpdate($reverse_file, $mysqli, 'event_schedule', 'event_schedule_id', (array)$event, array('event_type_id' => $newType_id));
				
				$registers = RetrieveTransactionRegister($event, $mysqli);
				
				foreach ($registers as $register) {
					$new_transaction_type = 0;
					if (in_array($register->transaction_type_id , array($refund_princ_id,$refund_3rd_party_princ_id))) {
						$new_transaction_type =  $newType_trans_princ_id;
					} elseif (in_array($register->transaction_type_id , array($refund_fees_id,$refund_3rd_party_fees_id))) {
						$new_transaction_type =  $newType_trans_fees_id;
					} else {
						$log->Write("!!ERROR - WRONG TRANSACTION TYPE");
						continue;
					}
					_doUpdate($reverse_file, $mysqli, 'transaction_register', 'transaction_register_id', (array)$register, array('transaction_type_id' => $new_transaction_type));
					
					$ledger = RetrieveTransactionLedger($register, $mysqli);
					if ($ledger = RetrieveTransactionLedger($register, $mysqli)) {
						_doUpdate($reverse_file, $mysqli, 'transaction_ledger', 'transaction_ledger_id', (array)$ledger, array('transaction_type_id' => $new_transaction_type));
					}
				}
				fclose($reverse_file);
				$mysqli->commit();
				echo "Finished Converting.\n";
				flush();
			
				break;
			case 2:
				echo "Splitting amount into new event\n";
				flush();
				$newType = $row_re->name_short == 'refund'?'refund_3rd_party':'refund';

				$newType_id = $newType == 'refund'?$refund_id:$refund_3rd_party_id;
				$newType_trans_princ_id = $newType == 'refund'?$refund_princ_id:$refund_3rd_party_princ_id;
				$newType_trans_fees_id = $newType == 'refund'?$refund_fees_id:$refund_3rd_party_fees_id;
				
				$reverse_file = fopen($file_name, 'a');
				
				echo "New event is a ".$newType."\n";
				flush();
				$principal_amount = readline("Enter Amount to split into principal: ");
				$non_principal_amount = readline("Enter Amount to split into non-principal: ");
			
				if (!($event = RetrieveEvent($row_re->event_schedule_id, $mysqli))) {
					$log->Write("!!ERROR: COULDN'T FIND EVENT $event_id");
					continue;
				}
				
				$mysqli->Start_Transaction();
				_doUpdate($reverse_file, $mysqli, 'event_schedule', 'event_schedule_id', (array)$event, array('amount_non_principal' => $event->amount_non_principal - $non_principal_amount, 'amount_principal' => $event->amount_principal - $principal_amount));
				$fields = (array)$event;
				$fields['event_type_id'] = $refund_3rd_party_id;
				$fields['amount_principal'] = $non_principal_amount;
				$fields['amount_non_principal'] = $principal_amount;
					
				unset($fields['event_schedule_id']);
				unset($fields['origin_id']);
				unset($fields['origin_group_id']);
				$new_schedule_id = _doAdd($reverse_file, $mysqli, 'event_schedule', 'event_schedule_id', $fields);
					
				$registers = RetrieveTransactionRegister($event, $mysqli);
					
				foreach ($registers as $register) {
					$new_transaction_type = 0;
					if (in_array($register->transaction_type_id , array($refund_princ_id,$refund_3rd_party_princ_id))) {
						$new_transaction_type =  $newType_trans_princ_id;
					} elseif (in_array($register->transaction_type_id , array($refund_fees_id,$refund_3rd_party_fees_id))) {
						$new_transaction_type =  $newType_trans_fees_id;
					} else {
						$log->Write("!!ERROR - WRONG TRANSACTION TYPE");
						continue;
					}
					
					if ($new_transaction_type == $newType_trans_princ_id) {
						$amount = $principal_amount;
						$principal_amount = 0;
					} else {
						$amount = $non_principal_amount;
						$non_principal_amount = 0;
					}
					if ($amount != 0) {
						if ($principal_amount != $register->amount) {
							_doUpdate($reverse_file, $mysqli, 'transaction_register', 'transaction_register_id', (array)$register, array(
								'amount' => $register->amount - $principal_amount,
							));
							
							$fields = (array)$register;
							unset($fields['transaction_register_id']);
							$fields['event_schedule_id'] = $new_schedule_id;
							$fields['transaction_type_id'] = $new_transaction_type;
							$fields['amount'] = $principal_amount;
							$new_register_id = _doAdd($reverse_file, $mysqli, 'transaction_register', 'transaction_register_id', $fields);
							
							if ($ledger = RetrieveTransactionLedger($register, $mysqli)) {
								_doUpdate($reverse_file, $mysqli, 'transaction_ledger', 'transaction_ledger_id', (array)$ledger, array(
									'amount' => $ledger->amount - $principal_amount,
								));
					
								$fields = (array)$ledger;
								unset($fields['transaction_ledger_id']);
								$fields['transaction_register_id'] = $new_register_id;
								$fields['transaction_type_id'] = $new_transaction_type;
								$fields['amount'] = $split_amount;
								_doAdd($reverse_file, $mysqli, 'transaction_ledger', 'transaction_ledger_id', $fields);
							}
						} else {
							_doUpdate($reverse_file, $mysqli, 'transaction_register', 'transaction_register_id', (array)$register, array(
								'transaction_type_id' => $new_transaction_type,
								'event_schedule_id' => $new_schedule_id,
							));
							
							if ($ledger = RetrieveTransactionLedger($register, $mysqli)) {
								_doUpdate($reverse_file, $mysqli, 'transaction_ledger', 'transaction_ledger_id', (array)$ledger, array(
									'transaction_type_id' => $new_transaction_type,
								));
							}
						}
					}
				}
				
				fclose($reverse_file);
				$mysqli->commit();
				break;
			case 3:
				echo "Doing Nothing\n";
				flush();
				break;
		}
	}
}

function RetrieveEvent($event_id, $mysqli) {
	$event_type_query = "SELECT * FROM event_schedule WHERE event_schedule_id = $event_id";
	
	$event_result = $mysqli->Query($event_type_query);
	
	if (!($event_row = $event_result->Fetch_Object_Row())) {
		return false;
	} else {
		return $event_row;
	}
}

function RetrieveTransactionRegister($event, $mysqli) {
	$transaction_register_query = "SELECT * FROM transaction_register WHERE event_schedule_id = {$event->event_schedule_id}";
	$register_result = $mysqli->Query($transaction_register_query);
	
	$registers = array();
	while ($register_row = $register_result->Fetch_Object_Row()) {
		$registers[] = $register_row;
	}
	
	return $registers;
}

function RetrieveTransactionLedger($register, $mysqli) {
	$transaction_ledger_query = "SELECT * FROM transaction_ledger WHERE transaction_register_id = {$register->transaction_register_id}";
	$ledger_result = $mysqli->Query($transaction_ledger_query);
	
	$ledgers = array();
	while ($ledger_row = $ledger_result->Fetch_Object_Row()) {
		$ledgers[] = $ledger_row;
	}
	
	return isset($ledgers[0])?$ledgers[0]:null;
}


function RetrieveEventTypeId($short_name, $mysqli) {
	$result = $mysqli->Query("SELECT event_type_id FROM event_type WHERE name_short = '$short_name'");
	if ($row = $result->Fetch_Object_Row()) {
		return $row->event_type_id;
	} else {
		return false;
	}
}

function RetrieveTransactionTypeId($short_name, $mysqli) {
	$result = $mysqli->Query("SELECT transaction_type_id FROM transaction_type WHERE name_short = '$short_name'");
	if ($row = $result->Fetch_Object_Row()) {
		return $row->transaction_type_id;
	} else {
		return false;
	}
}

function _doUpdate($reverse_file, $mysqli, $table, $key_field, $original, $change) {
	$sql = 'UPDATE '.$table.' SET ';
	$sets = array();
	foreach ($change as $name => $value) {
		$sets[] = $name."='".$mysqli->Escape_String($value)."'";
	}
	$sql .= implode(', ', $sets);
	$sql .= ' WHERE '.$key_field." = '".$original[$key_field]."';";
	
	$mysqli->Query($sql);
	
	$reverse_change = array_intersect_key($original, $change);

	$reverse_sql = 'UPDATE '.$table.' SET ';
	$sets = array();
	foreach ($reverse_change as $name => $value) {
		$sets[] = $name."='".$value."'";
	}
	$reverse_sql .= implode(', ', $sets);
	$reverse_sql .= ' WHERE '.$key_field." = '";
	if (isset($change[$key_field])) {
		$reverse_sql .= $change[$key_field];
	} else {
		$reverse_sql .= $original[$key_field];
	}
	$reverse_sql .= "';";
	fwrite($reverse_file, $reverse_sql."\n");
}

function _doAdd($reverse_file, $mysqli, $table, $key_field, $fields) {
	$escaped_fields = array();
	foreach ($fields as $name => $value) {
		$escaped_fields[$name] = $mysqli->Escape_String($value);
	}
	$sql = 'INSERT '.$table.' ('.implode(', ', array_keys($escaped_fields)).") VALUES('".implode("', '", array_values($escaped_fields))."');";
	$mysqli->Query($sql);
	
	$id = $mysqli->Insert_Id();
	$reverse_sql = 'DELETE FROM '.$table.' WHERE '.$key_field." = '".$id."';";
	fwrite($reverse_file, $reverse_sql."\n");
	return $id;
}

function Print_Schedule($application_id, $mysqli) {
	$schedule_sql = <<<END_SQL
SELECT
		es.event_schedule_id,
		es.date_modified,
		et.name,
		tr.transaction_status,
		IF(tt.affects_principal = 'yes', amount, 0) as principal,
		IF(tt.affects_principal = 'no', amount, 0) as non_principal
		
	FROM
		event_schedule es
		LEFT JOIN transaction_register tr USING (event_schedule_id)
		LEFT JOIN event_type et ON es.event_type_id = et.event_type_id
		LEFT JOIN transaction_type tt ON tr.transaction_type_id = tt.transaction_type_id
	WHERE
		es.application_id = $application_id
		AND (es.event_status = 'scheduled'
		OR (es.event_status = 'registered' AND tr.transaction_status != 'failed'))
	ORDER BY es.date_event
END_SQL;

	$result = $mysqli->query($schedule_sql);
	
	echo str_pad("ID", 10, ' ', STR_PAD_RIGHT);
	echo str_pad("DATE", 30, ' ', STR_PAD_RIGHT);
	echo str_pad("DESCRIPTION", 30, ' ', STR_PAD_RIGHT);
	echo str_pad("STATUS", 20, ' ', STR_PAD_RIGHT);
	echo str_pad("PRINCIPAL", 10, ' ', STR_PAD_RIGHT);
	echo str_pad("NON PRINCIPAL", 10, ' ', STR_PAD_RIGHT);
	echo "\n";
	echo str_repeat('-', 110)."\n";
	while ($row = $result->Fetch_Object_Row()) {
		echo str_pad($row->event_schedule_id, 10, ' ', STR_PAD_RIGHT);
		echo str_pad($row->date_modified, 30, ' ', STR_PAD_RIGHT);
		echo str_pad($row->name, 30, ' ', STR_PAD_RIGHT);
		echo str_pad($row->transaction_status, 20, ' ', STR_PAD_RIGHT);
		echo str_pad($row->principal, 10, ' ', STR_PAD_RIGHT);
		echo str_pad($row->non_principal, 10, ' ', STR_PAD_RIGHT);
		echo "\n";
	}
	echo str_repeat('-', 100)."\n";
}



class Event_Split_Container {
	public $event_schedule_id = '';
	public $split_amount;
	
	public function __construct($id, $amount) {
		$this->event_schedule_id = $id;
		$this->split_amount = $amount;
	}
}

function readline($prompt) {
	echo "$prompt";
	flush();
	return trim(fgets(STDIN));
}

?>
