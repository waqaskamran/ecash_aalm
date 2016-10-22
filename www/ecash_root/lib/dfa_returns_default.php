<?php
require_once(LIB_DIR . 'dfa.php');
require_once(LIB_DIR . 'dfa_returns.php');
require_once(SQL_LIB_DIR . 'fetch_ach_return_code_map.func.php');
require_once(SQL_LIB_DIR . 'fetch_card_return_code_map.func.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");

/**
 *
 	OK, this is something different we're trying here!  For the first time in eCash Commercial's history, we're trying
 	to actually standardize something!  We're rolling out a standard collections process.  Currently 2 enterprises share
 	this process, but there will be more soon! Just you wait!
 * 
 */

class DefaultFailureDFA extends ReturnsDFABase {
	
	public $application_id;
	const NUM_STATES = 6;
	
	public function __construct()
	{

		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(3,5,7,17,18,25,27,30,35,45,50);
		$this->tr_functions = array(
			 0 => 'has_failures', 
			 1 => 'context_type',
			 2 => 'clearing_type',
			 3 => 'arrangements_process',
			 5 => 'lolwut',
			 6 => 'is_fatal',
			 7 => 'pretend_nothing_happened',
			 8 => 'clearing_type',
			 9 => 'is_fatal',
			 10 => 'at_failed_service_charge_limit',
			 11 => 'is_reattempt',
			 12 => 'is_reattempted_reattempt',
			 13 => 'is_reattempted_fee',
			 14 => 'is_fullpull',
			 15 => 'qc_eligible',
			 16 => 'application_status',
			 17 => 'past_due',
			 18 => 'collections_new',
			 19 => 'is_in_holding_status',
			 20 => 'has_credits',
			 21 => 'is_disbursement',
			 22 => 'at_failed_arrangement_limit',
			 30 => 'collections_contact',
			 35 => 'funding_failed',
			 25 => 'fatal_stuff',
			 27 => 'do_nothing',
			 45 => 'qc_ready',
			 50 => 'second_tier_ready'
			 
			 );

		$this->transitions = array ( 
			 0 => array (0 => 5, 1 => 20),
			 20 => array(0 => 19, 1 => 21),
			 19 => array(0 => 14, 1 => 27),
			 
			 21 => array(0 => 27, 1 => 35),
			 1 => array('scheduled' => 2, 'arrangement' => 22,	'alternate' => 8),
			 2 => array('ach' => 6, 'quickcheck' => 50, 'adjustment' => 7,  'other'=> 30),
			 8 => array('ach' => 9, 'quickcheck' => 50, 'adjustment' => 7,  'other'=> 7),
			 9 => array(0 => 7, 1 => 25),
			 6 => array(0 => 10, 1 => 25),
			 10 => array(0 => 11, 1 => 30),
			 11 => array(0 => 16, 1 => 12),
			 12 => array(0 => 13, 1 => 30),
			 13 => array(0 => 16, 1 => 30),
			 14 => array(0 => 1, 1 => 15),
			 15 => array(0 => 50, 1 => 45),
			 16 => array('active' => 17, 'past_due' => 18, 'collections_new' => 18, 'other' => 30),
			 22 => array(0 => 3, 1 => 15),
			 ); 
			
		parent::__construct();
	}
	// Here I'm setting up the 
	function run ($parameters) {

		require_once(SQL_LIB_DIR ."application.func.php");
		require_once(SQL_LIB_DIR ."fetch_status_map.func.php");
		require_once(SQL_LIB_DIR ."scheduling.func.php");
		require_once(SQL_LIB_DIR ."util.func.php");

		// Yes, I threw in everything but the kitchen sink in here.
		$application_id = $parameters->application_id;
		
		$data     = Get_Transactional_Data($application_id);
		$holidays = Fetch_Holiday_List();

		$parameters->log = get_log("scheduling");
		$parameters->info = $data->info;
		$parameters->rules = Prepare_Rules($data->rules, $data->info);
		$parameters->schedule = Fetch_Schedule($application_id);
		$parameters->verified = Analyze_Schedule($parameters->schedule, true);
		$parameters->grace_periods = Get_Grace_Periods();
		$parameters->pdc = new Pay_Date_Calc_3($holidays);
		$parameters->arc_map = Fetch_ACH_Return_Code_Map();
		$parameters->crc_map = Fetch_Card_Return_Code_Map();
		$parameters->event_transaction_map = Load_Transaction_Map(ECash::getCompany()->company_id);	
		$parameters->app_status_map = Fetch_Status_Map();
		$parameters->application_status_id = $data->info->application_status_id;
		$parameters->is_watched = $data->info->is_watched;
		$parameters->most_recent_failure = Grab_Most_Recent_Failure($application_id, $parameters->schedule);

		if (!$parameters->company_id) $parameters->company_id = ECash::getCompany()->company_id;

		// Get the app status
		$app_status = Fetch_Application_Status($application_id);
		foreach ($app_status as $key => $value) $parameters->$key = $value;

		$parameters->status   = Analyze_Schedule($parameters->schedule);

		return parent::run($parameters);
	}
	
	/**
	 * This does nothing.  This is for failures that we just don't care about.  Examples would be:
	 * A failed payment on an application in second tier status, a failed credit card payment on a collections app.
	 *
	 * @param unknown_type $parameters
	 */
	function do_nothing($parameters)
	{
		//We're literally doing nothing!
		$this->Log("We've experienced a return we don't care about.  Doing nothing!");
	}
	
	//These are actions that we perform when we get a fatal ACH return.  We want to perform these actions on most
	//fatally failed ach payments
	function fatal_stuff($parameters)
	{
		//check and see if it's on the same bank account, if so, add the has_fatal. If not, who cares?
		$this->fatal_flag($parameters);
		
		//We're only going to assess the ACH fee, send the letter, and truncate the schedule if they can be 
		//updated to this status.  If they can't, we're beyond that step, that ship has sailed.
		if($this->updateStatus($parameters->application_id, 'queued::contact::collections::customer::*root'))
		{
			//Send return letter 1
			//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_1_SPECIFIC_REASON', $parameters->status->fail_set[0]->transaction_register_id);
			ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);
			//truncate schedule
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
		}
		

	}
	
	/**
	 * This state is for applications that were in Active status, and have had a non-fatal return on a regularly
	 * scheduled payment
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function past_due($parameters)
	{
		//change status to past_due
		//remove from queues?
		if(	$this->updateStatus($parameters->application_id, 'past_due::servicing::customer::*root'))
		{		
			if($date_pair = $this->getFirstReturnDate($parameters))
			{
				$today = date('Y-m-d');
				
				$rules = $parameters->rules;
		
				$db = ECash::getMasterDb();
				try {
					$db->beginTransaction();
			
					//Assess ACH Fee
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', intval($rules['return_transaction_fee']));
					$oid = $parameters->status->fail_set[0]->event_schedule_id;
							
					$e = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_fee_ach_fail', 
							"ACH fee for non-fatal return for application in Active status, on event {$oid}");
							
					Post_Event($parameters->application_id, $e);
				
					// And then pay it.
					$amounts = array();
					$amounts[] = Event_Amount::MakeEventAmount('fee', -intval($rules['return_transaction_fee']));
							
					$e = Schedule_Event::MakeEvent($date_pair['event'], $date_pair['effective'], $amounts, 'payment_fee_ach_fail', 
								"ACH fee payment for non-fatal return for application in Active status, on event {$oid}");
							
					Record_Event($parameters->application_id, $e);
					
					// Add all the reattempts
					foreach($parameters->status->fail_set as $f) 
					{
						//schedule reattempt on failure
						$ogid = -$f->transaction_register_id;
						$reattempt = TRUE;
						foreach($parameters->schedule as $s)
						{
							if($s->origin_id && $f->transaction_register_id == $s->origin_id)
							{
								$reattempt = FALSE;
							}
						}
						if($reattempt)
						{
							Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
						}
						else
						{
							$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists.");
						}
					}
					$db->commit();
				} catch (Exception $e) {
					$this->Log(__METHOD__ . ': ' . $e->getMessage() . ' Unable to modify transactions.');
					$db->rollBack();
					throw $e;
				}
			}
			
			alignActionDateForCard($parameters->application_id);

			//Send return letter 2
			//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_2_SECOND_ATTEMPT', $parameters->status->fail_set[0]->transaction_register_id);		
			ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);		
		}

		return true;
	}
	
	/**
	 * This state applies to applications that have had a non-fatal return on a payment while in Past Due status.
	 *
	 * @param unknown_type $parameters
	 */
	function collections_new($parameters)
	{
		//We expect to hit this condition several times before moving on to collections_contact.
		//Change status to collections_new
		if($this->updateStatus($parameters->application_id, 'new::collections::customer::*root'))
		{
			//Schedule reattempt for failed transaction set
			//We really should check and see if this is the first attempt or a reattempt to determine which date
			//to use.
			if($date_pair = $this->getAdditionalReturnDate($parameters))
			{
				
				$db =  ECash::getMasterDb();
				try {
					$db->beginTransaction();
					// Add all the reattempts
					foreach($parameters->status->fail_set as $f) {
						$ogid = -$f->transaction_register_id;
						$reattempt = TRUE;
						foreach($parameters->schedule as $s)
						{
							if($s->origin_id && $f->transaction_register_id == $s->origin_id)
							{
								$reattempt = FALSE;
							}
						}
						if($reattempt)
						{
							Reattempt_Event($parameters->application_id, $f, $date_pair['event'], $ogid);
						}
						else
						{
							$this->Log("Skipping reattempt ({$f->transaction_register_id}). One already exists.");
						}
					}
					$db->commit();
				} catch (Exception $e) {
					$this->Log(__METHOD__ . ': ' . $e->getMessage() . ' Unable to modify transactions.');
					$db->rollBack();
					throw $e;
				}
			}
			
			alignActionDateForCard($parameters->application_id);
			//Send Return Letter 3
			//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_3_OVERDUE_ACCOUNT', $parameters->status->fail_set[0]->transaction_register_id);		
			ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);		
		}
	}

	/**
	 * This does status updating with a little sanity checking.  This prevents retarded situations like an application
	 * going from Collections Contact to Past Due, or a second tier app getting sent back into collections.
	 * It sets up an array which determines a hierarchy of statuses, and prevents the status from moving backwards.
	 *
	 * @param int $application_id
	 * @param string $status_chain
	 * @return unknown
	 */
	function updateStatus($application_id,$status_chain)
	{
		//The idea is to set up a sequence for statuses, and make sure that status changes can not happen out of sequence
		//by going backwards.  
		//For example: An application in Collections Contact should not be able to go back to Past Due status
		//Or an application in Made Arrangements status should not be able to go into Collections New status.
		
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$status_list = array();
		$status_list[$asf->toId('active::servicing::customer::*root')] = 10;
		$status_list[$asf->toId('past_due::servicing::customer::*root')] = 20;
		$status_list[$asf->toId('new::collections::customer::*root')] = 30;
		$status_list[$asf->toId('current::arrangements::collections::customer::*root')] = 40;
		$status_list[$asf->toId('queued::contact::collections::customer::*root')] = 40;
		$status_list[$asf->toId('follow_up::contact::collections::customer::*root')] = 40;
		$status_list[$asf->toId('settled::customer::*root')] = 45;
		$status_list[$asf->toId('ready::quickcheck::collections::customer::*root')] = 50;
		$status_list[$asf->toId('sent::quickcheck::collections::customer::*root')] = 55;
		$status_list[$asf->toId('sent::external_collections::*root')] = 70;
		$status_list[$asf->toId('pending::external_collections::*root')] = 60;

		$application =  ECash::getApplicationByID($application_id);
		
		$current_status = $application->application_status_id;
		$new_status = $asf->toId($status_chain);

		if(array_key_exists($current_status,$status_list) && array_key_exists($new_status,$status_list) && $status_list[$current_status] > $status_list[$new_status])
		{
			//If we're trying to update to an invalid status , and we're in an inactive status, let's just roll back to 
			//the previous status.
			$inactive_statuses = array();
			$inactive_statuses[] = $asf->toId('paid::customer::*root');
			$inactive_statuses[] = $asf->toId('settled::customer::*root');
			//If status is Inactive, rollback to the previous status
			if(in_array($application->application_status_id,$inactive_statuses))
			{
				if($prev_status = $application->getPreviousStatus())
				{
					$this->Log("Reverting status to previous status {$prev_status}");
					Update_Status(NULL, $application_id, $prev_status->application_status_id, NULL, NULL, FALSE);
				}
			}
			$this->Log("Attempted to update status to a status from an earlier process {$status_chain}, this is bad!  No status update will take place");
			
			return false;
		}
		
		return Update_Status(null, $application_id, $status_chain, NULL, NULL, false);
	}
	
	/**
	 * This moves an application into Collections Contact status.  This is, by far, the most commonly hit end state.
	 * This state applies to applications:
	 * With more than the maximum allowed number of service charge failures
	 * With failed arrangements
	 * With a failed partial payment
	 * With a failed non-ach payment created using Arrange Next Payment.
	 *
	 * @param unknown_type $parameters
	 */
	function collections_contact($parameters)
	{
		//Truncate schedule
		Remove_Unregistered_Events_From_Schedule($parameters->application_id);
		//Stop interest accrual
		
		//Send return letter 4
		//ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'RETURN_LETTER_4_FINAL_NOTICE', $parameters->status->fail_set[0]->transaction_register_id);		
		ECash_Documents_AutoEmail::Queue_For_Send($parameters->application_id, 'PAYMENT_FAILED', $parameters->status->fail_set[0]->transaction_register_id);		
		//move to collections_contact
		$this->updateStatus($parameters->application_id, 'queued::contact::collections::customer::*root');
		//collections general queue
		
		//schedule full_pull
		if($date_pair = $this->getFullPullDate($parameters))
		{
			//Schedule_Full_Pull($parameters->application_id, NULL, NULL, $date_pair['event'], $date_pair['effective']);
		}
	}
	
	/**
	 * Clears out any discounts they received on arrangements, because they broke their arrangements!
	 *
	 * @param object $parameters
	 */
	function fail_arrangement_discount($parameters) 
	{
		$discounts = array();
		//get_log('scheduling')->Write(print_r($parameters->schedule, true));
		foreach ($parameters->schedule as $e) 
		{
			if (($e->context == 'arrangement' || $e->context == 'partial') && 
			  (in_array($e->type, array('adjustment_internal', 'adjustment_internal_fees', 'adjustment_internal_princ')))) 
			  {
			  	if ($e->status == 'scheduled') 
				{
			  		Record_Scheduled_Event_To_Register_Pending($e->date_event, $parameters->application_id, $e->event_schedule_id);
			  		Record_Event_Failure($parameters->application_id, $e->event_schedule_id);
			  	} 
				elseif ($e->status != 'failed') 
				{
					Record_Transaction_Failure($parameters->application_id, $e->transaction_register_id);
			  	}
			}
		}
	}



	/**
	 * This collections model pays close attention to a transactions clearing type to try and determine the next course
	 * of action.  This function evaluates the clearing type and assigns it to a certain group, which determines
	 * the next step in the DFA
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function clearing_type ($parameters) 
	{
		$fail_set = $parameters->status->fail_set;
		$map = $parameters->event_transaction_map;
		$f = $parameters->most_recent_failure;
		$this->Log("Failure has a clearing type of:".$map[$f->type]->clearing_type);
		switch($map[$f->type]->clearing_type)
		{
			case 'ach':
				return 'ach';
				break;
			case 'quickcheck':
				return 'quickcheck';
				break;
			case 'adjustment':
				return 'adjustment';
			default:
				return 'other';
				break;
		}
	}
	
	/**
	 * This collections model bases the bulk of its decisioning on the context type of a transaction.
	 * This function evaluates the context type, and assigns it to a group, which is used to determine the next course
	 * of action.
	 * The current breakdown is:
	 * 'scheduled' (Payments which the system generated, or system generated payments that were replaced by an agent)
	 * 'arrangement' (Payments which are manually arranged by an agent as part of the collections process)
	 * 'alternate' (Payments that were manually arranged by an agent that are not part of the collections process.  These payments are
	 *  typically regarded as 'bonus' payments)
	 * 
	 * 
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function context_type ($parameters) 
	{
		$fail_set = $parameters->status->fail_set;
		$f = $parameters->most_recent_failure;
		$this->Log("Most recent failure has a context of {$f->context}");
		switch ($f->context)
		{
			case 'generated':
			case 'reattempt':
			case 'arrange_next':
				return 'scheduled';
			break;
			
			case 'arrangement':
			case 'partial':
				return 'arrangement';
			break;
			
			case 'manual':
			case 'paydown':
			case 'payout':
			case 'cancel':
			default:
				return 'alternate';
			break;
			
		}
	}
	
	/**
	 * Evaluates whether or not the application has reached the limit of failed service charges, as determined by the 
	 * max_svc_charge_failures business rule.  This function only counts the number of failed service charge
	 * payments.  Reattempts are included in this count.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function at_failed_service_charge_limit($parameters)
	{
		$r = $parameters->rules;
		$failure_count = 0;
		foreach ($parameters->status->posted_schedule as $e)
		{
			if ($e->type == 'payment_service_chg' && $e->status == 'failed')
			{
				$failure_count++;
			}
		}
		$this->Log("Failed SVC Limit: {$r['max_svc_charge_failures']} | Current SC Failures: {$failure_count}");
		return (($failure_count >= $r['max_svc_charge_failures'])?1:0);
	}
	
	/**
	 * Evaluates whether or not the application has reached the maximum number of failed arrangements, as determined
	 * by the max_arr_failures business rule.
	 * Partial payments are NOT included in this count.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function at_failed_arrangement_limit($parameters)
	{
		$failed_arrangements = array();
		$rules = $parameters->rules;
		if(!empty($rules['max_arr_failures']))
		{
			$arrangement_limit = is_array($rules['max_arr_failures'])? $rules['max_arr_failures']['max_arr_failures'] : $rules['max_arr_failures'];
		}
		else 
		{
			$arrangement_limit = 2;			
			$this->Log("Couldn't find rule for max_arr_failures.  Using a limit of {$arrangement_limit} instead");
		}
		
		foreach ($parameters->status->posted_schedule as $e)
		{
			if ($e->status  == 'failed' && $e->context == 'arrangement')
			{
				$failed_arrangements[$e->event_schedule_id] = $e;
			}
		} 
		
		
		$this->Log("Application has ".count($failed_arrangements)." failed arrangements out of {$arrangement_limit}");
		if(count($failed_arrangements) >= $arrangement_limit)
		{
			return 1;
		}
		return 0;
	}
	
	/**
	 * In addition to a transaction's context and clearing type, the other main factor that is evaluated is the application's
	 * current status.
	 * This evaluates the status and groups it based on where in the 'process' it belongs.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function application_status($parameters)
	{
		//Get the application's status to determine our next move
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);

		switch ($application->application_status_id)
		{
			case $asf->toId('active::servicing::customer::*root'):
			case $asf->toId('paid::customer::*root'):
			case $asf->toId('settled::customer::*root'):
				return 'active';
				break;
			case $asf->toId('past_due::servicing::customer::*root');
				return 'past_due';
				break;
			case $asf->toId('new::collections::customer::*root');
				return 'collections_new';
				break;
			default:
				return 'other';
				break;
		}
	}
	
	/**
	 * Checks to see if the application is eligible to have quickchecks performed on it.
	 * It evaluates the last ACH return code that the application received, and checks it against a list of return
	 * codes defined in the config.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function qc_eligible($parameters)
	{
		$qc_allowable = is_array(ECash::getConfig()->QC_ALLOWABLE) ? ECash::getConfig()->QC_ALLOWABLE : array();
		$return_code = 'LOLWUT?';
		//get the return code of the last ach return and determine whether or not it's in the QC allowable list
		foreach ($parameters->status->fail_set as $f) 
		{
			if($f->clearing_type == 'ach')
			{
				$return_code = $f->return_code;
			}
		}
		$this->Log("Last ACH return code was $return_code");
		if(in_array($return_code,$qc_allowable))
		{
			$this->Log("Last ACH return code was $return_code, QC allowable");
			return 1;
		}
		return 0;
	}
	
	/**
	 * checks to see if the transaction is a reattempt of a reattempt.
	 * This checks to see if the failure has a context of 'reattempt' and an origin ID,
	 * it then checks the posted events in the schedule to find the transaction it reattempted (using the origin_id)
	 * and determines if that transaction is a reattempt (Has an origin ID and a context of reattempt)
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_reattempted_reattempt($parameters)
	{
		//Does this have an origin_id and a context of reattempt?  Does the transaction it's reattempting also have one?
		//Uh-oh!
		foreach ($parameters->status->fail_set as $f) 
		{
			//Reattempts have an origin_id (because they originated from another transaction
			//Reattempts also have a context of reattempt!
			if ($f->origin_id != null && $f->context == 'reattempt') 
			{
				foreach ($parameters->status->posted_schedule as $e)
				{
					if ($e->transaction_register_id == $f->origin_id && $e->origin_id != null && $e->context == 'reattempt')
					{
						return 1;	
					}
				}
			}
		}
		
		return 0;
	}
	
	/**
	 * Checks to see if the transaction is a reattempt of a ach fee payment
	 * This checks to see if the failure has a context of 'reattempt' and an origin ID,
	 * it then checks the posted events in the schedule to find the transaction it reattempted (using the origin_id)
	 * and determines if that transaction is an ACH fee payment (by identifying its type as 'payment_fee_ach_fail').
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_reattempted_fee($parameters)
	{
		//Does this have an origin_id and a context of reattempt?  Is the transaction it's reattempting a fee? Uh-oh!
		foreach ($parameters->status->fail_set as $f) 
		{
			//Reattempts have an origin_id (because they originated from another transaction
			//Reattempts also have a context of reattempt!
			if ($f->origin_id != null && $f->context == 'reattempt') 
			{
				foreach ($parameters->status->posted_schedule as $e)
				{
					if ($e->transaction_register_id == $f->origin_id && $e->type == 'payment_fee_ach_fail')
					{
						return 1;	
					}
				}
			}
		}
		
		return 0;
	}
	
	/**
	 * This extends the main holding status check.  It looks to see if the application is in a status where
	 * no processing should happen to the application.  If an application is in one of these statuses, it's too late
	 * to do anything about a failure.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function is_in_holding_status($parameters) 
	{
		if(parent::is_in_holding_status($parameters))
		{
			return 1;
		}
		//Does this meet the general criteria for in holding status?  Does it meet our specific criteria as well?
	
	
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);

		$ignore = array();
		$ignore[] = $asf->toId('chargeoff::collections::customer::*root');
		//Ain't no going back from 2nd tier!
		$ignore[] = $asf->toId('pending::external_collections::*root');
		$ignore[] = $asf->toId('sent::external_collections::*root');
		//You're ready to have a quickcheck.  There's no going back!
		$ignore[] = $asf->toId('ready::quickcheck::collections::customer::*root');
		//We're not adding QC sent to this list.  If it was on the list, how would we process QC failures?
			
		if(in_array($application->application_status_id,$ignore))
		{
			return 1;
		}
		return 0;
	}

	/**
	 * This pretends that nothing happened.  If the customer is paid off, it moves thhem back to their previous status
	 * (because they're no longer paid off!).  It then runs complete schedule to regenerate the schedule and 
	 * continue as if nothing happened.  This behavior typically happens when a "bonus" payment fails.  Like a non-fatal paydown,
	 * a manual payment, or a non-fatal payout.
	 *
	 * @param unknown_type $parameters
	 */
	function pretend_nothing_happened($parameters)
	{
		//if the loan is inactive, revert the status to the previous status.
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$application =  ECash::getApplicationByID($parameters->application_id);
		$inactive_statuses = array();
		$inactive_statuses[] = $asf->toId('paid::customer::*root');
		$inactive_statuses[] = $asf->toId('settled::customer::*root');
		//If status is Inactive(Paid), rollback to the previous status
		if(in_array($application->application_status_id,$inactive_statuses))
		{
			if($prev_status = $application->getPreviousStatus())
			{
				Update_Status(NULL, $parameters->application_id, $prev_status->application_status_id);
			}
		}
		//make a note that we're looking the other way on this failure.
		$this->Log("There was a return on a transaction that we just don't care about!");
		//regenerate the schedule.
		Complete_Schedule($parameters->application_id);		
	}
	
	/**
	 * Funding of the application failed.  Adjust out the fees/interest, truncate the schedule,
	 *  and move the app into funding_failed status.
	 * We might want to prevent this from happening on an app that's been paid off?
	 *
	 * @param unknown_type $parameters
	 */
	function funding_failed($parameters)
	{
		$status = $parameters->verified;

		// Gather the total of all unpaired fees/scs, and adjust for it.
		$total = 0.0;
		foreach ($status->posted_schedule as $e) 
		{
			if(($e->type == 'assess_service_chg') || ($e->type == 'converted_service_chg_bal')) 
			{
				if($e->status == 'complete')
				{
					$total += $e->fee_amount;
				}
			}
		}

		foreach ($status->outstanding['ach'] as $e) 
		{
			$total += $e->fee_amount;
		}
 		$db = ECash::getMasterDb();
		try 
		{
			$db->beginTransaction();
	
			// Remove the schedule immediately
			Remove_Unregistered_Events_From_Schedule($parameters->application_id);
	
			if ($total > 0.0) 
			{
				$today = date("Y-m-d");
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$total);
				$e = Schedule_Event::MakeEvent($today, $today, $amounts,
							       'adjustment_internal',
							       'Adjusting out all accrued fees due to failure.');
				Post_Event($parameters->application_id, $e);
			}
			$db->commit();
		} catch (Exception $e) {
			$this->Log(__METHOD__.": Unable to modify account.");
			$db->rollBack();
			throw $e;
		}

		$this->updateStatus($parameters->application_id, 'funding_failed::servicing::customer::*root');
	}
	
	/**
	 * Moves an app into QC ready status!  If an application qualifies for quickchecks we....
	 * Check to see if they can have a fatal flag and fee assessed.
	 * Truncate the schedule.
	 * Expire all affiliations.
	 * Change the application's status to QC Ready.
	 * ????
	 * Profit
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function qc_ready($parameters)
	{
		$this->fatal_flag($parameters);
		//Remove scheduled events, expire affiliations, and move it to QC.
		Remove_Unregistered_Events_From_Schedule($parameters->application_id);
		$application = ECash::getApplicationById($parameters->application_id);
		$affiliations = $application->getAffiliations();
		$affiliations->expireAll();
		$this->updateStatus($parameters->application_id, 'ready::quickcheck::collections::customer::*root');
		return true;		
	}
	
	/**
	 * Moves an application into 2nd tier ready status! 
	 * Check to see if they can have a fatal flag and fee assessed.
	 * Truncate the schedule.
	 * Expire all affiliations.
	 * Change the application's status to 2nd tier pending!
	 * Kill your parents
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function second_tier_ready($parameters)
	{
		$this->fatal_flag($parameters);
		//Remove scheduled events, expire affiliations, and move it to 2nd tier.
		Remove_Unregistered_Events_From_Schedule($parameters->application_id);
		$application = ECash::getApplicationById($parameters->application_id);
		$affiliations = $application->getAffiliations();
		$affiliations->expireAll();
		$this->updateStatus($parameters->application_id, 'pending::external_collections::*root');
		return true;		
	}
	
	/**
	 * This is the end state for when arrangements or partials failed, and we haven't exceeded any limits, so we're gonna do it all
	 * over again!
	 * E-mails the customer.
	 * Checks to see if they can have a fatal flag and fee assessed.
	 * Fails their arrangement discount
	 * Moves them to collections contact status
	 * Removes them from any queues
	 * Truncates their schedule
	 * Adds them to the controlling agent's myqueue for 3 business days.
	 * Inserts them into the Collections General/Fatal queue with a delay of 3 business days
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function arrangements_process($parameters) 
	{
		//I'm really ashamed of this, it completely undoes any of the good provided by the CFE rules by adding these
		//bizarre delays on failures.
		//ToDo: Fix this!  There, all better!
		$pdc = $parameters->pdc;
		$application_id = $parameters->application_id;
		$today = date('Y-m-d');
		$agent = null;
		//Send Arrangements Missed letter
		//ECash_Documents_AutoEmail::Queue_For_Send($application_id, 'ARRANGEMENTS_MISSED', $parameters->status->fail_set[0]->transaction_register_id);		
		
		$application = ECash::getApplicationById($application_id);
		$flags = $application->getFlags();
		$this->fatal_flag($parameters);
		$affiliations = $application->getAffiliations();
		$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');

		if(!empty($currentAffiliation))
		{
			$agent = $currentAffiliation->getAgent();
		}
		//fail arrangements discount
		$this->fail_arrangement_discount($parameters);
		//Update status to Collections Contact, and only do the other stuff if 
		if($this->updateStatus($parameters->application_id, 'queued::contact::collections::customer::*root'))
		{
			$qm = ECash::getFactory()->getQueueManager();
			//remove from queues, just in case.  We don't want any CFE rules screwing things up for us!
			$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
			$days_forward = 0;
			$date_inactive_expiration = strtotime($today);
			Remove_Unregistered_Events_From_Schedule($application_id);
			//Insert into controlling agent's myqueue for 3 business days, if they have a controlling agent.
			if(!empty($agent))
			{
				$days_forward = 3;
				$date_inactive_expiration = strtotime($pdc->Get_Business_Days_Forward($today,$days_forward));
				$this->Log("Adding to {$agent->getAgentId()}'s Myqueue to expire in {$days_forward} business days on ".date('Y-m-d',$date_inactive_expiration));
				$agent->getQueue()->insertApplication($application, 'collections', $date_inactive_expiration);
			}
			
			
			//Insert into collections general/fatal queue after the 3 days
			if (!$flags->get('has_fatal_ach_failure') &&  !$flags->get('has_fatal_card_failure')) 
			{
				//Schedule fullpull if possible (no fatal ACH flag)
				if($date_pair = $this->getFullPullDate($parameters))
				{
					//Schedule_Full_Pull($parameters->application_id, NULL, NULL, $date_pair['event'], $date_pair['effective']);
				}
				$queue_name = 'collections_general';
			}
			else 
			{
				$queue_name = 'collections_fatal';
			}
			
			$this->Log("Adding to {$queue_name} to be available in {$days_forward} business days on ".date('Y-m-d',$date_inactive_expiration));
			$queue_item = $qm->getQueue($queue_name)->getNewQueueItem($application_id);
			$queue_item->DateAvailable = $date_inactive_expiration;
			$qm->moveToQueue($queue_item, $queue_name);

		}
		return true;		
	}
	/**
	 * This extends the parent fatal flag function which checks to see if the application has a fatal ACH, and then checks
	 * to see if they already have the fatal flag set.  If not, it sets it.
	 * 
	 * In addition to this functionality, this function also assesses an ACH fee if the flag was added.
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function fatal_flag($parameters)
	{
		$fatal = false;
		if(parent::fatal_flag($parameters))
		{
			//In addition to the basic fatal flag stuff, we're also going to assess an ACH fee as punishment for setting
			//the Fatal ACH Flag, that'll learn ya.
			$fatal = true;
			$today = date('Y-m-d');
			$this->Log("This doesn't have the fatal flag, let's charge them!");
				
			//Assess return fee for a fatal ACH!
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('fee', intval($parameters->rules['return_transaction_fee']));
			$event_schedule_id = $parameters->status->fail_set[0]->event_schedule_id;
								
			$e = Schedule_Event::MakeEvent($today, $today, $amounts, 'assess_fee_ach_fail', 
				"ACH fee on FATAL ACH return for {$event_schedule_id}");
					
			Post_Event($parameters->application_id, $e);
		}
		return $fatal;
	}
	
	/**
	 * For some inexplicable reason the failure DFA was run on an application without a failure!
	 * There's really nothing we can do but shrug our shoulders and ask 'lolwut?'
	 *
	 * @param unknown_type $parameters
	 * @return unknown
	 */
	function lolwut($parameters) 
	{
		$this->Log("No failures found in the fail_set for this application!");
		return false;
	}
	
}


?>
