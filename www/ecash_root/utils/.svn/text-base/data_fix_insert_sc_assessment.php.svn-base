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
	
	$application_id = 902221485;
	$name_short = 'assess_service_chg';
	$comment = 'Interest';
	$amount = 120;		// - for DEBITS --- CREDITS +++++ !!!!!!!!!
	$tomorrow = $today = date("Y-m-d");
	$date_created = date("Y-m-d H:i:s");
	//$today = '2013-08-28';
	//$tomorrow = '2013-08-29';
	//$date_created = '2013-08-28 09:00:00';


	$query = 
	"
		select
			event_type_id
		from
			event_type
		where
			company_id = {$company_id}
		and
			name_short = '{$name_short}'
		and
			active_status = 'active';
	";
	$result = $db->Query($query);
	$event_type_id = intval($result->fetch(PDO::FETCH_OBJ)->event_type_id);
	
	// Plase ammount to correct place !!!!!!!!!!!
	$bwo_balance = array(
				'principal' => 0,
				'service_charge' => $amount,
				'fee' => 0,
				'irrecoverable' => 0
			    );

	$amounts = array();
	$amounts[] = Event_Amount::MakeEventAmount('principal', $bwo_balance['principal']);
	$amounts[] = Event_Amount::MakeEventAmount('service_charge', $bwo_balance['service_charge']);
	$amounts[] = Event_Amount::MakeEventAmount('fee', $bwo_balance['fee']);
	$amounts[] = Event_Amount::MakeEventAmount('irrecoverable', $bwo_balance['irrecoverable']);
	//'scheduled' OR 'registered' !!!!!!!!
	$e = Schedule_Event::MakeEvent($today, $tomorrow, $amounts, $name_short, $comment,'registered');
	$e = Schedule_Event::MakeEvent($today, $tomorrow, $amounts, $name_short, $comment,'scheduled');
	
	//SELECT ONE:
	//IF SCHEDULED !!!
	//Record_Event($application_id, $e);
	//IF COMPLETED !!!	
	//Post_Event($application_id, $e);
	Record_Event($application_id, $e);


	$query = 
	"
		select
			event_schedule_id
		from
			event_schedule
		where
			company_id = {$company_id}
		and
			application_id = {$application_id}
		and
			event_type_id = {$event_type_id}
		order by
			date_modified desc
		limit 1
	";
	$result = $db->Query($query);
	$event_schedule_id = intval($result->fetch(PDO::FETCH_OBJ)->event_schedule_id);

	$query = 
	"
		select
			transaction_register_id
		from
			transaction_register
		where
			company_id = {$company_id}
		and
			application_id = {$application_id}
		and
			event_schedule_id = {$event_schedule_id}
	";
	$result = $db->Query($query);
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$transaction_register_id = $row->transaction_register_id;

		$query1 = 
		"
			update
				transaction_history
			set
				date_created = '{$date_created}'
			where
				transaction_register_id = {$transaction_register_id}
		";
		$db->Query($query1);

		$query1 = 
		"
			update
				transaction_ledger
			set
				date_created = '{$date_created}'
			where
				transaction_register_id = {$transaction_register_id}
		";
		$db->Query($query1);

		$query1 = 
		"
			update
				transaction_register
			set
				date_created = '{$date_created}'
			where
				transaction_register_id = {$transaction_register_id}
		";
		$db->Query($query1);
	}

	$query = 
	"
		update
			event_amount
		set
			date_created = '{$date_created}'
		where
			event_schedule_id = {$event_schedule_id}
	";
	$db->Query($query);

	$query = 
	"
		update
			event_schedule
		set
			date_created = '{$date_created}'
		where
			event_schedule_id = {$event_schedule_id}
	";
	$db->Query($query);
?>
