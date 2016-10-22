<?php

	/**
	 * CL Verify requires updates similar to CRA for the following conditions:
	 * 
	 * - Paid Accounts
	 * - ChargeOffs
	 * - Cancellations
	 * - Past Due Accounts
	 * - Accounts that went from Past Due to Active
	 * 
	 * This is almost an exact copy of the ECash_NightlyEvent_TeletrackUpdates
	 * class since the functionality is the same. [BR]
	 * 
	 * Related Ticket: GForge #20017 - CL Verify
	 * Related Ticket: GForge #16875 - TeleTrack Updates
	 * Related Ticket: GForge #22243 - Add past due and active reporting
	 */
	class ECash_NightlyEvent_CLVerifyUpdates extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'cl_verify_updates';
		protected $timer_name = 'clverify_update_timer';
		protected $process_log_name = 'clverify_updates';
		protected $use_transaction = FALSE;
		protected $crypt;
		protected $bureau_data;

		/**
		 * Used to get the call type name lookups
		 * from the Business Rules in 
		 * ECash_Data_Bureau::getIDVInformation()
		 */
		const rule_component_name = 'datax_call_types';
		const rule_component_parm_name = 'cl_verify_update';

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();

			$factory = ECash::getFactory();
			$this->crypt       = $factory->getModel('Encryptor');
			$this->bureau_data = $factory->getData('Bureau');
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
			
			$this->log->Write('Starting CLVerify Updates');

			$start_date = $this->start_date . " 00:00:00";
			$end_date = $this->end_date . ' 23:59:59';

			//Paid off status list
			$paid_status_list = array('paid::customer::*root');
			//Sometimes these statuses don't exist or apply to a company. So we only want to search for them if they
			//exist [W!-12-09-2008][#20017]
			$chargeoff_status_list = array(
				'chargeoff::collections::customer::*root',
				'sent::external_collections::*root'
			);
			//Transition statuses.  We may want to report on a specific status change, but sometimes one of these intermediate
			//statuses get's thrown in there, example:
			//When an application is past due, the typical status transition could be Active->Past Due or Active->Collections New
			//However, sometimes the status transition can look like  Active->Made Arrangements->Collections New or Active->Default->Collections New
			//So we need these statuses when trying determine whether or not to report an app based on a 3-part status history [W!-12-19-2008][#22243]
			$transition_status_list = array(
				'current::arrangements::collections::customer::*root',
				'default::collections::customer::*root'
			);
			//Past due status list.  This is past due & collections new
			$past_due_status_list = array(
				'new::collections::customer::*root',
				'past_due::servicing::customer::*root'
			
			);
			//Collections status list.  All other collections statuses [#38850] (taken from Analytics)
			$collections_status_list = array(
				'unverified::deceased::collections::customer::*root',
				'collections_rework::collections::customer::*root',
				'hold::arrangements::collections::customer::*root',
				'arrangements_failed::arrangements::collections::customer::*root',
				'follow_up::contact::collections::customer::*root',
				'dequeued::contact::collections::customer::*root',
				'queued::contact::collections::customer::*root',
				'pending::external_collections::*root'
			);

			//Active statuses
			$active_status_list = array('active::servicing::customer::*root');
			$active_apps = $this->findStatusChanges(
				$start_date,
				$end_date,
				$past_due_status_list, // start
				$transition_status_list, // inter
				$active_status_list, //end
				'active'
			);

			//It's also possible for an application to become past due by moving from inactive(paid)
			// to collections.  Gonna add inactive to the active status list for this
			$active_status_list[] = 'paid::customer::*root';
			$past_due_apps = $this->findStatusChanges(
				$start_date,
				$end_date,
				$active_status_list,
				$transition_status_list,
				$past_due_status_list,
				'past_due'
			);

			$collections_apps = $this->findApplications(
				$start_date,
				$end_date,
				$collections_status_list,
				'collections'
			);
			$paid_apps = $this->findApplications(
				$start_date,
				$end_date,
				$paid_status_list,
				'paid_off'
			);
			$chargeoff_apps = $this->findApplications(
				$start_date,
				$end_date,
				$chargeoff_status_list,
				'chargeoff'
			);

			$cancelled_apps = $this->getCancellations($start_date, $end_date);

			$applications = array_merge(
				$paid_apps,
				$collections_apps,
				$chargeoff_apps,
				$cancelled_apps,
				$past_due_apps,
				$active_apps
			);

			$datax_license_key = ECash::getConfig()->DATAX_LICENSE_KEY;
			$datax_password = ECash::getConfig()->DATAX_PASSWORD;

			foreach($applications as $application_data)
			{
				$this->log->Write("CLVerify Update Ran For Application: " . $application_data->application_id);

				$DataX = new ECash_DataX($datax_license_key, $datax_password, $application_data->call_type);
				$DataX->setRequest("Status_Update_CL_Verify");
				$DataX->setResponse("Status_Update");
				$DataX->execute((array)$application_data);
				$DataX->saveResult();
			}

			$this->log->Write('Finished CLVerify Updates');
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
		public function findApplications($date_start, $date_end, $status_list, $teletrack_status)
		{
			$status_list = "'".implode("','",$status_list)."'";
			$mssql_db = ECash::getAppSvcDB();
			$query = 'CALL sp_fetch_app_info_by_statuses_between_dates ("'.$status_list.'","'.$date_start.'",".'.$date_end.'");';
			$result = $mssql_db->query($query);
			
			$app_ids = array();
			while ($row = $result->fetch())
			{
				$app_ids[] = $row['application_id'];
			}
			if (empty($app_ids))
			{
				return $app_ids;
			}

			$id_placeholders = trim(str_repeat('?,', count($app_ids)),',');

			$query = "
				SELECT tr.application_id
				FROM transaction_register AS tr
					JOIN transaction_type AS tt ON tt.transaction_type_id = tr.transaction_type_id
				WHERE tr.application_id IN ({$id_placeholders})
					AND tt.name_short IN ('cancel_fees', 'cancel_principal')";
			$exclude_app_ids = DB_Util_1::querySingleColumn($this->db, $query, $app_ids);

			$app_ids = array_diff($app_ids, $exclude_app_ids);
			if (empty($app_ids))
			{
				return $app_ids;
			}
			return $this->bureau_data->getIDVInformation(
				$application_ids,
				$teletrack_status,
				self::rule_component_name,
				self::rule_component_parm_name
			);
		}

		/**
		 * This finds statuses based on a specific status transition.
		 *
		 * This is needed to track when applications go past_due (a
		 * typical application in collections will shift through
		 * numerous collections statuses, we do not want these to
		 * report past_due multiple times if the application did no go
		 * 'active' in the mean-time.  This also tracks if an
		 * application goes active from being past due.  Neither
		 * active, nor past due are terminal statuses so it is
		 * possible for it to change back and
		 * forth. [W!-12-19-2008][#22243]
		 * 
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @param array $start_status_list array of integers denoting the status the app needed to be in prior to the end_status
		 * @param array $intermediate_status_list array of integers denoting the status the app may have passed through
		 * @param array $end_status_list array of integers denoting the status you want the app to currently be in
		 * @param string $teletrack_status 'active'/'past_due'
		 * @return array of objects containing application data
		 */
		public function findStatusChanges($date_start, $date_end, $start_status_list, $intermediate_status_list, $end_status_list, $teletrack_status)
		{
			$start_status_list = "'".implode("','",$start_status_list)."'";
			$intermediate_status_list = "'".implode("','",$intermediate_status_list)."'";
			$end_status_list = "'".implode("','",$end_status_list)."'";

			$mssql_db = ECash::getAppSvcDB();
			$query = 'CALL sp_find_status_changes ("'.$start_status_list.'","'.$intermediate_status_list.'","'.$end_status_list.'","'.$date_start.'","'.$date_end.'");';
			$result = $mssql_db->query($query);

			if (!$result->rowCount())
			{
				return array();
			}

			$application_ids = array();
			while ($row = $result->fetch())
			{
				$application_ids[] = $row['application_id'];
			}

			$bureau_info = $this->bureau_data->getIDVInformation(
				$application_ids,
				$teletrack_status,
				self::rule_component_name,
				self::rule_component_parm_name
			);

			return $bureau_info;
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
		public function getCancellations($start_date, $end_date)
		{
			$query = "
				SELECT 	tl.application_id
				FROM
					transaction_ledger AS tl
				WHERE
					    tl.date_created BETWEEN ? AND ?
					AND 
						tl.company_id = ?
					AND 
						tl.transaction_type_id IN (
							SELECT transaction_type_id
							FROM transaction_type
							WHERE name_short IN ('cancel_fees', 'cancel_principal')
						)
					ORDER BY
						tl.date_created ASC ";

			$application_ids = DB_Util_1::querySingleColumn($this->db, $query, array($start_date, $end_date, $this->company_id));
			if(empty($application_ids))
			{
				return array();
			}

			return $this->bureau_data->getIDVInformation(
				$application_ids,
				'cancel',
				self::rule_component_name,
				self::rule_component_parm_name
			);
		}
	}

?>
