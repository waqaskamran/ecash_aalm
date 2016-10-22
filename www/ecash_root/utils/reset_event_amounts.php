<?php
/**
 * Run with extreme risk. It could jack things up TERRIBLY if ran on an 
 * account that already has event_amounts. In fact, this should just go away 
 * when the transaction model is implemented.
 * 
 * So what I am probably trying to say here...unless your name is mike lively 
 * leave this script alone.
 */
$application_id = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
include_once "../www/config.php";
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->autocommit(FALSE);

echo DB_USER, ':', DB_PASS, '@', DB_HOST, ':', DB_PORT, '/', DB_NAME;

$query = "
SELECT 
    event_schedule_id, amount_principal, amount_non_principal,
    company_id, application_id,
    (
    	SELECT transaction_register_id
    	  FROM 
    	  	transaction_register tr
    		JOIN transaction_type tt USING (transaction_type_id)
    	  WHERE
    	  	tr.event_schedule_id = es.event_schedule_id AND
    	  	tt.affects_principal = 'yes'
    ) principal_transaction_register_id,
    (
    	SELECT transaction_register_id
    	  FROM 
    	  	transaction_register tr
    		JOIN transaction_type tt USING (transaction_type_id)
    	  WHERE
    	  	tr.event_schedule_id = es.event_schedule_id AND
    	  	tt.affects_principal <> 'yes'
    ) non_principal_transaction_register_id
  FROM 
    event_schedule es
    JOIN application USING (application_id)
  WHERE
  	(amount_principal <> 0 OR amount_non_principal <> 0) AND
	event_type_id <> 23
";
$result = $mysqli->query($query);

$num_rows = $result->num_rows;
$count = 0;
echo "Number of Rows: $num_rows";
while ($row = $result->fetch_object()) {
	Main($mysqli, $row);
	$count++;
	echo '%', round($count / $num_rows * 100, 3), " Completed\n";
}

function remove_amounts($mysqli, $schedule_id) {
	$query = <<<END_SQL
DELETE FROM
	event_amount
  WHERE 
	event_schedule_id = $schedule_id
END_SQL;
	$result = $mysqli->Query($query);
}

function Retrieve_Event_Amount_Type_Map($mysqli) {
	static $map;
	
	if (empty($map)) {
		$result = $mysqli->Query("SELECT event_amount_type_id, name_short FROM event_amount_type");
		
		while ($row = $result->fetch_object()) {
			$map[$row->name_short] = $row->event_amount_type_id;
		}
	}
	
	return $map;
}

function Create_Event_Amounts($mysqli, $company_id, $application_id, $event_schedule_id, $transaction_register_id, Amount $amount, $is_reatt = false) {
	$reatt = $is_reatt ? 1 : 0;
	$query = <<<END_SQL
INSERT event_amount 
  (
  	event_schedule_id, 
	transaction_register_id,
  	event_amount_type_id, 
  	amount, 
  	application_id, 
  	num_reattempt, 
  	company_id,
  	date_created)
  VALUES(
	{$event_schedule_id},
	{$transaction_register_id},
	%d,
	%f,
	{$application_id},
	{$reatt},
	{$company_id},
	NOW()
  );
END_SQL;

	
	$amount_type_map = Retrieve_Event_Amount_Type_Map($mysqli);
	
	if ($amount->principal != 0) {
		$result = $mysqli->Query(sprintf($query, $amount_type_map['principal'], $amount->principal));
//		print_r(sprintf($query, $amount_type_map['principal'], $amount->principal));

		if (!$result) { echo $mysqli->error,"\n"; }
	}

	if ($amount->service_charges != 0) {
		$result = $mysqli->Query(sprintf($query, $amount_type_map['service_charge'], $amount->service_charges));
//		print_r(sprintf($query, $amount_type_map['service_charge'], $amount->service_charges));

		if (!$result) { echo $mysqli->error,"\n"; }
	}
	
	if ($amount->fees != 0) {
		$result = $mysqli->Query(sprintf($query, $amount_type_map['fee'], $amount->fees));
//		print_r(sprintf($query, $amount_type_map['fee'], $amount->fees));

		if (!$result) { echo $mysqli->error,"\n"; }
	}

	if ($amount->irrecoverable != 0) {
		$result = $mysqli->Query(sprintf($query, $amount_type_map['irrecoverable'], $amount->irrecoverable));
//		print_r(sprintf($query, $amount_type_map['irrecoverable'], $amount->irrecoverable));

		if (!$result) { echo $mysqli->error,"\n"; }
	}
}

function Fix_Event_Schedule_Amount($mysqli, $event_schedule_id, $principal, $non_principal) {
	$query = <<<END_SQL
UPDATE event_schedule
  SET
	amount_principal = %f ,
	amount_non_principal = %f
  WHERE
	event_schedule_id = %d;
END_SQL;

	$mysqli->Query(sprintf($query, $principal, $non_principal, $event_schedule_id));
}

function Main($mysqli, $event) {
	try {
		$p_amount = new Amount($event->amount_principal, 0, 0);
		$sc_amount = new Amount(0, $event->amount_non_principal, 0);
//		$p_amount = new Amount(0, 0, 0, $event->amount_principal);
//		$sc_amount = new Amount(0, 0, 0, $event->amount_non_principal);
		
		$ptrid = intval($event->principal_transaction_register_id);
		$nptrid = intval($event->non_principal_transaction_register_id);
		
		if ((!$ptrid) || (!$nptrid)) {
			$ptrid = max($ptrid, $nptrid);
			$nptrid = max($ptrid, $nptrid);
		}
		
		remove_amounts($mysqli, $event->event_schedule_id);
		Create_Event_Amounts($mysqli, $event->company_id, $event->application_id, $event->event_schedule_id, 
				$ptrid, $p_amount);
		Create_Event_Amounts($mysqli, $event->company_id, $event->application_id, $event->event_schedule_id, 
				$nptrid, $sc_amount);
				
		Fix_Event_Schedule_Amount($mysqli, $event->event_schedule_id, $event->amount_principal, $event->amount_non_principal);
		$mysqli->commit();
	} catch (Exception $e) {
		echo "ERROR: " . $e->getMessage();
		$mysqli->Rollback();
	}
}

class Amount {
	public $principal;
	public $service_charges;
	public $fees;
	public $irrecoverable;
	
	public function __toString() {
		$principal = str_pad(number_format($this->principal, 2), 10, ' ', STR_PAD_RIGHT);
		$service_charges = str_pad(number_format($this->service_charges, 2), 10, ' ', STR_PAD_RIGHT);
		$fees = str_pad(number_format($this->fees, 2), 10, ' ', STR_PAD_RIGHT);
		$irrecoverable = str_pad(number_format($this->irrecoverable, 2), 10, ' ', STR_PAD_RIGHT);
		return "Principal: \${$principal} Service Charge: \${$service_charges} Fee: \${$fees} Irrecoverable: \${$irrecoverable}";
	}
	
	public function __construct ($principal, $service_charges, $fees, $irrecoverable = 0) {
		$this->principal = $principal;
		$this->service_charges = $service_charges;
		$this->fees = $fees;
		$this->irrecoverable = $irrecoverable;
	}
	
	public function add(Amount $amount) {
		$this->principal += $amount->principal;
		$this->service_charges += $amount->service_charges;
		$this->fees += $amount->fees;
		$this->irrecoverable += $amount->irrecoverable;
	}
	
	public function subtract(Amount $amount) {
		$this->principal -= $amount->principal;
		$this->service_charges -= $amount->service_charges;
		$this->fees -= $amount->fees;
		$this->irrecoverable -= $amount->irrecoverable;
	}
	
	static public function max(Amount $amount1, Amount $amount2) {
		$principal = max($amount1->principal, $amount2->principal);
		$service_charges = max($amount1->service_charges, $amount2->service_charges);
		$fees = max($amount1->fees, $amount2->fees);
		$irrecoverable = max($amount1->irrecoverable, $amount2->irrecoverable);
		
		return new Amount($principal, $service_charges, $fees, $irrecoverable);
	}
	
	static public function abs(Amount $amount1) {
		return new Amount(abs($amount1->principal), abs($amount1->service_charges), abs($amount1->fees), abs($amount1->irrecoverable));
	}
}

?>
