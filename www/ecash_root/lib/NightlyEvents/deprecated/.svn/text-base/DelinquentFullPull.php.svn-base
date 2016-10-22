<?php
/**
 *  DelinquentFullPull.php
 *
 * Added to cfe codebase in case anyone else needs this functionality in the future.
 * Made to fulfill ticket GForge #17480.
 *
 * @deprecated
 * 
 */

class ECash_NightlyEvent_DelinquentFullPull extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, no use in setting up a business rule
	protected $business_rule_name = null; // 'delinquent_full_pull';
	protected $timer_name = 'Delinquent_Full_Pull';
	protected $process_log_name = 'delinquent_full_pull';
	protected $use_transaction = FALSE;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}

	/**
	 * A wrapper for the function Resolve_Past_Due_To_Active()
	 * originally located in ecash3.0/cronjobs/nightly.php
	 * and relocated into this class.
	 */
	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->Delinquent_Full_Pull($this->start_date, $this->end_date);
	}
	
	/**
	 * getMaxDelinquencyLimit
	 * Because I'm changing this process to operate on a per-company, per-loantype basis, that means that one single date limit
	 * will not fit all when rounding up the applications.  Also, we won't know what limit to use until we have an application to
	 * grab the ruleset for.  This means that we're going to get a much larger set of data from the query.  To help limit the size
	 * of these results, this function exists.
	 * It grabs all the full_pulls->days_delinquent rules for the current company and returns the highest value, this gives us
	 * a starting point. [W!-12-08-2008][#22081]
	 *
	 * @return int - the maximum number of days being used for the delinquency limit
	 */
	private function getMaxDelinquencyLimit()
	{
		
		//get all the diffferent values for full_pulls->days_delinquent
		$query = "
			SELECT 
				rscpv.parm_value
			FROM 
				rule_set_component_parm_value rscpv
			JOIN
				rule_component_parm rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
			JOIN
				rule_component rc ON rc.rule_component_id = rscpv.rule_component_id
			JOIN 
				rule_set rs ON rs.rule_set_id = rscpv.rule_set_id
			JOIN 
				loan_type lt ON lt.loan_type_id = rs.loan_type_id
			WHERE
				rc.name_short = 'full_pulls'
			AND
				rcp.parm_name = 'days_delinquent'
			AND
				lt.company_id = {$this->company_id}
			";
		$results = $this->db->query($query);
		
		//get the highest number of all the values		
		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			if($row->parm_value > $limit)
			{
				$limit = $row->parm_value;
			}
		}
		//return the highest number
		$limit = $limit ? $limit : 90;
		
		return $limit;
	}
	
	private function Delinquent_Full_Pull($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

		// Ok, as requested in #17480, 
		// if the 15th or the last day of the month should be the latest paydate
		$mid_month      = date('m', strtotime($start_date)) . '/15/' . date('Y', strtotime($start_date));

		// Date Effective
		$mid_month_de   = $mid_month;

		while ($pdc->Is_Weekend(strtotime($mid_month_de)) || $pdc->Is_Holiday(strtotime($mid_month_de)))
		{
			$mid_month_de = date('m/d/Y', strtotime($mid_month_de . '-1 day'));
		}

		// Now get the action date
		$mid_month_ad = date('m/d/Y', strtotime($mid_month_de . '-1 day'));

		while ($pdc->Is_Weekend(strtotime($mid_month_ad)) || $pdc->Is_Holiday(strtotime($mid_month_ad)))
		{
			$mid_month_ad = date('m/d/Y', strtotime($mid_month_ad . '-1 day'));
		}

		// Do the last day of the month
		$max_month      = date('m', strtotime($start_date)) . '/' . date('t', strtotime($start_date)) . '/' . date('Y', strtotime($start_date));

		// Date Effective
		$max_month_de   = $max_month;

		while ($pdc->Is_Weekend(strtotime($max_month_de)) || $pdc->Is_Holiday(strtotime($max_month_de)))
		{
			$max_month_de = date('m/d/Y', strtotime($max_month_de . '-1 day'));
		}

		// Now get the action date
		$max_month_ad = date('m/d/Y', strtotime($max_month_de . '-1 day'));

		while ($pdc->Is_Weekend(strtotime($max_month_ad)) || $pdc->Is_Holiday(strtotime($max_month_ad)))
		{
			$max_month_ad = date('m/d/Y', strtotime($max_month_ad . '-1 day'));
		}

		/*
		   echo "mid_month_ad = " . $mid_month_ad . "\n";
		   echo "mid_month_de = " . $mid_month_de . "\n";
		   echo "max_month_ad = " . $max_month_ad . "\n";
		   echo "max_month_de = " . $max_month_de . "\n";
		*/

		$cur_timestamp = date('m/d/Y', strtotime($start_date . '+1 day'));

		// check if today is $max_month_ad or $mid_month_ad, if so, schedule a full pull
		if ($cur_timestamp == $mid_month_ad || $cur_timestamp == $max_month_ad)
		{
			
			//The limit to use for the initial query to get fullpull candidates [W!-12-08-2008][#22081]
			$limit = $this->getMaxDelinquencyLimit();
			
			$ad = ($cur_timestamp == $mid_month_ad) ? $mid_month_ad : $max_month_ad;
			$de = ($cur_timestamp == $mid_month_ad) ? $mid_month_de : $max_month_de;

			$status_map = Fetch_Status_Map(FALSE);

			// All collections statuses
			//$collections_statuses[] = Search_Status_Map('indef_dequeue::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('new::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('arrangements_failed::arrangements::collections::customer::*root', $status_map);
			//            $collections_statuses[] = Search_Status_Map('current::arrangements::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('hold::arrangements::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('amortization::bankruptcy::collections::customer::*root', $status_map);
			//$collections_statuses[] = Search_Status_Map('dequeued::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('follow_up::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('queued::contact::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('arrangements::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('ready::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('return::quickcheck::collections::customer::*root', $status_map);
			$collections_statuses[] = Search_Status_Map('sent::quickcheck::collections::customer::*root', $status_map);

			$collections_list = implode(',', $collections_statuses);

			// Get all apps that have been in collections for the specified number of days or more [#22081]
			
			// This is the same query from the charge-off nightly. I question the usefulness
			// of using the date of the first failure, but I'm just going by what's already
			// been done.
			
			//Limited results to the current company, to help control the size of the resultset
			//Changed limit check from < to <=
			//The actual determination of whether they meet the delinquency days limit is done outside of the query  [W!-12-08-2008][#22081]
			$query = "
				SELECT 
					application_id,
					lf.first_failure,
					rule_set_id
				FROM 
					application
				JOIN 
					(
						SELECT 
							application_id, 
							MIN(date_effective) AS first_failure
						FROM 
							transaction_register
						WHERE 
							transaction_status =  'failed'
						GROUP BY 
							application_id
						HAVING 
							DATE_ADD(first_failure, INTERVAL {$limit} DAY) <= NOW() 
					) lf USING (application_id)
				LEFT OUTER JOIN 
					(
						SELECT 
							application_id, 
							COUNT(*) AS scheduled_count
						FROM 
							event_schedule
						WHERE 
							event_status = 'scheduled'
						GROUP BY 
							application_id
					) es USING (application_id)
				-- Check for Previous Full Pulls
                LEFT JOIN 
					(
						SELECT 
							application_id, 
							COUNT(*) AS num_full_pulls
						FROM 
							event_schedule
                        JOIN
                            event_type USING (event_type_id)
						WHERE 
							event_status = 'registered'
                        AND
                            name_short = 'full_balance'
						GROUP BY 
							application_id
					) AS prev_fp USING (application_id)
				LEFT OUTER JOIN
					(
						SELECT distinct application_id FROM ach join ach_return_code using (ach_return_code_id) where is_fatal = 'yes'
					) fatals USING (application_id)
				WHERE 
					scheduled_count IS NULL
				AND 
					application_status_id IN ({$collections_list})
				AND 
					company_id = {$this->company_id}
				AND
					fatals.application_id IS NULL
				AND
					prev_fp.num_full_pulls IS NULL
				ORDER BY
					first_failure ASC
			";

			$results = $this->db->query($query);

			while ($row = $results->fetch(PDO::FETCH_OBJ))
			{
				try
				{
					//get the delinquency limit rule
					$application = ECash::getApplicationById($row->application_id);
					$rules = $application->getBusinessRules();
					$app_limit = $rules['full_pulls']['days_delinquent'] ? $rules['full_pulls']['days_delinquent'] : 90;

					//If it meets the delinquency limit rule for it's rule set, FULLPULL!
					if (strtotime($row->first_failure . "+{$app_limit} day") <= time()) 
					{
						$this->log->Write("Adding full pull event for Application ID: {$row->application_id}.");
	
						// Remove it from all queues
						$qm = ECash::getFactory()->getQueueManager();
						$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($row->application_id));
	
						// Generate a full pull event
						// doing this manually, because I'm already over on time and there's an infinite loop somewhere, could
						// be from bum data on RC/Local
						Remove_Unregistered_Events_From_Schedule($row->application_id);
	
						$balance_info = Fetch_Balance_Information($row->application_id);
	
						$balance = array(
								'principal' => -$balance_info->principal_pending,
								'service_charge' => -$balance_info->service_charge_pending,
								'fee' => -$balance_info->fee_pending
								);
	
						$amounts = AmountAllocationCalculator::generateGivenAmounts($balance);
	
						$e = Schedule_Event::MakeEvent($ad, $de, $amounts, 'full_balance', 'Full balance pull on delinquent account');
	
						Record_Event($row->application_id, $e);
					}
				}
				catch (Exception $e)
				{
					$this->log->Write("FAILED adding full pull event for Application ID: {$row->application_id}. This is bad FIXME.");
					throw $e;
				}
			}
		}

		return TRUE;
	}
}


?>
