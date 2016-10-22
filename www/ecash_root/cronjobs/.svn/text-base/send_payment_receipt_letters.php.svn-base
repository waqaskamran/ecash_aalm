<?php

/**
 * Send payment receipt letters
 *
 * @package Cronjob
 */
class SendPaymentReceiptLetters
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
		$date = $this->pay_date_calc->Get_Calendar_Days_Backward($this->date, 1);

		$accounts = $this->getPaymentReceiptAccounts($date, $company_id);

		$this->logMessage(count($accounts) . ' number of Payment Receipt accounts to email');

		foreach ($accounts as $account_id)
		{
			if (empty($account_id))
			{
				continue;
			}

			$this->logMessage('Sending Payment Receipt Letter for account ' . $account_id);
			ECash_Documents_AutoEmail::Send($account_id, 'PAYMENT_RECEIPT');
			$app = ECash::getApplicationById($account_id);
			$app->destroyReferences();			
		}
	}
	
	private function getPaymentReceiptAccounts($date, $company_id)
	{
		$accounts = array();

		$query = "
		SELECT DISTINCT
			ap.application_id
		FROM
			application AS ap
		JOIN
			transaction_register AS tr ON (tr.application_id=ap.application_id)
		JOIN
			transaction_type AS tt ON (tt.company_id = tr.company_id
							AND tt.transaction_type_id = tr.transaction_type_id)
		JOIN
			transaction_history AS th ON (th.transaction_register_id = tr.transaction_register_id)
		LEFT JOIN
			transaction_history AS th1 ON (th1.transaction_register_id = th.transaction_register_id
			AND th1.transaction_history_id > th.transaction_history_id)
		WHERE
			tr.transaction_status = 'complete'
		AND
			tr.amount < 0
		AND
			tt.clearing_type IN ('ach','card','external')
		AND
			th.status_after = 'complete'
		AND
			DATE(th.date_created) = '{$date}'
		AND
			tr.company_id = {$company_id}
		AND
			th1.transaction_history_id IS NULL
		GROUP BY application_id
		";
		$results = $this->db->query($query);
		while ($row = $results->fetch(PDO::FETCH_ASSOC))
		{
			$accounts[] = $row['application_id'];
		}

		return $accounts;
	}

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

	$payment_receipt_letters = SendPaymentReceiptLetters::initializeCronJob($server);
	$payment_receipt_letters->processCronJob($company_id);
}

?>
