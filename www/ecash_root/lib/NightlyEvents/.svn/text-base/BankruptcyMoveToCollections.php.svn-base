<?php

	//	$manager->Define_Task('Status_Bankruptcy_Move_To_Collections', 'move_bankruptcy_to_collections', $sbmtc_timer, 'bankruptcy_move', array($server));

	class ECash_NightlyEvent_BankruptcyMoveToCollections extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'move_bankruptcy_to_collections';
		protected $timer_name = 'Bankruptcy_Move_To_Collections';
		protected $process_log_name = 'bankruptcy_move';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Status_Bankruptcy_Move_To_Collections()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->Status_Bankruptcy_Move_To_Collections($this->server);
		}

		private function Status_Bankruptcy_Move_To_Collections($server)
		{
			$log = get_log("scheduling");

			require_once(SQL_LIB_DIR ."scheduling.func.php");
			require_once(SQL_LIB_DIR ."util.func.php");
			require_once(CUSTOMER_LIB . "bankruptcy_to_collections_dfa.php");

			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'company_level');
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$interval = ($rules['bankruptcy_expire']) ? $rules['bankruptcy_expire'] : 120;

			$status_list = "";
			foreach (array('amortization', 'dequeued', 'queued', 'unverified') as $status)
			{
				$status_list .= "'{$status}::bankruptcy::collections::customer::*root',";
			}
			$status_list = rtrim($status_list, ',');

			$cutoff_date = date("Y-m-d H:i:s", strtotime("-{$interval} days"));

			$mssql_query = 'CALL sp_commercial_nightly_bankruptcy_to_collections ("'.$status_list.'", "'.$cutoff_date.'");';

			$app_service_result = ECash::getAppSvcDB()->query($mssql_query);

			while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
			{
				try
				{
					$this->db->beginTransaction();

					$application_id = $row->application_id;

					// Prep data for the DFA
					$data     = Get_Transactional_Data($row->application_id);
					$parameters = new stdClass();
					$parameters->application_id = $row->application_id;
	
					$parameters->log      = $log;
					$parameters->info     = $data->info;
					$parameters->rules    = Prepare_Rules($data->rules, $data->info);
					$parameters->schedule = Fetch_Schedule($row->application_id);
					$parameters->status   = Analyze_Schedule($parameters->schedule);
					$parameters->verified = Analyze_Schedule($parameters->schedule, true);
	
					// Set up the DFA and run it.
					if (!isset($dfas['btc'])) {
						$dfa = new BToCDFA($server);
						$dfa->SetLog($log);
						$dfas['btc'] = $dfa;
					} else {
						$dfa = $dfas['btc'];
					}
					$dfa->run($parameters);
	
					// Do we still need this?
					$application = ECash::getApplicationById($row->application_id);
					$affiliations = $application->getAffiliations();
					$affiliations->expireAll();
					
					$this->db->commit();
					
					$this->log->Write("Moved application {$application_id} from bankruptcy.");
				}
				catch (Exception $e)
				{
					$this->db->rollback();
	
					$this->log->Write("FAILED moving application {$application_id} from bankruptcy.");
					throw $e;
				}
			}

			return true;
		}


	}

?>
