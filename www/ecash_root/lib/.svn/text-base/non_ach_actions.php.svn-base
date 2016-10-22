<?php

require_once(COMMON_LIB_DIR  . "general_exception.1.php");
//require_once(COMMON_LIB_DIR  . "business_rules.class.php");
//require_once(LIB_DIR	     . "common_functions.php");
//require_once(SERVER_CODE_DIR . "paydate_info.class.php");
//require_once(SERVER_CODE_DIR . "stat.class.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");

class Non_ACH_Actions
{
	private $server;
	private $db;
	private $log;
	private $company_abbrev;
	private $company_id;
	private $ttypes_end_status_failed;
	private $ttypes_end_status_complete;
	
	public function __construct($server)
	{
		$this->server = $server;
		$this->db = ECash::getMasterDb();
		$this->log    = $server->log;
		$this->company_abbrev = strtolower($server->company);
		$this->company_id	  = $server->company_id;
				
	}

	/**
	 * Desc:
	 *		This loads the global variables with Non Ach Transaction Types that are
	 *		either 'failed' or 'complete'.
	 *
	 * Parm:
	 *		none.
	 *
	 *	Return.
	 *		none.
	 */
	private function Get_Non_ACH_Transaction_Types()
	{
		$this->ttypes_end_status_complete = array();
		$this->ttypes_end_status_failed   = array();
		
		$query = "
				SELECT
					transaction_type_id,
					name_short,
					pending_period,
					end_status
				FROM 
					transaction_type
				WHERE
					clearing_type <> 'ach'
		";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			if ($row['end_status'] == 'failed')
			{
				$this->ttypes_end_status_failed[] = $row['transaction_type_id'];
			}
			elseif ($row['end_status'] == 'complete')
			{
				$this->ttypes_end_status_complete[] = $row['transaction_type_id'];
			}
		}
	}

	private function Get_Pending_Windows()
	{
		static $pending_windows;
		
		if(! isset($pending_windows))
		{		
			$pending_windows = array();
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);

			$query = "SELECT transaction_type_id, pending_period, period_type FROM transaction_type";
			$result = $this->db->query($query);
			while ($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$window = intval($row->pending_period);
				switch ($row->period_type) {
				case "business":
					$limit = $pdc->Get_Business_Days_Backward(date("Y-m-d"), $window);
					break;
				case "calendar":
					$limit = date("Y-m-d", strtotime("-{$window} days", strtotime(date("Y-m-d"))));
				}
				$pending_windows[$row->transaction_type_id] = strtotime($limit);
			}
		}
		
		return $pending_windows;
	}

	public function Reschedule_Non_ACH_Failures()
	{
		require_once(SQL_LIB_DIR . "util.func.php");

		$reschedule_list = array();
		$pending_windows = $this->Get_Pending_Windows();

		if(! isset($this->ttypes_end_status_failed)) {
			$this->Get_Non_ACH_Transaction_Types();
		}
		
		$this->Get_Non_ACH_Transaction_Types();

		$query = "
					SELECT
						transaction_register_id,
						application_id,
						transaction_type_id,
						date_effective
					FROM
						transaction_register
					WHERE
							company_id = {$this->company_id}
						AND	transaction_type_id	IN (" . implode(",", $this->ttypes_end_status_failed) . ")
						AND transaction_status	IN ('new','pending') ";

		$result = $this->db->query($query);

		// GF #13394: Don't resend ARRANGEMENTS_MISSED for each failure, only send it once
		$apps_emailed = array();		

		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if (strtotime($row->date_effective) <= $pending_windows[$row->transaction_type_id])
			{
				$this->Mark_Transaction_Failed($row->transaction_register_id);
				$reschedule_list[] = $row->application_id;

				// Don't send duplicate emails
				if (!in_array($row->application_id, $apps_emailed))
				{
					//ECash_Documents_AutoEmail::Send($row->application_id, 'ARRANGEMENTS_MISSED');
					//$apps_emailed[] = $row->application_id;
				}
			}
		}

		// Now reschedule all these mofos
		$reschedule_list = array_unique($reschedule_list);
		foreach ($reschedule_list as $application_id)
		{			
			// Check to see if they should be inactive. If so then we
			// don't need to do any failure handling.
			if (Check_Inactive($application_id)) {
				continue;
			}

			$fdfap = new stdClass();
			$fdfap->application_id = $application_id;
			$fdfap->server = $this->server;
			$fdfa = new FailureDFA($application_id);
			$fdfa->run($fdfap);
		}

		return true;
	}

	public function Non_ACH_Deem_Successful($date_current)
	{
		$pending_windows = $this->Get_Pending_Windows();
		if(! isset($this->ttypes_end_status_failed)) {
			$this->Get_Non_ACH_Transaction_Types();
		}

		$query = "
					SELECT  transaction_register_id,
						application_id,
						transaction_type_id,
						date_effective
					FROM    transaction_register
					WHERE   company_id = {$this->company_id}
					AND	transaction_type_id	IN (" . implode(",", $this->ttypes_end_status_complete) . ")
					AND transaction_status	IN ('new','pending')
					AND date_effective <= '$date_current'
		";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if (strtotime($row->date_effective) <= $pending_windows[$row->transaction_type_id])
			{
				$post_result = Post_Transaction($row->application_id, $row->transaction_register_id);
			}
		}
		
		return true;
	}

	private function Reschedule_App($app_id)
	{
		$this->log->Write("Pre-ACH: Rescheduling - App ID $app_id.", LOG_INFO);
		Adjust_Schedule($app_id, $this->server);
		return true;
	}

	private function Mark_Transaction_Failed($transaction_register_id)
	{
		$agent_id = Fetch_Current_Agent();
		
		Set_Loan_Snapshot($transaction_register_id,"failed");
		
		$query = "
					UPDATE transaction_register
					SET
						transaction_status	= 'failed',
						modifying_agent_id 	= '$agent_id'
					WHERE
							transaction_register_id	= $transaction_register_id
						AND	company_id	= {$this->company_id}
						AND	transaction_status <> 'failed'
		";

		$this->db->exec($query);

		return true;
	}

}
?>
