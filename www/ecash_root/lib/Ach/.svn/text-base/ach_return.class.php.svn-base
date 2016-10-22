<?php

/**
 * Abstract ACH Return Class
 *
 */
require_once(LIB_DIR . "Ach/ach_return_interface.iface.php");
require_once(LIB_DIR . "Ach/ach_utils.class.php");

abstract class ACH_Return implements ACH_Return_Interface
{
	protected $log;
	protected $server;
	protected $company_id;
	protected $ach_utils;
	protected $holiday_ary;
	protected $paydate_obj;
	protected $paydate_handler;
	protected $biz_rules;
	protected $business_day;

	private static $RS		  = "\n";

	/**
	 * Used to determine whether or not the returns file will contain
	 * both the returns and corretions in one file or to retrieve and process
	 * two separate files.
	 */
	protected $COMBINED_RETURNS = FALSE;

	public function __construct(Server $server)
	{
		$this->log = ECash::getLog('ach');
		$this->server			= $server;
		$this->db = ECash::getMasterDb();
		$this->company_id		= $server->company_id;
		$this->company_abbrev	= strtolower($server->company);
		$this->ach_utils = new ACH_Utils($server);
	}

	/**
	 * Processes the ACH Returns for a particular date range
	 *
	 * @param string $end_date
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Returns($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'returns', $override_start_date);
	}

	/**
	 * Processes the ACH Corrections for a particular date range
	 *
	 * @param string $end_date
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Corrections($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'corrections', $override_start_date);
	}

	// Grabs application list from standby table and runs adjust_schedule.
	/**
	 * Fetches a list of applications in the standby table and then launches the
	 * Failure DFA.
	 *
	 * @param int $app_limit
	 * @return BOOL
	 */
	public function Reschedule_Apps($app_limit = 100)
	{
		require_once(CUSTOMER_LIB."failure_dfa.php");

		//mantis:7357 - filter company
		$query = "
			SELECT DISTINCT application_id
			FROM standby
			WHERE process_type='reschedule'
			AND company_id = {$this->company_id}
			ORDER BY date_created
			LIMIT {$app_limit} ";

		$result = $this->db->Query($query);

		if($result->rowCount() == 0) {
			return false;
		}

		$reschedule_list = array();

		while ($row = $result->fetch(PDO::FETCH_OBJ)) {
			$reschedule_list[] = $row->application_id;
		}

		$reschedule_list = array_unique($reschedule_list);
		$this->log->Write("Apps to reschedule: ". count($reschedule_list));

		foreach($reschedule_list as $application_id)
		{
			try
			{
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $this->server;

				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				Remove_Standby($application_id, 'reschedule');
			}
			catch (Exception $e)
			{
				$this->log->Write("Unable to reschedule app {$application_id}: {$e->getMessage()}");
				Remove_Standby($application_id, 'reschedule');
				Set_Standby($application_id, $this->company_id, 'reschedule_failed');
			}
		}

		return true;
	}

	/**
	 * Validates the corrected ABA number is the correct string length
	 * and is a numeric value
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_COR_ABA ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) == 9		)
		{
			$normalized_value = $value;
			return true;
		}

		return false;
	}

	/**
	 * Validates the corrected Account number is the correct string
	 * length and is a numeric value
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_COR_Account ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) > 3		&&
		     strlen($value) < 18		)
		{
			$normalized_value = $value;
			return true;
		}
		return false;
	}

	/**
	 * Validates the banking transaction code and sets the normalized
	 * value to 'checking' or 'savings' since that is what is stored
	 * in the eCash application table
	 *
	 * @param string $value
	 * @param string $normalized_value
	 * @return BOOL
	 */
	protected function Validate_Tran_Code ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     $value >= 22	&&
		     $value <= 39 		)
		{
			if ($value <= 29)
			{
				$bank_account_type = 'checking';
			}
			else
			{
				$bank_account_type = 'savings' ;
			}
			$normalized_value = $bank_account_type;
			return true;
		}

		return false;
	}

	/**
	 * Validates the the customer name is a string and within
	 * the appropriate lengths and returns the normalized
	 * first and last names.
	 *
	 * @param string $value
	 * @param string $normalized_name_last
	 * @param string $normalized_name_first
	 * @return BOOL
	 */
	protected function Validate_Name ($value, &$normalized_name_last, &$normalized_name_first)
	{
		$name_ary = explode(" ", $value);
		$name_first	= strtolower(trim($name_ary[0]));
		$name_last	= strtolower(trim($name_ary[1]));
		if ( strlen($name_last ) >  1	&&
		     strlen($name_last ) < 50	&&
		     strlen($name_first) >  0	&&
		     strlen($name_first) < 50		)
		{
			$normalized_name_last	= $name_last;
			$normalized_name_first	= $name_first;
			return true;
		}

		return false;
	}

	/**
	 * Method used for corrections processing to update the application record
	 *
	 * @param int $application_id - Id of the application to update
	 * @param array $app_update_ary - column value array of application columns to update
	 * @return mixed FALSE on failure, rowcount on success
	 */
	protected function Update_Application_Info ($application_id, $app_update_ary)
	{
		$agent_id = Fetch_Current_Agent();

		if ( empty($application_id) || count($app_update_ary) < 1 )
		{
			return false;
		}

		$app = ECash::getApplicationByID($application_id);

		foreach ($app_update_ary as $key => $value)
		{
			$app->{$key} = $value;
		}

		return $app->save();
	}

	/**
	 * Lame method that only wraps Send_Report_Request()
	 *
	 * @param string $type
	 * @param string $start_date
	 * @return bool
	 */
	public function Fetch_ACH_File($type, $start_date)
	{
		return $this->Send_Report_Request($start_date, $type);
	}

	/**
	 * Big nasty hairy function to download a report using whatever means necessary
	 * It also inserts the report response itself.
	 *
	 * @todo: Break this up into seperate classes
	 *
	 * @param string $report_date
	 * @param string $report_type
	 * @return bool
	 */
	public function Send_Report_Request($report_date, $report_type)
	{
		$return_val = array();
		/**
		 * Holds a query string emulating the request.
		 */
		$transport_type = ECash::getConfig()->ACH_TRANSPORT_TYPE;
		$batch_server   = ECash::getConfig()->ACH_BATCH_SERVER;
		$batch_login    = ECash::getConfig()->ACH_REPORT_LOGIN;
		$batch_pass     = ECash::getConfig()->ACH_REPORT_PASS;
		$transport_port   = ECash::getConfig()->ACH_BATCH_SERVER_PORT;

		for ($i = 0; $i < 5; $i++) { // make multiple request attempts
			try {
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server, $batch_login, $batch_pass, $transport_port);

				if (EXECUTION_MODE != 'LIVE' && $transport->hasMethod('setBatchKey'))
				{
					$transport->setBatchKey(ECash::getConfig()->ACH_BATCH_KEY);
				}

				if ($transport->hasMethod('setDate'))
				{
					$transport->setDate($report_date);
				}

				if ($transport->hasMethod('setCompanyId'))
				{
					$transport->setCompanyId($this->ach_report_company_id);
				}

				switch($report_type)
				{
					case "returns":
						$prefix = ECash::getConfig()->ACH_REPORT_RETURNS_URL_PREFIX;
						$suffix = ECash::getConfig()->ACH_REPORT_RETURNS_URL_SUFFIX;
						$returns_url = ECash::getConfig()->ACH_REPORT_RETURNS_URL;

						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("Ymd",strtotime($report_date)).$suffix;
						}
						else if($returns_url != NULL)
						{
							$url = $returns_url;
						}
						else
						{
							$url = ECash::getConfig()->ACH_REPORT_URL;
						}

						break;

					case "corrections":
						$prefix = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL_PREFIX;
						$suffix = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL_SUFFIX;
						$corrections_url = ECash::getConfig()->ACH_REPORT_CORRECTIONS_URL;

						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("Ymd",strtotime($report_date)).$suffix;
						}
						else if($corrections_url != NULL)
						{
							$url = $corrections_url;
						}
						else
						{
							$url = ECash::getConfig()->ACH_REPORT_URL;
						}

						break;
				}

				$report_response = '';
				$report_success = $transport->retrieveReport($url, $report_type, $report_response);

				if (!$report_success) {
					$this->log->write('(Try '.($i + 1).') Received an error code. Not trying again.');
					$this->log->write('Error: '.$report_response);
				}
				break;
			} catch (Exception $e) {
				$this->log->write('(Try '.($i + 1).') '.$e->getMessage());
				$report_response = '';
				$report_success = false;
				sleep(5);
			}
		}

		if ($report_success)
		{
			$request = 'report='.$report_type.
					'&sdate='.date("Ymd", strtotime($report_date)).
					'&edate='.date("Ymd", strtotime($report_date)).
					'&compid='.$this->ach_report_company_id;

			$this->log->Write("Successfully retrieved '".strlen($report_response)."' byte(s) $report_type report for $report_date from '$url'");
			/** @todo: Add Filename here!  **/
			$request = $url;
			$ach_report_id = $this->Insert_ACH_Report_Response($request, $report_response, $report_date, $report_type);

			return $ach_report_id;

		}
		else
		{
			$this->log->Write("ACH '$report_type' report: was unable to retrieve report from $url", LOG_ERR);
			return false;
		}
	}

	/**
	 * Fetches the most recent report by date that isn't 'obsolete'
	 *
	 * @todo: This method seems terribly redundant.  Other methods should be used
	 * to determine what reports should be pulled and processed.  And it should use
	 * a model.
	 *
	 * @param string $start_date
	 * @param string $report_type
	 * @return array $report
	 */
	public function Fetch_Report($start_date, $report_type)
	{
		if($report_type == 'returns')
		{
			$type = "RET";
		}
		else
		{
			$type = "COR";
		}
		// We want to grab only the most recent file in the case that there is more than one
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id,
							ach_report_request,
							remote_response as received
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		ach_report_request LIKE 'report={$type}%'
					AND		date_request = '{$start_date}'
					AND		report_status != 'obsoleted'
					ORDER BY date_created DESC
					LIMIT 1
			";
		$result = $this->db->Query($query);

		if($result->rowCount() > 0)
		{
			$report = $result->fetch(PDO::FETCH_ASSOC);
			return $report;
		}
		else
		{
			$this->log->Write("Unable to retrieve report type $report_type for $start_date");
			return false;
		}
	}

	/**
	 * Parses a CSV file passed in as a string and returns
	 * it as an array using keys defined in the $report_format.
	 *
	 * @todo: Test this, get rid of redundant methods in child classes
	 *
	 * @param string $return_file
	 * @param array $report_format
	 * @return array $parsed_data_ary
	 */
	public function Parse_Report_Batch ($return_file, $report_format)
	{
		// Split file into rows
		$return_data_ary = explode(self::$RS, $return_file);

		$parsed_data_ary = array();
		$i = 0;

		foreach ($return_data_ary as $line)
		{
			if ( strlen(trim($line)) > 0 )
			{
				$this->log->Write("Parse_Report_Batch():$line\n");
				//  Split each row into individual columns

				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];

				$parsed_data_ary[$i] = array();
				if(count($col_data_ary) != count($report_format))
					return false;
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][$report_format[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}
		return $parsed_data_ary;
	}

	/**
	 * Insert's an ACH report
	 *
	 * This is supposed to obsolete previous reports, though I think
	 * that's a bad idea.  There may be more than one report for a customer
	 * for a day, so a previous report may not actually be obsolete.
	 *
	 * @todo: Get rid of the obsoleting of previous reports, use models.
	 *
	 * @param string $request_data
	 * @param string $response
	 * @param string $start_date
	 * @param string $report_type
	 * @return int $ach_report_id
	 */
	public function Insert_ACH_Report_Response($request_data, $response, $start_date, $report_type)
	{
//		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
//			UPDATE ach_report
//			SET report_status = 'obsoleted'
//			WHERE ach_report_request = ".$this->db->quote($request_data)."
//			AND date_request = ".$this->db->quote($start_date)."
//			AND company_id = {$this->company_id}
//			";
//		$result = $this->db->Query($query);

		/**
		 * Converted this to a model since we're already planning on encrypting some of
		 * this data in the very near future. [BR]
		 */
		$ach_report = ECash::getFactory()->getModel('AchReport');
		$ach_report->date_created = Date('Y-m-d');
		$ach_report->date_request = $start_date;
		$ach_report->company_id = $this->company_id;
		$ach_report->ach_report_request = $request_data;
		$ach_report->remote_response = $response;
		$ach_report->remote_response_iv = md5($response);
		$ach_report->report_status = 'received';
		$ach_report->report_type = $report_type;
		$ach_report->content_hash = md5($response);
		$ach_report->save();

		return $ach_report->ach_report_id;
	}

	/**
	 * Update the status of an ach_report
	 *
	 * @todo: This should be using models...
	 *
	 * @param int $ach_report_id
	 * @param string $status
	 * @return BOOL always returns true
	 */
	protected function Update_ACH_Report_Status ($ach_report_id, $status)
	{
		$db = ECash::getMasterDb();

		$ach_report = ECash::getFactory()->getModel('AchReport');
		$ach_report->loadBy(array('ach_report_id' => $ach_report_id));

		$ach_report->report_status = $status;
		$ach_report->save();

		return true;
	}

	/**
	 * Attempts to get the application_id based on the ach record
	 *
	 * @param int $ach_id
	 * @return int on success, NULL on failure
	 */
	protected function Get_Return_App_ID ($ach_id)
	{
		$application_id = NULL;
		//We don't really care what company this is from. Removing company ID as a where condition. [W!-2009-05-19]
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT application_id
						FROM   ach
						WHERE  ach_id = {$ach_id}";

		$result = $this->db->Query($query);
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$application_id = $row['application_id'];
		}

		return $application_id;
	}

	/**
	 * Simple method for saving an ACH Exception
	 *
	 * This will take the associative array for the return record
	 * and map it to the data model.
	 *
	 * @param array $report_data
	 */
	protected function Insert_ACH_Exception($report_data=NULL)
	{
		if($report_data)
		{
			$effective_entry_date = date('Y-m-d');

			$ach_id		= ltrim($report_data['recipient_id'], '0');
			$recipient_name = isset($report_data['recipient_name']) ? trim($report_data['recipient_name']) : "";
			$debit_amount	=  isset($report_data['debit_amount']) ? trim($report_data['debit_amount']) : '0.00';
			$credit_amount	=  isset($report_data['credit_amount']) ? trim($report_data['credit_amount']) : '0.00';
			$reason_code	=  isset($report_data['reason_code']) ? trim($report_data['reason_code']) : "";

			$ach_exception = ECash::getFactory()->getModel('AchException');
			$ach_exception->date_created 	= time();
			$ach_exception->return_date		= $effective_entry_date;
			$ach_exception->recipient_id	= $ach_id;
			$ach_exception->recipient_name	= $recipient_name;
			$ach_exception->ach_id			= $ach_id;
			$ach_exception->debit_amount	= $debit_amount;
			$ach_exception->credit_amount	= $credit_amount;
			$ach_exception->reason_code		= $reason_code;
			$ach_exception->company_id		= $this->company_id;
			$ach_exception->save();
		}
	}

	/**
	 * Tries to find the transaction_register_id based on the ach_id,
	 * then updates it.  If it is already complete it will
	 * remove the ledger item.
	 *
	 * @todo: This doesn't seem to support multiple transaction_register_id's!
	 *
	 * @param int $ach_id
	 * @return BOOL
	 */
	protected function Update_Transaction_Register_ACH_Failure ($ach_id)
	{
		$agent_id = Fetch_Current_Agent($this->server);

		// First, look for the transaction register row
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT transaction_register_id, transaction_status
			FROM transaction_register
			WHERE ach_id = {$ach_id}";

		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);

		if ($row == null) {
			$this->log->Write("Could not locate transaction w/ ACH ID of {$ach_id}.");
			$exception = array(
				'ach_id'  => $ach_id,
				'exception' => "Could not locate transaction w/ ACH ID of {$ach_id}.",
				'recipient_name' => $recipient_name
			);
			$this->ach_exceptions[] = $exception;
			$this->ach_exceptions_flag = TRUE;
			return false;
		}

		$trid = $row->transaction_register_id;
		$trstat = $row->transaction_status;

		if($trstat == 'failed') {
			$this->log->Write("Transaction {$trid} already marked as failed! ACH ID:{$ach_id}.");
			$exception = array(
				'ach_id'  => $ach_id,
				'exception' => "Transaction {$trid} already marked as failed! ACH ID:{$ach_id}.",
				'recipient_name' => $recipient_name
			);
			$this->ach_exceptions[] = $exception;
			$this->ach_exceptions_flag = TRUE;
			return false;
		}

		Set_Loan_Snapshot($trid,"failed");

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE transaction_register
					SET
						transaction_status	= 'failed',
						modifying_agent_id	= '$agent_id'
					WHERE
							ach_id		= $ach_id
						AND	company_id	= {$this->company_id}
						AND	transaction_status in ('pending','complete') ";

		$this->log->Write("Setting transaction {$trid} w/ ACH ID of {$ach_id} to failed.");
		$result = $this->db->Query($query);

		// If this is complete, we need to strip it out of the transaction ledger
		if ($trstat == 'complete') {
			$query = "DELETE FROM transaction_ledger
                                  WHERE transaction_register_id = {$trid}";
			$this->log->Write("Deleting completed ledger item for transaction {$trid}");
			$result = $this->db->Query($query);
		}

		return true;
	}

	/**
	 * Helper method to get the COMBINED_RETURNS flag from the class.
	 *
	 * @return bool
	 */
	public function useCombined()
	{
		return $this->COMBINED_RETURNS;
	}

	/**
	 * Retrieves a PDO result set of ACH reports based on
	 * the report type and a date range
	 *
	 * @param string $report_type - 'returns', 'corrections'
	 * @param string $start_date  - 'Y-m-d'
	 * @param string $end_date    - 'Y-m-d'
	 * @param return PDOStatement
	 */
	public function fetchReportByDate($report_type, $start_date, $end_date)
	{
		$this->report_type = $report_type;

		/**
		 * Modified this so that we're only looking for reports that have not been processed,
		 * which is easier to determine by looking for a report status of 'received'.  Everything
		 * else would have either failed, been processed, or obsoleted.  Also we're now
		 * looking at the new report_type column.  [BR]
		 */
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id,
							ach_report_request,
							remote_response as received,
							date_request
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		report_type = '{$report_type}'
					AND		date_request BETWEEN '{$start_date}' AND '{$end_date}'
					AND		report_status = 'received'
					ORDER BY date_created DESC
			";
		return $this->db->Query($query);
	}

	/**
	 * Retrieves a PDO result set of ACH reports id's based on
	 * the report type and a date range
	 *
	 * @param string $report_type - 'returns', 'corrections'
	 * @param string $start_date  - 'Y-m-d'
	 * @param string $end_date    - 'Y-m-d'
	 * @param return PDOStatement
	 */
	public function fetchReportIdsByDate($report_type, $start_date, $end_date)
	{
		$this->report_type = $report_type;

		/**
		 * Modified this so that we're only looking for reports that have not been processed,
		 * which is easier to determine by looking for a report status of 'received'.  Everything
		 * else would have either failed, been processed, or obsoleted.  Also we're now
		 * looking at the new report_type column.  [BR]
		 */
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		report_type = '{$report_type}'
					AND		date_request BETWEEN '{$start_date}' AND '{$end_date}'
					AND		report_status = 'received'
					ORDER BY date_created DESC
			";
		return $this->db->Query($query);
	}

	/**
	 * Retrieves an ACH report using it's unique id
	 *
	 * @param integer $ach_report_id
	 * @return array $report
	 */
	public function fetchReportById($ach_report_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id,
							date_request,
							ach_report_request,
							remote_response as received
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		ach_report_id = " . $this->db->quote($ach_report_id) . "
			";
		$result = $this->db->Query($query);

		if($result->rowCount() > 0)
		{
			$report = $result->fetch(PDO::FETCH_ASSOC);
			return $report;
		}
		else
		{
			$this->log->Write("Unable to retrieve report id $ach_report_id");
			return false;
		}
	}

	/**
	 * Used to determind the appropriate start date for a particular report type
	 *
	 * @param string $start_date  - 'Y-m-d'
	 * @param string $end_date    - 'Y-m-d'
	 * @param string $report_type - 'returns', 'corrections'
	 */
	public function getReportRunDates(&$start_date, &$end_date, $report_type)
	{
		$this->business_day = $end_date;

		// Get the most recent business date of the last report execution of the same type
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT
						max(business_day) as last_run_date
					FROM
						process_log
					WHERE
							step	 = 'ach_{$report_type}'
						AND	state	 = 'completed'
						AND company_id	 = {$this->company_id}
						AND business_day <> '1970-01-01'
		";
		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		if( !empty($row['last_run_date']) )
		{
			$last_run_date = $row['last_run_date'];
			$start_date = date("Y-m-d", strtotime("+1 day", strtotime($last_run_date)));
		}
		else
		{
			$start_date = date("Y-m-d", strtotime("now"));
		}

	}

	/**
	 * This is a convenience method that will return the valid report types that
	 * will be stored in the ach_report table under the 'report_type' column.
	 * It is used to validate the report_type being passed before inserting the report
	 * and for the user-interface to select a valid type.
	 *
	 * @return array Example: array('returns' => 'Returns')
	 */
	public function getReportTypes()
	{
		return array(	'returns' 		=> 'Returns',
						'corrections' 	=> 'Corrections'
		);
	}

	//asm 104
	public function getFormatTypes()
	{
		$providers = array();
		$pr_model = ECash::getFactory()->getModel('AchProvider');
		$pr_array = $pr_model->loadAllBy(array());
		foreach ($pr_array as $pr)
		{
			$providers[$pr->name_short] = $pr->name;
		}

		return $providers;
	}

	/*
		Builds and returns the application service client
		@return ECash_ApplicationService_Appclient
	*/
	public function getAppClient()
	{
		if(empty($this->app_client))
		{
			$this->app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		}
		return $this->app_client;
	}
	/*
		Prepares and sends data to the appservice for BankInfo
		@param array array of Array
        				(
					    [bank_aba] => 041208735
					    [bank_account] => 991258507264
					    [application_id] => 500059404
					)

	*/
	public function SendChangesToAppService($update_array)
	{
		$array_Chunks = $this->SplitIntoSizedArrays($update_array, 1000);
		foreach($array_Chunks as $send_array)
		{
			$this->bulkUpdateBankInfo($send_array);
		}
	
	}
	/*
		Prepares data to be sent to the appservice for BankInfo

	*/
	protected function SplitIntoSizedArrays($array, $step){
	    $new = array();
	 
	    $k = 0;
	    foreach ($array as $app_id => $values){
				
		$values['application_id'] = $app_id;
		if(count($new[$k]) >= $step)
		{
			$k++;
		}
		$new[$k][$app_id] = $values;
	    }
	 
	    return $new;
	}
	/*
		Bulk updates changes to application to the application service
		@param array array of application keyed column data that is to be changed
		array([appid] => array([column] => value,[column]=> value), [appid] => array([column] => value,[column]=> value))

	*/
	public function bulkUpdateBankInfo(array $updateArray)
	{
		$this->getAppClient()->bulkUpdateBankInfo($updateArray);

	}

}

?>
