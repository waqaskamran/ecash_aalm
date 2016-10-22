<?php
/**
 * This moves an application to the next level of collections (2nd tier or quickchecks, depending) after a set number of 
 * days after they've entered collections contact.  
 *
 */

class ECash_NightlyEvent_MoveCollectionsToExternal extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	// this needs to be ran everyday, I should make a business rule anyways, but, in our corporate culture sometimes we sacrifice quality for speed.
	protected $business_rule_name = null; // 'move_collections_to_external';
	protected $timer_name = 'move_collections_to_external';
	protected $process_log_name = 'move_collections_to_external';
	protected $use_transaction = FALSE;

	public function __construct()
	{
		$this->classname = __CLASS__;

		parent::__construct();
	}

	public function run()
	{
		// Sets up the Applog, any other pre-requisites in the parent
		parent::run();

		$this->moveToExternalCollections($this->start_date, $this->end_date);
	}
	
	/**
	 * getMaxDelinquencyLimit
	 * Because I'm changing this process to operate on a per-company, per-loantype basis, that means that one single date limit
	 * will not fit all when rounding up the applications.  Also, we won't know what limit to use until we have an application to
	 * grab the ruleset for.  This means that we're going to get a much larger set of data from the query.  To help limit the size
	 * of these results, this function exists.
	 * It grabs all the days_to_ext_collections->days_to_ext_collections rules for the current company and returns the highest value, this gives us
	 * a starting point.
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
				rc.name_short = 'days_to_ext_collections'
			AND
				rcp.parm_name = 'days_to_ext_collections'
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
		$limit = $limit ? $limit : 30;
		
		return $limit;
	}
	
	private function moveToExternalCollections($start_date, $end_date)
	{
		$holidays  = Fetch_Holiday_List();
		$pdc       = new Pay_Date_Calc_3($holidays);
		$biz_rules = new ECash_BusinessRulesCache($this->db);

		$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
		$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
			
		//Used to limit the query for grabbing apps to move on to the next step.
		$limit = $this->getMaxDelinquencyLimit();
		

		$status_map = Fetch_Status_Map(FALSE);

		// All collections statuses
		$collections_statuses[] = Search_Status_Map('follow_up::contact::collections::customer::*root', $status_map);
		$collections_statuses[] = Search_Status_Map('queued::contact::collections::customer::*root', $status_map);
		
		$qc_codes = ECash::getConfig()->QC_ALLOWABLE;
		$qc_list 		= implode("','", $qc_codes);
		$collections_list = implode(',', $collections_statuses);
		$start_status = Search_Status_Map('queued::contact::collections::customer::*root', $status_map);
		// Get all apps that have been in collections for the specified number of days or more 
		
		// I kinda ripped off the full pull query.  This is the same basic concept, but not....
		
		//The actual determination of whether they meet the delinquency days limit is done outside of the query 
		$query = "
			SELECT 
				application_id,
                es.scheduled_count,
                tr.pending_count,
                ass.entered_collections,
                if(fatal_flag.application_id,1,0) AS fatal,
                if(achr.name_short IN ('{$qc_list}'),1,0) AS qc_eligible,
				rule_set_id
			FROM 
				application
			JOIN 
				(
					SELECT 
						application_id, 
						MIN(date_created) AS entered_collections
					FROM 
						status_history
					WHERE 
						application_status_id = {$start_status}
					GROUP BY 
						application_id
					HAVING 
						DATE_ADD(entered_collections, INTERVAL {$limit} DAY) <= NOW() 
				) ass USING (application_id)
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
			LEFT OUTER JOIN
            (
                SELECT
                    application_id,
                    COUNT(*) AS pending_count
               FROM
                    transaction_register
               WHERE
                    transaction_status = 'pending'
              GROUP BY
                    application_id
            ) tr USING (application_id)
			LEFT OUTER JOIN
				(
					SELECT 
						a.application_id ,
                        name_short,
                        ach_report_id
					FROM 
						ach a
					JOIN 
						ach_return_code using (ach_return_code_id) 
                    WHERE ach_status = 'returned'
                    AND a.ach_report_id = (SELECT MAX(ach_report_id) FROM ach WHERE application_id = a.application_id AND ach_return_code_id IS NOT NULL)
                    GROUP BY a.application_id
				) achr USING (application_id)
			LEFT OUTER JOIN
                (
                    SELECT 
                        application_id
                    FROM
                        application_flag
                    JOIN
                      flag_type USING (flag_type_id)
                   WHERE 
                      name_short IN ('has_fatal_ach_failure','has_fatal_card_failure')
                   AND
                     application_flag.active_status = 'active'
                ) fatal_flag USING (application_id)
			WHERE 
				scheduled_count IS NULL
			AND
				pending_count IS NULL
			AND
                fatal_flag.application_id IS NOT NULL
			AND 
				application_status_id IN ({$collections_list})
			AND 
				company_id = {$this->company_id}
			ORDER BY
				entered_collections ASC;
		";

		$results = $this->db->query($query);

		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			try
			{
				//get the delinquency limit rule
				$application_id = $row->application_id;
				$application = ECash::getApplicationById($application_id);
				$rules = $application->getBusinessRules();
				$app_limit = $rules['days_to_ext_collections'] ? $rules['days_to_ext_collections'] : 30;

				//If it meets the delinquency limit rule for it's rule set, Time to send it to the next step!
				if (strtotime($row->entered_collections . "+{$app_limit} day") <= time()) 
				{
					
					
					if($row->qc_eligible)
					{
						//send them to QC process
						$status_chain = 'ready::quickcheck::collections::customer::*root';
						
					}
					else 
					{
						//send them to 2nd tier process
						$status_chain = 'pending::external_collections::*root';
					}
					
					Remove_Unregistered_Events_From_Schedule($application_id);
					$affiliations = $application->getAffiliations();
					$affiliations->expireAll();
					Update_Status(null, $application_id, $status_chain, NULL, NULL, false);
					$this->log->Write("Expired from Collections.  Moving Application ID: {$application_id} to $status_chain.");

				}
			}
			catch (Exception $e)
			{
				$this->log->Write("FAILED Moving Application ID: {$row->application_id}. to next collections step!  This is bad FIXME.");
				throw $e;
			}
		}

		return TRUE;
	}
}


?>
