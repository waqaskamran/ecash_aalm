<?php
/**
 * @package reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

/**
 * SQL TABLE
 *
CREATE TABLE resolve_daily_cash_report (
	date_modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	date_created TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	resolve_daily_cash_report_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	report_date DATE NOT NULL DEFAULT '0000-00-00',
	company_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
	serialized_data MEDIUMBLOB NOT NULL,
	new_customers INT(10) UNSIGNED NOT NULL DEFAULT '0',
	reactivated_customers INT(10) UNSIGNED NOT NULL DEFAULT '0',
	refunded_customers INT(10) UNSIGNED NOT NULL DEFAULT '0',
	resend_customers INT(10) UNSIGNED NOT NULL DEFAULT '0',
	cancelled_customers INT(10) UNSIGNED NOT NULL DEFAULT '0',
	paid_out_customers_ach INT(10) UNSIGNED NOT NULL DEFAULT '0',
	paid_out_customers_non_ach INT(10) UNSIGNED NOT NULL DEFAULT '0',
	nsf DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	total_debited DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	net_cash_collected DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	credit_card_payments DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	chargebacks DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	western_union_deposit DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	money_order_deposit DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	quick_check_deposit DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	moneygram_deposit DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	crsi_recovery DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	pinion_recovery DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	loan_disbursement DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	principal_debited DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	fees_debited DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	service_charges_debited DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	refund_total DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	PRIMARY KEY (resolve_daily_cash_report_id),
	UNIQUE KEY idx_rslv_dly_cash_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

 */

/**
 * After the batch is run the daily cash report data is fetched and saved to the database. The customer will then
 * need to go to the Daily Cash Report in the Overview Reports section. They will have to choose a date. If a past date is
 * chosen it will simply display the report. If the current date is chosen a dialog box will popup asking if they would like
 * to set the deposit data. If the 'Yes' button is clicked, a popup box will open where they can enter in the deposit values.
 * Once 'OK' is selected the dialog box will close and the main window will update the report and display it. Upon displaying the
 * report there will be an option to download the pdf report and an option to send the pdf report as an attachment. Clicking the
 * option to send the pdf report will result in a screen where the customer can enter in an email address. We will allow a default
 * set of email addresses for the customer to enter as well.
 */

require_once(SERVER_MODULE_DIR . "reporting/daily_cash_pdf.php");
require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once(LIB_DIR.'Mail.class.php');
require_once( SERVER_CODE_DIR . "reattempts_report_query.class.php" );
require_once( "libolution/Object.1.php" );
require_once( "libolution/Mail/Trendex.1.php" );
require_once("pay_date_calc.3.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class Report extends Report_Generic
{
	protected $report_search_criteria = array(
		'specific_date',
		'company_id',
	);

	protected $report_query_class = array(
		'Daily_Cash_Report_Query',
		'Fetch_Daily_Cash_Report',
	);

	public function handleSpecialRequest()
	{
		switch ($this->request->action)
		{
			case 'update_deposit_data':
				list ($specific_date) = $this->Get_Specific_Date($data);

				$search_query = new Daily_Cash_Report_Query($this->server);
				$search_query->Update_Manual_Data($specific_date, $this->request->deposit);

				echo json_encode(array('success' => TRUE));
				exit;
				break;
		}
	}

	public function Generate_Report()
	{
		//parent::Generate_Report();

		
			// Generate_Report() expects the following from the request form:
		// company_id
		try
		{
			$this->search_query = new Daily_Cash_Report_Query($this->server,null,$this->request->company_id);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  
			  'end_date_MM'   => $this->request->end_date_month,
			  'end_date_DD'   => $this->request->end_date_day,
			  'end_date_YYYY' => $this->request->end_date_year,
			  			  
			  'company_id' => $this->request->company_id,
			  
			);

			
			
		/*	if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}*/
			
			$specific_date_YYYYMMDD = "{$data->search_criteria['specific_date_YYYY']}-{$data->search_criteria['specific_date_MM']}-{$data->search_criteria['specific_date_DD']}";
			
			$end_date_YYYYMMDD = "{$data->search_criteria['end_date_YYYY']}-{$data->search_criteria['end_date_MM']}-{$data->search_criteria['end_date_DD']}";
				
			$start_date_YYYYMMDD = "{$data->search_criteria['start_date_YYYY']}-{$data->search_criteria['start_date_MM']}-{$data->search_criteria['start_date_DD']}";

			if ($this->request->update_deposit_data == 1) 
			{
				$this->search_query->Update_Manual_Data($end_date_YYYYMMDD, $this->request->deposit);
			}


			$data->search_results = $this->search_query->Fetch_Daily_Cash_Report($start_date_YYYYMMDD, $end_date_YYYYMMDD);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
		//$data->report_date = date('m/d/Y', strtotime($specific_date_YYYYMMDD));
		$data->start_date = $start_date_YYYYMMDD;
		$data->end_date = $end_date_YYYYMMDD;
		$data->company_name = ECash::getConfig()->COMPANY_NAME;
		if (count($data->search_results))
		{
			$data->search_results['start_date'] = $data->start_date;
			$data->search_results['end_date'] = $data->end_date;
			$data->search_results['date'] = $data->report_date;
			$data->search_results['company_name'] = $data->company_name;
		}

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['daily_cash']['report_data'] = $data;
		$_SESSION['reports']['daily_cash']['url_data'] = array('name' => 'Daily Cash Report', 'link' => '/?module=reporting&mode=daily_cash');
		
		$data = $_SESSION['reports']['daily_cash']['report_data'];

		//$data->report_date = date('m/d/Y', strtotime($data->search_criteria['specific_date']));
		//$data->company_name = ECash::getConfig()->COMPANY_NAME;

		if (count($data->search_results))
		{
			$data->search_results['date'] = $data->report_date;
			$data->search_results['company_name'] = $data->company_name;
		}
	}

	public function Download_Report()
	{
		$data = $_SESSION['reports']['daily_cash']['report_data'];
		$data->filename = '/tmp/daily_cash_report_'.uniqid().'.pdf';

		$pdf = new Daily_Cash_Report_PDF($data->company_name, $data->start_date.' to '.$data->end_date, $data->search_results);
		$pdf->Create_PDF($data->filename);

		//We've had issues with the E-mail functionality.  Namely the eCash window opening inside the reporting window.
		//Email functionality is currently commented out.
		if (empty($this->request->email_to)) {
			$data->download = true;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('download',$this->report_name);
		} else {
			$recipients = $this->request->email_to;
			$tokens = array(
				'subject' => 'Daily Cash Report PDF - ' . $data->start_date.'-'.$data->end_date,
				'report_date' => $data->start_date.' to '.$data->end_date,
				'from' => ECash::getConfig()->MANAGER_EMAIL_ADDRESS);
			$contents = file_get_contents($data->filename);
			$attachments = array(
				array(
					'method' => 'ATTACH',
					'filename' => 'daily_cash_report_'.$data->start_date.'_'.$data->end_date.'.pdf',
					'mime_type' => 'application/pdf',
					'file_data' => gzcompress($contents),
					'file_data_size' => strlen($contents),
				)
			);

			ECash_Mail::sendMessage('ECASH_DAILY_CASH_REPORT', $recipients, $tokens, $attachments);

			ECash::getTransport()->Set_Levels("close_pop_up");
			ECash::getTransport()->Set_Data($data);
		}
	}
}

class Daily_Cash_Report_Query extends Base_Report_Query
{
	const TIMER_NAME = "Daily Cash Report";
	const ARCHIVE_TIMER = "Archived Daily Cash Report";
	const PROCESS_LOG_NAME = 'build_daily_cash_report';

	private $today;
	private $next_business_day;
	private $today_is_business_day;
	private $manual_data;
	/**
	 * @var ECash_ProcessStatus
	 */
	private $process_log;
	//This defines columns that are manually defined and do not aggregate
	private $order_manual = array(
		'lead acquisition cost' => 'lead_acquisition_cost',
		'datax cost'	=>	'datax_cost',
		'hms other cost'	=>	'hms_other_cost',
		'money order deposit' => 'money_order_deposit'
	);
	private $order_monthly = array(
		'new customers' => 'new_customers',
		'reactivated customers' => 'reactivated_customers',
		//'card reactivations' => 'card_reactivated_customers',
		'refunded customers' => 'refunded_customers',
		'resend customers' => 'resend_customers',
		'cancelled customers' => 'cancelled_customers',
		'paid out customers (ach)' => 'paid_out_customers_ach',
		'paid out customers (non-ach)' => 'paid_out_customers_non_ach',
		'nsf$' => 'nsf',
		'debit returns' => 'returns',
		'credit returns' => 'credit_returns',
		'total debited' => 'total_debited',
		'net cash collected' => 'net_cash_collected',
		'credit card payments' => 'credit_card_payments',
		'chargebacks' => 'chargebacks',
		'moneygram deposit' => 'moneygram_deposit',
//		'money order deposit' => 'money_order_deposit',
//		'quick check deposit' => 'quick_check_deposit',
//		'quick checks returned' => 'quick_check_returns',
//		'crsi recovery' => 'crsi_recovery',
//		'recovery' => 'pinion_recovery',
		//'final collections' => 'final_collections',
//		'bad debt write off (principal)' => 'bad_debt_write_off_principal',
//		'bad debt write off (fees)' => 'bad_debt_write_off_fees',
		'loan disbursement' => 'loan_disbursement',
		'new principal debited' => 'new_principal_debited',
		'reattempted principal debited' => 're_principal_debited',
		'total principal debited' => 'principal_debited',
		'new service charges debited' => 'new_service_charges_debited',
		'reattempted service charges debited' => 're_service_charges_debited',
		'total service charges debited' => 'service_charges_debited',
		'new fees debited' => 'new_fees_debited',
		'reattempted fees debited' => 're_fees_debited',
		'total fees debited' => 'fees_debited',
		'refund total' => 'refund_total',
		//'lead_acquisition_cost' => 'lead_acquisition_cost',
		//'datax_cost'	=>	'datax_cost',
		//'hms_other_cost'	=>	'hms_other_cost'
	);
	private $order_future = array(
		'active' => 'Active',
		'bankruptcy notification' => 'Bankruptcy Notification',
		'bankruptcy verification' => 'Bankruptcy Verified',
		'amortization' => 'Amortization',
		'amortization' => 'Amortization',
		'collections' => 'Collections',
		'collections new' => 'Collections New',
		'collections contact' => 'Collections Contact',
		'contact follow up' => 'Contact Followup',
		'funding failed' => 'Funding Failed',
		'past due' => 'Past Due',
		'made arrangements' => 'Made Arrangements',
//		'qc arrangements' => 'QC Arrangements',
//		'qc ready' => 'QC Ready',
//		'qc returned' => 'QC Returned',
//		'qc sent' => 'QC Sent',
		'inactive paid' => 'Inactive (Paid)',
//		'second tier ready' => 'Second Tier (Pending)',
//		'second tier sent' => 'Second Tier (Sent)',
//		'inactive recovered' => 'Inactive (Recovered)',
	);
	private $status_map;


	static private function Test_Database() {
		return new PDO('sqlite:/virtualhosts/ecash_development/daily_cash.sq3');
	}

	public function __construct(Server $server, $today = null, $company_id = null)
	{
		parent::__construct($server);
		$this->db = ECash::getMasterDb(); 
		if ($company_id)
		{
			$this->company_id = $company_id;
		}
		$this->today = empty($today) ? date('Y-m-d') : $today;
		$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		$this->today_is_business_day = $pdc->isBusinessDay(strtotime($this->today));
		$this->next_business_day = $pdc->Get_Next_Business_Day($this->today);
		$this->status_map = Fetch_Status_Map();
	}

	public function Fetch_Daily_Cash_Report($start_date,$end_date)
	{
		$data = $this->Fetch_Past_Data($start_date, $end_date, self::ARCHIVE_TIMER);
		return $data;
	}

	public function Create_Daily_Cash_Report() {
	//	$this->process_log = new ECash_ProcessStatus(MySQLi_1e::Get_Instance(), $this->company_id, self::PROCESS_LOG_NAME, $this->today);

	//	$this->process_log->startProcess();
	//	try
	//	{
			$data = $this->Fetch_Current_Data(self::TIMER_NAME);
			$this->Save_Data($data);
		//	$this->process_log->updateProcess('completed');
		// echo "Daily Cash Report for $this->company_id - COMPLETED! ";
/*		}
		catch (Exception $e)
		{
			echo "FAILED!";
		//	$this->process_log->updateProcess('failed');
		}
*/	}

/**
 * Update_Manual_Data
 * Uses the manual data entered in the report.
 * The daily cash report was/is intended to run for a single day.  This made perfect sense for a single date, but for a date range
 * this makes no sense and is retarded, and whoever thought up the idea of using the "Daily Cash Report" for a date range should be punished.  
 * Because We still need to store this data for generating PDF reports, we are tacking the manual data on to the end date of the
 * date range.
 *
 * @param unknown_type $report_date
 * @param array $manual_data
 */
	public function Update_Manual_Data($report_date, Array $manual_data) {
		$data = $this->Fetch_Past_Data($report_date,$report_date, self::ARCHIVE_TIMER);
		$this->manual_data = $manual_data;
		foreach ($manual_data as $key => $value) {
			$data['period'][$key]['span'] = $value;
			//$data['monthly'][$key]['week'] += $value;
			//$data['monthly'][$key]['month'] += $value;
		}
		$this->Save_Data($data, $report_date);
	}

	private function Save_Data($data, $report_date = null) {
		if (isset($report_date)) 
		{
			$this->Update_Data($data, $report_date);
		} else {
			$this->Insert_Data($data);
		}
	}

	private function Insert_Data($data) {
		$columns = implode(",\n\t\t\t\t", array_keys($data['monthly']));
		$values = '\''.implode("',\n\t\t\t\t'", array_values($data['monthly'])).'\'';

		unset($data['monthly']);
		$serialized_data = $this->db->quote(serialize($data));

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			REPLACE INTO resolve_daily_cash_report
			(
				date_modified,
				date_created,
				report_date,
				company_id,
				serialized_data,
				$columns
			) VALUES (
				NOW(),
				NOW(),
				'{$this->today}',
				'{$this->company_id}',
				{$serialized_data},
				$values
			)
		";
		//echo "\n\n\n$query\n\n\n";
		$result = $this->db->query($query);
	}

	private function Update_Data($data, $report_date) {
		$agregate_columns = array();

		foreach ($data['period'] as $column => $values) {
			if (!empty($this->order_monthly[$column]))
			{
				$agregate_columns[] = "{$this->order_monthly[$column]} = '{$values['span']}'";
			}
		}
		
		if(!empty($agregate_columns))
		{
			$values = ','.implode(",\n\t\t\t\t", array_values($agregate_columns));
		}
		else 
		{
			$values = '';
		}
		
		unset($data['monthly']);
		$serialized_data = $this->db->quote(serialize($data));

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			UPDATE resolve_daily_cash_report
			SET
				serialized_data = {$serialized_data}
				$values
			WHERE
				report_date = '{$report_date}' AND
				company_id = '{$this->company_id}'
		";

		$result = $this->db->Query($query);
	}

	private function Get_Intercept_Reserve() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$data = array(
			'intercept_reserve' => 2000
		);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $data;
	}

	/**
	 * Returns the date that the daily cash report was last run.
	 *
	 * <b>Revision History</b>
	 * <ul>
	 *     <li><b>2007-09-27 - mlively</b><br>
	 *         Fixed the call to Get_Closest_Business_Day_Forward to work
	 *         properly.
	 *     </li>
	 * </ul>
	 *
	 * @return string - A date in YYYY-MM-DD format.
	 */
	public function getLastCashReportRun()
	{
		static $last_run;

		if (empty($last_run))
		{
			//$process_log = new ECash_ProcessLog(MySQLI_1e::Get_Instance(), $this->company_id);
			//$process = $process_log->getProcessByNameStatus(self::PROCESS_LOG_NAME, 'completed', $this->today, true);

			if (empty($process) || strtotime($process->business_day) >= strtotime($this->today))
			{
				$last_run = $this->today;
			}
			else
			{
				$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
				$last_run = $pdc->Get_Calendar_Days_Forward($process->business_day, 1);
			}
		}

		return $last_run;
	}

	private function Fetch_Current_Advance_Numbers() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$active_status = "'active'";
		$collection_statuses = "'".implode("','", array(
			'past due',
			'made arrangements',
			'collections contact',
			'contact followup',
			'collections new',
			'collections (dequeued)'
		))."'";

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(if(status = {$active_status}, total_balance, 0)) advances_active,
				SUM(if(status IN ({$collection_statuses}), total_balance, 0)) advances_collections
			FROM
				open_advances_report
			WHERE
				report_date = '{$this->today}' AND
				company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $result->fetch(PDO::FETCH_ASSOC);
		
		
	}

	private function Fetch_Current_Flash_Data() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$flash_statuses = array(
								'Active',
								'Arrangements Failed',
								'Arrangements Hold',
								'Amortization',
								'Bankruptcy Notification',
								'Bankruptcy Verified',
								'Collections New',
								'Collections Contact',
								'Collections Followup',
								'Collections Contact',
								'Past Due',
								'Made Arrangements',
						//		'QC Ready',
						//		'QC Sent',
						//		'QC Arrangements',
						//		'QC Returned',
						//		'Second Tier (Pending)',
								'Inactive (Recovered)',
						//		'Second Tier (Sent)',
								'Inactive (Paid)',
								'Funding Failed',
								);

		$flash_statuses = "'" . implode("','", $flash_statuses) . "'";

		// mantis:10272
		// Data is now pulled from the resolve_flash_report table, as this
		// table is populated by essentially the same query. This will ensure
		// consistency between the the flash and daily cash reports. Note that
		// the resolve_flash_report table is now populated by batch_maintenance.

		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				model,
				status,
				count
			FROM
				resolve_flash_report
			WHERE
				status IN ({$flash_statuses})
				AND date = '{$this->today}'
			--	AND	loan_type IN ('standard')
				AND	company_id IN ({$this->company_id})
			GROUP BY
				model, status
		";

		$result = $this->db->Query($query);

		$flash_data = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$flash_data[$row['status']][$row['model']] = $row['count'];
		}

		$future_data = array();
		foreach ($this->order_future as $label => $key) {
			if (isset($flash_data[$key])) {
				$future_data[$label] = $flash_data[$key];
			} else {
				$future_data[$label] = array();
			}
		}

		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $future_data;
	}

	private function Fetch_Monthly_Data() {
		$daily_totals = array_merge(
			$this->Fetch_ACH_Return_Totals(),
			$this->Fetch_ACH_Totals(),
			$this->Fetch_Customer_Credit_Counts(),
			$this->Fetch_Cancellations(),
			$this->Fetch_Payout_Data(),
			$this->Fetch_Reattempt_Data(),
			//$this->Fetch_Quickcheck_Return_Totals(),
			//$this->Fetch_Quickcheck_Deposit(),
			$this->fetchManualAndExtCollectionsDeposits()
		);

		return $daily_totals;
	}

	/**
	 * Fetch_Period_Aggregates
	 * Because the eCash Commercial version of the Daily Cash Report isn't daily at all, and has to be run for a date span
	 * Period Aggregates are needed.
	 * I'm not going to pretend to understand how this works.  It adds things up based on a date range or something.
	 *
	 * @param array $columns The columns to be aggregating
	 * @param date $start_date the start of the date range
	 * @param date $end_date the end of the date range
	 * @return array
	 */
	private function Fetch_Period_Aggregates($columns,$start_date,$end_date)
	{
		//$this->timer->Timer_Start("DC:".__METHOD__);
		//$end_date = isset($day) ? $day : $this->today;
		$select_columns = array();
		foreach ($columns as $column) {
			$select_columns[] = "IFNULL(SUM({$column}), 0) as {$column}";
		}

		$select_columns = implode(",\n\t\t\t\t", $select_columns);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				{$select_columns}
			FROM
				resolve_daily_cash_report
			WHERE
				report_date >= {$this->db->quote($start_date)} AND
				report_date <= {$this->db->quote($end_date)} AND
				company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);

		//$this->timer->Timer_Stop("DC:".__METHOD__);

		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row;
		}
		else
		{
			return array_combine($columns, array_fill(0, count($columns), 0));
		}
	}
	private function Fetch_Weekly_Aggregates($columns, $day = null) {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$end_date = isset($day) ? $day : $this->today;
		$previous_sunday = date('Y-m-d', strtotime('previous sunday, +1 day', strtotime($end_date)));
		$select_columns = array();
		foreach ($columns as $column) {
			$select_columns[] = "IFNULL(SUM({$column}), 0) as {$column}";
		}

		$select_columns = implode(",\n\t\t\t\t", $select_columns);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				{$select_columns}
			FROM
				resolve_daily_cash_report
			WHERE
				report_date >= '{$previous_sunday}' AND
				report_date <= '{$end_date}' AND
				company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);

		//$this->timer->Timer_Stop("DC:".__METHOD__);

		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row;
		}
		else
		{
			return array_combine($columns, array_fill(0, count($columns), 0));
		}
	}

	private function Fetch_Monthly_Aggregates($columns, $day = null) {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$end_date = isset($day) ? $day : $this->today;
		$first_of_month = date('Y-m-01', strtotime($end_date));
		$select_columns = array();
		foreach ($columns as $column) {
			$select_columns[] = "IFNULL(SUM({$column}), 0) as {$column}";
		}

		$select_columns = implode(",\n\t\t\t\t", $select_columns);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				{$select_columns}
			FROM
				resolve_daily_cash_report
			WHERE
				report_date >= '{$first_of_month}' AND
				report_date <= '{$end_date}' AND
				company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);
		
		$report = $result->fetch(PDO::FETCH_ASSOC);
		
		//$this->timer->Timer_Stop("DC:".__METHOD__);

		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row;
		}
		else
		{
			return array_combine($columns, array_fill(0, count($columns), 0));
		}
	}

	private function Fetch_Quickcheck_Deposit()
	{
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(ecld.amount) total
			FROM
				ecld
				JOIN ecld_file ef USING (ecld_file_id)
			WHERE
				ef.company_id = {$this->company_id} AND
				ef.date_created BETWEEN '{$this->getLastCashReportRun()} 00:00:00' AND '{$this->today} 23:59:59'
		";

		$result = $this->db->Query($query);
		
		
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return array('quick_check_deposit' => $row['total']);
		}

		return array('quick_check_deposit' => 0);
	}

	private function Fetch_ACH_Return_Totals() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(IF(ach.ach_type = 'debit', ach.amount, 0)) returns,
				SUM(IF(ach.ach_type = 'debit' AND arc.name_short IN ('N1', 'R01', 'P-N', 'A'), ach.amount, 0)) nsf,
				SUM(IF(ach.ach_type = 'credit', ach.amount, 0)) credit_returns
			FROM
				ach_report ar
				JOIN ach USING (ach_report_id)
				JOIN ach_return_code arc USING (ach_return_code_id)
			WHERE
				ar.ach_report_request LIKE 'report=returns%' AND
				ar.date_request BETWEEN '{$this->getLastCashReportRun()}' AND '{$this->today}' AND
				ar.company_id = {$this->company_id}
		";

		$result = $this->db->Query($query);
		$return_info = $result->fetch(PDO::FETCH_ASSOC);
		
		//mantis:7358 - added company to the query
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(ae.debit_amount) returns,
				SUM(ae.credit_amount) credit_returns
			FROM
				ach_exception ae
			WHERE
				ae.return_date BETWEEN '{$this->getLastCashReportRun()}' AND '{$this->today}'
			   AND
				ae.company_id = {$this->company_id}
		";
		$result = $this->db->Query($query);
		
		$return_info2 = $result->fetch(PDO::FETCH_ASSOC);

		$return_info['returns'] += $return_info2['returns'];
		$return_info['credit_returns'] += $return_info2['credit_returns'];

		//mantis:9177 - added the tr.amount > 0
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(IF(tr.amount > 0, tr.amount, 0)) returns,
				ABS(SUM(IF(tr.amount < 0, tr.amount, 0))) credit_returns
			FROM
				transaction_register tr
				JOIN event_schedule es USING (event_schedule_id)
				JOIN event_type et USING (event_type_id)
			WHERE
				et.name_short = 'cashline_return' AND
				es.date_effective BETWEEN '{$this->getLastCashReportRun()}' AND '{$this->today}' AND
				es.company_id = {$this->company_id}
		";

		$result = $this->db->Query($query);
		
		
		$return_info3= $result->fetch(PDO::FETCH_ASSOC);

		$return_info['returns'] += $return_info3['returns'];
		$return_info['credit_returns'] += $return_info3['credit_returns'];

//		$this->timer->Timer_Stop("DC:".__METHOD__);
		return $return_info;
	}

	private function Fetch_Quickcheck_Return_Totals() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(ecld.amount) quick_check_returns
			FROM
				ecld_return er
				JOIN ecld USING (ecld_return_id)
			WHERE
				DATE(er.date_created) BETWEEN '{$this->getLastCashReportRun()}' AND '{$this->today}' AND
				ecld.company_id = {$this->company_id}
		";
		
		$result = $this->db->Query($query);
		
		return $result->fetch(PDO::FETCH_ASSOC);
		
		//$this->timer->Timer_Stop("DC:".__METHOD__);
	}

	private function Fetch_ACH_Totals() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				SUM(IF(ach_type = 'credit', tr.amount, 0)) loan_disbursement,
				SUM(IF(ach_type = 'credit' AND tt.name_short LIKE 'refund%', tr.amount, 0)) refund_total
			FROM
				ach
				JOIN transaction_register tr USING (ach_id)
				JOIN event_schedule es USING (event_schedule_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE
				es.date_event = '{$this->today}' AND
				es.date_effective = '{$this->next_business_day}' AND
				es.company_id = {$this->company_id}
		";

		//$this->timer->Timer_Stop("DC:".__METHOD__);
		$result = $this->db->Query($query);
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Returns the totals for card loans.
	 *
	 * Currently the only total returned is the amount of disbursements made for
	 * card loans.
	 *
	 * @return Array
	 */
	private function Fetch_Card_Totals()
	{
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				IFNULL(SUM(tl.amount), 0) card_loan_disbursement
			FROM
				transaction_ledger tl
			WHERE
				tl.transaction_type_id = (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE
						name_short = 'card_loan_disbursement' AND
						company_id = {$this->company_id}
				) AND
				tl.date_posted = '{$this->today}'
		";

		$result = $this->db->Query($query);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	private function Fetch_Reattempt_Data()
	{
		//$this->timer->Timer_Start("DC:".__METHOD__);

		$reattemptQuery = new Reattempts_Report_Query($this->server);

		if ($this->today_is_business_day)
		{
			$data = $reattemptQuery->Fetch_Reattempts_Data($this->next_business_day, $this->next_business_day, $this->company_id);

			if(!empty($data))
			{
				$currentData = array_shift(array_shift($data));
			}
		//	$this->timer->Timer_Stop("DC:".__METHOD__);
		}

		if ($currentData)
		{
			return array(
				'total_debited' => -(
					$currentData['new_principal'] +
					$currentData['re_principal'] +
					$currentData['new_svr_charge'] +
					$currentData['re_svr_charge'] +
					$currentData['new_fees'] +
					$currentData['re_fees']
				),
				'principal_debited' => -($currentData['new_principal'] + $currentData['re_principal']),
				'service_charges_debited' => -($currentData['new_svr_charge'] + $currentData['re_svr_charge']),
				'fees_debited' => -($currentData['new_fees'] + $currentData['re_fees']),
				'new_principal_debited' => -$currentData['new_principal'],
				'new_service_charges_debited' => -$currentData['new_svr_charge'],
				'new_fees_debited' => -$currentData['new_fees'],
				're_principal_debited' => -$currentData['re_principal'],
				're_service_charges_debited' => -$currentData['re_svr_charge'],
				're_fees_debited' => -$currentData['re_fees'],
			);
		} else
		{
			return array();
		}
	}

	/**
	 * Returns the count of the type of credits sent.
	 *
	 * The types of credits are:
	 * <ul>
	 *     <li><b>Refund</b> - A refund transaction or an adjustment that
	 *         increases the card balance.
	 *     </li>
	 *     <li><b>Resend</b> - The loan disbursement for an account has been
	 *         resent.
	 *     </li>
	 *     <li><b>React</b> - The loan being funded is a react.</li>
	 *     <li><b>New</b> - The loan being funded is a new loan.</li>
	 *     <li><b>Card React</b> - The loan being funded is a card react.</li>
	 * </ul>
	 *
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>2007-10-23 - mlively</b><br>
	 *         Added the card react field to the query.
	 *     </li>
	 * </ul>
	 *
	 * @return Array
	 */
	private function Fetch_Customer_Credit_Counts() {
		//$this->timer->startTimer("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				COUNT(IF(loan_type = 'Refund', 1, NULL)) refunded_customers,
				COUNT(IF(loan_type = 'Resend', 1, NULL)) resend_customers,
				COUNT(IF(loan_type = 'React', 1, NULL)) reactivated_customers,
				COUNT(IF(loan_type = 'New', 1, NULL)) new_customers,
				COUNT(IF(loan_type = 'Card React', 1, NULL)) card_reactivated_customers
			FROM (
				SELECT
					(CASE
						WHEN
							lt.name_short = 'card'
						THEN
							'Card React'
					-- Is this an adjustment instead of a loan disbursement?
						WHEN (
							SELECT
								COUNT(*)
							FROM
								transaction_type tt4
								JOIN event_transaction et4 USING (transaction_type_id)
								JOIN event_schedule es4 USING (event_type_id)
							WHERE
								(
									tt4.clearing_type = 'adjustment' OR
									tt4.name_short LIKE 'refund%'
								) AND
								es4.event_schedule_id = es.event_schedule_id
							) > 0 AND ((es.amount_principal >  0) OR (es.amount_non_principal >  0))
						THEN
							'Refund'

						-- is there a previous failure for this app?
						WHEN (
							SELECT
								COUNT(*)
							FROM
								transaction_register tr3
								JOIN transaction_type tt3 USING (transaction_type_id)
							WHERE
								tr3.application_id = a.application_id AND
								tt3.name_short = 'loan_disbursement' AND
								tr3.transaction_status = 'failed' AND
								tr3.date_effective < '{$this->next_business_day}'
							) > 0
						THEN
							'Resend'

						WHEN
							a.is_react = 'yes'
						THEN
							'React'

						ELSE 'New'
					END) AS loan_type
				FROM
					application a
					JOIN loan_type lt USING (loan_type_id)
					JOIN event_schedule es USING (application_id)
					JOIN event_type et USING (event_type_id)
				WHERE
					es.date_effective IN ('{$this->today}', '{$this->next_business_day}')
					AND es.date_event = '{$this->today}'
					AND	et.name_short IN ('loan_disbursement','adjustment_external','refund','refund_3rd_party','card_loan_disbursement')
					AND	a.company_id IN ({$this->company_id})
				GROUP BY
					es.event_schedule_id
			) tmp;
		";

		$result = $this->db->Query($query);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	private function Fetch_Cancellations() {
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				count(DISTINCT transaction_register.application_id) cancelled_customers
			FROM
				transaction_register
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es USING (event_schedule_id)
			WHERE
				es.date_event = '{$this->today}' AND
				es.date_effective IN ('{$this->today}', '{$this->next_business_day}') AND
				tt.name_short IN ('card_cancel', 'cancel_fees', 'cancel_principal') AND
				es.company_id = '{$this->company_id}'
		";

		$result = $this->db->Query($query);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	private function Fetch_Payout_Data() {
		$row = array(
			'paid_out_customers_ach' => ($this->today_is_business_day ? $this->fetchPayoutCount($this->next_business_day, true) : 0),
			'paid_out_customers_non_ach' => $this->fetchPayoutCount($this->today, false),
		);
		return $row;
	}

	private function fetchPayoutCount($date_effective, $is_ach)
	{
		$not_in_payouts = implode(',', array($this->status_ids['fund_failed'], $this->status_ids['withdrawn']) );
//		$cashline_ids = implode(',', array($this->status_ids['cashline'], $this->status_ids['in_cashline'], $this->status_ids['pending_transfer']));
		$ach_where = $is_ach ? 'tr.ach_id IS NOT NULL' : 'tr.ach_id IS NULL';
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				count(payouts) paid_out_customers
			FROM
				(
					SELECT
						IF(
							(
								SELECT
									SUM(
										IF(
											tr.transaction_register_id IS NULL,
											es.amount_principal + es.amount_non_principal,
											tr.amount
										)
									)
								FROM
									event_schedule es
									LEFT JOIN transaction_register tr USING (event_schedule_id)
								WHERE
									es.date_effective <= '{$date_effective}' AND
									es.application_id = a.application_id AND
									(
										es.event_status = 'scheduled' OR
										tr.transaction_status IN ('pending','complete')
									) AND
									es.context != 'cancel'
							) <= 0 AND a.application_status_id NOT IN ({$not_in_payouts}),
							1,
							NULL
						) payouts
					FROM
						event_schedule es
						JOIN event_type AS et USING (event_type_id)
						JOIN application a ON es.application_id = a.application_id
						JOIN loan_type lt USING (loan_type_id)
						LEFT JOIN transaction_register tr USING (event_schedule_id)
					WHERE
						{$ach_where} AND
						es.date_effective = '{$date_effective}' AND
						es.event_status <> 'suspended' AND
						(tr.transaction_status <> 'failed' OR tr.transaction_status IS NULL) AND
						es.company_id IN ({$this->company_id}) AND
						lt.name_short IN ('standard', 'card') AND
						es.amount_principal <= 0 AND
						es.amount_non_principal <= 0 AND
						(es.amount_principal + es.amount_non_principal) < 0 AND
						-- a.application_status_id NOT IN ({$cashline_ids}) AND
						(et.name <> 'Cancel' OR es.amount_non_principal < 0)
					GROUP BY
						es.application_id
				) tmp;
		";
		$result = $this->db->Query($query);
		//$result = get_mysqli()->Query($query);
		$report = $result->fetch(PDO::FETCH_ASSOC);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $report['paid_out_customers'];
	}

	/**
	* This function with no arguments fetches total amount of manual and external collections deposits
	* for the given day
	*
	* Revision History:
	*		alexanderl - 10/18/2007 - added money order deposit [mantis:12335]
	*
	* @returns array $rows
	*/
	public function fetchManualAndExtCollectionsDeposits()
	{
		//$this->timer->Timer_Start("DC:".__METHOD__);
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT
			SUM(
				IF(et.name_short = 'moneygram', -tl.amount, 0)
			) moneygram_deposit,
			SUM(
				IF(et.name_short = 'money_order', -tl.amount, 0)
			) money_order_deposit,
			SUM(
				IF(et.name_short = 'credit_card', -tl.amount, 0)
			) credit_card_payments,
			SUM(
				IF(et.name_short IN ('chargeback', 'chargeback_reversal'), -tl.amount, 0)
			) chargebacks,
			SUM(
				IF(et.name_short IN('ext_recovery', 'ext_recovery_reversal') AND
					ecb.ext_collections_co = 'recovery'
				, -tl.amount, 0)
			) pinion_recovery,
			SUM(
				IF(et.name_short IN('ext_recovery', 'ext_recovery_reversal') AND
					ecb.ext_collections_co = 'final collections'
				, -tl.amount, 0)
			) final_collections,
			SUM(
				IF(et.name_short = 'debt_writeoff'
					AND tt.name_short = 'debt_writeoff_princ', -tl.amount, 0)
			) AS bad_debt_write_off_principal,
			SUM(
				IF(et.name_short = 'debt_writeoff'
					AND tt.name_short = 'debt_writeoff_fees', -tl.amount, 0)
			) AS bad_debt_write_off_fees
		FROM
			event_schedule es
			JOIN event_type et USING (event_type_id)
			JOIN transaction_register tr USING (application_id, event_schedule_id)
			JOIN transaction_ledger tl USING (application_id, transaction_register_id)
			JOIN transaction_type AS tt ON (tt.transaction_type_id = tl.transaction_type_id)
			LEFT JOIN ext_collections ec USING (application_id)
			LEFT JOIN ext_collections_batch ecb USING (ext_collections_batch_id)
		WHERE
			tl.date_created BETWEEN '{$this->getLastCashReportRun()} 00:00:00' AND '{$this->today} 23:59:59' AND
			tl.company_id = {$this->company_id}
		";

		$result = $this->db->Query($query);
		//$result = get_mysqli()->Query($query);
		//$this->timer->Timer_Stop("DC:".__METHOD__);
		return $result->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Creates and returns the data for $this->today's cash report.
	 *
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>2007-10-23 - mlively</b>
	 *         Added Card totals to the root of the array.
	 *     </li>
	 * </ul>
	 *
	 * @return Array
	 */
	public function Fetch_Current_Data($timer)
	{
		$this->timer->startTimer( $timer );

		$data = array_merge(
			$this->Get_Intercept_Reserve(),
			$this->Fetch_Current_Advance_Numbers(),
			$this->Fetch_Card_Totals(),
			array('future' => $this->Fetch_Current_Flash_Data()),
			array('monthly' => $this->Fetch_Monthly_Data())
		);

		$this->timer->stopTimer( $timer );

		return $data;

	}

	public function Fetch_Past_Data($start_date,$end_date, $timer)
	{
	//	$this->timer->Timer_Start( $timer );
		$query= "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				*
			FROM
				resolve_daily_cash_report
			WHERE
				report_date BETWEEN {$this->db->quote($start_date)} AND {$this->db->quote($end_date)}
				AND									
				company_id = '{$this->company_id}'
		";
		
		$period = $this->Fetch_Period_Aggregates($this->order_monthly, $start_date, $end_date);
		$result = $this->db->Query($query);
		while ($report = $result->fetch(PDO::FETCH_ASSOC)) {
		
		
			//$report = $result->fetch(PDO::FETCH_ASSOC);
	
			if ($report['serialized_data']) {
				$report_data = unserialize($report['serialized_data']);
				$data = $report_data;

				$data['future'] = array();
				//We only want to use future data for the 
				foreach ($report_data['future'] as $report_key => $report_value) 
				{
					if(array_key_exists($report_key,$this->order_future))
					{
						$data['future'][$report_key] = $report_value;
					}
					else 
					{
						//echo "<br> Haha, not inserting $report_key";
					}
				}
				
				$current_day = $report;
				//$week = $this->Fetch_Weekly_Aggregates(array_values($this->order_monthly), $specific_date);
				//$month = $this->Fetch_Monthly_Aggregates(array_values($this->order_monthly), $specific_date);
	
	//			$data['monthly'] = array();
				$data['period'] = array();
				
				//populate data from the serialized data
				foreach ($this->order_monthly as $label => $key) {
					$value = $current_day[$key];
					$data['period'][$label] = array(
				//		'today' => $value,
				//		'week' => $week[$key],
				//		'month' => $month[$key],
						'span' => $period[$key]
					);
				}
				
				//Populate manual data						
				if (is_array($this->manual_data)) 
				{
					foreach ($this->manual_data as $key => $value) {
					$data['period'][$key]['span'] = $value;
					}
					
				}
				//If the manual data hasn't been inserted, try to pull it from the serialized data
				else 
				{

					foreach ($this->order_manual as $label => $key)
					{

						$value = $report_data['period'][$label];
						$data['period'][$label] = $value;
					}
				}
			} else {
				$data = array();
			}
		}
		return $data;
	}
}

?>
