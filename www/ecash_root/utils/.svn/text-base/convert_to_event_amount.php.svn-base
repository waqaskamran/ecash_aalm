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
    application_id 
  FROM 
    application a 
  WHERE 
    EXISTS (
      SELECT 1 FROM event_schedule WHERE application_id = a.application_id LIMIT 1
    ) AND
    NOT EXISTS (
      SELECT 1 FROM event_amount WHERE application_id = a.application_id
    )
";
$result = $mysqli->query($query);

//while ($row = $result->fetch_object()) {
//	$application_id = $row->application_id;
	Main($mysqli, $application_id);
//}

function Get_Event_Schedule($mysqli, $application_id) {
	$query = <<<END_SQL
SELECT
		es.event_schedule_id, 
		es.application_id, 
		es.company_id,
		et.name_short,
		tr.transaction_status,
		tr.amount, 
		IF(tr.transaction_register_id IS NULL, 0, tr.transaction_register_id) transaction_register_id,
		es.amount_principal,
		es.amount_non_principal,
		es.origin_id,
		es.origin_group_id,
		es.date_effective,
		tt.affects_principal
	FROM event_schedule es
	LEFT JOIN event_type et USING (event_type_id)
	LEFT JOIN transaction_register tr ON es.event_schedule_id = tr.event_schedule_id
	LEFT JOIN transaction_type tt USING (transaction_type_id)
	WHERE es.application_id = {$application_id}
	ORDER BY es.date_effective ASC
END_SQL;

	$result = $mysqli->Query($query);
	
	$events = array();
	while ($row = $result->fetch_object()) {
		$events[] = $row;
	}
	
	return $events;
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

function Main($mysqli, $application_id = null) {
	
	if (!$application_id) {
		echo "Enter Application ID: ";
		$application_id = trim(fgets(STDIN));
	}
	
	echo "Retrieving Event_Schedule...\n";
	$events = Get_Event_Schedule($mysqli, $application_id);
	echo "Event_Schedule Retrieved...\n";
	
	$running_balance = new Amount(0, 0, 0);
	$reattempt_balance = new Amount(0, 0, 0);
	foreach ($events as $event) {
		try {
			echo "Adding amounts to Event {$event->event_schedule_id}...\n";
			$original_amount_principal = $event->amount_principal;
			$original_amount_non_principal = $event->amount_non_principal;
			switch ($event->name_short) {
				case 'manual_payment':
				case 'full_balance':
				case 'quickcheck':
					if ($event->transaction_register_id) {
						$amount = -$event->amount;
					} else {
						$amount = -($event->amount_principal + $event->amount_non_principal);
					}
					if ($running_balance->fees > 0) {
						$fees = min($amount, $running_balance->fees);
						$amount -= $fees;
					}
					
					if ($running_balance->service_charges > 0) {
						$service_charges = min($amount, $running_balance->service_charges);
						$amount -= $service_charges;
					}

					if ($running_balance->principal > 0) {
						$principal = min($amount, $running_balance->principal);
						$amount -= $principal;
					}
					
					if ($amount > 0) {
						$service_charges += $amount;
					}

					$fees *= -1;
					$service_charges *= -1;
					$principal *= -1;
					
					$amount = new Amount($principal, $service_charges, $fees);
					$reattempt_amount = Amount::max($amount, $reattempt_balance);
					$non_reattempt_amount = clone $amount;
					$non_reattempt_amount->subtract($reattempt_amount);
					break;
				case 'assess_fee_ach_fail':
				case 'payment_fee_ach_fail':
				case 'writeoff_fee_ach_fail':
                                        if ($event->transaction_register_id) {
						if ($event->affects_principal == 'no') {
							$service_charges = 0;
							$fees = $event->amount;
							$principal = 0;
						} else {
							$service_charges = 0;
							$fees = 0;
							$principal = $event->amount;
						}
                                        } else {
                                                $service_charges = 0;
						$fees = $event->amount_non_principal;
						$principal = $event->amount_principal;
                                        }
					$amount = new Amount($principal, $service_charges, $fees);
					if ($event->origin_id == "") {
						$non_reattempt_amount = $amount;
						$reattempt_amount = new Amount(0, 0, 0);
					} else {
						$non_reattempt_amount = new Amount(0, 0, 0);
						$reattempt_amount = $amount;
					}
					break;
				case 'refund_3rd_party':
					if ($event->transaction_register_id) {
						$irrecoverable = $event->amount;
					} else {
						$irrecoverable = $event->amount_principal + $event->amount_non_principal;
					}
					$amount = $non_reattempt_amount = new Amount(0, 0, 0, $irrecoverable);
					$reattempt_amount = new Amount(0, 0, 0);
					break;
				default:
					if ($event->transaction_register_id) {
						if ($event->affects_principal == 'no') {
							$service_charges = $event->amount;
							$principal = 0;
						} else {
							$principal = $event->amount;
							$service_charges = 0;
						}
					} else {
						$service_charges = $event->amount_non_principal;
						$principal = $event->amount_principal;
					}
					$fees = 0;
					$amount = new Amount($principal, $service_charges, $fees);
					if ($event->origin_id == "") {
						$non_reattempt_amount = $amount;
						$reattempt_amount = new Amount(0, 0, 0);
					} else {
						$non_reattempt_amount = new Amount(0, 0, 0);
						$reattempt_amount = $amount;
					}
			}
					
			if ($event->transaction_status == 'complete') {
				$running_balance->add($amount);
				$reattempt_balance->subtract($reattempt_amount);
			} 
			
			if ($event->transaction_status == 'failed' && $event->origin_id == '') {
				$reattempt_balance->add($amount);
			}
			
			Create_Event_Amounts($mysqli, $event->company_id, $event->application_id, $event->event_schedule_id, $event->transaction_register_id, $non_reattempt_amount);
			Create_Event_Amounts($mysqli, $event->company_id, $event->application_id, $event->event_schedule_id, $event->transaction_register_id, $reattempt_amount, true);
			Fix_Event_Schedule_Amount($mysqli, $event->event_schedule_id, $original_amount_principal, $original_amount_non_principal);
			$mysqli->commit();
			echo "Amounts added to Event {$event->event_schedule_id}\n";
			echo "[Original]  Principal: \${$original_amount_principal} \t   Non-Principal: \${$original_amount_non_principal}\n\n";
			echo "[NEW]       ", $non_reattempt_amount, "\n";
			echo "[REATTEMPT] ", $reattempt_amount, "\n";
			echo "\n";
			echo "[BALANCE]   ", $running_balance, "\n";
			echo "[R-BALANCE] ", $reattempt_balance, "\n\n";
			echo str_repeat('-----', 16), "\n\n";

		} catch (Exception $e) {
			echo "ERROR: " . $e->getMessage();
			$mysqli->Rollback();
		}
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
