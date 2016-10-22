<?php

	//	$manager->Define_Task('Set_Completed_Accounts_To_Inactive', 'completed_accounts_to_inactive', $scati_timer, 'set_inactive', array($server));

	class ECash_NightlyEvent_SendZeroBalanceLetter extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'send_zbletter';
		protected $timer_name = 'SendZeroBalanceLetter';
		protected $process_log_name = 'send_zbletter';
		protected $use_transaction = FALSE;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();
		}

		/**
		 * A wrapper for the function Set_Completed_Accounts_To_Inactive()
		 * originally located in ecash3.0/cronjobs/nightly.php
		 * and relocated into this class.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();

			$this->SendZeroBalanceLetters($this->server);
		}

		private function SendZeroBalanceLetters(Server $server)
		{
			$holidays  = Fetch_Holiday_List();
			$pdc       = new Pay_Date_Calc_3($holidays);
			$biz_rules = new ECash_BusinessRulesCache($this->db);

			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($this->company, 'offline_processing');
			$rule_set_id  = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$rules        = $biz_rules->Get_Rule_Set_Tree($rule_set_id);

			$days = $rules['zbletter_wait'];
			if (empty($days))
			{
				$days = 0;
			}

			$check_date = $pdc->Get_Business_Days_Backward(date("Y-m-d"), $days);

			$status_list = "'paid::customer::*root','recovered::external_collections::*root'";

			$mssql_query = 'CALL sp_commercial_nightly_zero_balance_letter ("'.$status_list.'", "'.$check_date.'");';

			$app_service_result = ECash::getAppSvcDB()->query($mssql_query);

			while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
			{
				//Send the Zero balance letter
				$this->log->Write("Application {$row->application_id}: Sending Zero Balance Letter");
				ECash_Documents_AutoEmail::Queue_For_Send($row->application_id, 'ZERO_BALANCE_LETTER');
			}
		}
	}

?>
