<?php

/**
 * Send account summary letters based on fund date and debit date.
 *
 * The times in which we are supposed to send the Account Summary letters are as follows:
 * 1) The morning after an account is funded.
 * 2) The 2nd morning after an account is debited using an automatic debiting method (currently this is only ACH).
 *
 * @package Cronjob
 */
class SendAccountSummaryLetters
{
	/**
	 * Convience access to DB object
	 * @var object
	 */
	private $db;

	/**
	 * Holidays
	 * @var array
	 */
	private $holidays;

	/**
	 * Pay date calc object
	 * @var object
	 */
	private $pay_date_calc;

	/**
	 * Date to run cron job against.
	 * @var string Y-m-d
	 */
	private $date;
	
	/**
	 * Server Object
	 * @var server
	 */
	private $server;

	/**
	 * Application Status Id for active::servicing::customer::*root
	 * @var integer
	 */
	private $active_status;

	/**
	 * Application Status Id for past_due::servicing::customer::*root
	 * @var integer
	 */
	private $past_due_status;

	/**
	 * Application Status Id for new::collections::customer::*root
	 * @var integer
	 */
	private $collections_new_status;

	/**
	 * @param server $server
	 * @param object $db DB_Database_1
	 * @param object $log AppLog
	 * @param object $pay_date_calc Pay_Date_Calc_3
	 * @param string $date optional Date to run cron as; null = today
	 */
	public function __construct($server, DB_Database_1 $db, $log, $pay_date_calc, $date=null)
	{
		$this->server = $server;
		$this->db = $db;
		$this->log = $log;
		$this->pay_date_calc = $pay_date_calc;
		$this->active_status = 'active::servicing::customer::*root';
		$this->past_due_status = 'past_due::servicing::customer::*root';
		$this->collections_new_status = 'new::collections::customer::*root';

		if ($date === null)
		{
			$date = date('Y-m-d');
		}
		$this->date = $date;
	}

	/**
	 * Initialize the paydate calculater from the Cronjob server object.
	 *
	 * @param object $server Connection to the rest of the system; provided by the cron job handler.
	 */
	public static function initializeCronJob($server)
	{
		require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
		require_once(LIB_DIR . 'common_functions.php');

		$db = ECash::getMasterDb();
		$log = $server->log;
		$holidays = Fetch_Holiday_List();
		$pay_date_calc = new Pay_Date_Calc_3($holidays);

		return new self($server, $db, $log, $pay_date_calc);
	}

	/**
	 * Process the cron job. Get accounts based on the date and then send them the documents.
	 */
	public function processCronJob($company_id)
	{
		//asm 107
		/*
		$date_stamp = strtotime($this->date);
                if(!($this->pay_date_calc->isBusinessDay($date_stamp)))
		{
                        return;
		}
		*/
		/////////

		//$fundDate = $this->pay_date_calc->Get_Calendar_Days_Backward($this->date, 1);
		//$debitDate = $this->pay_date_calc->Get_Business_Days_Forward($this->date, 3);
		$debitDate = $this->pay_date_calc->Get_Calendar_Days_Backward($this->date, 7); // asm 107

		//$this->logMessage('Getting funded (' . $fundDate . ') and debited (' . $debitDate . ') accounts');
		$this->logMessage('Getting  debited (' . $debitDate . ') accounts');

		//$accounts = $this->getFundedAccounts($fundDate, $company_id);
		//$accounts = array_merge($accounts, $this->getDebitAccounts($debitDate, $company_id));
		$accounts = $this->getDebitAccounts($debitDate, $company_id);

		$this->logMessage(count($accounts) . ' number of accounts to email');

		foreach ($accounts as $account_id)
		{
			if (empty($account_id) || $this->isLastPayment($account_id, $company_id))
			{
				continue;
			}

			$this->logMessage('Sending Account Summary Letter for account ' . $account_id);
			ECash_Documents_AutoEmail::Send($account_id, 'ACCOUNT_SUMMARY');
			//[#53048] Hack to free memory w/o putting it in ECash_Documents_AutoEmail::Send()
			$app = ECash::getApplicationById($account_id);
			$app->destroyReferences();			
		}
	}

	/**
	 * Find accounts that were funded on a specific date.
	 *
	 * Depreciated RSK 07-09-2012
	 *
	 * @param string $date Date of form Y-m-d
	 * @return array application ids
	 */
	private function getFundedAccounts($date, $company_id)
	{
		$accounts = array();
		$app_ids = $this->getAppIdsForStatus($this->active_status);
		if (!empty($app_ids))
		{
			$in_stmt = implode(',', $app_ids);

			// Following query pulls up all accounts that were funded on date
			$query = "
				SELECT DISTINCT
					tr.application_id
				FROM
					transaction_register AS tr
					JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
				WHERE
					tt.name_short = 'loan_disbursement'
					AND tr.company_id = {$company_id}
					AND DATE(tr.date_created) = '{$date}'
					AND tr.application_id IN ({$in_stmt})";

			$results = $this->db->query($query);
			while ($row = $results->fetch(PDO::FETCH_ASSOC))
			{
				$accounts[] = $row['application_id'];
			}
		}

		return $accounts;
	}

	/**
	 * Gets an array of application_ids from the application_service that are in a status
	 * 
	 * @param string $status
	 * @return array
	 */
	private function getAppIdsForStatus($status)
	{
		$mssql_db = ECash::getAppSvcDB();
		$query = 'CALL sp_fetch_application_ids_by_application_status ("'.$status.'")';
		$result = $mssql_db->query($query);
		$app_ids = array();
		if (!empty($result))
		{
			while ($row = $result->fetch())
			{
				$app_ids[] = $row['application_id'];
			}
		}
		return $app_ids;
	}

	/**
	 * Find accounts that were debitted two days ago.
	 *
	 * Depreciated RSK 07-09-2012
	 *
	 * @param string $date Date of form Y-m-d
	 * @return array application ids
	 */
	private function getDebitAccounts_old($date, $company_id)
	{
		$accounts = array();

		// Only pull in debit accounts if it is for a business day.
		if (!$this->pay_date_calc->isBusinessDay(strtotime($date)))
		{
			$this->logMessage('Debited Accounts: Not a business day. Skipping.');
			return $accounts;
		}

		$app_ids = $this->getAppIdsForStatus($this->active_status);
		if (!empty($app_ids))
		{
			$in_stmt = implode(',', $app_ids);
			// The following query pulls up all accounts that had a debit on date
			$query = "
				SELECT DISTINCT
					tr.application_id
				FROM
					transaction_register AS tr
				JOIN transaction_type tt ON (tr.transaction_type_id = tt.transaction_type_id)
				WHERE
					tt.clearing_type = 'ach'
					AND tr.amount < 0.0
					AND tr.company_id = {$company_id}
					AND DATE(tr.date_created) = '{$date}'
					AND tr.application_id IN ({$in_stmt})";
			$results = $this->db->query($query);
			while ($row = $results->fetch(PDO::FETCH_ASSOC))
			{
				$accounts[] = $row['application_id'];
			}
		}
		return $accounts;
	}

	/**
	 * Find accounts that will be debited three days from now.
	 *
	 * New query RSK 07-09-2012
	 *
	 * @param string $date Date of form Y-m-d
	 * @return array application ids
	 */
	private function getDebitAccounts($date, $company_id)
	{
		$accounts = array();

		$app_ids = $this->getAppIdsForStatus($this->active_status);
		$app_ids = array_merge($app_ids, $this->getAppIdsForStatus($this->past_due_status));
		$app_ids = array_merge($app_ids, $this->getAppIdsForStatus($this->collections_new));
		if (!empty($app_ids))
		{
			$in_stmt = implode(',', $app_ids);
			// The following query pulls up all accounts that had a debit on date
			$query = "
				SELECT DISTINCT
					es.application_id
				FROM
					event_schedule AS es
				WHERE
					es.event_type_id IN (2,3,228,229)
					AND es.amount_principal <= 0.0
					AND es.amount_non_principal <= 0.0
					AND es.company_id = {$company_id}
					AND DATE(es.date_effective) = '{$date}'
					AND es.application_id IN ({$in_stmt})";
			$results = $this->db->query($query);
			while ($row = $results->fetch(PDO::FETCH_ASSOC))
			{
				$accounts[] = $row['application_id'];
			}
		}
		return $accounts;
	}

	/**
	 * Check if this is the customer's last payment
	 *
	 * @param integer $application_id
	 * @return bool true if the customer only has one more scheduled payment
	 */
	private function isLastPayment($application_id, $company_id)
	{
		$sql = "
			SELECT
				COUNT(*) AS num_scheduled_payments
			FROM
				event_schedule AS es
			JOIN event_type AS et USING (event_type_id)
			WHERE
				application_id='$application_id'
				AND es.company_id = {$company_id}
				AND et.name_short = 'payment_service_chg'
				AND es.event_status = 'scheduled'";
		$result = $this->db->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		return ($row['num_scheduled_payments'] <= 0);
	}

	/**
	 * Convience method to output information to both the applog and output log.
	 *
	 * @todo this should be moved into the cronjob engine
	 *
	 * @param string $message
	 */
	private function logMessage($message)
	{
		$this->log->Write($message);
	}
}

/**
 * MAIN processing code
 * @todo This should be automatically called by the cron job handler.
 */
function Main()
{
	global $server;
	$company_id = $server->company_id;
	require_once(LIB_DIR . 'Document/Document.class.php');

	$account_summary_letters = SendAccountSummaryLetters::initializeCronJob($server);
	$account_summary_letters->processCronJob($company_id);
}

?>
