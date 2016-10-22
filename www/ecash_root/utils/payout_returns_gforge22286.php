<?php
/**
 * Create payouts at the end of schedules for applications that have received a return, are active, and have no fatals
 * This is to facilitate the request made in #22286 [W!-12-18-2008]
 * This is a one time thing, and it runs with the eCash engine.
 */

function Main()
{
	$db = ECash_Config::getMasterDbConnection();
	global $server;
	
	
	$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
	$active_status_id = $status_list->toId('active::servicing::customer::*root');
	
	
	//get applications
	$sql = "
			SELECT
			    ea.application_id,
		    	SUM( IF( tr.transaction_status = 'complete' OR transaction_status = 'pending', ea.amount, 0)) pending_balance,
			    SUM( IF( tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL, ea.amount, 0)) scheduled_balance,
			    SUM( IF((tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL) AND eat.name_short = 'principal', ea.amount, 0)) scheduled_principal,
    			SUM( IF((tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL) AND eat.name_short = 'service_charge', ea.amount, 0)) scheduled_sc,
    			SUM( IF((tr.transaction_status != 'failed' OR tr.transaction_register_id IS NULL) AND eat.name_short = 'fee', ea.amount, 0)) scheduled_fee,
			    SUM( IF( tr.transaction_status = 'failed', 1,0)) failures,
			    SUM( IF( arc.is_fatal = 'yes', 1, 0)) fatals,
			    MAX(es.date_effective) as last_paydate
			FROM event_amount AS ea
			JOIN application AS a ON ea.application_id = a.application_id
			JOIN event_schedule es ON es.event_schedule_id = ea.event_schedule_id
			JOIN event_amount_type eat ON eat.event_amount_type_id = ea.event_amount_type_id
			LEFT JOIN transaction_register AS tr ON ea.transaction_register_id = tr.transaction_register_id
			LEFT JOIN ach ON tr.ach_id = ach.ach_id
			LEFT JOIN ach_return_code arc ON arc.ach_return_code_id = ach.ach_return_code_id
			WHERE a.application_status_id = {$db->quote($active_status_id)}
			GROUP BY ea.application_id
			HAVING 
				scheduled_balance <> 0
			AND 
				fatals < 1
			AND 
				failures >= 1

	";
	$result = $db->query($sql);
	$i = 0;
	while ($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		$i++;
		//get their next paydate (next paydate after their last scheduled date if they have stuff scheduled for the future)
		
		$application_id = $row['application_id'];
		$application = ECash::getApplicationById($application_id);
		$rules = $application->getBusinessRules();
		$last_paydate = $row['last_paydate'];
		$data = Get_Transactional_Data($application_id);
		
		//If they've been getting off the hook with an active schedule and no scheduled transactions, we're starting from today
		$start_date = strtotime($last_paydate) < strtotime(date('Y-m-d')) ? date('Y-m-d') : $last_paydate;

		$dates = Get_Date_List($data->info, $start_date, $rules, 20);
		
		$list_place = strtotime($start_date) > strtotime($last_paydate) ? 0 : 1;
		$date_effective = $dates['effective'][$list_place];
		echo "$application_id - Scheduling a payout of {$row['scheduled_balance']} (P:{$row['scheduled_principal']} I:{$row['scheduled_sc']} F:{$row['scheduled_fee']} for $date_effective ($i)\n";
		//Schedule a payout for that date
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('fee', -$row['scheduled_fee']);
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$row['scheduled_principal']);
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$row['scheduled_sc']);
		$event = Schedule_Event::MakeEvent($dates['event'][$list_place], $dates['effective'][$list_place],
					 						$amounts, 'payout',
					 						"Application had a return in the past, adding payout to end of schedule for full remaining balance of \${$row['scheduled_balance']}"
					 						,'scheduled','manual');
		//??
		Record_Event($application_id, $event);
		
		//Profit
	}
	
}



?>
