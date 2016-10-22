<?php

require_once(LIB_DIR . 'common_functions.php');
require_once(SERVER_CODE_DIR . 	"schedule_event.class.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(SQL_LIB_DIR . "fetch_ach_return_code_map.func.php");
require_once(SQL_LIB_DIR . "fetch_card_return_code_map.func.php");
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");
require_once(LIB_DIR . "AmountAllocationCalculator.php");
require_once(LIB_DIR . "PaymentArrangementException.php");
require_once(SQL_LIB_DIR . 'agent_affiliation.func.php');
require_once(ECASH_COMMON_DIR . "ecash_api/interest_calculator.class.php");

/**
 * Used to set an Internal Adjustment
 *
 * This function attempts to properly allocate the requested adjustment amount
 * the the proper balances if at all possible.
 *
 * @param integer	$application_id
 * @param array 	$request
 * @param integer 	$agent_id
 */
function Set_Adjustment($application_id, $request, $agent_id)
{
	$db = ECash::getMasterDb();
	$events = array();

	$log = get_log('scheduling');

	$date = date("Y-m-d", strtotime($request->adjustment_date));

	$log->Write("[Agent:{$agent_id}][AppID:$application_id] Adjustment for ${$amount} {$request->adjustment_type} to {$request->adjustment_target}");

	$balance_info = Fetch_Balance_Information($application_id);
	$balance = array(
		'principal' => $balance_info->principal_balance,
		'service_charge' => $balance_info->service_charge_balance,
		'fee' => $balance_info->fee_balance,
	);

	//OK, before we do the adjustment and stuff, let's create the interest first!

	$biz_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());

	if(! $application = ECash::getApplicationById($application_id))
	{
		throw new Exception("Cannot locate application $application_id");
	}
	$renewal =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
	$rules = $application->getBusinessRules();
	$schedule = Fetch_Schedule($application_id);
	$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
	$ecash_api = eCash_API_2::Get_eCash_API(null, ECash::getMasterDb(), $application_id);
	$service_charge_type = $rules['service_charge']['svc_charge_type'];

	switch ($service_charge_type)
	{
		case 'Daily':

			$rate_calc = $application->getRateCalculator();
			$int = $rate_calc->calculateCharge($balance['principal'], $paid_to['date'], $date);

			// Don't accrue past default date
			if ($rules['loan_type_model'] == 'CSO')
			{
				if ($renewal->hasDefaulted($application_id))
					$int = 0.00;
			}

			$rb = $paid_to['date'];

			$re = $date;

			//If its not a manual payment, or the date is in the future, let's add an interest comment
			if ($int > 0 && ((strtotime($paid_to['date']) < strtotime($date))))
			{
				$comment .= "[service charge includes interest of $int between $rb and $re]";
				$intcomment = "[interest between $rb and $re]";
			}
			//log the current balances before we modify the balances.
			$log->Write("Balances - P: {$balance['principal']} F: {$balance['fee']} INT: {$int} SC: {$balance['service_charge']} | AMT: $amt");

			//add interest to what is owed if the date is in the future Subtract amount from total balance
			if(((strtotime($paid_to['date']) < strtotime($date))))
			{
				$balance['service_charge'] = bcadd($balance['service_charge'],$int,2);
			}

			//allocate amounts! Amount allocation comes AFTER the adjustments are made
			$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$amt, $balance);

			break;
		case 'Fixed':
		default:
			$int = 0;
			//Do nothing!  BOO-YAH!
		break;
	}
	if($int > 0)
	{
		$intamounts = Array(Event_Amount::MakeEventAmount('service_charge', abs($int)));
		//Schedule service charge and register it on the same day!
		$sc_event = Schedule_Event::MakeEvent($date, $date, $intamounts, 'assess_service_chg', $intcomment, 'scheduled');
		$events[] = $sc_event;
	}
	////////////////////////
	$amount = $request->adjustment_type == 'credit' ? $request->adjustment_amount : -$request->adjustment_amount;

	// Adjust Principal


	$com = $request->adjustment_description . " (Agent: {$agent_id})";
	$amounts = array();

    // GF 8293 [benb]
    // when adjusting delivery fees, apply calculations to generic "fee"
    $target = ($request->adjustment_target == "deliveryfee") ? "fee" : $request->adjustment_target;

    // GF 6334 [benb]
    // when adjusting lien fees, apply calculations to generic "fee"
	$target = ($request->adjustment_target == "lienfee") ? "fee" : $target;

    $amounts = AmountAllocationCalculator::generateInternalAdjustment($target, round($amount, 2), $balance);

	if (count($amounts))
	{
		// GF 6334 [benb]
		if ($request->adjustment_target == "lienfee")
		{
			// This is so credits will register under "assess_fee_lien"
			$type = ($request->adjustment_type == "debit") ? "writeoff_fee_lien" : "assess_fee_lien";
		}
		else if ($request->adjustment_target == "deliveryfee")
		{
			$type = ($request->adjustment_type == "debit") ? "writeoff_fee_delivery" : "assess_fee_delivery";
		}
		else
		{
			$type = "adjustment_internal";
		}

		$event = Schedule_Event::MakeEvent($date, $date, $amounts, $type, $com, 'scheduled', 'manual');
		$events[] = $event;

		try
		{
			$db->beginTransaction();
			foreach ($events as $ev)
			{

				// Propagate to the event_schedule
				$ev_id = Record_Event($application_id, $ev);
				// If the event is set for "today", propagate it the reset of the way through.
				$today = time();

				if (strtotime($date) <= $today)
				{
					// Now propagate to the register
					$tr_ids = Record_Current_Scheduled_Events_To_Register($date, $application_id, $ev_id);
					// Finally to the ledger
					foreach ($tr_ids as $trid)
					{
						Post_Transaction($application_id, $trid);
					}
				}
			}
			$db->Commit();
		}
		catch (Exception $e)
		{
			$db->rollBack();
			throw $e;
		}
	}
}

function Merge_Schedules($original, $appends, $keep_assessments=false)
{
	$new_schedule = array();
	foreach($original as $event)
	{
		if($keep_assessments)
		{
			// If the event is scheduled and the fee amount isn't negative, it's a payment and
			// we want to get rid of it.  If it is negative, it's and assessment/arrangement
			// and we want to keep it.
		if (($event->status == 'scheduled') && ($event->event_name != "Interest")) continue;
			$new_schedule[] = $event;
		}
		else
		{
			if ($event->status == 'scheduled') break;
			$new_schedule[] = $event;
		}
	}
	$new_schedule = array_merge($new_schedule, $appends);
	return ($new_schedule);
}

function Application_Has_Discount($application_id)
{
	$schedule = Fetch_Schedule($application_id);

	foreach ($schedule as $e)
	{
		if ($e->comment == 'Discount adjustment' && $e->status == 'complete')
			return TRUE;
	}

	return FALSE;
}

function Set_Completed_Payments($application_id, $schedule, $structure, $module, $status)
{
	$log = ECash::getLog('scheduling');
	$db = ECash::getMasterDb();

	$application = ECash::getApplicationById($application_id);
	$rules = $application->getBusinessRules();

	if(isset($rules['check_payment_type']))
	{
		$check_type = $rules['check_payment_type'];
	}
	else
	{
		$check_type = 'ACH';
	}

	$additions = Set_Scheduled_Payments($schedule, $structure, $module, $status, false);
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] additional payments to complete:".print_r($additions,true));
	$db->beginTransaction();
	try
	{
		$new_evids = array();
		foreach($additions as $event)
		{
			$evid = Record_Event($application_id, $event);
			$new_evids[] = $evid;

			// If it happens to be a Personal Check then we don't want
			// to register or post the event because it's an ACH item.
			if($event->type === 'personal_check' && $check_type == 'ACH')
			{
				continue;
			}

			// If the date is marked for the future, skip recording it to the register.
			if(strtotime($event->date_event) > strtotime(date('Y-m-d')))
			continue;

			$trids = Record_Current_Scheduled_Events_To_Register($event->date_event,
			$application_id,
			$evid);
			foreach ($trids as $trid)
			{
				Post_Transaction($application_id, $trid);
			}
		}

		$db->commit();
	}
	catch (Exception $e)
	{
		$db->rollBack();
		throw $e;
	}

	return($new_evids);
}

function Post_DebtConsolidation_Payment($application_id, $event_schedule_id, $adjust_amount)
{
	$log = ECash::getLog('scheduling');
	$log->Write("[Application ID:{$application_id}][Schedule ID:{$event_schedule_id}] Posting Debt Consolidation Payment for {$adjust_amount}");

	if ($adjust_amount != 0)
	{
		/**
		 * Adjust out the amount from the bottom of the schedule. To ensure the end
		 * balance will be 0.
		 */
		$schedule = Fetch_Scheduled_DebtConsolidation_Payments($application_id);

		if ($adjust_amount > 0)
		{
			/**
			 * Get the last payment that is NOT the one being posted
			 */
			do
			{
				$event = array_pop($schedule);
				if ($event_schedule_id == $event->event_schedule_id)
				{
					$event_to_post = $event;
					continue;
				}

				if (empty($balance_event))
				{
					$balance_event = $event;
				}
			} while ($event);

			//Determine the amount to the transaction that is increasing.
			$adjust_by = $adjust_amount;
			$balance_info = array('fee' => 0, 'service_charge' => 0, 'principal' => 0);

			foreach (array('fee', 'service_charge', 'principal') as $type)
			{
				if ($adjust_by > 0)
				{
					$amount = min(-$event_to_post->{$type}, $adjust_by);
					$adjust_by = bcsub($adjust_by,$amount,2);
					$balance_info[$type] = bcsub($balance_info[$type],$amount,2);
					if ($amount > 0) Record_Event_Amount($application_id, $event_schedule_id, $type, $amount);
				}
			}

			//Create a new event there isn't one that can be adjusted to balance.
			if (!$balance_event)
			{
				$info = new stdClass();
				$info->direct_deposit = false;
				$info->day_int_one = date("j",strtotime($event_to_post->date_event));
				$info->paydate_model = "dm";

				$pd_calc = new Pay_Date_Calc_3(Fetch_Holiday_List());
				$dates = $pd_calc->Calculate_Pay_Dates("dm",$info,TRUE,1, $event_to_post->date_event);
				$date_effective = $pd_calc->Get_Next_Business_Day($dates[0]);

				$balance_event = Schedule_Event::MakeEvent($dates[0], $date_effective, array(), 'payment_debt', "Event created from debt consolidation posting", 'scheduled', 'arrangement', $event_to_post->debt_consolidation_company_id);

				$balance_event->event_schedule_id = Record_Event($application_id, $balance_event);
			}

			//And finally add the amounts to the the balancing transaction.
			foreach (array('fee', 'service_charge', 'principal') as $type)
			{
				if ($balance_info[$type] < 0)
				{
					Record_Event_Amount($balance_event->application_id, $balance_event->event_schedule_id, $type, $balance_info[$type]);
				}
			}
		}
		else
		{
			$adjust_by = -$adjust_amount;
			$balance_info = array('fee' => 0, 'service_charge' => 0, 'principal' => 0);
			while (count($schedule) && $adjust_by >= 0)
			{
				$event = array_pop($schedule);

				if ($event_schedule_id == $event->event_schedule_id)
				{
					continue;
				}

				$event_amount = $event->fee + $event->service_charge + $event->principal;
				$amount_adjusted = 0;
				foreach (array('fee', 'service_charge', 'principal') as $type)
				{
					if ($adjust_by > 0)
					{
						$amount = min(-$event->{$type}, $adjust_by);
						$adjust_by = bcsub($adjust_by,$amount,2);
						$balance_info[$type] = bcsub($balance_info[$type],$amount,2);
						$amount_adjusted = bcadd($amount_adjusted,$amount,2);
						Record_Event_Amount($event->application_id, $event->event_schedule_id, $type, $amount);
					}
				}

				if ($amount_adjusted >= -$event_amount)
				{
					Remove_One_Unregistered_Event_From_Schedule($application_id, $event->event_schedule_id);
				}
			}

			foreach (array('fee', 'service_charge', 'principal') as $type)
			{
				if ($balance_info[$type] < 0)
				{
					Record_Event_Amount($application_id, $event_schedule_id, $type, $balance_info[$type]);
				}
			}
			if (array_sum($balance_info) != $adjust_amount)
			{
				Record_Event_Amount($application_id, $event_schedule_id, 'principal', $adjust_amount - array_sum($balance_info));
			}
		}
	}

	$trids = Record_Current_Scheduled_Events_To_Register("2037-01-01", $application_id, $event_schedule_id, 'all');
	foreach ($trids as $trid)
	{
		Post_Transaction($application_id, $trid);
	}

	return $evid;
}

function Fetch_Scheduled_DebtConsolidation_Payments($application_id)
{
	$log = get_log('scheduling');
	$log->Write("[Application ID:{$application_id}] Fetching Debt consolidation payments");

	$db = ECash::getMasterDb();

	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT
		    es.application_id,
		    es.event_schedule_id,
		    es.date_event,
		    SUM(IF(eat.name_short = 'principal', ea.amount, 0)) as principal,
		    SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) as service_charge,
		    SUM(IF(eat.name_short = 'fee', ea.amount, 0)) as fee
		  FROM
		    event_schedule es
		    JOIN event_type et USING (event_type_id)
		    JOIN event_amount ea USING (event_schedule_id)
		    JOIN event_amount_type eat USING (event_amount_type_id)
		  WHERE
		    es.event_status = 'scheduled' AND
		    et.name_short = 'payment_debt' AND
		    es.application_id = {$application_id}
		  GROUP BY es.event_schedule_id
		  ORDER BY es.date_event ASC
	";
	$result = $db->query($query);

	$events = $result->fetchAll(PDO::FETCH_OBJ);

	return $events;
}

// Breaking out of the Model and creating a function for Debt Consolidation
// This built off Set_Scheduled_Payments but half the calories [RL]
function Set_ScheduledDebt_Payments($application_id, $structure, $status)
{
	$log = get_log("scheduling");
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$fees_applied = 0.0;
	$princ_applied = 0.0;

	$new_schedule = array();

	$base = $structure->payment_type;
	$my_structure = $structure->$base;
	$payment = $my_structure->amount;
	$interest = $my_structure->final_interest_charge;
	$amt = $payment;

	$num_payments = $structure->debt_payments;

	// We know the amount that will be paid and how many times
	// So thats the total balance
	$balance_info = Fetch_Balance_Information($application_id);

	if ($my_structure->arr_incl_pend == 'y')
	{
		$balance = array(
		'principal' => $balance_info->principal_balance,
		'fee' => $balance_info->fee_balance,
		'service_charge' => $balance_info->service_charge_balance + $interest,
		);
	}
	else
	{
		$balance = array(
		'balance' => $balance_info->principal_pending,
		'fee' => $balance_info->fee_pending,
		'service_charge' => $balance_info->service_charge_pending + $interest,
		);
	}
	$total_balance = array_sum($balance);

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] In Set_ScheduledDebt_Payments: Principal: {$balance['principal']}, Interests: {$balance['service_charge']}, Fees: {$balance['fee']}, Total Balance: {$total_balance}");

	$info = new stdClass();
	$info->direct_deposit = false;
	$info->day_int_one = date("j",strtotime($my_structure->date));
	$info->paydate_model = "dm";

	// Use the paydate model to generate paydates
	$dates = $pd_calc->Calculate_Pay_Dates("dm",$info,TRUE,$num_payments,$my_structure->date);
	// We need to add the first paydate
	$paydates = array_merge(array(date("Y-m-d",strtotime($my_structure->date))),$dates);

	if ($interest > 0 && !empty($paydates))
	{
		$log->Write( __FUNCTION__  . " :: Adding Interest: ".$interest);
		$interestamounts = Array(Event_Amount::MakeEventAmount('service_charge', abs($interest)));
		$date = $paydates[0];
		$next = $pd_calc->Get_Next_Business_Day($date);
		$event = Schedule_Event::MakeEvent($date, $date, $interestamounts, 'assess_service_chg', 'Interest up to debt consolidation date', 'scheduled');
		$new_schedule[] = $event;
	}

	for($i=0; $i<count($paydates); $i++)
	{
		$amt = min($payment, $total_balance);
		$total_balance = bcsub($total_balance,$amt,2);

		$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$amt, $balance);

		$comment = "Agent-initiated Debt payment action";

		$date = $paydates[$i];

		if (count($amounts))
		{
			$next = $pd_calc->Get_Next_Business_Day($date);
			$event = Schedule_Event::MakeEvent($date, $next, $amounts, 'payment_debt', $comment,
			'scheduled', 'arrangement',$my_structure->company);
			$new_schedule[] = $event;
		}

		$last_payment = $date;

	}
	return $new_schedule;
}

/*
*  This function is what creates the new schedule entries for manual payments and arrangements.
*  We need to determine which balances to apply the payment amounts to while also taking into
*  account that we have to allow payments that put the customer into a negative amount
*  (resulting in a credit balance.)  The Agents are supposed to issue refunds for the amount
*  of the negative balance the customer overpaid.
*/
function Set_Scheduled_Payments($schedule, $structure, $module, $status, $merge=true)
{
	$log = ECash::getLog('scheduling');
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$application_id = $structure->application_id;
	$transaction_type = $structure->payment_type;

	// Context is based on the type of payments the agent
	// set up for the customer.  We use this for reporting
	// to determine if the payment is a collections payment, etc. 
	switch($transaction_type)
	{
		case 'payment_arrangement':
			$context = 'arrangement';
			break;
		case 'partial_payment':
			$context = 'partial';
			break;
		case 'next_payment_adjustment':
		case 'manual_payment':
			$context = 'manual';
			break;
		case 'ad_hoc':
			$context = 'generated';
			break;
	}
	
	if(! $application = ECash::getApplicationById($application_id))
	{
		throw new Exception("Cannot locate application $application_id");
	}

	$graces = Get_Grace_Periods();
	$fees_applied = 0.0;
	$princ_applied = 0.0;

	$new_schedule = array();

	$base = $structure->payment_type;
	$num_payments = intval($structure->$base->num);

	$renewal =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
	$balance_info = Fetch_Balance_Information($application_id);

	$balance = array(
		'principal' 		=> $balance_info->principal_pending,
		'fee' 				=> $balance_info->fee_pending,
		'service_charge' 	=> $balance_info->service_charge_pending,
	);

	$total_balance = array_sum($balance);

	$rules = $application->getBusinessRules();
	$rate_calc = $application->getRateCalculator();
	
	/**
	 * Find the last date we're paid up till.  We'll calculate from this point
	 * up till the payment date.
	 */
	$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
	$paid_to_date = $paid_to['date'];
	
	$service_charge_type = $rules['service_charge']['svc_charge_type'];
	
	if(isset($rules['check_payment_type']))
	{
		$check_type = $rules['check_payment_type'];
	}
	else
	{
		$check_type = 'ACH';
	}

	$non_ach_array = array('adjustment_internal', 'credit_card', 'moneygram', 'money_order', 'western_union');
 	if($check_type != 'ACH')
    {
		$non_ach_array[] = 'personal_check';
    }

	for ($i = 0; $i < $num_payments; $i++)
	{
		$payment = $structure->$base->rows[$i];
		$payment_amount = floatval($payment->actual_amount);
		$due_date = date("Y-m-d", strtotime($payment->date));
		$payment_type = $payment->payment_type;

		//Set the interest amount's initial value, grab it from the row.
		$interest_amount = floatval($payment->interest_amount);
		
		//Start building comments
		$agent = ECash::getAgent();
		$comment = "[Payment created by " . $agent->getFirstLastName() . "]";		
		if (($payment->desc))
		{
			$comment .= " " . $payment->desc;
		}

		/**
		 * If the payment type is non-ach, we use the same day as the due date.
		 */
		if (in_array($payment_type, $non_ach_array))
		{
			$action_date = $due_date;
		}
		else
		{
			/**
			 * We should already be doing input validation on the front-end to prevent due dates 
			 * for today or in the past, but just in case we'll try and handle it here.
			 */
			if(strtotime($due_date) > strtotime(date('Y-m-d')) 
			&& strtotime($pd_calc->Get_Last_Business_Day($due_date)) >= strtotime(date('Y-m-d')))
			{
				$action_date = $pd_calc->Get_Last_Business_Day($due_date);
			}
			else
			{
				$action_date = date('Y-m-d');
			}
		}

		//Do stuff that's specific to a service charge type
		switch ($service_charge_type)
		{
			case 'Daily':
				$interest_amount = $rate_calc->calculateCharge($balance['principal'], $paid_to_date, $due_date);

				// Don't accrue past default date
				if ($rules['loan_type_model'] == 'CSO')
				{
					if ($renewal->hasDefaulted($application_id))
						$interest_amount = 0.00;
				}

				//[#47687] dont use $structure->payment->interest_range(s) from the submit, use the calculated paid to interest date
				//If its not a manual payment, or the date is in the future, let's add an interest comment
				if ($interest_amount > 0 && ($base != 'manual_payment' || (strtotime($paid_to_date) < strtotime($due_date))))
				{
					$days_diff = Date_Util_1::dateDiff(strtotime($paid_to_date), strtotime($due_date));
					$comment .= " [Service Charge includes Interest of $interest_amount for $days_diff days ({$paid_to_date} thru {$due_date})]";
					$intcomment = "Interest accrued for $days_diff days ({$paid_to_date} thru {$due_date})";
				}
				//log the current balances before we modify the balances.
				$log->Write("Balances - P: {$balance['principal']} F: {$balance['fee']} INT: {$interest_amount} SC: {$balance['service_charge']} | AMT: $payment_amount");

				//add interest to what is owed if the date is in the future Subtract amount from total balance
				if(((strtotime($paid_to_date) < strtotime($due_date))))
				{
					$balance['service_charge'] = bcadd($balance['service_charge'], $interest_amount, 2);
					$total_balance = bcadd($total_balance, $interest_amount, 2);
					$total_balance = bcsub($total_balance, $payment_amount, 2);
				}

				//allocate amounts! Amount allocation comes AFTER the adjustments are made
				$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$payment_amount, $balance);
				break;

			case 'Fixed':
			default:
				//build fixed interest comments
				$comment .= "[Fixed Service Charge]";
				$intcomment = "[Fixed Interest Charge]";

				//Log the current balances before we modify the interest.
				$log->Write("Balances - P: {$balance['principal']} F: {$balance['fee']} INT: {$interest_amount} SC: {$balance['service_charge']} | AMT: $payment_amount");

				//allocate amounts! We're allocating amounts BEFORE the adjustments are made!
				$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$payment_amount, $balance);

				//We're not assessing a service charge on applications with fixed interest! [#31223]
				$interest_amount = 0;
				$log->Write("Fixed interest loan.  Not assessing any interest");
				break;
		}

		if (count($amounts))
		{
			if($base == 'manual_payment')
			{
				$effects_princ = false;
				foreach($amounts as $amount_balance)
				{
					if ($amount_balance->event_amount_type == 'principal' && abs($amount_balance->amount) > 0)
					{
						$effects_princ = true;
						$effected_princ_amount = abs($amount_balance->amount);
					}
				}
			}

			if ($interest_amount > 0 && ( $base != 'manual_payment' || (strtotime($paid_to_date) < strtotime($due_date))))
			{
				//Check and see if the date is the date of the next service charge
				//If it is, don't register a new charge. Complete the current one [Agean #11047]
				//If payment arrangments are redone for the same day exclude payment arrangments from this. I hate you so much, Will [#17906]
				if ($base != 'payment_arrangement' && $status->next_service_charge_date != 'N/A' && strtotime($status->next_service_charge_date) == strtotime($due_date) && strtotime($due_date) <= time())
				{
					//Complete the current service charge
					$trids = Record_Current_Scheduled_Events_To_Register($due_date, $application_id, $status->next_service_charge_id);
					foreach ($trids as $trid)
					{
						Post_Transaction($application_id, $trid);
					}
				}
				else
				{
					$log->Write( __FUNCTION__  . " :: Adding Interest: ".$interest_amount);
					$intamounts = Array(Event_Amount::MakeEventAmount('service_charge', abs($interest_amount)));
					$event = Schedule_Event::MakeEvent($action_date, $action_date, $intamounts, 'assess_service_chg', $intcomment, 'scheduled');
					$new_schedule[] = $event;
				}
			}

			$log->Write( __FUNCTION__  . " :: Adding with payment event amounts: ".print_r($amounts, true));
			//non ach
			if (in_array($payment_type, $non_ach_array))
			{
				$event = Schedule_Event::MakeEvent($action_date, $due_date, $amounts, $payment_type, $comment, 'scheduled', $context);
			}
			else //ach
			{
				$event = Schedule_Event::MakeEvent($action_date, $due_date, $amounts, $payment_type, $comment, 'scheduled', $context);
			}
			$new_schedule[] = $event;
		}

		if(strtotime($due_date) > strtotime($last_payment)) $last_payment = $due_date;

		/**
		 * Update the Paid to Date - This is where we start to calculate interest from
		 * when there is more than one payment and we're using Daily Interest		 
		 */ 
 		$paid_to_date = $due_date;
	}

	if (isset($structure->$base->discount_amount) &&
		(floatval($structure->$base->discount_amount) > 0.0))
	{
		if (($structure->$base->discount_desc))
		{
			$comment = $structure->$base->discount_desc;
		}
		else
		{
			$comment = "Discount adjustment";
		}

		$amounts = AmountAllocationCalculator::generateAmountsFromBalance($structure->$base->discount_amount, $balance);
		$new_schedule[] = Schedule_Event::MakeEvent($last_payment, $last_payment, $amounts, 'adjustment_internal', $comment, 'scheduled', 'arrangement');
	}

	if ($merge)
	{
		$new_schedule = Merge_Schedules($schedule, $new_schedule);
	}

	return $new_schedule;
}


function Append_Schedule($application_id, $schedule=null)
{
	if ($schedule == null) { throw new Exception("No schedule passed to Append_Schedule!"); }

	$db = ECash::getMasterDb();

	$evids = array();

	try
	{
		$db->beginTransaction();
		// There seems to be a lot of cases where there aren't events
		if($schedule != NULL)
		{
			foreach ($schedule as $event)
			{
				if ($event->status != 'scheduled') continue;
				$evids[] = Record_Event($application_id, $event);
			}
		}
		$db->commit();
	}
	catch (Exception $e)
	{
		$db->rollBack();
		throw $e;
	}
	return $evids;
}

function Add_Paydown($application_id, $request)
{
	$log = get_log("scheduling");
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);
	$paydown_amt = round($request->amount, 2);

	$comment = "Paydown Request - " . $request->payment_description;

	if ($request->edate == 'select')
	{
		$due_date = $request->scheduled_date?date("Y-m-d", strtotime($request->scheduled_date)):NULL;
	}
	else
	{
		$due_date = date('Y-m-d', strtotime($request->edate));
	}

	if(!$due_date)
	{
		$_SESSION['error_message'] = "No date was selected for paydown";
		return false;
	}

	$action_date = $pd_calc->Get_Business_Days_Backward($due_date, 1);

	//It's possible that this will put the action date in the past, which is bad.
	//If that happens, use the scheduled date as the action date instead. [jeffd][IMPACT #13616]
	if(strtotime($action_date) < strtotime(date('Y-m-d')))
	{
		$action_date = $due_date;
		$due_date = $pd_calc->Get_Business_Days_Forward($due_date, 1);
	}

	$amounts = array();

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Adding principal paydown of {$paydown_amt} for {$application_id} on date {$due_date}");
	$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => -$paydown_amt));

	$paydown_payment = isCardSchedule($application_id) ? 'card_paydown' : 'paydown';

	$paydown = Schedule_Event::MakeEvent($action_date, $due_date, $amounts, $paydown_payment, $comment,'scheduled','paydown');
	Record_Event($application_id, $paydown);
	
	if ($paydown_payment == 'card_paydown') alignActionDateForCard($application_id);

	return true;
}

/**
 * Based off of Add_Paydown
 *
 * @todo refactor this and Paydown, lots of shared code
 */
function Add_Manual_ACH($application_id, $request)
{
	$log = get_log("scheduling");
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);
	$payment_amt = round($request->amount, 2);

	$comment = "Manual ACH Request - " . $request->payment_description;

	if ($request->edate == 'select')
	{
		$due_date = $request->scheduled_date?date("Y-m-d", strtotime($request->scheduled_date)):NULL;
	}
	else
	{
		$due_date = date('Y-m-d', strtotime($request->edate));
	}

	if(!$due_date)
	{
		$_SESSION['error_message'] = "No date was selected for Manual ACH";
		return false;
	}

	$action_date = $pd_calc->Get_Business_Days_Backward($due_date, 1);

	//It's possible that this will put the action date in the past, which is bad.
	//If that happens, use the scheduled date as the action date instead. [jeffd][IMPACT #13616]
	if(strtotime($action_date) < strtotime(date('Y-m-d')))
	{
		$action_date = $due_date;
		$due_date = $pd_calc->Get_Business_Days_Forward($due_date, 1);
	}

	$amounts = array();
	//pay down amounts other than principal first [#27768]
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Adding Manual ACH of {$payment_amt} for {$application_id} on date {$due_date}");

	//[#46151] Adjust balance info, accounting for scheduled payments
	//between now and the manual ACH due date
	$schedule = Fetch_Schedule($application_id);
	$balance_info = Fetch_Balance_Information($application_id);
	foreach($schedule as $e)
	{
		if($e->status == 'scheduled' && strtotime($e->date_effective) < strtotime($due_date))
		{
			foreach($e->amounts as $ea)
			{
				$balance_info->{$ea->event_amount_type . '_balance'} += $ea->amount;
			}
		}
	}

	$balance = array(
		'principal' =>  $balance_info->principal_balance,
		'service_charge' => $balance_info->service_charge_balance,
		'fee' => $balance_info->fee_balance,
		);

	$amounts = AmountAllocationCalculator::generateAmountsFromBalance($payment_amt, $balance);

	$payment = Schedule_Event::MakeEvent($action_date, $due_date, $amounts, 'manual_ach', $comment,'scheduled','manual');
	Record_Event($application_id, $payment);

	return true;
}

function Set_Refund($application_id, $request, $schedule)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);
	$log = get_log("scheduling");

	$frm_principal = floatval($request->display_principal);
	$frm_service = floatval($request->display_service_charge);
	$frm_other = floatval($request->display_other);

	$log->Write("Refund request for $application_id for P: $frm_principal, S: $frm_service, O: $frm_other");

	$today = date("Y-m-d");
	$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);
	$status = Analyze_Schedule($schedule);

	$fees_out = $frm_service;
	$principal_out = $frm_principal;
	$amount = floatval($request->amount);

	if ($request->amount > $status->posted_total)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID: {$application_id}] Refund request for amount greater than outstanding loan balance approved.");
	}

   	$evids = array();
	
	// Seperate out any refund not applying to a transaction.
	if ($frm_other != 0)
	{
		$amount = bcsub($amount,$frm_other,2);
		$description = "Principal: 0 + (Interest: 0 + Other: {$frm_other}) = Amount: {$frm_other}";
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array('irrecoverable' => $frm_other));
		$e = Schedule_Event::MakeEvent($today, $next_day, $amounts, 'refund_3rd_party', $description,null,'manual');
		$evids[] = Record_Event($application_id, $e);
	}

	$description = "Principal: {$frm_principal} + (Interest: {$frm_service} + Other: 0) = Amount: $amount";
	if ($amount != 0.00)
	{
		$payment = isCardSchedule($application_id) ? 'card_refund' : 'refund';
		$amounts = array();
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'principal' => $principal_out,
		'service_charge' => $fees_out
		));
		$e = Schedule_Event::MakeEvent($today, $next_day, $amounts, $payment, $description, null, 'manual');
		$evids[] = Record_Event($application_id, $e);
	}

	//[#50149] add agent affiliation to refund so it doesn't show up as just 'N/A'
	$application = ECash::getApplicationById($application_id);
	$affiliations = $application->getAffiliations();
	$agent = ECash::getAgent();
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Creating new affiliation w/ agent ". $agent->getAgentId());
	$currentAffiliation = $affiliations->add($agent, 'manual', 'creator', null);
	$currentAffiliation->associateWithScheduledEvents($evids);	
}

function Set_Chargeback($application_id, $request, $schedule)
{
	$f 	= 	0;
	$sc =	0;
	$p 	=	0;
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$today = date("Y-m-d");
	$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);

	$amount = abs(floatval($request->amount));

	$eventidarr = is_array($request->event_id) ? $request->event_id : array($request->event_id);

	$transidarr = array();
	foreach ($eventidarr as $e)
	{
		$transidarr = array_merge($transidarr,Fetch_Transaction_IDs_For_Event($e));
	}

	$transaction_found = false;

	$amounts = array();
	$balance = array(
	'principal' => 0,
	'service_charge' => 0,
	'fee' => 0,
	'irrecoverable' => 0
	);
	foreach ($schedule as $i => $e)
	{
		if (in_array($e->transaction_register_id,$transidarr))
		{
			$balance['principal'] = bcadd($balance['principal'],-$schedule[$i]->principal,2);
			$balance['service_charge'] = bcadd($balance['service_charge'],-$schedule[$i]->service_charge,2);
			$balance['fee'] = bcadd($balance['fee'],-$schedule[$i]->fee,2);
			$balance['irrecoverable'] = bcadd($balance['irrecoverable'],-$schedule[$i]->irrecoverable,2);
		}
	}
	
	if (isCardSchedule($application_id))
	{
		if($request->action == "chargeback_reversal")
		{
			$payment = 'card_chargeback_reversal';
		}
		else
		{
			$payment = 'card_chargeback';
		}
	}
	else
	{
		if($request->action == "chargeback_reversal")
		{
			$payment = 'chargeback_reversal';
		}
		else
		{
			$payment = 'chargeback';
		}
	}

	$casenum = ($request->case_number) ? "(Case Number:".$request->case_number.")" : "";
	$description = "Chargeback ({$request->action}) $casenum Amount: $amount - Transaction[".implode(",",$transidarr)."]:  ";
	if($request->action == "chargeback_reversal")
	{
		//reverse sign
		$amounts = AmountAllocationCalculator::generateAmountsForAReversal($amount, $balance,'fee',true);
	}
	else
	{
		$amounts = AmountAllocationCalculator::generateAmountsForAReversal($amount, $balance);
	}

	$e = Schedule_Event::MakeEvent($today, $today, $amounts, $payment, $description, 'registered','manual'); //mantis:4337 - add 'registered','manual'
	Post_Event($application_id, $e);
}

function GetRecoveryBalanceInfo($applicationId)
{
	$query = "
		SELECT
			SUM(IF(eat.name_short = 'principal', ea.amount, 0)) principal,
			SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) service_charge,
			SUM(IF(eat.name_short = 'fee', ea.amount, 0)) fee,
			SUM(IF(eat.name_short = 'irrecoverable', ea.amount, 0)) irrecoverable
		FROM
			event_amount ea
			JOIN event_amount_type eat USING(event_amount_type_id)
			JOIN transaction_register tr USING (transaction_register_id)
			JOIN transaction_type tt USING (transaction_type_id)
		WHERE
			ea.application_id = {$applicationId} AND
			tr.transaction_status = 'complete' AND
			tt.name_short LIKE 'ext_recovery%'
	";

	$db = ECash::getMasterDb();
	$result = $db->query($query);
	return $result->fetch(PDO::FETCH_ASSOC);
}

function Set_RecoveryReversal($application_id, $request)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$today = date("Y-m-d");
	$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);
	$amount = floatval($request->amount);
	$recoveryBalance = GetRecoveryBalanceInfo($application_id);
	$amounts = array();
	$description = "Recovery Reversal ({$request->action}) Amount: $amount:  ";
	$data = Gather_App_Transactions($application_id);
	for($j=0; $j<count($request->transaction_id); $j++)
	{

		for($i=0; $i<count($data); $i++)
		{
			$th = $data[$i];

			if ($th->transaction_register_id == $request->transaction_id[$j])
			{
				if(($th->name_short == "ext_recovery_princ"))
				{
					$type = 'principal';
				}
				else
				{
					$type ='fee';
				}
				$amounts[] = AmountAllocationCalculator::generateAmountsForAReversal($th->amount, $recoveryBalance , $type);
			//	echo '<pre>'.print_r($amounts,true).'</pre>';
			}
		}
	}
	foreach ($amounts as $amt)
	{
		$e = Schedule_Event::MakeEvent($today, $next_day, $amt, $request->action, $description,'registered','manual'); //mantis:4853 - add 'registered','manual'
		Post_Event($application_id, $e);
	}

}

function Set_Payout($application_id,$date_effective = null)
{
	require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');

	$log = get_log("scheduling");

	$log->Write("Scheduling Payout for $application_id");
	$schedule = Fetch_Schedule($application_id);
	$status = Analyze_Schedule($schedule,false);
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);
	$data = Get_Transactional_Data($application_id);
	$rules = Prepare_Rules($data->rules, $data->info);
	$renewal =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
	$application = ECash::getApplicationById($application_id);
	$rate_calc = $application->getRateCalculator();
	//there should always be a date_effective, but their didn't used to be, so just in case...
	if (!$date_effective)
	{
		$action_date = Get_Next_Action_Day($application_id,$schedule);
		if ($action_date)
		{
			$date_event = $action_date;
			$date_effective = $pd_calc->Get_Next_Business_Day($date_event);
		}
		else
		{
			$pair = Get_Next_Payday(date("Y-m-d"), $data->info, $rules);
			$date_event = $pair['event'];
			$date_effective = $pair['effective'];
		}
	}
	else
	{
		$date_event = $pd_calc->Get_Last_Business_Day($date_effective,1);

		//It's possible that this will put the action date in the past, which is bad.
		//If that happens, use the scheduled date as the action date instead. [jeffd][IMPACT #13616]
		if(strtotime($date_event) < strtotime(date('Y-m-d')))
		{
			$date_event = $pd_calc->Get_Next_Business_Day($date_event);
			$date_effective = $pd_calc->Get_Next_Business_Day($date_event);
		}
	}

	$type = isCardSchedule($application_id) ? 'card_payout' : 'payout';

	// Only generate interest if they have daily interest.
	$sc_amount = 0;
	if (strtolower($rules['service_charge']['svc_charge_type']) == 'daily')
	{
		//@EXAMPLE
		$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($schedule, FALSE, $rules);
		$start_date = strtotime($paid_to['first_failure_date']) > strtotime($paid_to['date']) ? strtotime($paid_to['first_failure_date']) : strtotime($paid_to['date']);
		$sc_amount = $rate_calc->calculateCharge($paid_to['principal'], $start_date, $date_effective);

		// Don't accrue past default date
		if ($rules['loan_type_model'] == 'CSO')
		{
			if ($renewal->hasDefaulted($application_id))
				$sc_amount = 0.00;
		}
	}
	
	//asm 38
	$early_payout_qualified = FALSE;
	if (isset($data->info->last_payment_date) && !$status->has_failed_int_adj_payout)
	{
		/*
		$days_diff = Date_Util_1::dateDiff(strtotime($date_event), strtotime($data->info->last_payment_date));
		if (
			($data->info->income_frequency == 'monthly' && $days_diff <= 5)
			||
			($data->info->income_frequency != 'monthly' && $days_diff <= 3)
		)
		{
			$early_payout_qualified = TRUE;
		}
		*/

		if ($data->info->income_frequency == 'monthly')
		{
			$adjustment_span = 5;
		}
		else
		{
			$adjustment_span = 3;
		}

		$ending = $pd_calc->Get_Business_Days_Forward($data->info->last_payment_date, $adjustment_span);

		if (strtotime($ending) >= strtotime($date_event))
		{
			 $early_payout_qualified = TRUE;
		}
	}

	if ($early_payout_qualified)
	{
		$name_short = 'adjustment_internal';
		$comment = 'Internal Adjustment - Early Payout';
		$amount = -$data->info->last_assessment_amount;
		$tomorrow = $today = date("Y-m-d");
		$date_created = date("Y-m-d H:i:s");
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
		$e = Schedule_Event::MakeEvent($today, $tomorrow, $amounts, $name_short, $comment,'registered','payout');
		Post_Event($application_id, $e);

		$comments = ECash::getApplicationById($application_id)->getComments();
		$comments->add("Early Payout", ECash::getAgent()->AgentId);
	}

	// Get their current balance information
	$balance_info = Fetch_Balance_Information($application_id);

	// These are now up to date totals
	$payout['service_charge'] = $balance_info->service_charge_pending + $sc_amount;
	$payout['fee']            = $balance_info->fee_pending;
	$payout['principal']      = $balance_info->principal_pending;

	// GF #13341: Service charges should never be a credit for a payout
	// due to a couple of data fix bugs, some scenarios exist where service charge
	// will end up being a credit.
	// If service_charge_pending is below zero, it should not credit for it, it should instead
	// apply the amount needed to make the balance 0 to principal. [benb]
	$payments = AmountAllocationCalculator::generateAmountsFromBalance( $payout['service_charge'] +
																		$payout['fee']            +
																		$payout['principal'],
																		$payout);

	if (count($payments))
	{
		//Wrap the calls in a transaction to prevent double payouts on closely timed request.
		$db = ECash::getMasterDb();
		try
		{
			$db->beginTransaction();

			Remove_Unregistered_Events_From_Schedule($application_id);
			if($sc_amount)
			{
				$sc_amounts[] = Event_Amount::MakeEventAmount('service_charge', $sc_amount);
				$sc_e = Schedule_Event::MakeEvent($date_event, $date_event,
				$sc_amounts, 'assess_service_chg', 'scheduled interest charge');
				Record_Event($application_id,$sc_e);
			}

			$e = Schedule_Event::MakeEvent($date_event, $date_effective, $payments, $type,
			ucwords($type) . " Request Payout", 'scheduled','payout');

			Record_Event($application_id, $e);
			if ($type == 'card_payout') alignActionDateForCard($application_id);
			$db->commit();
		} catch (Exception $e)
		{
			$db->rollBack();
			$log = get_log('scheduling');
			$log->Write("[Agent:{$_SESSION['agent_id']}][AppID: {$application_id}] Could not save payout: {$e->getMessage()}");
			return false;
		}
	}

	return true;
}

/**
 * Returns the next action date for the given application using the given
 * schedule. If a schedule is not passed then a query is issued to get the
 * date. So to keep database load down PLEASE pass a schedule array if it
 * already exists, and if it doesn't don't create one JUST for this function.
 *
 * If no next action day exists, false is returned.
 *
 * @param int $application_id
 * @param array $schedule
 * @return string 'YYYY-MM-DD'
 */
function Get_Next_Action_Day($application_id, Array $schedule = null)
{
	if (isset($schedule) && count($schedule))
	{
		foreach ($schedule as $event)
		{
			if ($event->status == 'scheduled')
			{
				return $event->date_event;
			}
		}
	}
	else
	{
		$query =  "
			SELECT MIN(date_event) action_date
			FROM event_schedule
			WHERE application_id = {$application_id} AND event_status = 'scheduled'
		";

		$db = ECash::getMasterDb();
		$result = $db->query($query);

		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row['action_date'];
		}
	}

	return false;
}

/*
* Grabs the next valid paydate based on the model based on first_date.
* Returns: array('date_event' => date string, 'date_effective' => date string)
*/
function Get_Next_Payday($first_date, $info, $rules)
{
	// Grab a list of dates from the fund_date onward.  Remove dates prior to today
	$date_list = Get_Date_List($info, $first_date, $rules, 20);

	$date_pair = array();
	while(strtotime($date_list['event'][0]) < strtotime(date('Y-m-d')))
	{
		array_shift($date_list['event']);
		array_shift($date_list['effective']);
	}

	$date_pair['event'] = $date_list['event'][0];
	$date_pair['effective'] = $date_list['effective'][0];

	return $date_pair;
}

function Full_Balance($application_id, $status, $schedule, $info, $rules, $date_event = NULL, $date_effective = NULL)
{
	$log = get_log("scheduling");
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Executing Full Balance");

	// First thing to do is remove all the scheduled stuff
	Remove_Unregistered_Events_From_Schedule($application_id);

	$next_payday = Get_Next_Payday(date("Y-m-d"), $info, $rules);
	$comment = "Attempt to secure full balance";

	// Track the principal balance (assume it's a positive balance)
	// and include anything pending
	$balance_info = Fetch_Balance_Information($application_id);
	$balance = array(
	'principal' => -($balance_info->principal_pending > 0 ? $balance_info->principal_pending : 0),
	'service_charge' => -($balance_info->service_charge_pending > 0 ? $balance_info->service_charge_pending : 0),
	'fee' => -($balance_info->fee_pending > 0 ? $balance_info->fee_pending : 0)
	);
	$amounts = AmountAllocationCalculator::generateGivenAmounts($balance);
	if (count($amounts))
	{
		$payment = isCardSchedule($application_id) ? 'card_full_balance' : 'full_balance';
		$date_event = (NULL === $date_event ? $next_payday['event'] : $date_event);
		$date_effective = (NULL === $date_effective ? $next_payday['effective'] : $date_effective);

		$e = Schedule_Event::MakeEvent($date_event, $date_effective, $amounts, $payment, $comment);
		Record_Event($application_id, $e);
	}
}

/**
 * Used by Conversion to create a schedule
 *
 * @param int $application_id
 * @param array $request
 * @param object $tr_info
 * @return array - status chain
 */
function Create_Edited_Schedule($application_id, $request, $tr_info)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);
	$log = get_log("scheduling");

	$schedule = array();
	$status_date = (isset($request->status_date)) ?
	(date("Y-m-d", strtotime($request->status_date))) : null;
	$date_fund_actual = $request->date_fund_actual;
	$log->Write("Creating Edited Schedule for {$application_id}");
	$principal_balance = floatval($request->principal_balance);
	$fees_balance = floatval($request->fees_balance);
	$return_amount = floatval($request->return_amount);
	$num_service_charges = ($request->num_service_charges == "max") ?
	5 : intval($request->num_service_charges);

	$agent_id = isset($request->controlling_agent) ? $request->controlling_agent : 0;

	$today = date("Y-m-d");
	$next_business_day = $pd_calc->Get_Next_Business_Day($today);
	Remove_Unregistered_Events_From_Schedule($application_id);

	// First generate the service charge placeholders
	for ($i = 0; $i < $num_service_charges; $i++)
	{
		$schedule[] = Schedule_Event::MakeEvent($today, $today, array(),
		'converted_sc_event',
		'Placeholder for Cashline interest charge');
	}

	// Now generate the balance amounts.
	if ($principal_balance > 0.0)
	{
		// If they requested "Funds Pending", we want to set the transaction to pending, and set
		// the event and the transaction to the date they specified.
		if($request->account_status == '17') // Set Funds Pending
		{
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => $principal_balance));
			$event = Schedule_Event::MakeEvent($status_date, $status_date, $amounts,
			'converted_principal_bal',
			"Converted principal amount for {$application_id}");

			$evid = Record_Event($application_id, $event);
			Record_Scheduled_Event_To_Register_Pending($status_date, $application_id, $evid);
		}
		else if ($request->account_status == '15') // Funding Failed
		{
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => -$principal_balance));
			$event = Schedule_Event::MakeEvent($today, $today, $amounts,
			'converted_principal_bal',
			"Converted principal amount for {$application_id}");
			$evid = Record_Event($application_id, $event);
			$trids = Record_Scheduled_Event_To_Register_Pending($today, $application_id, $evid);
			foreach ($trids as $trid)
			{
				Record_Transaction_Failure($application_id, $trid);
			}
		}
		else
		{
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => $principal_balance));
			$schedule[] = Schedule_Event::MakeEvent($today, $today, $amounts,
			'converted_principal_bal',
			"Converted principal amount for {$application_id}");
		}
	}
	if ($fees_balance > 0.0)
	{
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => $fees_balance));
		$schedule[] = Schedule_Event::MakeEvent($today, $today, $amounts,
		'converted_service_chg_bal',
		"Converted interest charge amount for {$application_id}");
	}

	// Scheduling section
	$rules = Prepare_Rules($tr_info->rules, $tr_info->info);
	$scs_left = max($rules['service_charge']['max_svc_charge_only_pmts'] - $num_service_charges, 0);
	$sc_amt = $principal_balance * $rules['interest'];
	$sc_comment = "sched svc chg ({$rules['interest']})";
	$payments = $principal_balance / $rules['principal_payment_amount'];
	$total_payments = $rules['service_charge']['max_svc_charge_only_pmts'] + $payments + 1; //add one for the loan date

	if(!in_array($request->account_status, array("19")))
	{
		$dates = Get_Date_List($tr_info->info, $next_business_day, $rules, ($total_payments ) * 4, null, $next_business_day);

		$index = 0;
		if (isset($date_fund_actual) && ($date_fund_actual != ''))
		{
			$date_fund_actual = preg_replace("/-/", "/", $date_fund_actual);
			$log->Write("Date fund actual is: {$date_fund_actual}");
			$window = date("Y-m-d", strtotime("+10 days", strtotime($date_fund_actual)));
			while (strtotime($window) > strtotime($dates['event'][$index])) $index++;
		}
		else
		{
			while (strtotime($today) > strtotime($dates['event'][$index])) $index++;
		}
	}

	switch($request->account_status)
	{
		case "1":
		case "2": $status_chain = array("active", "servicing", "customer", "*root"); break;
		case "3": $status_chain = array("sent", "external_collections", "*root"); break;
		case "4": $status_chain = array("new", "collections", "customer", "*root"); break;
		case "5": $status_chain = array("queued", "contact", "collections", "customer", "*root"); break;
		case "6": $status_chain = array("pending", "external_collections", "*root"); break;
		case "7": $status_chain = array("unverified", "bankruptcy", "collections", "customer", "*root"); break;
		case "8": $status_chain = array("recovered", "external_collections", "*root"); break;
		case "9": $status_chain = array("paid", "customer", "*root"); break;
		case "10":
		case "12":
		case "13": $status_chain = array("ready", "quickcheck", "collections", "customer", "*root"); break;
		case "11":
		case "14": $status_chain = array("sent", "quickcheck", "collections", "customer", "*root"); break;
		case "15": $status_chain = array("funding_failed", "servicing", "customer", "*root"); break;
		case "16": $status_chain = array("queued", "contact", "collections", "customer", "*root"); break;
		case "17": $status_chain = array("active", "servicing", "customer", "*root"); break;
		case "18": $status_chain = array("past_due", "servicing", "customer", "*root"); break;
		case "19": $status_chain = array("sent","external_collections","*root"); break;
		case "20": $status_chain = array("queued", "contact", "collections", "customer", "*root"); break;
		case "21": $status_chain = array("sent", "quickcheck", "collections", "customer", "*root"); break;
	}

	if (in_array($request->account_status, array("1", "2", "4", "17","18")))
	{
		if ($request->account_status == "18")
		{
			$old_sc_amt = $sc_amt;
			$today = date("Y-m-d");
			$next_day = $pd_calc->Get_Business_Days_Forward($today, 1);
			if ($fees_balance != 0.00)
			{
				$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => -$fees_balance));
				$schedule[] = Schedule_Event::MakeEvent($today, $next_day,
				$amounts,
				'payment_service_chg', 'repull',
				'scheduled', 'generated',$application_id,
				-$application_id);
				$return_amount = bcsub($return_amount,$fees_balance,2);
			}

			if ($return_amount > 0)
			{
				$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => -$return_amount));
				$schedule[] = Schedule_Event::MakeEvent($today, $next_day,
				$amounts,
				'repayment_principal', 'repull',
				'scheduled','generated',$application_id,
				-$application_id);
				$principal_balance = bcsub($principal_balance,$return_amount,2);
				$sc_amt = $principal_balance * $rules['interest'];
			}
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => $sc_amt));
			$schedule[] = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_service_chg');

			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('fee' => $rules['return_transaction_fee']));
			$schedule[] = Schedule_Event::MakeEvent($today, $today,
			$amounts,
			'assess_fee_ach_fail', 'ACH Fee Assessed');

			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('fee' => -$rules['return_transaction_fee']));
			$schedule[] = Schedule_Event::MakeEvent($today, $next_day,
			$amounts,
			'payment_fee_ach_fail', 'ACH fee payment');

			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => -$sc_amt));
			$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index],
			$dates['effective'][$index],$amounts, 'payment_service_chg');

		}
		else
		{
			if ($fees_balance != 0.00)
			{
				$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => -$fees_balance));
				$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index], $dates['effective'][$index],
				$amounts, 'payment_service_chg', 'sched svc chg payment');
			}
		}
		for ($i = 0; $i < $scs_left; $i++)
		{
			if ($sc_amt == 0.0) break;
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => $sc_amt));
			$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index], $dates['event'][$index],
			$amounts, 'assess_service_chg', $sc_comment);
			$index++;
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => -$sc_amt));
			$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index], $dates['effective'][$index],
			$amounts, 'payment_service_chg',
			'sched svc chg payment');
		}

		while ($principal_balance > 0)
		{
			$charge_amount  = min($principal_balance, $rules['principal_payment_amount']);
			$amounts = AmountAllocationCalculator::generateGivenAmounts(array('principal' => -$charge_amount));
			$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index], $dates['effective'][$index],
			$amounts, 'repayment_principal', 'principal repayment');
			$principal_balance = bcsub($principal_balance,$charge_amount,2);
			if($principal_balance > 0)
			{
				$sc_amt = $principal_balance * $rules['interest'];
				$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => $sc_amt));
				$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index],
				$dates['event'][$index],
				$amounts, 'assess_service_chg',
				$sc_comment);
				$index++;
				$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => -$sc_amt));
				$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index],
				$dates['effective'][$index],
				$amounts, 'payment_service_chg',
				'sched svc chg payment');
			}

		}
	}
	else if ($request->account_status == "15") // for Funding Failed
	{
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'service_charge' => -$request->fees_balance
		));
		$event = Schedule_Event::MakeEvent($today, $today,
		$amounts, 'adjustment_internal',
		'Internal Adjustment');
		Post_Event($application_id, $event);
	}
	else if ($request->account_status == "21") // ACH Return After QC
	{
		if ($status_date != null) $date = $status_date;
		else $date = $today;

		// Quickcheck (Pending)
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'service_charge' => -$request->fees_balance,
		'principal' => -$request->principal_balance,
		));
		$schedule[] = Schedule_Event::MakeEvent($date, $date,
		$amounts, 'quickcheck',
		'Quickcheck');

		// Cashline Return (Completed)
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'principal' => $request->return_amount
		));
		$event = Schedule_Event::MakeEvent($today, $today,
		$amounts, 'cashline_return',
		'Cashline Return');
		Post_Event($application_id, $event);
	}
	if ($request->account_status == "5")
	{
		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'service_charge' => -$request->fees_balance,
		'principal' => -$request->principal_balance,
		));
		$schedule[] = Schedule_Event::MakeEvent($dates['event'][$index], $dates['effective'][$index],
		$amounts, 'full_balance',
		'Full Pull Attempt');
	}

	if (in_array($request->account_status, array("12", "13", "14")))
	{
		if ($status_date != null) $date = $status_date;
		else $date = $today;

		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'service_charge' => -$request->fees_balance,
		'principal' => -$request->principal_balance,
		));
		$schedule[] = Schedule_Event::MakeEvent($date, $date,
		$amounts, 'quickcheck',
		'Quickcheck');
	}

	if (in_array($request->account_status, array("14", "11")))
	{
		if ($status_date != null) $date = $status_date;
		else $date = $today;

		$amounts = AmountAllocationCalculator::generateGivenAmounts(array(
		'service_charge' => -$request->fees_balance,
		'principal' => -$request->principal_balance,
		));
		$schedule[] = Schedule_Event::MakeEvent($date, $date,
		$amounts, 'quickcheck',
		'Quickcheck');
	}

	if (in_array($request->account_status, array('6', '3', '20')))
	{
		if ($status_date != null) $date = $status_date;
		else $date = $today;

		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$request->principal_balance);
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$request->fees_balance);
		$schedule[] = Schedule_Event::MakeEvent($date, $date,
		$amounts, 'quickcheck',
		'Quickcheck');

		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$request->principal_balance);
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$request->fees_balance);
		$schedule[] = Schedule_Event::MakeEvent($date, $date,
		$amounts, 'quickcheck',
		'Quickcheck');
	}

	if(in_array($request->account_status, array("19")))
	{
		Update_Status(NULL,$application_id,$status_chain);
	}

	if (in_array($request->account_status, array(4,5,10,11,16)) && $agent_id)
	{
		$application = ECash::getApplicationById($application_id);
		$affiliations = $application->getAffiliations();
		$affiliations->add(ECash::getAgentById($agent_id), 'collections', 'owner', null);
	}

	if (count($schedule) > 0) Update_Schedule($application_id, $schedule);
	return ($status_chain);
}

/**
 * Analyzes a customer's schedule and tallies balances, fetches general scheduling information
 *
 * This function is used for grabbing information regarding the balance and status of
 * and account by looking at the schedule information.
 *
 * DENORMALIZATION:  If you are making changes in how schedules are processed, please check
 *  the /ecash_common/ecash_api/interest_calculator.php which does a subset of this analysis for interest purposes.
 *
 * @param array $schedule - Array of event objects
 * @param boolean $verify - If true, includes scheduled events when accounting balances
 * @return object many miscellaneous values
 */
function Analyze_Schedule($schedule, $verify=false, $rules=NULL)
{

	$ach_failure_map = Fetch_ACH_Return_Code_Map();
	$card_failure_map = Fetch_Card_Return_Code_Map();
	$grace_periods = Get_Grace_Periods();
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$status = new stdClass();

	// General items or totals

	// If $verify == false, this will be the position of the first scheduled event (if there is one)
	// If $verify == true, this will be the position of the last event in the schedule + 1
	$status->stopping_location = 0;
	$status->ach_fee_count = 0;
	$status->max_reattempt_count = 0;
	$status->principal_in_failures = FALSE;
	$status->has_arrangements = FALSE;
	$status->has_failed_arrangements = FALSE;
	$status->has_debt_consolidation_payments = FALSE;
	$status->has_manual_renewals = FALSE;
	$status->has_scheduled_reattempts = FALSE;
	$status->initial_principal = 0.0;
	$status->num_qc = 0;
	$status->num_ach_card_failures = 0;
	$status->num_fatal_failures = 0;
	$status->num_registered_events = 0;
	$status->num_scheduled_events = 0;
	$status->posted_service_charge_count = 0;
	$status->attempted_service_charge_count = 0;
	$status->num_reg_sc_assessments = 0;
	$status->has_failed_int_adj_payout = FALSE;

	// Client-side information
	$status->next_amt_due = 0.0;
	$status->next_due_date = "N/A";
	$status->next_action_date = "N/A";
	$status->next_principal_due_date = "N/A";
	$status->last_service_charge_date = 'N/A';
	$status->next_service_charge_date = 'N/A';
	$status->next_service_charge_id = NULL;
	$status->shift_date_range = NULL;
	$status->reversible = FALSE;
	$status->cancellable = TRUE;
	$status->can_chargeback = FALSE;
	$status->can_reverse_chargeback = FALSE;
	$status->can_renew_loan = FALSE;

	// Title Loan Fee related items
	$status->has_transfer_fee = FALSE;
	$status->has_delivery_fee = FALSE;
	$status->has_lien_fee = FALSE;

	// Pending items only *from sum_pending_*
	$status->num_pending_items = 0;
	$status->pending_principal = 0.0;
	$status->pending_fees = 0.0;
	$status->pending_interest = 0.0;
	$status->pending_total = 0.0;

	// posted stuff (i.e. only 'complete' items affect these)
	// *from balance_complete*
	$status->posted_principal = 0.0;
	$status->posted_fees = 0.0;
	$status->posted_interest = 0.0;
	$status->posted_total = 0.0;
	$status->paid_interest = 0.0;
	$status->paid_interest_count = 0;
	$status->paid_principal = 0.0;
	$status->paid_principal_count = 0;
	$status->paid_fees = 0.0;
	$status->paid_fee_count = 0;

	$status->Completed_SC_Credits = 0;
	$status->Completed_SC_Debits =0;
	$status->Failed_Princ_non_reatts = 0;
	$status->Completed_Reatts = 0;

	// Posted and pending (for use in payouts and other pending sensitive transactions)
	$status->posted_and_pending_principal = 0.0;
	$status->posted_and_pending_fees = 0.0;
	$status->posted_and_pending_interest = 0.0;
	$status->posted_and_pending_total = 0.0;
	$status->posted_service_charge_total = 0.0;

	// running balances (included everything analyzed)
	$status->running_principal = 0.0;
	$status->running_fees = 0.0;
	$status->running_interest = 0.0;
	$status->running_total = 0.0;
	$status->total_paid = 0.0;

	// Lists
	$status->quickchecks = array();
	$outstanding = array('ach' => array(), 'sc' => array());
	$fail_set = array();
	$debits = array();
	$posted_schedule = array();
	$oids = array();
	$status->failed_partials = array();
	// Internal value only
	$date_effective_init = $fail_date = '1970-01-01';

	// If there are no schedule items, you can't cancel - there's nothing to cancel - no items mean no balance too.
	if (count($schedule) == 0) $status->cancellable = false;

	// if we have only cancel type transactions, we can't cancel.  This flag is for if we see any.
	$non_cancel_trans = false;

	foreach ($schedule as $e)
	{
		/* We need to make sure we get ALL origin ids to determine
		the appropriate failset.  */
		if ($e->origin_id !== null)
		{
			$oids[] = $e->origin_id;
		}

		// if we have only cancel type transactions, we can't cancel.  Flag if we see any.
		if (!in_array($e->type, array('cancel','card_cancel'))) $non_cancel_trans = true;

		// Used to determine whether or not the schedule has any
		// scheduled events
		if($e->status === 'scheduled')
		{
			$status->num_scheduled_events++;
			if($e->type == 'assess_service_chg' && $e->service_charge != 0 && $status->next_service_charge_date == 'N/A')
			{
				$status->next_service_charge_id = $e->event_schedule_id;
				$status->next_service_charge_date = $e->date_event;
			}
		}

		if($e->status === 'scheduled' && $e->context === 'reattempt') $status->has_scheduled_reattempts = TRUE;

		if(in_array($e->type, array('repayment_principal','card_repayment_principal')) && $e->context === 'manual') $status->has_manual_renewals = TRUE;

		if($e->type === 'payment_debt' || $e->type === 'payment_debt_principal' || $e->type === 'payment_debt_fees')
		{
			$status->has_debt_consolidation_payments = TRUE;
		}

		// From this point on, we don't do scheduled items unless we're verifying.
		if (!($verify) && ($e->status === 'scheduled')) continue;
		if($e->status !== 'scheduled') $status->num_registered_events++;

		// First add it to the running balances
		if ($e->status !== 'failed')
		{
			$status->running_principal = bcadd($status->running_principal,$e->principal,2);
			$status->running_fees = bcadd($status->running_fees,$e->fee,2);
			$status->running_interest = bcadd($status->running_interest,$e->service_charge,2);
		}

		if($e->type === 'quickcheck') { $status->num_qc++; $status->quickchecks[] = $e; }
		if ($e->context === 'arrangement' || $e->context === 'partial')
		{

			if ($e->status === 'scheduled')
			$status->has_arrangements = true;

			// Mantis:9820 - business rule needs to apply to failed arrangements only.  flagging state here.
			if ($e->status === 'failed')
			{
			$status->has_failed_arrangements = true;
				if($e->context == 'partial')
				{
					$status->failed_partials[] = $e;
				}
			}

		}


		// Determine how it affects aggregates
		switch($e->status)
		{
			case 'failed':
				{
					if ($e->principal > 0.0) $status->principal_in_failures = true;
					if (@strtotime($e->return_date) > @strtotime($fail_date))
					{
						$fail_date = $e->return_date;
						$fail_set = array();
					}
					$fail_set[] = $e;

					if($e->context == 'arrangement' || $e->context == 'partial')
					{
						$status->made_arrangement = TRUE;
					}
					//add check if fatal ach is on current bank acct
					if (isset($e->ach_return_code_id) && $ach_failure_map[$e->ach_return_code_id]['is_fatal'] === 'yes' && $e->bank_aba == $e->current_bank_aba && $e->bank_account == $e->current_bank_account) $status->num_fatal_failures++;
					//add check if fatal card
					if (isset($e->return_code) && ($e->is_fatal == 1) && ($e->clearing_type == 'card')) $status->num_fatal_failures++;

					if (Is_Service_Charge_Payment($e))
					{
						$status->attempted_service_charge_count++;
					}

					if (($e->type == 'adjustment_internal' || $e->type == 'adjustment_internal_fees') && $e->context == 'payout')
					{
						$status->has_failed_int_adj_payout = TRUE;
					}
					////////////
					if ($e->clearing_type == 'ach' || $e->clearing_type == 'card')
					{
						if ($e->date_effective > $date_effective_init)
						{
							$status->num_ach_card_failures++;
							$date_effective_init = $e->date_effective;
						}
					}
				}
				break;
			case 'pending':
				{
					$status->num_pending_items++;

					// There was a problem with Complete Schedule not seeing the pending
					// service charges and would produce additional charges.  This appears
					// to fix it and not cause harm elsewhere. [Mantis:1680]
					if (Is_Service_Charge_Payment($e))
					{
						$status->posted_service_charge_count++;
						$status->attempted_service_charge_count++;
						$status->posted_service_charge_total = bcadd($status->posted_service_charge_total,$e->service_charge,2);
						$el = array_shift($outstanding['sc']);
						$el->associated_event = $e;
					}

					$status->pending_principal = bcadd($status->pending_principal,$e->principal,2);
					$status->pending_fees = bcadd($status->pending_fees,$e->fee,2);
					$status->pending_interest = bcadd($status->pending_interest,$e->service_charge,2);
					if ($e->principal > 0)
					{
						$status->posted_principal = bcadd($status->posted_principal,$e->principal,2);
					}

					if ($e->service_charge <> 0) $status->last_pending_service_charge_date = $e->date_event;

					$status->posted_and_pending_principal = bcadd($status->posted_and_pending_principal,$e->principal);
					$status->posted_and_pending_fees = bcadd($status->posted_and_pending_fees,$e->fee);
					$status->posted_and_pending_interest = bcadd($status->posted_and_pending_interest,$e->service_charge);
					$status->posted_and_pending_total = bcadd($status->posted_and_pending_total,($e->fee + $e->service_charge + $e->principal));

					}
			case 'new':
			case 'scheduled':
				{
					$gp = intval($grace_periods[$e->type]->pending_period);
					$gt = $grace_periods[$e->type]->period_type;
					switch($gt)
					{
						case "calendar":	$n = "Calendar"; break;
						case "business":
						default:			$n = "Business"; break;
					}
					$function_name = "Get_{$n}_Days_Forward";
					if ($e->type === 'quickcheck')
					{
						// The first condition was wrong when setting it to date_effective... don't
						// know if we need to change anything else here.
						if ($e->status === 'pending') $start_date = $e->date_event;
						else $start_date = $e->date_event;
						$end_date = $pd_calc->$function_name($start_date, $gp);
					}
					else
					{
						if ($e->status === 'pending')
						{
							$end_date = $pd_calc->$function_name($e->date_effective, $gp);
						}
						else
						{
							$end_date = $pd_calc->$function_name($e->date_effective, $gp);
						}
					}
					$e->pending_end = $end_date;
				} break;
			case 'complete':
				{
					if (preg_match('/refund_3rd_party/', $e->type) != 0) continue;
					$status->posted_principal = bcadd($status->posted_principal,$e->principal);
					$status->posted_fees = bcadd($status->posted_fees,$e->fee);
					$status->posted_interest = bcadd($status->posted_interest,$e->service_charge);
					$status->posted_total = bcadd($status->posted_total,($e->principal + $e->service_charge + $e->fee));

					$status->posted_and_pending_principal = bcadd($status->posted_and_pending_principal,$e->principal);
					$status->posted_and_pending_fees = bcadd($status->posted_and_pending_fees,$e->fee);

					$status->posted_and_pending_interest = bcadd($status->posted_and_pending_interest,$e->service_charge) ;

					$status->posted_and_pending_total = bcadd($status->posted_and_pending_total,($e->principal + $e->service_charge + $e->fee));

					if ($e->service_charge <> 0)
					{
						$status->last_pending_service_charge_date = $e->date_event;
						$status->last_service_charge_date = $e->date_event;
					}
					if($e->type == 'assess_service_chg' && $e->service_charge != 0 )
					{
						//$status->next_service_charge_id = $e->event_schedule_id;
						$status->last_service_charge_date = $e->date_event;
					}
					if ($e->principal < 0)
					{
						$status->paid_principal = bcadd($status->paid_principal,$e->principal,2);
						$status->paid_principal_count ++;
					}
					if ($e->fee < 0)
					{
						$status->paid_fees = bcadd($status->paid_fees,$e->fee,2);
						$status->paid_fee_count ++;
					}
					if ($e->service_charge < 0)
					{
						$status->paid_interest = bcadd($status->paid_interest,$e->service_charge,2);
						$status->paid_interest_count ++;
					}

					foreach ($e->amounts as $a)
					{
						if ($a->amount < 0) $status->total_paid = bcadd($status->total_paid,$a->amount,2);
					}

				}
			default:
		}

		if (($e->principal + $e->fee + $e->service_charge) < 0.0) $debits[] = $e;

		// Now determine how it affects event breakdown -- only completed items
		// OR non-failure items if we're verifying
		if (($e->status === 'complete') || ($verify && ($e->status != 'failed')))
		{
			if ($e->status === 'scheduled')
			{
				if (($status->next_principal_due_date === "N/A") &&
				$e->principal < 0)
				{
					$status->next_principal_due_date = $e->date_event;
				}

				if (($status->next_action_date === "N/A") &&
				(($e->principal + $e->service_charge + $e->fee) < 0))
				{
					if (strlen($e->date_event) == 10)
					{
						$status->next_action_date = substr($e->date_event, 5, 2) . '-'
						. substr($e->date_event, -2, 2) . '-'
						. substr($e->date_event, 0, 4);
					}
					else
					{
						$status->next_action_date = $e->date_event;
					}

					if (strlen($e->date_effective) == 10)
					{
						$status->next_due_date = substr($e->date_effective, 5, 2) . '-'
						. substr($e->date_effective, -2, 2) . '-'
						. substr($e->date_effective, 0, 4);
					}
					else
					{
						$status->next_due_date = $e->date_effective;
					}
					$status->shift_date_range = array();

					// Generate the dates that can be used in the stupid dropdown
					$adjustment_span = isset($rules['max_sched_adjust']) ? $rules['max_sched_adjust'] : 1;
					$ending = $pd_calc->Get_Business_Days_Forward($e->date_effective, $adjustment_span);
					$i = $pd_calc->Get_Closest_Business_Day_Forward(date("Y-m-d"));
					while (strtotime($i) < strtotime($ending))
					{
						$status->shift_date_range[] = $i;
						$i = $pd_calc->Get_Next_Business_Day($i);
					}
				}
				if ((($e->principal + $e->fee + $e->service_charge) < 0) &&
				(date("m-d-Y", strtotime($e->date_event)) === $status->next_action_date))
				{
					$status->next_amt_due += abs($e->principal + $e->fee + $e->service_charge);
				}
			}

			switch($e->type)
			{
				case 'converted_principal_bal':
				case 'loan_disbursement':
				case 'card_disbursement':
				case 'moneygram_disbursement':
				case 'check_disbursement':
					if (($e->service_charge < 0.00) || ($e->fee < 0.00) || ($e->principal <> 0.00))
					{
						$cancelation_delay = ($rules['cancelation_delay']) ? $rules['cancelation_delay'] : 3;
						$cancel_limit = $pd_calc->Get_Business_Days_Forward($e->date_event, $cancelation_delay);
						// The rule is "If it's been less than x days since the last payment."
						if (strtotime("now") > (strtotime($cancel_limit) + 86399)) $status->cancellable = false;
					}
					$status->initial_principal = $e->principal;
					break;

				case 'assess_service_chg':
				case 'converted_service_chg_bal':
					if($e->status === 'complete')
					{
						$status->Completed_SC_Credits = bcadd($status->Completed_SC_Credits,$e->service_charge,2);

					}

					$status->num_reg_sc_assessments++;
					$outstanding['sc'][] = $e;
					break;
					// Converted sc event means a) they already accrued a sc, and 2) they already paid a sc
				case 'converted_sc_event':
				case 'credit_card_fees':
				case 'manual_ach':
					$status->posted_service_charge_count++;
					$status->attempted_service_charge_count++;
					break;
				case 'payment_service_chg':
				case 'card_payment_service_chg':
					if ($e->status === 'complete')
					{
						$status->posted_service_charge_count++;
						$status->attempted_service_charge_count++;
						$status->posted_service_charge_total = bcadd($status->posted_service_charge_total,$e->service_charge,2);

						$status->Completed_SC_Debits = bcadd($status->Completed_SC_Debits,$e->service_charge,2);
					}
					$el = array_shift($outstanding['sc']);
					$el->associated_event = $e;
					break;
				case 'assess_fee_ach_fail':
				case 'assess_fee_card_fail':
					$status->ach_fee_count++;
					$outstanding['ach'][] = $e;
					break;
				case 'payment_fee_ach_fail':
				case 'payment_fee_card_fail':
					$el = array_shift($outstanding['ach']);
					$el->associated_event = $e;
					break;
				case 'writeoff_fee_ach_fail':
				case 'writeoff_fee_card_fail':
					$el = array_shift($outstanding['ach']);
					$el->associated_event = $e;
					break;
				case 'chargeback':
					$status->can_reverse_chargeback = TRUE;
					break;
				case 'assess_fee_lien':
					$status->has_lien_fee = TRUE;
					break;
				case 'assess_fee_delivery':
					$status->has_delivery_fee = TRUE;
					break;
				case 'assess_fee_transfer':
					$status->has_transfer_fee = TRUE;
					break;
				case 'credit_card':
				case 'credit_card_princ':
				case 'credit_card_fees':
					if($e->status === 'complete' )
					{
						$status->Completed_Reatts = bcadd($status->Completed_Reatts,$e->principal_amount,2);
						$status->Completed_SC_Debits = bcadd($status->Completed_SC_Debits,$e->service_charge,2);

					}
					$status->can_chargeback = TRUE;
					break;

				case 'payout':
				case 'card_payout':
					$status->has_payout = TRUE;
					break;
				case 'repayment_principal':
				case 'card_repayment_principal':
					if($e->status === 'complete' && !empty($e->origin_id) )
					{
						$status->Completed_Reatts = bcadd($status->Completed_Reatts,$e->principal_amount,2);

					}
					elseif ($e->status === 'failed' && empty($e->origin_id))
					{
						$status->Failed_Princ_non_reatts = bcadd($status->Failed_Princ_non_reatts,$e->principal_amount,2);

					}

				case 'payment_arranged':
				case 'payment_arranged_princ':
				case 'card_payment_arranged':
				case 'card_payment_arranged_princ':
					if($e->status === 'complete' && !empty($e->origin_id))
					{
						$status->Completed_Reatts = bcadd($status->Completed_Reatts,$e->principal_amount,2);

					}
					elseif ($e->status === 'failed' && empty($e->origin_id) && $e->context == 'manual')
					{
						$status->Failed_Princ_non_reatts = bcadd($status->Failed_Princ_non_reatts,$e->principal_amount,2);

					}
					break;
				case 'payment_arranged_fees':
				case 'card_payment_arranged_fees':
				case 'payment_debt':
				case 'paydown':
				case 'card_paydown':
				case 'payout_principal':
				case 'payout_fees':
				case 'cancel_principal':
				case 'card_payout_principal':
				case 'card_payout_fees':
				case 'card_cancel_principal':
				case 'adjustment_internal':
				case 'adjustment_internal_princ':
				case 'adjustment_internal_fees':
					break;
				case 'payment_manual':
				case 'payment_manual_princ':
				case 'card_payment_manual':
				case 'card_payment_manual_princ':
					if($e->status === 'complete')
					{
						$status->Completed_Reatts = bcadd($status->Completed_Reatts,$e->principal_amount,2);
						$status->Completed_SC_Debits = bcadd($status->Completed_SC_Debits,$e->service_charge,2);

					}
					break;
				case 'payment_manual_fees':
				case 'card_payment_manual_fees':
				case 'full_balance':
				case 'card_full_balance':
				case 'quickcheck':
				case 'debt_writeoff':
				case 'debt_writeoff_princ':
				case 'debt_writeoff_fees':
				case 'ext_recovery':
				case 'ext_recovery_princ':
				case 'ext_recovery_fees':
					break;
				case 'money_order':
				case 'money_order_fees':
				case 'money_order_princ':
				case 'moneygram':
				case 'moneygram_fees':
				case 'moneygram_princ':
				case 'western_union':
				case 'western_union_fees':
				case 'western_union_princ':
					if($e->status === 'complete' )
					{
						$status->Completed_Reatts = bcadd($status->Completed_Reatts,$e->principal_amount,2);
						$status->Completed_SC_Debits = bcadd($status->Completed_SC_Debits,$e->service_charge,2);

					}
					break;
				case 'refund':
				case 'card_refund':
				case 'refund_3rd_party':
				case 'cancel':
				case 'card_cancel':


					break; // Nothing to do for these yet
			}
		}
		$status->stopping_location++;
		$posted_schedule[] = $e;
	}

	//echo "<pre>".print_r($posted_schedule,true)."</pre>SC_credit:$status->Completed_SC_Credits  + SC_debits:$status->Completed_SC_Debits  - Failed_princ:$status->Failed_Princ_non_reatts - completed_reatt:$status->Completed_Reatts<br>";
	//$status->past_due_balance = ($status->Completed_SC_Credits + $status->Completed_SC_Debits) - ($status->Failed_Princ_non_reatts - $status->Completed_Reatts);
	$status->past_due_balance = bcsub(bcadd($status->Completed_SC_Credits, $status->Completed_SC_Debits), bcsub($status->Failed_Princ_non_reatts, $status->Completed_Reatts));

	$status->outstanding = $outstanding;

	// if we have only cancel type transactions, we can't cancel.  If this flag is not set, they were all cancel transactions.  It is not cancellable.
	if (!$non_cancel_trans) $status->cancellable = false;


	if (!$verify)
	{
		// We need to filter the fail set - the initial run-through
		// put in ALL the failures, but we only really want the
		// newest ones. This is determined by the fact that if
		// the transaction_register_id of any failures equals the
		// origin_id of any other transaction, the former failure is
		// old, and therefore should be removed.
		$new_fail_set = array();
		foreach ($fail_set as $f)
		{
			if(! in_array($f->transaction_register_id, $oids))
			{
				$new_fail_set[] = $f;
			}
		}

		$status->fail_set = $new_fail_set;
	}

	//$status->max_reattempt_count = Count_Max_Reattempts($schedule);

	$status->debits = $debits;
	$status->posted_schedule = $posted_schedule;
	$schedule_status = $status;

	$status->pending_total = $status->pending_principal + $status->pending_fees + $status->pending_interest;
	$status->posted_total = $status->posted_principal + $status->posted_fees + $status->posted_interest;
	$status->running_total = $status->running_principal + $status->running_fees + $status->running_interest;

	$status->posted_principal = round($status->posted_principal, 2); 	//mantis:4560
	$status->posted_fees = round($status->posted_fees, 2);			//mantis:4560
	$status->posted_interest = round($status->posted_interest, 2);			//mantis:9701

	// Check to see if we allow manual loan renewals and we're within the renewal period
	if(($status->next_principal_due_date !== 'N/A') && (ECash::getConfig()->LOAN_RENEWAL_PENDING_PERIOD))
	{
		if(	Date_Util_1::dateDiff(date('Y-m-d'), $status->next_principal_due_date) <= ECash::getConfig()->LOAN_RENEWAL_PENDING_PERIOD)
		{
			$status->can_renew_loan = TRUE;
		}
	}
	return $status;
}

/**
 * helper function to determine if an event is a service charge
 * payment of any sort (ACH, credit card, etc.)
 */
function Is_Service_Charge_Payment($event)
{
	if(in_array($event->context, array('generated', 'arrange_next')))
	{
		foreach($event->amounts as $ea)
		{
			if($ea->event_amount_type == 'service_charge' && $ea->amount < 0)
				return TRUE;
	   	}
	}
	return FALSE;
}

/**
 * Returns only scheduled debits from an array of scheduled events.
 *
 * @param array $schedule_array
 * @return array
 */
function Retrieve_Scheduled_Debits($schedule_array)
{
	$scheduled_payments = array();
	foreach ($schedule_array as $event)
	{
		if (($event->status == 'scheduled') &&
		(($event->principal_amount + $event->fee_amount) < 0.0))
		{
			$scheduled_payments[] = $event;
		}
	}

	return $scheduled_payments;
}

function Reattempt_Event($application_id, $f, $date_event, $ogid, $comm=null)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$next_day = $pd_calc->Get_Business_Days_Forward($date_event, 1);
	if ($comm == null)
	{
		$comm = "Reattempt of transaction {$f->transaction_register_id}";
	}
	$values = array(
	'principal' => $f->principal,
	'service_charge' => $f->service_charge,
	'fee' => $f->fee,
	);
	$amounts = AmountAllocationCalculator::generateGivenAmounts($values);
	$e = Schedule_Event::MakeEvent($date_event, $next_day, $amounts, $f->type, $comm,'scheduled',
	'reattempt', $f->transaction_register_id,
	$ogid);
	//if (isCardSchedule($application_id)) alignActionDateForCard($application_id);
	Record_event($application_id, $e);
}

function Reattempt_Event_Manual($application_id, $f, $next_day, $ogid, $comm=null)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$date_event = $pd_calc->Get_Business_Days_Backward($next_day, 1);
	if ($comm == null)
	{
		$comm = "Reattempt of transaction {$f->transaction_register_id}";
	}
	$values = array(
	'principal' => $f->principal,
	'service_charge' => $f->service_charge,
	'fee' => $f->fee,
	);
	$amounts = AmountAllocationCalculator::generateGivenAmounts($values);
	$e = Schedule_Event::MakeEvent($date_event, $next_day, $amounts, $f->type, $comm,'scheduled',
	'reattempt', $f->transaction_register_id,
	$ogid);
	//if (isCardSchedule($application_id)) alignActionDateForCard($application_id);
	Record_event($application_id, $e);
}

function Update_Schedule($application_id, $schedule, $made_arrangement = false)
{
	if ($schedule === null) { throw new Exception ("No schedule passed to Update_Schedule!" . var_export(debug_backtrace, true)); }

	$evids = array();
	// Clear out any unfinished business

	Remove_Unregistered_Events_From_Schedule($application_id);

	// There seems to be a lot of cases where there aren't events
	if($schedule != NULL)
	{
		$arrangement_set = FALSE;
		foreach ($schedule as $event)
		{
			if(!$arrangement_set && $made_arrangement && in_array($e->type, array('repayment_principal','card_repayment_principal')) && ECash::getConfig()->HANDLE_SHIFTS_AS_ARRANGEMENTS)
			{
				$event->context = 'arrangement';
				$arrangement_set = TRUE;
			}
			if ($event->status != 'scheduled') continue;

			// Debt Consolidation uses the origin_id to reference Debt Company [RayL]
			if($event->type == "payment_debt")
			{
				$comp_id = $event->origin_id;
				$event->origin_id = null;
			}
			else
			{
				$comp_id = null;
			}

			$evid = Record_Event($application_id, $event);

			//Associate Debt Company with Event [RayL]
			if($event->type == "payment_debt" && isset($comp_id))
			Assoc_Event_Debt_Company($comp_id, $evid);

			if ($evid != null) $evids[] = $evid;
		}
	}

	return $evids;
}

function Recalculate_Schedule_Dates($schedule, $info, $rules, $start_date=null)
{
	$holidays = Fetch_Holiday_List();
	$pd_calc = new Pay_Date_Calc_3($holidays);

	$new_schedule = array();
	$len = count($schedule) * 2;

	$grace = 10;
	$rules = Prepare_Rules($rules, $info);
	if (isset($rules['grace_period']))
	{
		$grace = $rules['grace_period'];
	}
	$dates = Get_Date_List($info, (($start_date == null)? date("Y-m-d") : $start_date), $rules, $len);
	if (($start_date == null) || (strlen($start_date) <= 0))
	{
		$start_date = $dates['event'][0];
	}

	// Get rid of dates before the start date
	while (strtotime($dates['event'][0]) < strtotime($start_date))
	{
		array_shift($dates['event']);
		array_shift($dates['effective']);
	}

	// If we were given an explicit start date, make sure it's honored.
	if ($start_date != $dates['event'][0])
	{
		$next_day = $pd_calc->Get_Next_Business_Day($start_date);
		array_unshift($dates['event'], $start_date);
		array_unshift($dates['effective'], $next_day);
	}

	// Make sure we're at least "grace period" days out
	$fst_svc_chg = strtotime("+".$grace." days", strtotime($start_date));
	while (strtotime($dates['event'][0]) < $fst_svc_chg)
	{
		$shifted = false;
		$first_event = $dates['event'][0];
		$first_effective = $dates['effective'][0];
		// Yes, this is kinda ugly.
		$fst_svc_chg = date("Y-m-d", strtotime("+".$grace."days", strtotime($start_date)));
		while (strtotime($dates['effective'][0]) < strtotime($fst_svc_chg))
		{
			$shifted = true;
			array_shift($dates['event']);
			array_shift($dates['effective']);
		}
		if ($shifted)
		{
			array_unshift($dates['event'], $first_event);
			array_unshift($dates['effective'], $first_effective);
		}
	}

	$idx = 0;
	$old_date =  null;
	$disbursement_date = null;
	$use_original_dates = false;

	foreach ($schedule as $event)
	{
		if ($event->status == 'scheduled')
		{
			// If it's the loan disbursement, keep the existing scheduled date
			// because it was what the loan docs state and the customer agreed on.
			if (in_array($event->type, array('loan_disbursement','card_disbursement')))
			{
				$disbursement_date = $event->date_event;
				$new_schedule[] = $event;
				continue;
			}
			// If the event date is the same as the disbursement date, we'll go
			// ahead and keep the event as is.
			if($event->date_event == $disbursement_date)
			{
				$new_schedule[] = $event;
				continue;
			}
			if ($old_date == null) $old_date = $event->date_event;
			if ($old_date != $event->date_event)
			{
				$old_date = $event->date_event;
				$idx++;
			}

			if ($idx == 0)
			{
				$event->is_shifted = true;

				// If we're shifting the schedule backwards let's just not adjust the rest
				// of the dates
				if(strtotime($start_date)  < strtotime($event->date_event))
				{
					$use_original_dates = true;
				}

				$event->date_event = $dates['event'][$idx];
				$event->date_effective = $dates['effective'][$idx];
			}
			//mantis:4843 - filter debt consolidation - added && $event->type != 'payment_debt'
			elseif ($event->is_shifted != 1 && $use_original_dates == false && $event->type != 'payment_debt')
			{
				$event->date_event = $dates['event'][$idx];
				$event->date_effective = $dates['effective'][$idx];
			}

			$new_schedule[] = $event;
		}
	}

	return $new_schedule;
}

function postCashlineReturn($application_id, $return_amount, $is_fatal)
{
	$amounts = AmountAllocationCalculator::generateGivenAmounts(array('service_charge' => $return_amount));
	$today = date('Y-m-d');
	$event_type = $is_fatal ? 'h_fatal_cashline_return' : 'h_nfatal_cashline_return';

	try
	{
		$db = ECash::getMasterDb();
		$db->beginTransaction();
		// Create the visible cashline return
		Go_All_The_Way_On_The_First_Date(
		$application_id, 'cashline_return', NULL, NULL,	$amounts, $today,
		$today, '', ($is_fatal ? 'Fatal ' : 'Non-Fatal ') . 'Cashline Return', 'generated'
		);


		// Create and fail the hidden cashline return
		$evid = Record_Schedule_Entry(
		$application_id, $event_type, NULL, NULL, $today, $today,
		'Cashline Return', 'generated'
		);

		Record_Event_Amount($application_id, $evid, 'service_charge', -$return_amount);

		$trids = Record_Current_Scheduled_Events_To_Register(
		date('Y-m-d', strtotime("+1 day")), $application_id, $evid, 'all'
		);

		foreach ($trids as $trid)
		{
			Record_Transaction_Failure($application_id, $trid);
		}
		$db->commit();
	}
	catch (Exception $e)
	{
		get_log('scheduling')->Write("There was an error posting a cashline return: {$e->getMessage()}");
		$db->rollBack();
		throw $e;
	}
}

function Record_Event($application_id, $event)
{

	$log = get_log("scheduling");
	$db = ECash::getMasterDb();
	static $level = 0;

	if ($level == 2)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Failed to create event:\n".print_r($event,2));
		logBackTrace(debug_backtrace());
		get_log('alert_errors')->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Failed to create event.");
		return null;
	}

	$isolated = ($db->InTransaction) ? false : true;

	if ($isolated) {
		$level++;

		try {
			$db->beginTransaction();

			$evid = Record_Schedule_Entry($application_id, $event->type,
										  $event->origin_id, $event->origin_group_id,
										  $event->date_event, $event->date_effective,
										  $event->comment, $event->context, null,
										  $event->is_shifted);
			$total_amount = 0;

			foreach ($event->amounts as $ea)
			{
				/** @var $ea Event_Amount **/
				if ($ea->amount)
				{
					Record_Event_Amount($application_id, $evid, $ea->event_amount_type, $ea->amount, $ea->num_reattempt);
				}
				$total_amount = bcadd($total_amount,$ea->amount,2);
			}
			$db->commit();
		}
		catch (Exception $e)
		{
			$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Attempt {$level} failed. Reattempting. ({$e->getMessage()})");
			$db->rollBack();
			$evid = Record_Event($application_id, $event);
		}
	}
	else
	{
		$evid = Record_Schedule_Entry($application_id, $event->type,
									  $event->origin_id, $event->origin_group_id,
									  $event->date_event, $event->date_effective,
									  $event->comment, $event->context, null,
									  $event->is_shifted);
		$total_amount = 0;

		foreach ($event->amounts as $ea)
		{
			/* @var $ea Event_Amount */
			if ($ea->amount)
			{
				Record_Event_Amount($application_id, $evid, $ea->event_amount_type, $ea->amount, $ea->num_reattempt);
			}
			$total_amount = bcadd($total_amount,$ea->amount,2);
		}
	}
	if ($evid != null)
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Creating event {$evid} [Event: {$event->date_event}] [Effective: {$event->date_effective}] [{$event->type} / {$event->context}] ({$total_amount})");
	if ($isolated) $level--;
	return $evid;
}

function Group_Reattempts($s)
{
	$group_id = 0;

	foreach ($s as $event)
	{
		if ($event->origin_id != null)
		{
			if ($group_id == 0) $group_id = - abs($event->origin_id);
			$event->origin_group_id = $group_id;
		}
	}
}

/**
 * Follows the reattemtpts in a schedule and returns the max # of reattempts
 *
 * @param array $schedule
 * @param integer $origin_id
 * @return integer
 */
function Count_Max_Reattempts($schedule, $origin_id=null)
{
	static $recursion_level;
	if (empty($origin_id))
	{
		$max_level = 0;
		$reatt_count = 0;
		for($x = 0; $x < count($schedule); $x++)
		{
			$e = $schedule[$x];
			$recursion_level = 1;
			if ($e->origin_id !== NULL && $e->origin_id > 0)
			{
				$reatt_count = max($reatt_count, Count_Max_Reattempts($schedule, $e->origin_id));
				$recursion_level--;
				if($recursion_level > $max_level) $max_level = $recursion_level;
			}
		}
		//return $reatt_count;
		return $max_level;
	}
	else
	{
		$recursion_level++;
		for($i = 0; $i < count($schedule); $i++)
		{
			$e = $schedule[$i];
			if ($e->transaction_register_id === $origin_id)
			{
				if ($e->origin_id === NULL) return 0;
				else return (Count_Max_Reattempts($schedule, $e->origin_id) + 1);
			}
		}
	}
}

/**
 * Complete Schedule is intended to take an existing schedule, take
 * into account the registered events, and then regenerate a new one
 * while also taking into account any special arrangements or payments
 * that have been made to the account.
 *
 * This function is currently used for four things:
 *  - Modifying the pay date model
 *  - Regenerating the Schedule after a Manual Payment
 *  - Regenerating the Schedule after failing a transaction
 *  - Regenerating the Schedule for data fixes
 *
 * @param int $application_id
 * @param bool $save - This determines if the changes need to be written to the schedule
 * @param array $preview_events - These are the additions to add to the scheduled
 * @param bool $skip_first_interest_payment
 */
function Complete_Schedule($application_id, $save = TRUE, $preview_events = Array(), $skip_first_interest_payment = false)
{
	//[#31251]
	Adjust_Negative_Balances($application_id, FALSE);

	require_once(CUSTOMER_LIB. "create_schedule_dfa.php");

	$log = ECash::getLog('scheduling');
	$db = ECash::getMasterDb();
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Completing schedule, Save is set to " . var_export($save, true));

	// Grab the current schedule as it sits.
	$current_schedule   = Fetch_Schedule($application_id);
	$status = Analyze_Schedule($current_schedule, TRUE);

	if (!_Check_Should_Complete_Schedule($application_id, $status, $current_schedule))
	{
		return Array();
	}

	$classifications = _Classify_Schedule($current_schedule, $preview_events);

	// grab the schedule again and analyze what's been completed so far.
	$schedule   = Remove_Unregistered_Events_From_This_Schedule($current_schedule);
	$status 	= Analyze_Schedule($schedule);
	$verified 	= Analyze_Schedule($schedule, true);

	$tr_data 	= Get_Transactional_Data($application_id);
	$rules 		= Prepare_Rules($tr_data->rules, $tr_data->info);

	$ecash_api = eCash_API_2::Get_eCash_API(null, $db, $application_id);

	$parameters = new stdClass();
	$parameters->log = $log;
	$parameters->skip_first = !empty($preview_events);
	$parameters->skip_first_interest_payment = $classifications->skip_first;
	$parameters->rules = $rules;
	$parameters->tr_data = $tr_data;// used in clk schedule dfas
	$parameters->info = $tr_data->info;
	$parameters->schedule = $schedule;
	$parameters->special_payments = $classifications->special_payments;
	$parameters->last_service_charge_date = $status->last_service_charge_date;
	$parameters->reattempts = $classifications->reattempts;
	$parameters->verified = $verified;// used in impact schedule dfas
	$parameters->application_id = $application_id;
	$parameters->balance_info = Fetch_Balance_Information($application_id);
	$parameters->delinquency_date = $ecash_api->getDelinquencyDate($application_id);
	$parameters->fund_amount = $tr_data->info->fund_actual;
	$parameters->fund_date = $tr_data->info->date_fund_stored;
	$parameters->fund_method = $tr_data->info->schedule_model;

	// The create_schedule_dfa uses the calculator
	$holidays = Fetch_Holiday_List();
	$parameters->pdc = new Pay_Date_Calc_3($holidays);
	$parameters->skip_first = $classifications->skip_first;

	// These variables aren't used by the agean dfa, but are used by the others.
	if (isset($current_schedule[$status->stopping_location]->date_event) &&
		(!in_array($current_schedule[$status->stopping_location]->type, _get_ignored_events()) ||
		$current_schedule[$status->stopping_location]->is_shifted))
	{
		$parameters->next_action_date = $current_schedule[$status->stopping_location]->date_event;
		$parameters->next_due_date = $parameters->pdc->Get_Next_Business_Day($parameters->next_action_date);
	}
	else
	{
		$parameters->next_action_date = false;
		$parameters->next_due_date = false;
	}
	$app = ECash::getApplicationById($application_id);
	$app_status = $app->getStatus();
	$columns = $app_status->getColumns();
	foreach ($columns as $key) $parameters->$key = $app_status->$key;// used in complete_schedule_dfa

	$parameters->status = $status;// used in complete_schedule_dfa


	//We're trying to determine if the application had/has a rollover to recreate
	$parameters->has_pending_rollover = FALSE;
	$sc_payments_pending = 0;
	$broker_fees_pending = 0;
	foreach ($current_schedule as $e)
	{
		if ($e->type == 'cso_assess_fee_broker' && $e->status == 'scheduled')
		{
			$broker_fees_pending++;
		}
		//if ($e->type == 'payment_service_chg' && $e->status == 'scheduled')
		if (in_array($e->type, array('payment_service_chg','card_payment_service_chg')) && $e->status == 'scheduled')
		{
			$sc_payments_pending++;
		}
	}
	if(strtoupper($rules['loan_type_model']) == 'CSO')
	{
		$parameters->has_pending_rollover = ($broker_fees_pending >= 1 )? TRUE : FALSE;
	}
	else
	{
		$parameters->has_pending_rollover = ($sc_payments_pending > 1) ? TRUE : FALSE;
	}
	
	$parameters->may_use_card_schedule = mayUseCardSchedule($application_id, $app->getCompanyId());

	// Decide which DFA to run.
	if ($classifications->sent_disbursement)
	{
		if (!isset($dfas['complete_schedule']))
		{
			$dfa = ECash::getFactory()->getClass('DFA_CompleteSchedule');
			$dfa->setLog($log);
			$dfas['complete_schedule'] = $dfa;
		}
		else
		{
			$dfa = $dfas['complete_schedule'];
		}
		$log->Write('Running Complete Schedule DFA');
	}
	else
	{
		if (!isset($dfas['create_schedule']))
		{
			$dfa = new CreateScheduleDFA();
			$dfa->SetLog($log);
			$dfas['create_schedule'] = $dfa;
		}
		else
		{
			$dfa = $dfas['create_schedule'];
		}
		$log->Write('Running Create Schedule DFA');
	}

	$new_events = $dfa->run($parameters);

	if ($save)
	{
		// Remove all unregistered events.  We'll regenerate them.
		Remove_Unregistered_Events_From_Schedule($application_id);
		if (!empty($new_events))
		{
	    	foreach ($new_events as $e)
	    	{
	        	Record_Event($application_id, $e);
	    	}
		}
	}

	return $new_events;
}

function Adjust_Negative_Balances($application_id, $complete_schedule = TRUE)
{
	//only run this once per request
	static $has_adjusted = FALSE;
	if(!$has_adjusted)
	{
		$has_adjusted = TRUE;
		$db = ECash::getMasterDb();
		$log = get_log("scheduling");
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Checking for negative balances");

		$balance_info = Fetch_Balance_Information($application_id);

		$balance = array('principal' => $balance_info->principal_pending,
						 'service_charge' => $balance_info->service_charge_pending,
						 'fee' => $balance_info->fee_pending);

		$adjustment_amounts = AmountAllocationCalculator::createBalanceAdjustments($balance);

		$date = date('Y-m-d');
		$added_events = FALSE;

		try
		{
			$db->beginTransaction();
			foreach($adjustment_amounts as $amount)
			{
				$event = Schedule_Event::MakeEvent($date, $date, array($amount), 'adjustment_internal', 'Internal Adjustment to overcome possible negative balance(s)', 'scheduled', 'manual');
				$evid = Record_Event($application_id, $event);
				$trids = Record_Current_Scheduled_Events_To_Register($date, $application_id, $evid);
				foreach ($trids as $trid)
				{
					Post_Transaction($application_id, $trid);
				}
				$added_events = TRUE;
			}
			$db->Commit();
		}
		catch (Exception $e)
		{
			$db->rollBack();
			throw $e;
		}

		if($complete_schedule && $added_events)
		{
			Complete_Schedule($application_id);
		}
	}
}

function _get_ignored_events($type = NULL) {
	switch ($type)
	{
		case 'regenerated':
			return array('assess_service_chg',
				'payment_service_chg',
				'repayment_principal',
				'card_payment_service_chg',
				'card_repayment_principal',
				'cso_assess_fee_broker',
				'cso_pay_fee_broker',
				'cso_pay_fee_app',
				);
		default:
			return array('assess_service_chg',
				'payment_service_chg',
				'repayment_principal',
				'card_payment_service_chg',
				'card_repayment_principal',
				//'payout',
				'assess_fee_ach_fail',
				'payment_fee_ach_fail',
				//'card_payout',
				'assess_fee_card_fail',
				'payment_fee_card_fail',
				'cso_assess_fee_broker',
				'cso_pay_fee_broker',
				'cso_pay_fee_app',);
	}
}

/**
 * Classify Schedule is an internal processing function for Complete Schedule.
 * It classifies and flags based on the schedule state in ways that will be used for the dfa call.
 *
 * @param int $application_id
 * @param array $schedule
 */
function _Classify_Schedule ($current_schedule, $preview_schedule) 
{
	// Ignore these types of events because we'll regenerate them in the DFA
	$ignored_events = _get_ignored_events();

	if(!empty($preview_schedule))
	{
		$ignored_events = array_merge($ignored_events,array('payment_fee_lien','payment_fee_delivery','payment_fee_transfer'));
	}

	$classifications = new StdClass();
	$classifications->sent_disbursement = false;
	$classifications->skip_first = false;
	$classifications->special_payments = array();
	$classifications->reattempts = array();
	$disbursement_types = array('loan_disbursement', 'card_disbursement', 'converted_principal_bal', 'converted_service_chg_bal','moneygram_disbursement', 'check_disbursement');

	$first_scheduled_date = null;

	foreach($current_schedule as $e)
	{
		// Set first date if relevant
		if (empty($first_scheduled_date) && $e->type ==='scheduled') $first_scheduled_date = $e->date;

		// Shifted payments are kept if they happen on the first date.
		if ($e->status === 'scheduled')
		{
			// Shifted payments are kept if they happen on the first date.
			if ($e->is_shifted  && $e->date == $first_scheduled_date && empty($preview_schedule))
			{
				$e->REASON = 'A';
				$classifications->special_payments[] = $e;
				$classifications->skip_first = true;
				$classifications->skip_past_date = $e->date;
			}

			// Grab any special payments, arrangements
			elseif((empty($e->origin_id)) && (!in_array($e->type, $ignored_events)) && !$e->is_shifted)
			{
				$e->REASON = 'B';

				if( in_array($e->event_type, array('payout','card_payout')) || $e->context == 'generated')
					continue;

				$e->REASON = 'C';

				$classifications->special_payments[] = $e;
			}

			// Include Manual Renewals
			elseif(in_array($e->type, array('repayment_principal','card_repayment_principal')) && $e->context === 'manual')
			{
				$e->REASON = 'D';
				$classifications->special_payments[] = $e;
			}

			// Look for reattempts, ACH Fees
			if(((! empty($e->origin_id)) || (in_array($e->type, array('assess_fee_ach_fail','assess_fee_card_fail'))) || (in_array($e->type, array('payment_fee_ach_fail','payment_fee_card_fail')))) && empty($preview_schedule))
			{
				$classifications->reattempts[] = $e;
			}
		}

		if (in_array($e->type, $disbursement_types) && $e->status != 'scheduled')
		{
			$classifications->sent_disbursement = true;
		}
	}

	if(!empty($preview_schedule))
	{
		$first_scheduled_date = null;

		foreach($preview_schedule as $e)
		{
			// Set first date if relevant
			if (empty($first_scheduled_date) && $e->type ==='scheduled') $first_scheduled_date = $e->date;

			// Shifted payments are kept if they happen on the first date.
			if ($e->is_shifted  && $e->date == $first_scheduled_date)
			{
				$e->REASON = 'A';
				$classifications->special_payments[] = $e;
				$classifications->skip_first = true;
				$classifications->skip_past_date = $e->date;
			}
		}
	}

	return $classifications;
}

/**
 * Check Should Complete Schedule is a internal checking function for Complete Schedule.
 * It checks to see if the state of the application is appropriate for completing the schedule.
 *
 * @param int $application_id
 * @param array $schedule
 */
function _Check_Should_Complete_Schedule ($application_id, $status, $schedule)
{
	/**
	 * Check the status and whether or not there are any fatal failures to
	 * determine whether or not this code should be run.
	 */
	$app = ECash::getApplicationById($application_id);
	$app_status = $app->getStatus();
	
	/*
	$flags = $app->getFlags();
	if(
		$flags->get('cust_no_ach')
		&& (!isCardSchedule($application_id))
	)
	{
		return false;
	}
	*/

	$status_chain = array();
	if ($app_status->level0) $status_chain[] = $app_status->level0;
	if ($app_status->level1) $status_chain[] = $app_status->level1;
	if ($app_status->level2) $status_chain[] = $app_status->level2;
	if ($app_status->level3) $status_chain[] = $app_status->level3;
	if ($app_status->level4) $status_chain[] = $app_status->level4;
	if ($app_status->level5) $status_chain[] = $app_status->level5;
	$status_chain = implode('::', $status_chain);

	// GF #13514: Removed 3 collections statuses from acceptable statuses. [benb]
	$acceptable_status = array(
	'active::servicing::customer::*root',
	//'past_due::servicing::customer::*root',
	'new::collections::customer::*root',
	'indef_dequeue::collections::customer::*root',
	//'dequeued::contact::collections::customer::*root',
	//'queued::contact::collections::customer::*root',
	//'follow_up::contact::collections::customer::*root',
	'approved::servicing::customer::*root',
	);

	$log = get_log("scheduling");

	// No accounts with fatal failures or that aren't an acceptable status
	if ($status_chain != 'active::servicing::customer::*root' &&
	(($status->num_fatal_failures > 0) || !in_array($status_chain, $acceptable_status))) {
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Is in an invalid status ($status_chain) or has fatal errors ({$status->num_fatal_failures}), not completing schedule.");
		return false;
	}

	// mantis:7875 If no balance, and account has been funded...
	if($status->posted_and_pending_total <= 0
	&& $status_chain != 'approved::servicing::customer::*root')
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Is in an invalid status, not completing schedule.");
		return false;
	}

	// Do not run if the account has arrangments and a balance
	if ($status->has_arrangements && $status->posted_and_pending_total > 0)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Is in an arrangement status and has a balance, not completing schedule.");
		return false;
	}
	return true;

}

function Post_Event($application_id, $event)
{
	$log = get_log("scheduling");
	$evid = Go_All_The_Way_On_The_First_Date($application_id, $event->type, $event->origin_id, $event->origin_group_id,
	$event->amounts, $event->date_event, $event->date_effective,"",
	$event->comment, $event->context);
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Immediately posting event {$evid} [{$event->type}]");
}

function Register_Single_Event($application_id, $request)
{
	$amt = round(floatval($request->amount), 2);
	$comment = $request->payment_description;
	switch ($request->action)
	{
		case 'recovery': $payment_type = "ext_recovery"; break;
		case 'writeoff': $payment_type = "debt_writeoff"; break;
	}
	$date = date("Y-m-d", strtotime("now"));

	$balance_info = Fetch_Balance_Information($application_id);
	$balance = array(
	'principal' => $balance_info->principal_balance,
	'service_charge' => $balance_info->service_charge_balance,
	'fee' => $balance_info->fee_balance,
	);
	$amounts = AmountAllocationCalculator::generateAmountsFromBalance(-$amt, $balance);

	$event = Schedule_Event::MakeEvent($date, $date, $amounts, $payment_type, $comment);
	try
	{
		$db->beginTransaction();
		$evid = Record_Event($application_id, $event);
		$trids = Record_Current_Scheduled_Events_To_Register($date, $application_id, $evid);
		foreach ($trids as $trid)
		{
			Post_Transaction($application_id, $trid);
		}

		$db->commit();
	}
	catch (Exception $e)
	{
		$db->rollBack();
		throw $e;
	}
}

function Prepare_Rules($rules, $info)
{
	$application = ECash::getApplicationById($info->application_id);
	$rate_calc = $application->getRateCalculator();	
	// Note, this only applies to Fixed Rate loans
	$rules['interest'] = $rate_calc->getPercent() / 100.0;
	if (!isset($rules['grace_period'])) $rules['grace_period'] = 10;

	// Set up the withdrawal period model mappings
	if( isset($rules['debit_frequency'][$info->income_frequency]) )
	{
		switch($rules['debit_frequency'][$info->income_frequency])
		{
			case 'every other pay period': $rules['period_skip'] = 1; break;
			case 'every pay period': $rules['period_skip'] = 0; break;
			default: $rules['period_skip'] = 0;
		}
	}
	else
	{
		$rules['period_skip'] = 0;
	}
	return $rules;
}

/**
 * Builds a date list baed on the paydate model from first_date forward for the
 * total number of payments requested (if possible). The function will try to account
 * for any skip periods in the schedule starting from the date_fund date.
 *
 * @param object $info - Transaction Info
 * @param string $first_date - First date to calculate from
 * @param array $rules - Business Rules
 * @param int $total_dates (optional) - Number of dates to return
 * @param int $num_service_charges  (optional) - Number of service charges this account has
 * @return array array(array('date_event' => 'date string'), array('date_effective' => 'date string'))
 */
function Get_Date_List($info, $first_date, $rules, $total_dates = 1, $num_service_charges = null, $start_date = null)
{
	$calc = new ECash_DueDateCalculator($info, $first_date, $rules, $num_service_charges);
	return $calc->getDateList($total_dates, $start_date);
}

function Schedule_Full_Pull($application_id, $info = null, $rules = null, $date_event = NULL, $date_effective = NULL)
{
	$data = Get_Transactional_Data($application_id);
	$schedule = Fetch_Schedule($application_id);
	$status = Analyze_Schedule($schedule);
	$flags = new Application_Flags(null, $application_id);
	//If this account has a fatal ACH flag on it, we do not want to execute a fullpull.  Trying to debit money from an account that has a fatal
	//return is a major no-no.  It's a guaranteed waste of an ACH transaction (which costs money to do), and it could also run afoul of the law [#21751][W!-12-05-2008]
	if($flags->Get_Flag_State('has_fatal_ach_failure'))
	{
		$log = get_log("scheduling");
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Full Pull is not being scheduled because this account has a fatal ACH.");
	}
	elseif($flags->Get_Flag_State('has_fatal_card_failure'))
	{
		$log = get_log("scheduling");
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Full Pull is not being scheduled because this account has a fatal Card transaction.");
	}
	else
	{
		$new_schedule = Full_Balance($application_id, $status, $schedule, $data->info, $data->rules, $date_event, $date_effective);
	}
}

function Get_Grace_Periods()
{
	$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT name_short, pending_period, period_type
		FROM   transaction_type
		UNION
		SELECT event_type.name_short, min(transaction_type.pending_period) AS pending_period,
                              transaction_type.period_type
		FROM   transaction_type, event_type, event_transaction
		WHERE
			event_transaction.active_status = 'active'
		AND event_transaction.event_type_id = event_type.event_type_id
		AND event_transaction.transaction_type_id = transaction_type.transaction_type_id
		AND transaction_type.name_short <> event_type.name_short
		GROUP BY event_type.name_short";

	// DLH, 2005.11.29, There are some event_types which have a different name_short than their
	// corresponding transaction_types which is causing a mismatch in Loan_Scheduler::Analyze_Schedule
	// for the case of a new or scheduled event.  The solution is to simply include the mismatching
	// event_type.name_short in the result set using a UNION above.
	// -------------------------------------------------------------------------------------------------

	$db = ECash::getMasterDb();
	$results = $db->query($sql);
	$periods = array();
	while ($row = $results->fetch(PDO::FETCH_OBJ))
	{
		$periods[$row->name_short] = $row;
	}

	return $periods;
}

function Has_A_Scheduled_Event($application_id)
{
	$query = "
		-- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			SELECT
				count(*) as 'count'
			FROM
				event_schedule
			WHERE
				application_id =  {$application_id}
			AND
				event_status = 'scheduled'";

	$db = ECash::getMasterDb();
	$result = $db->query($query);
	$row = $result->fetch(PDO::FETCH_OBJ);

	if ($row->count > 0)
	{
		return true;
	}

	return false;
}

function fetchCashlineSchedule($application_id)
{
	$query = "
		SELECT
			ct.transaction_date date,
			ct.transaction_type type,
			ct.transaction_amount amount,
			ct.transaction_amount_paid amount_paid,
			ct.transaction_date_paid date_paid,
			ct.transaction_balance balance,
			ct.transaction_due_date date_due
		FROM
			cl_transaction ct
			JOIN cl_customer clc USING (customer_id)
		WHERE
			clc.application_id = {$application_id} AND
			ct.transaction_date >= (
				SELECT MAX(transaction_date)
				FROM cl_transaction
				WHERE
					transaction_type = 'advance' AND
					customer_id = clc.customer_id
			)
		ORDER BY date
	";

	$db = ECash::getMasterDb();
	$result = $db->query($query);

	return $result->fetchAll(PDO::FETCH_ASSOC);
}

function fetchCashlineNotes($application_id)
{
	$query = "
		SELECT
			cn.note_date date,
			cn.note_reference reference,
			cn.note_date_next_action action_date,
			cn.note_login_id login_id,
			cn.note_body body
		FROM
			cl_notes cn
			JOIN cl_customer clc USING (customer_id)
		WHERE
			clc.application_id = {$application_id}
		ORDER BY note_date
	";

	$db = ECash::getMasterDb();
	$result = $db->query($query);

	return $result->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch the schedule for an application
 *
 * @param integer $application_id
 * @return array of event objects
 */
function Fetch_Schedule($application_id, $load_bank_info = TRUE)
{
	$query = "
	(
	SELECT et.name_short as 'type',
            es.event_schedule_id,
            es.date_modified,
            DATE_FORMAT(es.date_modified, '%m/%d/%Y %H:%i:%S') AS date_modified_display,
            es.event_type_id as 'type_id',
            NULL as 'transaction_register_id',
            NULL as 'ach_id',
            NULL as 'clearing_type',
            es.origin_id,
            es.origin_group_id,
            es.context,
            et.name as 'event_name',
            et.name_short as 'event_name_short',
            es.amount_principal as 'principal_amount',
            es.amount_non_principal as 'fee_amount',
            ea.principal as 'principal',
            ea.service_charge as 'service_charge',
            ea.fee as 'fee',
            ea.irrecoverable as 'irrecoverable',
            es.date_event as 'date_event',
            DATE_FORMAT(es.date_event, '%m/%d/%Y') AS date_event_display,
            es.date_effective as 'date_effective',
            DATE_FORMAT(es.date_effective, '%m/%d/%Y') AS date_effective_display,
            es.event_status as 'status',
            NULL as 'date_registered',
            es.configuration_trace_data as 'comment',
    		NULL as 'ach_return_code_id',
            NULL as 'return_date',
            NULL as 'return_date_display',
            NULL as 'return_code',
            NULL as is_fatal,
            dces.company_id as 'debt_consolidation_company_id',
            es.is_shifted as is_shifted,
			NULL AS bank_aba,
			NULL AS bank_account,
			NULL AS encryption_key_id,
			NULL AS current_bank_aba,
			NULL AS current_bank_account,
			aaes.agent_affiliation_id AS agent_affiliation
    FROM event_schedule es
    JOIN event_type et USING (event_type_id)
    JOIN (
      SELECT
	  easub.event_schedule_id,
	  SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
	  SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
	  SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
	  SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
	FROM
	  event_amount easub
	  LEFT JOIN event_amount_type eat USING (event_amount_type_id)
		WHERE easub.application_id = {$application_id}
	GROUP BY easub.event_schedule_id) ea USING (event_schedule_id)
	LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
	LEFT JOIN agent_affiliation_event_schedule AS aaes USING (event_schedule_id)
    WHERE es.application_id = {$application_id}
    AND es.event_status = 'scheduled'
	)
	UNION
	(
    SELECT  tt.name_short as 'type',
            tr.event_schedule_id,
            tr.date_modified,
            DATE_FORMAT(tr.date_modified, '%m/%d/%Y %H:%i:%S') AS date_modified_display,
            tr.transaction_type_id as 'type_id',
            tr.transaction_register_id,
            tr.ach_id,
            tt.clearing_type,
            es.origin_id,
            es.origin_group_id,
            es.context,
            tt.name as 'event_name',
            tt.name_short as 'event_name_short',
            IF(tt.affects_principal LIKE 'yes', tr.amount, 0.00) as 'principal_amount',
            IF(tt.affects_principal LIKE 'yes', 0.00, tr.amount) as 'fee_amount',
            ea.principal as 'principal',
            ea.service_charge as 'service_charge',
            ea.fee as 'fee',
            ea.irrecoverable as 'irrecoverable',
            DATE(es.date_event) as 'date_event',
            DATE_FORMAT(es.date_event, '%m/%d/%Y') AS date_event_display,
            tr.date_effective,
            DATE_FORMAT(tr.date_effective, '%m/%d/%Y') AS date_effective_display,
            tr.transaction_status as 'status',
            DATE_FORMAT(tr.date_created, '%m/%d/%Y') AS date_registered,
            es.configuration_trace_data as 'comment',
            arc.ach_return_code_id as ach_return_code_id,
            CASE
              WHEN tt.clearing_type = 'ach' AND ar.ach_report_id IS NOT NULL
              THEN ar.date_request
              ELSE
                (
                    SELECT th_1.date_created
                      FROM transaction_history th_1
                      WHERE
                        th_1.transaction_register_id = tr.transaction_register_id
                        AND tr.transaction_status = 'failed'
                        AND th_1.status_after = 'failed'
                      ORDER BY
                        th_1.date_created DESC
                      LIMIT 1
                )
            END as 'return_date',
            CASE
              WHEN tt.clearing_type = 'ach' AND ar.ach_report_id IS NOT NULL
              THEN DATE_FORMAT(ar.date_request, '%m/%d/%Y %H:%i:%S')
              ELSE
              	(
              		SELECT DATE_FORMAT(th_1.date_created, '%m/%d/%Y %H:%i:%S')
              		  FROM transaction_history th_1
              		  WHERE
              		  	th_1.transaction_register_id = tr.transaction_register_id
              		  	AND tr.transaction_status = 'failed'
              		  	AND th_1.status_after = 'failed'
              		  ORDER BY
              		  	th_1.date_created DESC
              		  LIMIT 1
              	)
            END as return_date_display,
            IF(tt.clearing_type = 'card', cp.reason_code, arc.name_short) as 'return_code',
            -- arc.is_fatal as is_fatal,
	    IF(tt.clearing_type = 'card', cpr.fatal_fail, arc.is_fatal) as is_fatal,
            dces.company_id as 'debt_consolidation_company_id',
            es.is_shifted as is_shifted,
			ach.bank_aba,
			bank_account.bank_account,
			bank_account.encryption_key_id,
			NULL as current_bank_aba,
			NULL as current_bank_account,
			aaes.agent_affiliation_id AS agent_affiliation
    FROM    transaction_register tr
    JOIN event_schedule AS es USING (event_schedule_id)
	LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
	LEFT JOIN agent_affiliation_event_schedule AS aaes USING (event_schedule_id)
    LEFT JOIN (
		SELECT
			easub.transaction_register_id,
			SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
			SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
			SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
			SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
		FROM	event_amount AS easub
		LEFT JOIN event_amount_type AS eat USING (event_amount_type_id)
		WHERE easub.application_id = {$application_id}
		GROUP BY easub.transaction_register_id
    ) ea USING(transaction_register_id)
    LEFT JOIN transaction_type AS tt USING (transaction_type_id)
    LEFT JOIN ach USING (ach_id)
    LEFT JOIN bank_account ON ach.bank_account = bank_account.bank_account_id
    LEFT JOIN ach_report AS ar USING (ach_report_id)
    LEFT JOIN ach_return_code AS arc USING (ach_return_code_id)
    -- card
    LEFT JOIN card_process AS cp USING (card_process_id)
    LEFT JOIN card_process_response AS cpr USING (reason_code)
    WHERE tr.application_id = {$application_id}
	)
	ORDER BY date_event, date_effective, principal_amount asc, fee_amount ASC ";

	$db = ECash::getMasterDb();
	$schedule = array();
	$result = $db->query($query);
	
	if($load_bank_info)
	{
		$crypt = new ECash_Models_Encryptor($db);
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$bank_info = $app_client->getBankInfo($application_id);
	}
	
	while($row = $result->fetch(PDO::FETCH_OBJ))
	{
		if(!empty($bank_info))
		{
			$row->current_bank_aba = $bank_info->bank_aba;
			$row->current_bank_account = $bank_info->bank_account;
		}
		if($load_bank_info)
		{
			$row->bank_account = trim($crypt->decrypt($row->bank_account, $row->encryption_key_id));
		}
		$event = Schedule_Event::Load_From_Row($row);
		$event->amounts = Event_Amount::Load_Amounts_From_Fetch_Schedule_Row($row);
		$schedule[] = $event;
	}

	return $schedule;
}

function Remove_Schedule_Amounts($schedule_id, $register_id = null)
{
	settype($schedule_id, 'integer');
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();
	$log->Write("[Agent:{$_SESSION['agent_id']}][Schedule ID:{$schedule_id}][Register ID:{$register_id}] Removing event amounts");
	if ($register_id)
	{
		$register_id_query = "AND transaction_register_id = '$register_id'";
	}
	else
	{
		$register_id_query = "";
	}
	$query = <<<END_SQL
DELETE FROM
	event_amount
  WHERE
	event_schedule_id = $schedule_id
	$register_id_query
END_SQL;

	$db->exec($query);

	$query = "-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "\n".<<<END_SQL
UPDATE
	arrangement_history
  SET
	amount_payment_principal = 0,
	amount_payment_non_principal= 0
  WHERE
	event_schedule_id = $schedule_id
END_SQL;

	$db->exec($query);
}

function Fetch_Schedule_Amounts($schedule_id)
{
	settype($schedule_id, 'integer');
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	$log->Write("[Agent:{$_SESSION['agent_id']}][Schedule ID:{$schedule_id}] Fetching Balance Information");
	$query = <<<END_SQL
SELECT ea.*, eat.name_short as event_amount_type
  FROM
	event_amount ea
	LEFT JOIN event_amount_type eat USING(event_amount_type_id)
  WHERE
	ea.event_schedule_id = $schedule_id
END_SQL;

	$result = $db->query($query);

   return $result->fetchAll(PDO::FETCH_OBJ);
}

// Returns true if application has events in the event schedule of the event
// names passed as an array.
function Application_Has_Events_By_Event_Names($application_id, $event_names)
{
    settype($application_id, 'integer');
    $db = ECash::getMasterDb();

    $event_names_list = "('" . implode("','",$event_names) . "')";

    $query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT	COUNT(*) as event_count
		FROM	event_schedule AS es
		JOIN 	event_type et USING (event_type_id)
		WHERE
				es.application_id = $application_id
		AND
				et.name_short IN $event_names_list ";

    $result = $db->query($query);

    $row = $result->fetch(PDO::FETCH_OBJ);

    if ($row->event_count > 0)
		return true;
	else
		return false;
}

/**
 * Fetch_Balance_Total_By_Event_Names
 * Fetches the total for events with a certain name, of a certain type.
 *
 * @param integer $application_id The app_id you want to get the amount for.
 * @param array $event_names the names of the events you want the total for (like 'assess_fee_lien')
 * @param array $event_types The list of event amount types you want to include in this balance ('fee','principal','service_charge').   Null includes all types.
 * @return float the total amount.
 */
function Fetch_Balance_Total_By_Event_Names($application_id, $event_names, $event_types = null)
{
	settype($application_id, 'integer');
	$db = ECash::getMasterDb();
	$event_type_where = '';
	$event_names_list = "('" . implode("','",$event_names) . "')";

	if (is_array($event_types) && sizeof($event_types>0))
	{
		$event_types_list = "('" . implode("','", $event_types). "')";
		$event_type_where = "AND eat.name_short IN {$event_types_list}";
	}
	$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT
				SUM(IF( tr.transaction_status IN ('complete', 'pending') {$event_type_where}, ea.amount, 0)) total_amount
		FROM	event_amount AS ea
		JOIN 	event_amount_type AS eat USING (event_amount_type_id)
		JOIN 	event_schedule AS es ON es.event_schedule_id =  ea.event_schedule_id
		JOIN 	event_type AS et ON et.event_type_id = es.event_type_id
		JOIN 	transaction_register AS tr ON tr.event_schedule_id = es.event_schedule_id
		WHERE
				ea.application_id = $application_id
		AND
				et.name_short IN $event_names_list ";


    $result = $db->query($query);

    if ($row = $result->fetch(PDO::FETCH_OBJ))
		return $row->total_amount;
	else
		return 0;
}

function Fetch_Balance_Information($application_id)
{
	settype($application_id, 'integer');
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	// This should eventually pull from loan_snapshot_fly or loan_snapshot
	//$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Fetching Balance Information");
	$query = <<<END_SQL
SELECT
    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending,
    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending') AND tt.name_short = 'assess_fee_delivery', ea.amount, 0)) delivery_fee
  FROM
	event_amount ea
	JOIN event_amount_type eat USING (event_amount_type_id)
	JOIN transaction_register tr USING(transaction_register_id)
	JOIN transaction_type tt USING (transaction_type_id)
  WHERE
	ea.application_id = $application_id
  GROUP BY ea.application_id

END_SQL;
	$result = $db->query($query);
	return $result->fetch(PDO::FETCH_OBJ);
}

function Record_Schedule_Entry($application_id, $event_type, $origin_id,
$origin_group_id, $date_event, $date_effective, $trace_data="", $context, $source_name = null, $is_shifted = false)
{
	$log = get_log("scheduling");

	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();
	$rules = $application->getBusinessRules();
	$reverse_map = Load_Reverse_Map($company_id);
	$event_type_map	= Load_Event_Type_Map($company_id);
	$ach_event_types = Load_ACH_Event_Types($company_id);

	if($event_type_map == NULL ) throw new Exception("No Event Type Map!");
	if(($event_type_map[$event_type] == NULL ) &&
	($reverse_map[$event_type] == NULL) && (!ctype_digit((string) $event_type)))
	throw new Exception("Event Type does not exist in Map: $event_type");

	$disp_origin_id = $origin_id;
	$disp_origin_group_id = $origin_group_id;

	if($origin_id == '') { $disp_origin_id = 'NULL'; }
	if($origin_group_id == '') 	{ $disp_origin_group_id = 'NULL'; }

	try
	{
		// We'll have problems if this field is blank.

		if (ctype_digit((string) $event_type))
		{
			$etid = $event_type;
		}
		elseif (isset($event_type_map[$event_type]))
		{
			$etid = $event_type_map[$event_type];
		}
		else
		{
			$etid = $reverse_map[$event_type];
		}

		/**
		 * This is a confusing piece of code, but basically it checks to see if
		 * the date_event (Action date) is for today.  If it is, it checks to
		 * see if the batch is closed and adjusts the dates appropriately.  If
		 * it isn't but today is a not a business day we'll keep the event date
		 * the same but change the effective date to two business days ahead.
		 *
		 * The reason we're not adjusting the event_date if it's not a business
		 * day is because some companies will send their batches on the weekend.
		 */
		$date_event_stamp = strtotime($date_event);
		if($date_event_stamp === strtotime(date("Y-m-d")))
		{
			$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());

			// If it's an ACH type event ...
			if(in_array($etid, $ach_event_types))
			{
				// If the batch has already run, adjust it
				if(Has_Batch_Closed($company_id) && in_array($etid, $ach_event_types))
				{
					$allow_batch = false;
					/*
						if today is friday and allows sat batch or
						today is saturday and allow sun batch  or
						tommorrow is a holiday and allow holiday
						move date_event forward 1 calendar day and process
					*/
					if(($rules['ach_weekend_batch']['allow_holiday'] == 'Yes' && $pdc->Is_Holiday(strtotime($pdc->Get_Calendar_Days_Forward($date_event, 1)))) ||
						($rules['ach_weekend_batch']['allow_sun'] == 'Yes' && date("D", $date_event_stamp) == 'Sat') ||
						($rules['ach_weekend_batch']['allow_sat'] == 'Yes' && date("D", $date_event_stamp) == 'Fri'))
					{
						$date_event = $pdc->Get_Calendar_Days_Forward($date_event, 1);
						$allow_batch = true;
					}
					/*
						if today is friday and allow sun batch but not sat batch
						move date_event forward 2 calendar days and process
					*/
					elseif($rules['ach_weekend_batch']['allow_sun'] == 'Yes' && date("D", $date_event_stamp) == 'Fri')
					{
						$date_event = $pdc->Get_Calendar_Days_Forward($date_event, 2);
						$allow_batch = true;

					}
					/*
						default to next business day
					*/
					else
					{
						$date_event = $pdc->Get_Next_Business_Day($date_event);
						$date_effective = $pdc->Get_Next_Business_Day($date_event);

					}
					/*
						Determine the dates to move the date_event and date_effective by business rules
					*/
					if($allow_batch)
					{
						if($rules['ach_weekend_batch']['day_type'] == 'Business')
						{
							$date_event     = $pdc->Get_Business_Days_Forward($date_event, $rules['ach_weekend_batch']['action_forward']);
							$date_effective = $pdc->Get_Business_Days_Forward($date_event, $rules['ach_weekend_batch']['effective_forward']);
						}
						else
						{
							$date_event     = $pdc->Get_Calendar_Days_Forward($date_event, $rules['ach_weekend_batch']['action_forward']);
							$date_effective = $pdc->Get_Calendar_Days_Forward($date_event, $rules['ach_weekend_batch']['effective_forward']);
						}
					}

					$trace_data .= " [Adjusted for Batch]";
				}
				// The date_event is a weekend or holiday, adjust it
				else if(! $pdc->isBusinessDay($date_event_stamp))
				{
					if(($rules['ach_weekend_batch']['allow_holiday'] == 'Yes' && $pdc->Is_Holiday($date_event_stamp)) ||
						($rules['ach_weekend_batch']['allow_sun'] == 'Yes' && date("D", $date_event_stamp) == 'Sun') ||
						($rules['ach_weekend_batch']['allow_sat'] == 'Yes' && date("D", $date_event_stamp) == 'Sat'))
					{
						if($rules['ach_weekend_batch']['day_type'] == 'Business')
						{
							$date_event     = $pdc->Get_Business_Days_Forward($date_event, $rules['ach_weekend_batch']['action_forward']);
							$date_effective = $pdc->Get_Business_Days_Forward($date_event, $rules['ach_weekend_batch']['effective_forward']);
						}
						else
						{
							$date_event     = $pdc->Get_Calendar_Days_Forward($date_event, $rules['ach_weekend_batch']['action_forward']);
							$date_effective = $pdc->Get_Calendar_Days_Forward($date_event, $rules['ach_weekend_batch']['effective_forward']);
						}

					}
					else
					{
						$date_effective = $pdc->Get_Business_Days_Forward($date_event, 2);
					}
					$trace_data .= " [Adjusted Due Date for Weekend/Holiday]";
				}
			}
		}

		$source_map = Get_Source_Map();
		if (empty($source_name))
		{
			$source_id = $source_map[DEFAULT_SOURCE_TYPE];
		}
		else
		{
			$source_id = $source_map[$source_name];
		}

		$is_shifted = ($is_shifted ? 1 : 0);
		$db = ECash::getMasterDb();
		$query = "
			INSERT INTO event_schedule
				(
					date_created,
					company_id,
					application_id,
					event_type_id,
					origin_id,
					origin_group_id,
					configuration_trace_data,
					event_status,
					date_event,
					date_effective,
					context,
					source_id,
					is_shifted
				)
			VALUES
				(
					current_timestamp,
					{$company_id},
					$application_id,
					{$etid},
					$disp_origin_id,
					$disp_origin_group_id,
					{$db->quote($trace_data)},
					{$db->quote('scheduled')},
					{$db->quote($date_event)}, {$db->quote($date_effective)},
					{$db->quote($context)}, {$db->quote($source_id)},
					{$db->quote($is_shifted)}
				) ";


		$query = preg_replace("/(?<=[\n\t])''(?=[\n,])/", "NULL", $query);
		$db->exec($query);
		$event_schedule_id = $db->lastInsertId();
		if(($context == "arrangement") || ($context == "partial") || ($context == "arrange_next"))
		{
			$agid = isset($_SESSION['agent_id']) 	? $_SESSION['agent_id']
			: "(select agent_id from agent where login = 'ecash_support' LIMIT 1)";

			$query = "
				INSERT INTO arrangement_history
					(
						date_created,
						company_id,
						date_payment,
						agent_id,
						event_type_id,
						event_schedule_id,
						application_id
					)
				VALUES
					(
						current_timestamp,
						$company_id,
						'$date_effective',
						$agid,
						$etid,
						$event_schedule_id,
						$application_id
					)
			";

			$db->exec($query);
		}
		if ($origin_group_id == NULL)
		{
			$upd_query = "
			UPDATE event_schedule
			SET origin_group_id = {$event_schedule_id}
			WHERE event_schedule_id = {$event_schedule_id} ";
			$db->exec($upd_query);
		}
	}
	catch(Exception $e)
	{
		$log->Write($e->getMessage());
		throw $e;
	}

	return $event_schedule_id;
}

//mantis:8673 - added $transaction_register_id
function Record_Event_Amount($application_id, $event_schedule_id, $amount_type, $amount, $num_reattempt = 0, $transaction_register_id = 0)
{
	if ($amount == 0)
	{
		//get_log('scheduling')->Write("Ignoring 0 value amount.");
		return 0;
	}
	get_log('scheduling')->Write("Adding new event amount to {$event_schedule_id} [Type:{$amount_type}] [Amount:{$amount}] [Reattempt:{$num_reattempt}]");
	settype($event_schedule_id, 'integer');
	settype($amount, 'float');
	settype($num_reattempt, 'integer');

	try
	{
		/**
		 * To prevent a possible case of mixed company id's, I'm pulling the Application
		 * up and getting the company_id off of it rather than fetching it from the
		 * ECash Object. [GF#17150][BR]
		 */
		$application = ECash::getApplicationById($application_id);
		$company_id = $application->getCompanyId();

		$amount_type_map = Retrieve_Event_Amount_Type_Map();
		$amount_query = <<<END_SQL
	INSERT INTO event_amount
	  (
		event_schedule_id,
		transaction_register_id,
		event_amount_type_id,
		amount,
		application_id,
		num_reattempt,
		company_id,
		date_created
	  )
	  VALUES
	  (
		{$event_schedule_id},
		{$transaction_register_id},
		{$amount_type_map[$amount_type]},
		{$amount},
		{$application_id},
		{$num_reattempt},
		{$company_id},
		current_timestamp
	  )
END_SQL;

		$db = ECash::getMasterDb();
		$db->exec($amount_query);
		$insert_id = $db->lastInsertId();

		// Update Arranagment History
		$history_amount_type = ($amount_type == "principal") ? "amount_payment_principal" : "amount_payment_non_principal";
		$history_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */ \n" . <<<END_SQL
	UPDATE arrangement_history
		SET {$history_amount_type} = {$history_amount_type} + {$amount}
	WHERE application_id = {$application_id} AND event_schedule_id = {$event_schedule_id}
END_SQL;

		$db->exec($history_query);
	}
	catch (Exception $e)
	{
		get_log('alert_errors')->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}][EventID:{$event_schedule_id}] Failed to create event: ".$e->getMessage());
		throw $e;
	}
	return $insert_id;
}

function Retrieve_Event_Amount_Type_Map ()
{
	static $map;

	if (empty($map))
	{
		$db = ECash::getMasterDb();
		$result = $db->query("SELECT event_amount_type_id, name_short FROM event_amount_type");

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$map[$row->name_short] = $row->event_amount_type_id;
		}
	}

	return $map;
}

function Record_Current_Scheduled_Events_To_Register($date_current, $application_id=NULL, $event_schedule_id=NULL, $mode='all', $do_not_use=NULL, $ach_provider_id=NULL)
{
	/*
	In the initial release, certain capabilities incorporated into the database design will not be implemented
	due to time constraints.
	The unimplemented features include the following:
	>	event-to-transaction explosion with prorated amount distribution to individual transactional components,
	where such proration is defined in the event_transaction mapping table;
	>	transaction spawning into n-tuples, each representing a fractional part of the raw scheduled amount;
	However, we WILL support event-to-transaction explosion with prorated amount distribution to two individual
	transactional components (one for principal and one for "non-principal"), where such proration is defined by the
	separate principal and non-principal amounts stored in event_schedule.

	We will use two-phase row selection here to prevent possible locking misbehavior if InnoDB didn't choose to use
	the "right" index (by application_id, date_event). Also, some or all of the event_transaction and transaction_type
	reference data rows would effectively be locked because it is referred to in the join, a completely unacceptable
	condition. Finally, if application_id weren't selected, ALL of the event_schedule rows would be locked, too.
	*/

	if ($ach_provider_id === NULL)
	{
		$ach_providers_query_join = "";
		$ach_providers_query_where = "";
	}
	else
	{
		$ach_provider_id = intval($ach_provider_id);
		$ach_providers_query_join = " JOIN application_ach_provider AS appr ON (appr.application_id = sched.application_id) ";
		$ach_providers_query_where = " AND appr.ach_provider_id = {$ach_provider_id} ";
	}

	$log = get_log("scheduling");

	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	if($application_id != NULL)
	{
		$application = ECash::getApplicationById($application_id);
		$company_id = $application->getCompanyId();
	}
	else
	{
		$company_id = ECash::getCompany()->company_id;
	}

	$reverse_map = Load_Reverse_Map($company_id);

	$application_balances = array();
	$transaction_register_ids	= array();
	$event_schedule_id_prev		= -1;

	//[#45234] Don't record events while in a hold status (ACH only [#46516])
	$app_data = ECash::getFactory()->getData('Application');
	$hold_status_ids = $app_data->getHoldingStatusIds();
	$placeholders = substr(str_repeat('?,', count($hold_status_ids)), 0, -1);
		
	$mode = strtolower(trim($mode));
	if ( !in_array($mode, array('all','ach','adjustment', 'external', 'accrued charge','landmark','card')))
	{
		throw new Exception("Invalid mode parameter passed to " . __METHOD__);
	}

	try
	{
		$db = ECash::getMasterDb();
		$query_sel1 = "
					SELECT
						sched.event_schedule_id,
						sched.application_id,
						   et.transaction_type_id,
						   tt.affects_principal,
						   tt.clearing_type,
						(
							SELECT count(*)
							FROM event_transaction etref
							WHERE etref.event_type_id = sched.event_type_id
							  AND etref.active_status = 'active'
						) as propagation_count,
						sched.amount_principal,
						sched.amount_non_principal,
						sched.date_effective,
						sched.date_event,
						sched.context
					FROM
						event_schedule		sched
					{$ach_providers_query_join}
					JOIN event_transaction AS et ON (et.event_type_id = sched.event_type_id)
					JOIN transaction_type AS tt ON (tt.transaction_type_id = et.transaction_type_id)
					LEFT OUTER JOIN 
						(SELECT application_id, COUNT(*) AS cust_no_ach
							FROM application_flag af 
							JOIN flag_type ft USING (flag_type_id) 
							WHERE name_short = 'cust_no_ach'
							AND af.active_status = 'active'
							GROUP BY application_id) cnaf
						ON (sched.application_id = cnaf.application_id)					
					WHERE
						(
							tt.clearing_type != 'ach'
							OR
							(
								tt.clearing_type = 'ach'
								AND cnaf.cust_no_ach IS NULL
							)
						)
						{$ach_providers_query_where}
						AND et.active_status		=  'active'
						AND sched.date_event		<= ?
						AND sched.event_status		=  'scheduled'
						AND sched.company_id		=  ? ";

		$values = array();
		$values[] = $date_current;
		$values[] = $company_id;
		
		if ($mode != 'all')
		{
			$query_sel1 .= " AND tt.clearing_type = ?";
			$values[] = $mode;
		}
		if (!empty($application_id))
		{
			$query_sel1 .= " AND sched.application_id = ?";
			$values[] = $application_id;
		}
		if (!empty($event_schedule_id))
		{
			$query_sel1 .= " AND sched.event_schedule_id = ?";
			$values[] = $event_schedule_id;
		}
		/**
		 * For GForge [#28213]  Could theoretically be used for specific application IDs
		 */
		if($mode == 'external' && empty($application_id) && empty($event_schedule_id))
		{
			$query_sel1 .= "
			UNION
					SELECT
						sched.event_schedule_id,
						sched.application_id,
						   et.transaction_type_id,
						   tt.affects_principal,
						   tt.clearing_type,
						(
							SELECT count(*)
							FROM event_transaction etref
							WHERE etref.event_type_id = sched.event_type_id
							  AND etref.active_status = 'active'
						) as propagation_count,
						sched.amount_principal,
						sched.amount_non_principal,
						sched.date_effective,
						sched.date_event,
						sched.context
					FROM
						event_schedule		sched
					JOIN event_transaction AS et ON (et.event_type_id = sched.event_type_id)
					JOIN transaction_type AS tt ON (tt.transaction_type_id = et.transaction_type_id)
					JOIN event_schedule sched2 ON (sched2.date_event = sched.date_event and sched2.application_id = sched.application_id)
					JOIN event_transaction AS et2 ON (et2.event_type_id = sched2.event_type_id)
					JOIN transaction_type AS tt2 ON (tt2.transaction_type_id = et2.transaction_type_id)
					WHERE
						et.active_status		=  'active'
						AND sched.date_event		<= ?
						AND sched.event_status		=  'scheduled'
						AND sched.company_id		=  ?
						AND tt.clearing_type = 'accrued charge'
						AND et2.active_status		=  'active'
						AND sched2.event_status		=  'scheduled'
						AND sched2.company_id		=  ?
						AND tt2.clearing_type = 'external'
			";
			$values[] = $date_current;
			$values[] = $company_id;
			$values[] = $company_id;			
		}


		$query_sel1 .= " ORDER BY application_id, date_effective, event_schedule_id";
		
		$result1 = $db->prepare($query_sel1);
		$result1->execute($values);
		$query_rows = array();		
		$application_ids = array();

		while($row = $result1->fetch(PDO::FETCH_OBJ))
		{
			$query_rows[] = $row;
			$application_ids[] = $row->application_id;
		}
		if(!empty($application_ids))
		{
			$bulkdata = ECash::getFactory()->getData('Application');
			$application_data = $bulkdata->getApplicationData($application_ids);
			if(empty($application_data))
			{
				throw new Exception('Bulk App Service Call Failed and returned no data in ' . __FUNCTION__);
			}	
		}
	
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		foreach($query_rows as $row1)
		{
			$row_status_id = $asf->toId($application_data[$row1->application_id]['application_status']);

			if(in_array($row_status_id, $hold_status_ids) || $application_data[$row1->application_id]['is_watched'] == 'yes')
			{
				continue;
			}
			$log->Write("[Agent:{$_SESSION['agent_id']}] Event ID {$row1->event_schedule_id}: Scheduled -> Registered");
			if ($row1->event_schedule_id != $event_schedule_id_prev)
			{
				//For any kind of arranged schedule we need to adjust the amount allocations at the time the event is registered.
				//The 'clearing_type not equal to ach' clause added for mantis:6725
				if (($row1->context == 'arrangement' || $row1->context == 'partial') && ($row1->clearing_type !== 'ach' && $row1->clearing_type !== 'card'))
				{
					//Before we go reallocating things all willy-nilly and potentially ruin Christmas, let's register
					//the currently scheduled service charges that we should be paying with this arrangement. AGEAN LIVE 14518
					$results = Record_Scheduled_Event_To_Register_Pending($date_current,$row1->application_id,NULL,'accrued charge');

					$amt = $row1->amount_principal + $row1->amount_non_principal;

					$balance_info = Fetch_Balance_Information($row1->application_id);
					$log->Write("Current Balance info - principal {$balance_info->principal_pending} - SC {$balance_info->service_charge_pending} - Fee {$balance_info->fee_pending}");
					$application_balances[$row1->application_id] = array(
					'principal' => $balance_info->principal_pending,
					'service_charge' => $balance_info->service_charge_pending,
					'fee' => $balance_info->fee_pending,
					);
					$log->Write("Current Balance info - principal {$balance_info->principal_pending} - SC {$balance_info->service_charge_pending} - Fee {$balance_info->fee_pending}");
					$newAmounts = AmountAllocationCalculator::generateAmountsFromBalance($amt, $application_balances[$row1->application_id]);

					Remove_Schedule_Amounts($row1->event_schedule_id);
					$amount_principal = 0;
					$amount_non_principal = 0;
					foreach ($newAmounts as $amount)
					{
						if ($amount->event_amount_type == 'principal')
						{
							$amount_principal = bcadd($amount_principal,$amount->amount,2);
						}
						else
						{
							$amount_non_principal = bcadd($amount_non_principal,$amount->amount,2);
						}
						Record_Event_Amount($row1->application_id, $row1->event_schedule_id, $amount->event_amount_type, $amount->amount);
					}
				}
				else
				{
					$amount_principal = $row1->amount_principal;
					$amount_non_principal = $row1->amount_non_principal;
				}
				$amount_cap = abs($row1->amount_principal + $row1->amount_non_principal);
				$tot_amount	= 0;

				//[#45234] If date_event < today adjust the
				//transaction register's date effective according to
				//the clearing type
				$holidays = Fetch_Holiday_List();
				$pdc = new Pay_Date_Calc_3($holidays);
				$today = date('Y-m-d');
				if(strtotime($row1->date_event) < strtotime($today))
				{
				    if($row1->clearing_type == 'ach' || $row1->clearing_type == 'card')
				    {
				        $row1->date_effective = $pdc->Get_Next_Business_Day($today);
					}
					else
					{
				        $row1->date_effective = $today;
				    }					
				}
				
				$process	= false;

				$query_sel2 = "
							SELECT
								event_schedule_id,
								event_status
							FROM
								event_schedule
							WHERE
									event_schedule_id = {$row1->event_schedule_id}
							AND	event_status	  = 'scheduled'
							FOR UPDATE ";

				$result2 = $db->query($query_sel2);
				if ($result2->fetch(PDO::FETCH_OBJ))
				{
					$process = TRUE;
				}
			}

			if ($process)
			{
				$amount = 0;
				$event_types  =  Retrieve_Event_Amount_Type_Map($db);
				if ($row1->propagation_count == 1)
				{
					// This will be the sum of both the principal and non-principal amount buckets
					$amount = $amount_principal + $amount_non_principal;
					$event_amount_type_check = " ";
				}
				elseif ($row1->affects_principal == 'yes')
				{
					$amount = $amount_principal;
					$event_amount_type_check = "AND event_amount_type_id = '{$event_types['principal']}'";
				}
				else
				{
					$amount = $amount_non_principal;
					$event_amount_type_check = "AND event_amount_type_id <> '{$event_types['principal']}'";
				}

				//Original note: Implement failsafe in case there are more than two component transactions defined for this event type
				//New note: While failsafes are fine and good, this code is preventing an internal adjustment to be created that
				//balances out principal amounts. So, if this really needs a failsafe it needs to be done in a different way.
				//				$tot_amount += abs($amount);
				//				if ($tot_amount > $amount_cap)
				//				{
				//					$amount = 0;
				//				}

				if ($amount != 0 || $do_not_use == 'db_sodomizer' )
				{
					$source_map = Get_Source_Map();
					$source_id = $source_map[DEFAULT_SOURCE_TYPE];
					$agent_id = Fetch_Current_Agent();

					$query_ins = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						INSERT INTO transaction_register
							(
								date_created,
								company_id,
								application_id,
								transaction_type_id,
								transaction_status,
								event_schedule_id,
								amount,
								date_effective,
								source_id,
								modifying_agent_id
							)
						VALUES
							(
								current_timestamp,
								{$company_id},
								{$row1->application_id},
								{$row1->transaction_type_id},
								'new',
								{$row1->event_schedule_id},
								$amount,
								'{$row1->date_effective}',
								'{$source_id}',
								'{$agent_id}'
							) ";


					$db->exec($query_ins);
					if (!empty($row1->event_schedule_id))
					{
						$transaction_register_id_new = $db->lastInsertId();
						//We only want to populate this array if we're only updating something relatively specific.
						//If we're updating all of a certain transaction type, the results could be monstrous and lead to memory issues.
						if (!empty($event_schedule_id) || !empty($application_id))
						{
							$transaction_register_ids[] = $transaction_register_id_new;
						}
						$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$row1->application_id}] Transaction ID added: ". $transaction_register_id_new);

						$event_amount_query = "
						UPDATE event_amount
						  SET
							transaction_register_id = {$transaction_register_id_new}
						  WHERE
							event_schedule_id = {$row1->event_schedule_id}
							{$event_amount_type_check}
						";

						$db->exec($event_amount_query);
					}

				}
			}

			if ($row1->event_schedule_id != $event_schedule_id_prev)
			{
				if ($process)
				{

					$query_upd = "
						UPDATE event_schedule
						SET
						event_status = 'registered'
					WHERE
						event_schedule_id = {$row1->event_schedule_id}
						AND event_status  = 'scheduled' ";

					$db->exec($query_upd);
				}
			}
			$event_schedule_id_prev = $row1->event_schedule_id;
		}
	}
	catch(Exception $e)
	{
		throw $e;
	}

	//We're only returning an array of transaction_register_ids if we're going to have a relatively small set based on
	//relatively specific criteria.  Otherwise, the consequences could be dire!!!!!!!!
	if (!empty($event_schedule_id) || !empty($application_id))
	{
		return $transaction_register_ids;
	}

	return true;
}

function Remove_One_Unregistered_Event_From_Schedule($application_id, $event_schedule_id)
{
	settype($application_id, 'int');
	settype($event_schedule_id, 'int');
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Removing scheduled event $event_schedule_id");

	$event_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "
	SELECT *
	  FROM event_schedule
	  WHERE event_schedule_id = {$event_schedule_id}
	  	AND application_id = {$application_id}";

	$result = $db->query($event_query);

	if (!$event = $result->fetch(PDO::FETCH_OBJ))
	{
		throw new Exception("Could not find event [$event_schedule_id] in application [$application_id]");
	}

	if ($event->event_status != 'scheduled')
	{
		throw new Exception("Event [$event_schedule_id] was not a scheduled event. Only unscheduled events can be removed with Remove_One_Unregistered_Event_From_Schedule");
	}

	$delete_event_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "
	DELETE FROM event_schedule
	  WHERE event_schedule_id = {$event_schedule_id}
	  	AND application_id = {$application_id}";

	$db->exec($delete_event_query);

	$delete_event_amount_query = "
	DELETE FROM event_amount
	  WHERE event_schedule_id = {$event_schedule_id}";

	$db->exec($delete_event_amount_query);

	//asm 38, for early payout, remove associated adjustment
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();
	$event_type_id = $event->event_type_id;
	$et = ECash::getFactory()->getModel('EventType');
	$et->loadBy(array('event_type_id' => $event_type_id,));
	$name_short = $et->name_short;

	switch ($name_short)
	{
		case 'payout':
		case 'card_payout':
			$et = ECash::getFactory()->getModel('EventType');
			$et->loadBy(array(
			             		'company_id' => $company_id,
						'name_short' => 'adjustment_internal',
			));
			$event_type_id = $et->event_type_id;

			$es = ECash::getFactory()->getModel('EventSchedule');
			$es_array = $es->loadAllBy(array(
							'application_id' => $application_id,
							'event_type_id' => $event_type_id,
							'context' => 'payout',
							'event_status' => 'registered',
			));

			if (count($es_array) > 0)
			{
				foreach ($es_array as $es_record)
				{
					$tr_check = ECash::getFactory()->getModel('TransactionRegister');
					$tr_check_array = $tr_check->loadAllBy(array(
										     'event_schedule_id' => $es_record->event_schedule_id,
										     'transaction_status' => 'complete',
					));
					if (count($tr_check_array) > 0)
					{
						foreach ($tr_check_array as $tr_check_array_record)
						{
							$event_schedule_id_delete = $tr_check_array_record->event_schedule_id;

							//delete from transaction history
							$th = ECash::getFactory()->getModel('TransactionHistory');
							$th_delete = ECash::getFactory()->getModel('TransactionHistory');
							$th_array = $th->loadAllBy(array('transaction_register_id' => $tr_check_array_record->transaction_register_id,
							));
							if (count($th_array) > 0)
							{
								foreach ($th_array as $th_record)
								{
									$loaded = $th_delete->loadBy(array('transaction_history_id' => $th_record->transaction_history_id,));
									if ($loaded)
									{
									     	$th_delete->delete();
																														}
								}
							}

							//delete from transaction ledger
							$tl = ECash::getFactory()->getModel('TransactionLedger');
							$tl_delete = ECash::getFactory()->getModel('TransactionLedger');
							$tl_array = $tl->loadAllBy(array('transaction_register_id' => $tr_check_array_record->transaction_register_id,));
							if (count($tl_array) > 0)
							{
								foreach ($tl_array as $tl_record)
								{
									$loaded = $tl_delete->loadBy(array('transaction_ledger_id' => $tl_record->transaction_ledger_id,));
									if ($loaded)
									{
										$tl_delete->delete();
									}
								}
							}

							//delete from transaction register
							$tr = ECash::getFactory()->getModel('TransactionRegister');
							$loaded = $tr->loadBy(array('transaction_register_id' => $tr_check_array_record->transaction_register_id,));
							if ($loaded)
							{
								$tr->delete();
							}

							//remove from event amount
							$ea = ECash::getFactory()->getModel('EventAmount');
							$ea_delete = ECash::getFactory()->getModel('EventAmount');
							$ea_array = $ea->loadAllBy(array('transaction_register_id' => $tr_check_array_record->transaction_register_id,
											'event_schedule_id' => $event_schedule_id_delete,
							));
							if (count($ea_array) > 0)
							{
								foreach ($ea_array as $ea_record)
								{
									$loaded = $ea_delete->loadBy(array('event_amount_id' => $ea_record->event_amount_id,));
									if ($loaded)
									{
										$ea_delete->delete();
									}
								}
							}
							
							//remove from event schedule
							$es_delete = ECash::getFactory()->getModel('EventSchedule');
							$loaded = $es_delete->loadBy(array('event_schedule_id' => $event_schedule_id_delete));
							if ($loaded)
							{
								$es_delete->delete();
							}
						}
					}
				}
			}

			break;

		default:
			break;
	}
}


function Remove_Unregistered_Events_From_Schedule($application_id)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Removing scheduled events");

	try
	{
		if ($application_id == null) throw new Exception("No app id supplied");
		/*
		$event_map = array();
		$application = ECash::getApplicationById($application_id);
		$company_id = $application->getCompanyId();
		$et = ECash::getFactory()->getModel('EventType');
		$et_array = $et->loadAllBy(array(
						'company_id' => $company_id,
						'active_status' => 'active',
		));
		if (count($et_array) > 0)
		{
			foreach ($et_array as $et_record)
			{
				$event_map[$et_record->event_type_id] = $et_record->name_short;
			}
		}
		*/

		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT event_schedule_id, event_type_id
				FROM event_schedule
				WHERE event_status = 'scheduled'
				AND application_id = {$application_id} ";
		$result = $db->query($sql);
		$ids = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->event_schedule_id;
			/*
			if ($event_map[$row->event_type_id] == 'payout')
			{
				Remove_One_Unregistered_Event_From_Schedule($application_id, $row->event_schedule_id);
			}
			*/
		}

		if (count($ids))
		{
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					DELETE FROM event_amount
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
			$sql = "
					DELETE FROM event_schedule
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
		}

		foreach ($ids as $id)
		{
			Remove_Arrangement_Affiliation(null, $id);
		}
	}
	catch(Exception $e)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:$application_id] Removing scheduled events failed!");
		throw $e;
	}

	return true;
}

function Remove_Unregistered_ACH_Events_From_Schedule($application_id, $remove_accruals = FALSE)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Removing scheduled ACH events");
	
	if ($remove_accruals)
	{
		$remove_accruals_sql = " OR evt.name_short IN ('assess_service_chg') ";
	}
	else
	{
		$remove_accruals_sql = "";
	}

	try
	{
		if ($application_id == null) throw new Exception("No app id supplied");

		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT DISTINCT
					es.event_schedule_id,
					es.event_type_id
				FROM
					event_schedule AS es
				JOIN
					event_amount AS ea ON (ea.event_schedule_id = es.event_schedule_id)
				JOIN
					event_type AS evt ON (evt.company_id = es.company_id
									AND evt.event_type_id = es.event_type_id)
				JOIN
					event_transaction AS et ON (et.company_id = es.company_id
									AND et.event_type_id = es.event_type_id)
				JOIN
					transaction_type AS tt ON (tt.company_id = et.company_id
									AND tt.transaction_type_id = et.transaction_type_id)
				WHERE
					es.event_status = 'scheduled'
				AND
					es.application_id = {$application_id}
				AND
					(
						(tt.clearing_type IN ('ach') AND ea.amount < 0)
						{$remove_accruals_sql}
					)
		";
		$result = $db->query($sql);
		$ids = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->event_schedule_id;
			/*
			if ($event_map[$row->event_type_id] == 'payout')
			{
				Remove_One_Unregistered_Event_From_Schedule($application_id, $row->event_schedule_id);
			}
			*/
		}

		if (count($ids))
		{
			$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					DELETE FROM event_amount
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
			$sql = "
					DELETE FROM event_schedule
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
		}
	}
	catch(Exception $e)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:$application_id] Removing scheduled ACH events failed!");
		throw $e;
	}

	return true;
}

/* Same as Remove_Unregistered_Events_From_Schedule only it just does it on a schedule array and returns it, rather than changing in the database */
function Remove_Unregistered_Events_From_This_Schedule($schedule)
{
	$new_schedule = array();

	foreach ($schedule as $e)
	{
		if($e->status != 'scheduled') $new_schedule[] = $e;
	}

	return $new_schedule;
}

/**
 * Suspends paydown and payout events instead of removing them (mantis:4454)
 *
 * @param int $application_id application id from which to remove and suspend events
 * @return boolean returns TRUE if the transactions are successfull
 */
function Remove_And_Suspend_Events_From_Schedule($application_id)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();


	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Removing and suspending scheduled events");

	try
	{
		if ($application_id === NULL)
			throw new Exception("No app id supplied");

		// Removing:
		$sql = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT es.event_schedule_id
				FROM event_schedule es
				JOIN event_type et ON (es.event_type_id = et.event_type_id)
				WHERE es.event_status = 'scheduled'
				AND et.name_short NOT IN ('paydown','payout','card_paydown','card_payout')
				AND es.application_id = {$application_id} ";
		$result = $db->query($sql);
		$ids = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->event_schedule_id;
		}

		if (count($ids))
		{
			$sql = "
					DELETE FROM event_amount
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
			$sql = "
					DELETE FROM event_schedule
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
		}

		foreach ($ids as $id)
		{
			Remove_Arrangement_Affiliation(NULL, $id);
		}

		// Suspending:
		$sql = "
				SELECT es.event_schedule_id
				FROM event_schedule es
				JOIN event_type et ON (es.event_type_id = et.event_type_id)
				WHERE es.event_status = 'scheduled'
				AND et.name_short IN ('paydown','payout','card_paydown','card_payout')
				AND es.application_id = {$application_id} ";
		$result = $db->query($sql);
		$events = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$events[] = $row->event_schedule_id;
		}

		if (count($events))
		{
			$sql = "
					UPDATE event_schedule
					SET event_status = 'suspended'
					WHERE event_schedule_id IN(".implode(', ', $events).")
					";
			$db->exec($sql);
		}

		foreach ($events as $event)
		{
			Remove_Arrangement_Affiliation(NULL, $event);
		}
	}
	catch(Exception $e)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:$application_id] Removing and suspending scheduled events failed!");
		throw $e;
	}

	return TRUE;
}

function Record_Scheduled_Event_To_Register_Pending($date_event, $application_id, $event_schedule_id, $mode='all')
{
	$db = ECash::getMasterDb();
	$trids = Record_Current_Scheduled_Events_To_Register($date_event, $application_id, $event_schedule_id, $mode);
	$agent_id = Fetch_Current_Agent();
	foreach ($trids as $trid)
	{
		$upd_query = "
						UPDATE transaction_register
						SET transaction_status = 'pending',
							modifying_agent_id = '{$agent_id}'
						WHERE transaction_register_id = {$trid}
						AND transaction_status = 'new'";

		$db->exec($upd_query);
	//We're only returning an array of transaction_register_ids if we're going to have a relatively small set based on
	//relatively specific criteria.  Otherwise, the consequences could be dire!!!!!!!!
	}
	$app = ECash::getApplicationById($application_id);
	$engine = $app->getEngine();
	$engine->executeEvent('PENDING_TRANSACTION');

	return $trids;
}

/**
 * Records a failure on the transaction(s) based on the event_schedule_id
 *
 * @param int $application_id
 * @param int $event_schedule_id
 * @return bool true on success, false on failure
 */
function Record_Event_Failure($application_id, $event_schedule_id)
{
	$log = get_log("scheduling");
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Recording Transaction Failure(s) for event_schedule_id {$event_schedule_id}");

	$trids = Fetch_Transaction_IDs_For_Event($event_schedule_id);

	if(count($trids) == 0)
	return false;

	//It's totally possible for one of the transactions to not get failed successfully (because it's already failed)
	//But still successfully fail the rest. [W!-02-03-2009][#23170]
	$failure_success = false;
	foreach($trids as $trid)
	{
		$fail_result = Record_Transaction_Failure($application_id, $trid);
		if($fail_result == true)
		{
			$failure_success = true;
		}
	}
	return $failure_success;
}

function Record_Transaction_Failure($application_id, $trid = NULL)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Recording Transaction Failure for transaction {$trid}");
	$agent_id = Fetch_Current_Agent();

	Set_Loan_Snapshot($trid,"failed");

	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		UPDATE transaction_register
		SET transaction_status = 'failed',
			modifying_agent_id = '{$agent_id}'
		WHERE transaction_register_id = {$trid}
		AND application_id = {$application_id}
		AND transaction_status <> 'failed' ";

	if (!$db->exec($query))
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Manually setting transaction register entry {$trid} failed: " . print_r($db->errorInfo(), TRUE));
		return FALSE;
	}

	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		DELETE FROM transaction_ledger
		WHERE transaction_register_id = {$trid}
		AND application_id = {$application_id} ";

	$db->exec($query);

	return TRUE;
}

/**
 * Post the transaction_register items for a given event_schedule_id
 *
 * @param int $application_id
 * @param int $event_schedule_id
 * @param bool $manual_override passed to Post_Transaction
 * @return bool true on success, false on failure
 */
function Post_Recorded_Events($application_id, $event_schedule_id, $manual_override = false)
{
	$log = get_log("scheduling");
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Posting Transaction(s) for event_schedule_id {$event_schedule_id}");

	$trids = Fetch_Transaction_IDs_For_Event($event_schedule_id);

	if(count($trids) == 0)
	return false;

	foreach($trids as $trid)
	{
		$post_result = Post_Transaction($application_id, $trid, $manual_override);
		//removed because it was causing problems with manually setting transactions to complete
		//when one transaction was already complete and the other wasn't. This is only used for manually setting them. [jeffd][IMPACT LIVE #10788]
//		if($post_result === false)
//		return false;
	}
}

/**
 * Takes a row in the transaction register and marks it complete if it's in the 'new' or
 * 'pending' statuses ('failed' included when manual_override is true) and creates an
 * entry in the transaction ledger for the item.
 *
 * @param int $application_id
 * @param int $transaction_register_id
 * @param bool $manual_override - Used to override the safeguard against 'failed' transactions
 * @return bool True or False based on whether the inserts were successful
 */
function Post_Transaction($application_id, $transaction_register_id, $manual_override = false)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();
	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();

	if($manual_override == true)
	{
		$transaction_status_line = "transaction_status IN ('new','pending', 'failed')";
	}
	else
	{
		$transaction_status_line = "transaction_status IN ('new','pending')";
	}

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Posting transaction {$transaction_register_id}");
	$rows_inserted = 0;

	try
	{
		$query_sel = "
					SELECT
						transaction_type_id,
						amount,
						date_effective
					FROM
						transaction_register
					WHERE
						$transaction_status_line
						AND company_id = {$company_id}
						AND application_id = {$application_id}
						AND transaction_register_id	= {$transaction_register_id}
					FOR UPDATE ";

		$result = $db->query($query_sel);
		if ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$source_map = Get_Source_Map();
			$source_id = $source_map[DEFAULT_SOURCE_TYPE];
			$query_ins = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				INSERT INTO transaction_ledger
					(
						date_created,
						company_id,
						application_id,
						transaction_type_id,
						transaction_register_id,
						amount,
						date_posted,
						source_id
					)
				VALUES
					(
						current_timestamp,
						{$company_id},
						$application_id,
						{$row->transaction_type_id},
						$transaction_register_id,
						{$row->amount},
						'{$row->date_effective}',
						'$source_id'
					) ";

			$rows_inserted = $db->exec($query_ins);
			$agent_id = Fetch_Current_Agent();

			Set_Loan_Snapshot($transaction_register_id,"complete");

			$query_upd = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				UPDATE transaction_register
				SET
					transaction_status = 'complete',
					modifying_agent_id = '{$agent_id}'
				WHERE
						transaction_register_id	= $transaction_register_id
					AND $transaction_status_line ";

			$db->exec($query_upd);
		}
	}
	catch(Exception $e)
	{
		throw $e;
	}

	if ($rows_inserted < 1) return false;

	return true;
}

function Go_All_The_Way_On_The_First_Date($application_id, $event_type, $origin_id,
$origin_group_id, $amounts, $date_event,
$date_effective, $external_payment_ref_id="",
$trace_data="", $context) //mantis:4853 - added $context
{
	$today_is_a_big_day = "2037-01-01";

	$evid = Record_Schedule_Entry($application_id, $event_type, $origin_id, $origin_group_id,
	$date_event, $date_effective, $trace_data, $context); //mantis:4853 - added $context

	foreach ($amounts as $ea)
	{
		/* @var $ea Event_Amount */
		Record_Event_Amount($application_id, $evid, $ea->event_amount_type, $ea->amount, $ea->num_reattempt);
	}

	$trids = Record_Current_Scheduled_Events_To_Register($today_is_a_big_day, $application_id, $evid, 'all', 'immediate posting');
	foreach ($trids as $trid)
	{
		Post_Transaction($application_id, $trid);
	}

	return $evid;
}

function Fetch_Scheduled_Payments_Left($application_id)
{
	$db = ECash::getMasterDb();
	$query = "
       select count(DISTINCT(es.date_effective)) as payments_left
       from event_schedule as es
       join event_type as et on (et.event_type_id = es.event_type_id)
       where
       es.event_status = 'scheduled' and
       et.name_short IN ('payment_service_chg','repayment_principal', 'card_payment_service_chg', 'card_repayment_principal') and
       application_id = {$application_id}
	";
	$row = $db->query($query)->fetch(PDO::FETCH_OBJ);
	$val = $row->payments_left;
	return $val;
}

function Fetch_Pending_Items($application_id)
{
	$db = ECash::getMasterDb();
	$select_query = "
	SELECT tr.*, tt.name_short, tt.name, tt.pending_period,
       	ADDDATE(tr.date_effective, tt.pending_period) as 'due_date'
	FROM transaction_register tr, transaction_type tt
	WHERE tr.transaction_status = 'pending'
	AND tr.transaction_type_id = tt.transaction_type_id
	AND tr.application_id = {$application_id} ";

	$results = $db->query($select_query);
	return $results->fetchAll(PDO::FETCH_OBJ);
}

function qc_remap($a) { return $a->event_schedule_id; }

function Verify_Import($application_id)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();

	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Verifying import of {$application_id}");
	$select_query = "
	SELECT es.date_event, es.event_schedule_id, et.name_short as 'type'
	FROM event_schedule es, event_type et
	WHERE es.application_id = {$application_id}
	AND et.event_type_id = es.event_type_id
	AND et.name_short IN ('converted_principal_bal',
                      	'converted_service_chg_bal',
                      	'assess_fee_ach_fail',
			'assess_fee_card_fail',
                      	'converted_sc_event',
                      	'quickcheck')
	ORDER BY es.date_event ";

	$result = $db->query($select_query);
	$today = date("Y-m-d", strtotime("now"));
	$app = ECash::getApplicationById($application_id);
	$status = $app->getStatus();

	$events = array();
	while ($row = $result->fetch(PDO::FETCH_OBJ)) $events[] = $row;
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Size of events is ". count($events));
	// Quickly iterate through to see if a QC failure is in there.
	$qcs = array();
	foreach ($events as $e) { if ($e->type == 'quickcheck') $qcs[] = $e; }
	if ($status->level1 == 'external_collections')
	{
		foreach ($qcs as $qc)
		{
			$trids = Record_Current_Scheduled_Events_To_Register($qc->date_event, $application_id, $qc->event_schedule_id, 'all', 'db_sodomizer');

			$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Setting event {$qc->event_schedule_id} to FAILED");
			foreach ($trids as $trid)
			{
				Record_Transaction_Failure($application_id, $trid);
			}
		}
	}
	elseif (count($qcs) == 2)
	{
		// Our first little QC friend is a failure at life...
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Setting event {$qcs[0]->event_schedule_id} to FAILED");
		$trids = Record_Current_Scheduled_Events_To_Register($qcs[0]->date_event,
		$application_id, $qcs[0]->event_schedule_id);
		foreach ($trids as $trid) Record_Transaction_Failure($application_id, $trid);

		// The second is still pending
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Setting event {$qcs[1]->event_schedule_id} to PENDING");
		Record_Scheduled_Event_To_Register_Pending($qcs[1]->date_event, $application_id,
		$qcs[1]->event_schedule_id);

	}
	elseif (count($qcs) == 1)
	{
		// Jury's still out on the first one
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Setting event {$qcs[0]->event_schedule_id} to PENDING");
		Record_Scheduled_Event_To_Register_Pending($qcs[0]->date_event, $application_id,
		$qcs[0]->event_schedule_id);
	}
	$qcs = array_map("qc_remap", $qcs);
	foreach ($events as $e)
	{
		if (in_array($e->event_schedule_id, $qcs)) continue;
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$_SESSION['current_app']->application_id}] Setting event {$e->event_schedule_id} to COMPLETE");
		$trids = Record_Current_Scheduled_Events_To_Register($today, $application_id, $e->event_schedule_id, 'all', 'db_sodomizer');
		foreach ($trids as $trid)
		{
			Post_Transaction($application_id, $trid);
		}
	}

	if ($_SESSION['current_app']->is_watched == 'yes')
	{
		$log->Write("Watch Status: {$_SESSION['current_app']->is_watched}, removing registered events.");
		Remove_Unregistered_Events_From_Schedule($_SESSION['current_app']->application_id);
	}

	// Remove any affiliations that might be in there
	$del_query = "
	DELETE FROM agent_affiliation
	WHERE application_id = {$application_id}
	AND affiliation_area = 'conversion'
	AND affiliation_type = 'owner' ";

}

function Get_Quickchecks($application_id)
{
	$schedule = Fetch_Schedule($application_id);
	$qcs = array();

	foreach($schedule as $e)
	{
		if ($e->type == 'quickcheck') $qcs[] = $e;
	}
	return $qcs;
}

function Register_Quickcheck($application_id, $date_qc, $principal_amt, $fees_amt, $status)
{
	$db = ECash::getMasterDb();

	/**
	 * To prevent a possible case of mixed company id's, I'm pulling the Application
	 * up and getting the company_id off of it rather than fetching it from the
	 * ECash Object. [GF#17150][BR]
	 */
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();

	$agent_id = ECash::getAgent()->agent_id;

	$db->beginTransaction();

	// Ensure the values are negative.  QC's will always be debits.
	$principal_amt = -abs($principal_amt);
	$fees_amt = -abs($fees_amt);

	try
	{
		$source_map = Get_Source_Map();
		$source_id = $source_map[DEFAULT_SOURCE_TYPE];

		$query_ins1 = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		INSERT INTO event_schedule(date_created, company_id, application_id,
					event_type_id, origin_group_id,
					configuration_trace_data, amount_principal,
					amount_non_principal, event_status, date_event, date_effective, source_id)
		VALUES(CURRENT_TIMESTAMP, {$company_id}, {$application_id},
          	(SELECT event_type_id from event_type where name_short = 'quickcheck'),
			'auto', 0, 'QC Inserted from Conversion', {$principal_amt},
			{$fees_amt}, 'registered', {$date_qc}, {$date_qc}, '{$source_id}')";

		$db->exec($query_ins1);
		$esid = $db->lastInsertId();
		$total = $principal_amt + $fees_amt;
		$query_ins2 = "
	INSERT INTO transaction_register
	(
		date_created,
		company_id,
		application_id,
		transaction_type_id,
		transaction_status,
		event_schedule_id,
		amount,
		date_effective,
		source_id,
		modifying_agent_id
	)
	VALUES
	(
		current_timestamp,
		{$company_id},
		{$application_id},
		(SELECT transaction_type_id
	 	FROM event_transaction
	 	WHERE event_type_id = (SELECT event_type_id from event_type where name_short = 'quickcheck')),
		'{$status}',
		{$esid},
		{$total},
        	{$date_qc},
        	'{$source_id}',
		'{$agent_id}'
	)";

		$db->exec($query_ins2);
		$trid = $db->lastInsertId();

		/** @TODO this never gets run */
		$event_amount_query =  "
	UPDATE event_amount
	  SET
		transaction_register_id = {$trid}
	  WHERE
		event_schedule_id = {$esid}";

		$db->commit();

		Set_Loan_Snapshot($trid,$status);

	}
	catch (Exception $e)
	{
		if ($db->InTransaction)
		{
			$db->rollBack();
		}
		throw $e;
	}
}

function Gather_App_Transactions($application_id)
{
	$db = ECash::getMasterDb();

	$transactions = array();
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					tt.affects_principal,tr.transaction_register_id,tr.amount,
					tt.name_short,tr.transaction_status
				 FROM
					transaction_register AS tr
				JOIN transaction_type AS tt USING (transaction_type_id)
				WHERE
					tr.application_id = $application_id";
	$result = $db->query($query);
	return $result->fetchAll(PDO::FETCH_OBJ);
}

function Get_Payment_Arrangement_History($application_id)
{
	$db = ECash::getMasterDb();
	$query = "
					select
					   ah.date_created as Date_Created,
					   ah.date_payment as Payment_Date,
					   et.name as Transaction_Type,
					   ah.amount_payment_principal as Principal_Amount,
					   ah.amount_payment_non_principal as Non_Principal_Amount,
					   concat(lower(ag.name_last), ', ', lower(ag.name_first)) 	AS Agent,
					   COALESCE(es.event_status,'deleted') as Status
					from
					    arrangement_history as ah
					    LEFT join event_schedule as es on (es.event_schedule_id = ah.event_schedule_id)
					    LEFT join event_type as et on (et.event_type_id = ah.event_type_id)
					    LEFT join agent as ag on (ag.agent_id = ah.agent_id)
					where
						ah.application_id = {$application_id}
					order by
						Date_Created,Payment_Date";
	$result = $db->query($query);
	return $result->fetchAll(PDO::FETCH_OBJ);
}

function Grab_Most_Recent_Completed_Payment($schedule, $start_date = '01-01-1970')
{
	$stamp = strtotime($start_date); // Start off with a date WAY in the past
	$completed = null;

	foreach($schedule as $e)
	{
		if(($e->status === 'complete') && (strtotime($e->date_event) > $stamp) &&
			($e->principal < 0 || $e->fee < 0 || $e->service_charge < 0)    &&
			$e->clearing_type != 'adjustment')
		{
			$completed = $e;
			$stamp = strtotime($e->date_event);
		}
	}

	return $completed;
}

function Grab_Most_Recent_Failure($application_id, $schedule)
{
	$stamp = strtotime("01-01-1970"); // Start off with a date WAY in the past
	$failure = null;
	foreach($schedule as $e)
	{
		if(($e->status == 'failed') && (strtotime($e->return_date) > $stamp))
		{
			$failure = $e;
			$stamp = strtotime($e->return_date);
		}
	}

	return $failure;
}

function Get_Transaction_History($transaction_register_id)
{
	$db = ECash::getMasterDb();
	$query = "SELECT h.*, a.name_last, a.name_first FROM transaction_history h join agent a using(agent_id) WHERE transaction_register_id = {$transaction_register_id} ORDER BY date_created ASC";

	$result = $db->query($query);

	if ($result)
	{
		return $result;
	}
	else
	{
		return false;
	}
}

function Grab_Transactions_Previous_Status($transaction_register_id)
{
	$db = ECash::getMasterDb();
	$query = "SELECT status_before FROM transaction_history WHERE transaction_register_id = {$transaction_register_id} ORDER BY date_created DESC LIMIT 1";

	$result = $db->query($query);

	$row = $result->fetch(PDO::FETCH_OBJ);

	if ($row)
	{
		return $row->status_before;
	}
	else
	{
		return false;
	}
}

function Grab_All_Failures($application_id, $schedule)
{
	$failures = array();

	foreach($schedule as $e)
	{
		if($e->status == 'failed')
		{
			$failures[] = $e;
		}
	}

	return $failures;
}

function Has_Fatal_Failures($application_id, $schedule = NULL)
{
	$has_fatal_qc = FALSE;

	$failure_map = Fetch_ACH_Return_Code_Map();

	if($schedule === NULL)
	{
		$schedule = Fetch_Schedule($application_id);
	}

	$failures = Grab_All_Failures($application_id, $schedule);
	foreach($failures as $failure)
	{
		if('yes' == $failure_map[$failure->ach_return_code_id]['is_fatal'])
		{
			$has_fatal_qc = TRUE;
		}
	}

	return($has_fatal_qc);
}

/**
 * Returns an array of transaction_register ID's for a given
 * event_schedule_id
 *
 * @param int $event_schedule_id
 * @return array of integers
 */
function Fetch_Transaction_IDs_For_Event($event_schedule_id)
{
	$db = ECash::getMasterDb();

	//Check if Bundling is enabled based on the company's business rules.
	$bundling_enabled = (strtolower(Company_Rules::Get_Config('ach_bundling')) == 'yes');

	//If we're bundling ACH transactions, let's get the ach_id (Where applicable) and fail all events with the same ach [W!-02-03-2009][#23170]
	if($bundling_enabled)
	{
		$ach_query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 	ach_id
			FROM   	transaction_register
			WHERE	event_schedule_id = {$db->quote($event_schedule_id)}	";

		$result = $db->query($ach_query);
		if ($ach_id = $result->fetch(PDO::FETCH_OBJ)->ach_id)
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 	transaction_register_id
			FROM   	transaction_register
			WHERE	ach_id = {$db->quote($ach_id)}";
		}
		else
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 	transaction_register_id
			FROM   	transaction_register
			WHERE	event_schedule_id = {$db->quote($event_schedule_id)}";
		}
	}
	else
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT 	transaction_register_id
			FROM   	transaction_register
			WHERE	event_schedule_id = {$db->quote($event_schedule_id)}";
	}

	$result = $db->query($query);
	$trids = array();
	while($row = $result->fetch(PDO::FETCH_OBJ))
	{
		$trids[] = $row->transaction_register_id;
	}

	return $trids;
}

/**
 * Returns a list of api payments for an account.
 *
 * @param int $application_id
 * @return array
 */
function Fetch_API_Payments($application_id)
{
	settype($application_id, 'int');

	$db = ECash::getMasterDb();
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		SELECT
			api_payment_id,
			name_short event_type,
			amount,
			date_event
		FROM
			api_payment
			JOIN event_type USING (event_type_id)
		WHERE
			application_id = {$application_id}
		AND
			api_payment.active_status = 'active'
	";

	$result = $db->query($query);
	return $result->fetchAll(PDO::FETCH_OBJ);

}

/**
 * Removes a scheduled api payment from an account
 * Update: Instead of actually removing the item we
 * just set it to inactive.
 * @param int $api_payment_id
 */
function Remove_API_Payment($api_payment_id)
{
	settype($api_payment_id, 'int');

	$db = ECash::getMasterDb();
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		  UPDATE
			api_payment
		  SET
			active_status = 'inactive'
		  WHERE
			api_payment_id = {$api_payment_id}
	";

	$db->exec($query);

}

/**
 * Changes event status from suspended to scheduled before creating the new schedule (mantis:4454)
 *
 * @param int $application_id application id for which the schedule is being created
 * @return boolean returns TRUE if the transactions are successfull
 */
function Restore_Suspended_Events($application_id)
{
	$log = get_log("scheduling");
	$db = ECash::getMasterDb();
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Restoring suspended events");

	try
	{
		if ($application_id === NULL)
		throw new Exception("No app id supplied");

		$sql = "
					SELECT event_schedule_id
					FROM event_schedule
					WHERE    event_status = 'suspended'
					     AND application_id = {$application_id}
					";
		$result = $db->query($sql);

		$ids = array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$ids[] = $row->event_schedule_id;
		}

		if (count($ids))
		{

			$sql = "
					UPDATE event_schedule
					SET event_status = 'scheduled'
					WHERE event_schedule_id IN(".implode(', ', $ids).")
					";
			$db->exec($sql);
		}
	}
	catch(Exception $e)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:$application_id] Restoring suspending scheduled events failed!");
		throw $e;
	}

	Complete_Schedule($application_id);

	return TRUE;
}

/**
 * Returns bool TRUE if event of [$type] is scheduled, FALSE if not
 *
 * @param array $schedule Returned by Fetch_Schedule()
 * @param string $type name or name_short of event type
 * @return bool TRUE if event of [$type] is scheduled, FALSE if not
 */
function Event_Type_Is_Scheduled($schedule, $type)
{
	foreach ($schedule as $event)
	{
		if ( ($event->event_name_short == $type || $event->event_name == $type)
		&& $event->status == 'scheduled')
		{
			return TRUE;
		}
	}

	return FALSE;
}

/**
 * Allow a customer to renew their loan for one pay period
 *
 * @param int $application_id
 */
function renewLoan($application_id)
{
	require_once(CUSTOMER_LIB. "renew_schedule_dfa.php");

	$db = ECash::getMasterDb();
	$log = get_log("scheduling");
	$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Renewing schedule");

	$tr_data = Get_Transactional_Data($application_id);

	// Currently, only applications that are Active are acceptable
	$status_chain = Status_Utility::Get_Status_Chain_By_ID($tr_data->info->application_status_id);
	$acceptable_statuses = array('active::servicing::customer::*root');

	$schedule = Fetch_Schedule($application_id);
	$status   = Analyze_Schedule($schedule, TRUE);

	// If there's no balance, we do not need to continue.
	if($status->posted_and_pending_total <= 0)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Account has no balance, not renewing schedule.");
		return;
	}

	// Do not run if the account has arrangments and a balance
	if ($status->has_arrangements && $status->posted_and_pending_total > 0)
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Account has arrangements and a balance, not renewing schedule.");
		return;
	}

	// No accounts with fatal failures or that aren't an acceptable status
	if (($status->num_fatal_failures > 0) || ! in_array($status_chain, $acceptable_statuses))
	{
		$log->Write("[Agent:{$_SESSION['agent_id']}][AppID:{$application_id}] Is in an invalid status or has fatal errors, not renewing schedule.");
		return;
	}

	// Ignore these events because we'll regenerate them in the DFA
	$ignored_events = _get_ignored_events('regenerated');

	$sent_disbursement = false;
	$num_scs_assessed = 0;
	$special_payments = array();
	$disbursement_types = array('loan_disbursement', 'card_disbursement', 'converted_principal_bal', 'converted_service_chg_bal','moneygram_disbursement', 'check_disbursement');

	foreach($schedule as $e)
	{
		if($e->type === 'assess_service_chg' && $e->status === 'completed')
		{
			$num_scs_assessed++;
		}

		// Grab any special payments, arrangements
		if(($e->status  === 'scheduled') && (!in_array($e->type, $ignored_events) || $e->is_shifted))
		{
			$special_payments[] = $e;
		}

		// This probably should either be done sooner or cut out completely.
		if (in_array($e->type, $disbursement_types) && $e->status != 'scheduled')
		{
			$sent_disbursement = true;
		}
	}

	$ecash_api = eCash_API_2::Get_eCash_API(null, $db, $application_id);

	// Remove all unregistered events.  We'll regenerate them if necessary.
	Remove_Unregistered_Events_From_Schedule($application_id);

	$parameters = new stdClass();
	$parameters->application_id = $application_id;
	$parameters->fund_amount = $tr_data->info->fund_actual;
	$parameters->fund_date = $tr_data->info->date_fund_stored;
	$parameters->tr_data = $tr_data;
	$parameters->info = $tr_data->info;
	$parameters->rules = Prepare_Rules($tr_data->rules, $tr_data->info);
	$parameters->schedule = $schedule;
	$parameters->status = $status;;
	$parameters->balance_info = Fetch_Balance_Information($application_id);
	$parameters->special_payments = $special_payments;
	$parameters->num_scs_assessed = $num_scs_assessed;
	$parameters->delinquency_date = $ecash_api->getDelinquencyDate($application_id);
	$parameters->status_chain = $status_chain;

	$parameters->log = $log;
	$parameters->pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());

	if (isset($current_schedule[$status->stopping_location]->date_event))
	{
		$parameters->next_action_date = $current_schedule[$status->stopping_location]->date_event;
		$parameters->next_due_date = $parameters->pdc->Get_Next_Business_Day($parameters->next_action_date);
	}
	else
	{
		$parameters->next_action_date = false;
		$parameters->next_due_date = false;
	}

	// Use the eCash API to get the service charge amount
	//require_once('ecash_common/ecash_api/ecash_api_factory.class.php');
	//$parameters->api = eCash_API_Factory::Get_eCash_API($_SESSION['company'], ECash::getMasterDb(), $application_id);
	$dfa = new RenewScheduleDFA();
	$dfa->SetLog($log);

	$log->Write("Running Renew Schedule DFA for $application_id");
	$new_events = $dfa->run($parameters);
    foreach ($new_events as $e)
    {
        Record_Event($application_id, $e);
    }


}



	function getReattemptDate($reattempt_date, $application, $delay = 0)
	{
		$application_id = $application->application_id;
		$rules = $application->getBusinessRules();
		$data     = Get_Transactional_Data($application_id);
		$info = $data->info;
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$today = $pdc->Get_Calendar_Days_Forward(date('Y-m-d'), $delay);

		$date_pair = array();
		$date_pair['event'] = '';
		$date_pair['effective'] = '';

		switch($reattempt_date)
		{
			// Re-Attempt the next business day
			case 'immediate':
				if(Has_Batch_Closed())
				{
					$date_pair['event'] = $pdc->Get_Next_Business_Day($today);
				}
				else
				{
					$date_pair['event'] = $today;
				}
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;

			// Since HMS & Impact get their returns at the end of the day this is almost always going to
			// be the same as 'immediate'.
			case '1 day':
				$date_pair['event'] = $pdc->Get_Next_Business_Day($today);
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;

			case '2 days':
			case '3 days':
			case '4 days':
			case '5 days':
			case '6 days':
			case '7 days':
			case '8 days':
			case '9 days':
			case '10 days':
				$days = trim(substr($reattempt_date, 0, 2));
				$date_pair['event'] = $pdc->Get_Business_Days_Forward($today, $days);
				$date_pair['effective'] = $pdc->Get_Next_Business_Day($date_pair['event']);
				break;

			case 'next pay day or 15 days':

	       		$next_paydate = Get_Next_Payday(date("Y-m-d"), $info, $rules);
				$fortnight_n_change = $pdc->Get_Calendar_Days_Forward($today, 15);

				if(strtotime($fortnight_n_change) < strtotime($next_paydate['effective']))
				{
					$date_pair['effective'] = $pdc->Get_Closest_Business_Day_Forward($fortnight_n_change);
					$date_pair['event'] = $pdc->Get_Business_Days_Backward($fortnight_n_change,1);
				}
				else
				{
					$date_pair = $next_paydate;
				}

			break;

			case 'next friday':
				if (Has_Batch_Closed())
				{
					$date = strtotime($today . ' +2 day');
				}
				else
				{
					$date = strtotime($today . ' +1 day');
				}
				while ($date_pair['effective'] == '')
				{
					if(date('w',$date) == 5)
					{
						if($pdc->Is_Holiday($date))
						{
							$date_pair['effective'] = $pdc->Get_Business_Days_Backward($date,1);
						}
						else
						{
							$date_pair['effective'] = date('Y-m-d',$date);
						}
						$date_pair['event'] = $pdc->Get_Business_Days_Backward($date_pair['effective'],1);
					}
					$date = strtotime(date('Y-m-d',$date) . ' +1 day');
				}
				break;

			case '15th or 30th':

				if (Has_Batch_Closed())
				{
					$date = strtotime($today .  ' +2 day');
				}
				else
				{
					$date = strtotime($today . ' +1 day');
				}
				while ($date_pair['effective'] == '')
				{

					if(date('j',$date) == 15 || date('j',$date) == 30)
					{

						if ($pdc->isBusinessDay($date))
						{
							$date_pair['effective'] = date('Y-m-d',$date);
						}
						else
						{
							$date_pair['effective'] = $pdc->Get_Business_Days_Backward($date,1);
						}
						$date_pair['event'] = $pdc->Get_Business_Days_Backward($date_pair['effective'],1);
					}
					$date = strtotime(date('Y-m-d',($date)) . ' +1 day');
				}
				break;

			case 'none':
				$date_pair = null;
				break;
			// Re-Attempt on the customer's next pay day (Default)
			case 'next pay day':
			default:
				$date_pair = Get_Next_Payday($today, $info, $rules);
				break;
		}
		return $date_pair;

	}

/*
 * Lookup for event type name - caches for speed
 */
function Get_Event_Type_Name ($name_short, $company_id)
{
	global $event_name_cache;

	if (isset($event_name_cache[$company_id][$name_short]))
	{
		return $event_name_cache[$company_id][$name_short];
	}

	$query = "
				SELECT name
				FROM event_type
				WHERE name_short = '{$name_short}'
				 AND company_id = {$company_id}
				";

	$db = ECash::getMasterDb();
	$result = $db->query($query);

	if ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$event_name_cache[$company_id][$name_short] = $row['name'];
		return $row['name'];
   	}
}

function mayUseCardSchedule($application_id, $company_id = NULL)
{
	$flag_type = ECash::getFactory()->getModel('FlagType');
	$loaded = $flag_type->loadBy(array('name_short'=>'card_schedule','active_status'=>'active',));
	if (!$loaded)
	{
		return FALSE;
	}

	if ($company_id === NULL)
	{
		$application = ECash::getApplicationById($application_id);
		$company_id = $application->getCompanyId();
	}

	// Fetch Payment Card Info.
	$card_info = ECash::getFactory()->getModel('CardInfoList');
	$card_info->loadBy(array('application_id' => $application_id));

	$active_card_saved = FALSE;
	$ci_model = ECash::getFactory()->getModel('CardInfo');
	$ci_array = $ci_model->loadAllBy(array(
						'application_id' => $application_id,
						'active_status' => 'active',
	));
	if (count($ci_array) > 0)
	{
		$active_card_saved = TRUE;
	}
	else
	{
		return FALSE;
	}

	// Card Authorization doc
	$card_authorization_received = FALSE;
	$doc_list_model = ECash::getFactory()->getModel('DocumentList');
	$doc_list_model->loadBy(array(
					'company_id' => $company_id,
					'active_status' => 'active',
					'name_short' => 'Card Authorization',
	));

	$doc_model = ECash::getFactory()->getModel('Document');
	$doc_array = $doc_model->loadAllBy(array(
						'application_id' => $application_id,
						'company_id' => $company_id,
						'document_event_type' => 'received',
						'document_list_id' => $doc_list_model->document_list_id,
	));
	if (count($doc_array) > 0)
	{
		$card_authorization_received = TRUE;
	}
	
	return ($active_card_saved && $card_authorization_received);
}

function isCardSchedule($application_id)
{
	$application = ECash::getApplicationById($application_id);
	$company_id = $application->getCompanyId();
	$flags = $application->getFlags();
	return ($flags->get('card_schedule') && mayUseCardSchedule($application_id, $company_id));
}

function alignActionDateForCard($application_id)
{
	$db = ECash::getMasterDb();
	$company_id = ECash::getCompany()->company_id;
	$clearing_type = 'card';

	$sql = "
	SELECT DISTINCT
	es.event_schedule_id,
	et.name AS event_name,
	es.date_event,
	es.date_effective,
	ea.amount
	FROM event_schedule AS es
	JOIN event_amount AS ea USING (event_schedule_id)
	JOIN event_type AS et ON (et.event_type_id=es.event_type_id
	AND et.company_id={$company_id}
	AND et.active_status='active')
	JOIN event_transaction AS evtr ON (evtr.event_type_id=et.event_type_id
	AND evtr.company_id={$company_id}
	AND evtr.active_status='active')
	JOIN transaction_type AS tt ON (tt.transaction_type_id=evtr.transaction_type_id
	AND tt.company_id={$company_id}
	AND tt.active_status='active')
	WHERE es.application_id={$application_id}
	AND es.event_status='scheduled'
	AND tt.clearing_type='{$clearing_type}'
	AND ea.amount < 0
	AND es.date_event < es.date_effective
	";
	$result = $db->query($sql);
	while ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		try
		{
			$query = "
			UPDATE event_schedule
			SET date_event = date_effective
			WHERE application_id={$application_id}
			AND event_schedule_id = {$row->event_schedule_id}
			";
			$db->query($query);
			
			$query = "
			UPDATE event_schedule AS es
			JOIN event_type AS et ON (et.event_type_id=es.event_type_id
			AND et.company_id={$company_id}
			AND et.active_status='active')
			SET es.date_event = '{$row->date_effective}',
			es.date_effective = '{$row->date_effective}'
			WHERE es.application_id={$application_id}
			AND et.name_short='assess_service_chg'
			AND es.event_status='scheduled'
			AND es.date_effective='{$row->date_event}'
			";
			$db->query($query);
		}
		catch(Exception $e)
		{
			continue;
		}
	}
}

?>
