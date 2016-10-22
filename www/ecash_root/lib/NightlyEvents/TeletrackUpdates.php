<?php

	/**
	 * TeleTrack requires updates similar to CRA for the following conditions:
	 * 
	 * - Paid Accounts
	 * - ChargeOffs
	 * - Cancellations
	 * 
	 * Since the updates are rather generic and the cases are few, rather than
	 * do what we did with CRA I just made this a nightly event. [BR]
	 * 
	 * Related Ticket: GForge #16875
	 *
	 */
	class ECash_NightlyEvent_TeletrackUpdates extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'teletrack_updates';
		protected $timer_name = 'teletrack_update_timer';
		protected $process_log_name = 'teletrack_updates';
		protected $use_transaction = FALSE;
		
		protected $status_list;
		protected $crypt;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
			
			$this->status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$this->crypt = new ECash_Models_Encryptor(ECash::getMasterDb());

		}

		/**
		 * The main function for pulling in all of the data and reporting it to DataX.
		 * 
		 * This pulls in all of the application data for Paid, Chargeoff / 2nd Tier, and 
		 * Cancellations and then sends the updates to DataX one at a time.  Afterwards
		 * both the request and the response are stored in the bureau_inquiry table
		 * so that it can be referenced later on.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->log->Write('Starting Teletrack Updates');
			
			$start_date = $this->start_date . " 00:00:00";
			$end_date = $this->end_date . ' 23:59:59';

			$applications = array();

			$paid_status_list = array();
			if($paid_status = $this->status_list->toId('paid::customer::*root'))
			{
				$paid_status_list[] = $paid_status;
				$paid_apps = $this->findApplications($start_date, $end_date, $paid_status_list, 'paid_off');
				$applications = array_merge($applications, $paid_apps);
			}

			$chargeoff_status_list = array();
			if($chargeoff = $this->status_list->toId('chargeoff::collections::customer::*root'))
			{
				$chargeoff_status_list[] = $chargeoff;
			}

			if($sent = $this->status_list->toId('sent::external_collections::*root'))
			{
				$chargeoff_status_list[] = $sent;
			}
			
			if(!empty($chargeoff_status_list))
			{
				$chargeoff_apps = $this->findApplications($start_date, $end_date, $chargeoff_status_list, 'chargeoff');
				$applications = array_merge($applications, $chargeoff_apps);
			}
			
			$cancelled_apps = $this->getCancellations($start_date, $end_date);
			$applications = array_merge($applications, $cancelled_apps);
			
			$datax_license_key = ECash::getConfig()->DATAX_LICENSE_KEY;
			$datax_password    = ECash::getConfig()->DATAX_PASSWORD;
			
			foreach($applications as $application_data)
			{
				$this->log->Write("Teletrack Update Ran For Application: " . $application_data->application_id);
				
				$DataX = new ECash_DataX($datax_license_key, $datax_password, $application_data->call_type);
				$DataX->setRequest("Status_Update");
				$DataX->setResponse("Status_Update");
				
				$DataX->execute((array)$application_data);
				$DataX->saveResult();
			}
			$this->log->Write('Finished Teletrack Updates');
		}

		/**
		 * Method to search for applications within a list of statuses
		 * 
		 * This method is pretty generic since we can use it for both Paid
		 * and Chargeoff scenarios.  That's why the teletrack_status is 
		 * passed in.
		 *
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @param array $status_list  array of integers
		 * @param string $teletrack_status 'paid_off'
		 * @return array of objects containing application data
		 */
		public function findApplications($start_date, $end_date, $status_list, $teletrack_status)
		{
			$insert_list = "'".implode("','",$status_list)."'";
			$mssql_db = ECash::getAppSvcDB();
			$query = 'CALL sp_fetch_app_info_by_status ("'.$insert_list.'", NULL, "'.$start_date.'");';
			$results = $mssql_db->query($query);

			$app_ids = array();
			$ruleset_ids = array();
			$return_data = array();
			while ($row = $results->fetch())
			{
				$app_ids[] = $row['application_id'];
				$ruleset_ids[] = $row['rule_set_id'];
				$app_ssns[$row['application_id']] = $row['ssn'];
			}

			$app_ids = array_unique($app_ids);
			$ruleset_ids = array_unique($ruleset_ids);
			$app_in = implode(',', $app_ids);
			$ruleset_in = implode(',', $ruleset_ids);

			$query = "
				SELECT
					at.application_id,
					rscpv.parm_value AS call_type, 
					at.transaction_code,
					'{$teletrack_status}' as status
				FROM
					application_teletrack AS at
					JOIN rule_set_component_parm_value AS rscpv
					JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
					JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
				WHERE
					at.application_id IN ({$app_in})
					AND rscpv.rule_set_id IN ({$ruleset_in})
					AND rc.name_short = 'datax_call_types'
					AND rcp.parm_name = 'teletrack_update' 
					AND	NOT EXISTS (
						SELECT 'X'
						FROM transaction_register AS tr
						JOIN transaction_type AS tt ON tt.transaction_type_id = tr.transaction_type_id
						WHERE tr.application_id = at.application_id
						AND tt.name_short IN ('cancel_fees', 'cancel_principal')
					)
				GROUP BY
					application_id";
			$result = $this->db->Query($query);

			$applications = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$row->ssn = $app_ssns[$row->application_id];
				$row->company_id = $this->company_id;
				$row->encryption_key_id = NULL;
				$applications[] = $row;
			}

			return $applications;
		}

		/**
		 * Get info from the transaction ledger and app teletrack for cancelations
		 * 
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @return array - indexed by app_id
		 */
		protected function getCancellationTransactionInfo($date_start, $date_end)
		{
			$query = "
				SELECT
					tl.application_id,
					tl.company_id,
					at.transaction_code
				FROM 
					transaction_ledger tl
					JOIN application_teletrack at ON at.application_id = tl.application_id
				WHERE
					tl.date_created BETWEEN '{$date_start}' AND '{$date_end}'
					AND tl.company_id = {$this->company_id}
					AND	tl.transaction_type_id IN (
							SELECT transaction_type_id
							FROM transaction_type
							WHERE name_short IN ('cancel_fees', 'cancel_principal')
						)
				ORDER BY tl.date_created ASC
			";
			$result = $this->db->Query($query);
			$app_info = array();
			while ($row = $result->fetch())
			{
				$app_info[$row['application_id']] = $row;
			}

			return $app_info;
		}

		/**
		 * Gets the rulesetids for applications in the app service
		 * 
		 * @param array $app_ids
		 * @return array - indexed by application_id
		 */
		protected function getRulesetIdsByAppIds($app_ids)
		{
			$mssql_db = ECash::getAppSvcDB();
			
			$app_ids = "'".implode("','",$app_ids)."'";
			$query = 'CALL sp_fetch_app_info_by_id ("'.applicationList.'");';
			$result = $mssql_db->query($query);
			$ruleset_ids = array();
			while ($row = $result->fetch())
			{
				$ruleset_ids[$row['application_id']] = $row['rule_set_id'];
			}

			return $ruleset_ids;
		}

		/**
		 * Gets the call type for rule set ids
		 * 
		 * @param array $ruleset_ids
		 * @return array - indexed by rule_set_id
		 */
		protected function getCallTypeByRuleSetIds($rule_set_ids)
		{
			$rule_set_in = implode(',', $rule_set_ids);
			$query = "
				SELECT 
					rscpv.rule_set_id,
					rscpv.parm_value AS call_type
				FROM
					rule_set_component_parm_value AS rscpv
					JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
					JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
				WHERE 
					rscpv.rule_set_id IN ({$rule_set_in})
					AND rc.name_short = 'datax_call_types'
					AND rcp.parm_name = 'teletrack_update'
			";
			$results = $this->db->query($query);
			$call_type_values = array();
			while ($row = $results->fetch())
			{
				$call_type_values[$row['rule_set_id']] = $row['call_type'];
			}

			return $call_type_values;
		}

		/**
		 * Method to retrieve applications that have cancelled
		 * 
		 * This is similar to findApplications except it looks only
		 * for accounts that have had complete cancellation transactions.
		 *
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @return array of objects containing application data
		 */
		protected function getCancellations($date_start, $date_end)
		{
			$app_info = $this->getCancellationTransactionInfo($date_start, $date_end);
			$ruleset_ids = $this->getRulesetIdsByAppIds(array_keys($app_info));
			$call_types = $this->getCallTypeByRuleSetIds($ruleset_ids);

			$apps = array();
			foreach ($app_info as $app_info_row)
			{
				$app = new stdClass();
				foreach ($app_info_row as $key => $field)
				{
					if (!strcasecmp($key,'rule_set_id'))
					{
						$app->call_type = $ruleset_values[$field];
					}
					else 
					{
						$app->$key = $field;
					}
				}
				$app->call_type = $call_types[$app->application_id];
				$app->status = 'cancel';
				$app->encryption_key_id = NULL;
				$apps[] = $app;
			}

			return $apps;
		}
	}
?>
