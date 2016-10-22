<?php
/**
php data_fix_insert_sc_payment.php
*/
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once "../www/config.php";
require_once(LIB_DIR."common_functions.php");
require_once(SQL_LIB_DIR.'util.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(CUSTOMER_LIB."failure_dfa.php");
require_once(SERVER_CODE_DIR . 'comment.class.php');
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");

$db = ECash::getMasterDb();
$company_id = 1;
$agent_id = 1;
$server = ECash::getServer();
$server->company_id = $company_id;

echo "Started...\n";

$holidays = Fetch_Holiday_List();
$pdc = new Pay_Date_Calc_3($holidays);


//CHANGE
$application_id = ;
$ach_id = ;
$amount = -75.00;
//$today = "2013-07-30";
$today = date("Y-m-d");;
/////////////
$tomorrow = $pdc->Get_Next_Business_Day($today);
$date_created = $today . " 09:00:00";

$name_short = 'payment_service_chg';
$comment = 'Interest Payment';

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
$e = Schedule_Event::MakeEvent($today, $tomorrow, $amounts, $name_short, $comment,'registered');
Post_Event($application_id, $e);
	
$query40 = "
SELECT transaction_register_id
FROM transaction_register
WHERE
application_id={$application_id}
AND transaction_type_id=3 -- SC
ORDER BY transaction_register_id DESC
LIMIT 1
";
$result40 = $db->query($query40);
$row40 = $result40->fetch(PDO::FETCH_OBJ);
$transaction_register_id = $row40->transaction_register_id;

$query41 = "
SELECT event_schedule_id
FROM event_schedule
WHERE application_id={$application_id}
AND event_type_id=3 -- SC
ORDER BY event_schedule_id DESC
LIMIT 1
";
$result41 = $db->query($query41);
$row41 = $result41->fetch(PDO::FETCH_OBJ);
$event_schedule_id = $row41->event_schedule_id;

$query42 = 
"
update
transaction_ledger
set
date_created = '{$date_created}',
date_modified = '{$date_created}',
date_posted = '{$today}'
where
transaction_register_id = {$transaction_register_id}
";
$db->query($query42);
		

$query42 = "
UPDATE transaction_register
SET
date_created = '{$date_created}',
date_modified = '{$date_created}',
-- source_id=2,
date_effective='{$tomorrow}',
ach_id={$ach_id}
WHERE transaction_register_id={$transaction_register_id}
";
$db->query($query42);

$query42 = 
"
update
transaction_history
set
date_created = '{$date_created}'
where
transaction_register_id = {$transaction_register_id}
";
$db->query($query42);
				
$query42 = 
"
update
event_amount
set
date_created = '{$date_created}',
date_modified = '{$date_created}'
where
event_schedule_id = {$event_schedule_id}
";
$db->query($query42);

$query42 = 
"
update
event_schedule
set
date_created = '{$date_created}',
date_modified = '{$date_created}'
where
event_schedule_id = {$event_schedule_id}
";
$db->query($query42);

////////////////////////////////////////////////////ASSESSMENT
$name_short = 'assess_service_chg';
$comment = 'Interest - Restored';

$application = ECash::getApplicationById($application_id);
$rate_calc = $application->getRateCalculator();
$rate_percent = $rate_calc->getPercent();

$schedule = Fetch_Schedule($application_id);
$status = Analyze_Schedule($schedule,false);

$assesment_amount = ($status->posted_and_pending_principal) * $rate_percent / 100;

$bwo_balance = array(
'principal' => 0,
'service_charge' => $assesment_amount,
'fee' => 0,
'irrecoverable' => 0
);

$amounts = array();
$amounts[] = Event_Amount::MakeEventAmount('principal', $bwo_balance['principal']);
$amounts[] = Event_Amount::MakeEventAmount('service_charge', $bwo_balance['service_charge']);
$amounts[] = Event_Amount::MakeEventAmount('fee', $bwo_balance['fee']);
$amounts[] = Event_Amount::MakeEventAmount('irrecoverable', $bwo_balance['irrecoverable']);
$e = Schedule_Event::MakeEvent($today, $today, $amounts, $name_short, $comment,'registered');
Post_Event($application_id, $e);
	
$query40 = "
SELECT transaction_register_id
FROM transaction_register
WHERE
application_id={$application_id}
AND transaction_type_id=4 -- Assess
ORDER BY transaction_register_id DESC
LIMIT 1
";
$result40 = $db->query($query40);
$row40 = $result40->fetch(PDO::FETCH_OBJ);
$transaction_register_id = $row40->transaction_register_id;

$query41 = "
SELECT event_schedule_id
FROM event_schedule
WHERE application_id={$application_id}
AND event_type_id=4 -- Assess
ORDER BY event_schedule_id DESC
LIMIT 1
";
$result41 = $db->query($query41);
$row41 = $result41->fetch(PDO::FETCH_OBJ);
$event_schedule_id = $row41->event_schedule_id;

$query42 = 
"
update
transaction_ledger
set
date_created = '{$date_created}',
date_modified = '{$date_created}',
date_posted = '{$today}'
where
transaction_register_id = {$transaction_register_id}
";
$db->query($query42);		

$query42 = "
UPDATE transaction_register
SET
date_created = '{$date_created}',
date_modified = '{$date_created}',
-- source_id=2,
date_effective='{$today}'
WHERE transaction_register_id={$transaction_register_id}
";
$db->query($query42);

$query42 = 
"
update
transaction_history
set
date_created = '{$date_created}'
where
transaction_register_id = {$transaction_register_id}
";
$db->query($query42);
				
$query42 = 
"
update
event_amount
set
date_created = '{$date_created}',
date_modified = '{$date_created}'
where
event_schedule_id = {$event_schedule_id}
";
$db->query($query42);

$query42 = 
"
update
event_schedule
set
date_created = '{$date_created}',
date_modified = '{$date_created}'
where
event_schedule_id = {$event_schedule_id}
";
$db->query($query42);

?>
