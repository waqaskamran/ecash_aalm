<?php

// Usage:
// php data_fix_insert_sc_assessment.php 

// update transaction_register set amount=0.00 where transaction_register_id=;
// update event_amount set amount=0.00 where transaction_register_id=;

putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR.'scheduling.func.php');
require_once CUSTOMER_LIB . 'failure_dfa.php';
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');


if ($_SERVER['argc'] > 1)
{
	echo "Usage: php data_fix_insert.php\n";
	exit(1);
}
        $company_id = 1;

	$db = ECash::getMasterDb();

	$query = "
	SELECT DISTINCT ap.application_id
	FROM application AS ap
	WHERE ap.application_status_id IN (123,137,138,132,134,190,111)
	-- AND ap.application_id=901660614
	AND NOT EXISTS
	(SELECT tr.transaction_register_id FROM transaction_register AS tr
	JOIN transaction_type AS tt ON (tt.company_id=1 AND tt.transaction_type_id = tr.transaction_type_id)
	WHERE tr.application_id = ap.application_id
	AND tt.name_short IN ('assess_fee_ach_fail','assess_fee_card_fail'))

	AND EXISTS
	(SELECT tr.transaction_register_id FROM transaction_register AS tr
	JOIN transaction_type AS tt ON (tt.company_id=1 AND tt.transaction_type_id = tr.transaction_type_id)
	WHERE tr.application_id = ap.application_id
	AND tr.transaction_status = 'failed'
	AND tr.amount < 0
	AND tt.clearing_type IN ('ach','card'))
	";

	$result = $db->Query($query);
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$application_id = intval($row->application_id);
		echo $application_id, "\n";

	if (isCardSchedule($application_id))
	{
		$name_short = 'assess_fee_card_fail';
		$comment = 'Card Fee';
	}
	else
	{
		$name_short = 'assess_fee_ach_fail';
		$comment = 'ACH Fee';
	}

	$amount = 20;		// - for DEBITS --- CREDITS +++++ !!!!!!!!!
	$tomorrow = $today = date("Y-m-d");
	$date_created = date("Y-m-d H:i:s");
	//$today = '2013-08-28';
	//$tomorrow = '2013-08-29';
	//$date_created = '2013-08-28 09:00:00';


	// Plase ammount to correct place !!!!!!!!!!!
	$bwo_balance = array(
				'principal' => 0,
				'service_charge' => 0,
				'fee' => $amount,
				'irrecoverable' => 0
			    );

	$amounts = array();
	$amounts[] = Event_Amount::MakeEventAmount('principal', $bwo_balance['principal']);
	$amounts[] = Event_Amount::MakeEventAmount('service_charge', $bwo_balance['service_charge']);
	$amounts[] = Event_Amount::MakeEventAmount('fee', $bwo_balance['fee']);
	$amounts[] = Event_Amount::MakeEventAmount('irrecoverable', $bwo_balance['irrecoverable']);
	//'scheduled' OR 'registered' !!!!!!!!
	$e = Schedule_Event::MakeEvent($today, $tomorrow, $amounts, $name_short, $comment,'registered');
	
	//SELECT ONE:
	//IF SCHEDULED !!!
	//Record_Event($application_id, $e);
	//IF COMPLETED !!!	
	Post_Event($application_id, $e);

	Complete_Schedule($application_id);
	}

?>
