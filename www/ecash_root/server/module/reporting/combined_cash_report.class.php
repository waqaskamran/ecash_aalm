<?php
/**
 * @package Reporting
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

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once(SERVER_MODULE_DIR . "reporting/daily_cash_pdf.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( SERVER_CODE_DIR . "reattempts_report_query.class.php" );
require_once( LIBOLUTION_DIR . "Object.1.php" );
require_once( LIBOLUTION_DIR . "Mail/Trendex.1.php" );
require_once( COMMON_LIB_DIR . "pay_date_calc.3.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");


class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		// company_id
		try
		{
			$this->search_query = new Daily_Cash_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id' => $this->request->company_id,
			);

			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$specific_date_YYYYMMDD = "{$data->search_criteria['specific_date_YYYY']}-{$data->search_criteria['specific_date_MM']}-{$data->search_criteria['specific_date_DD']}";
	
			if ($this->request->update_deposit_data == 1) 
			{
				$this->search_query->Update_Manual_Data($specific_date_YYYYMMDD, $this->request->deposit);
			}


			$data->search_results = $this->search_query->Fetch_Daily_Cash_Report( $specific_date_YYYYMMDD);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
		$data->report_date = date('m/d/Y', strtotime($specific_date_YYYYMMDD));
		$data->company_name = ECash::getConfig()->COMPANY_DEPT_NAME;
		if (count($data->search_results))
		{
			$data->search_results['date'] = $data->report_date;
			$data->search_results['company_name'] = $data->company_name;
		}

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['daily_cash']['report_data'] = $data;
		$_SESSION['reports']['daily_cash']['url_data'] = array('name' => 'Combined Cash Report', 'link' => '/?module=reporting&mode=combined_cash');
	}

	public function Download_Report()
	{
		$data = $_SESSION['reports']['daily_cash']['report_data'];
		$data->filename = '/tmp/daily_cash_report_'.uniqid().'.pdf';

		$pdf = new Daily_Cash_Report_PDF($data->company_name, $data->report_date, $data->search_results);
		$pdf->Create_PDF($data->filename);

		if (empty($this->request->email_to)) 
		{
			$data->download = true;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('download',$this->report_name);
		} 
		else 
		{
			$recipients = $this->request->email_to;
			$tokens = array(
				'subject' => 'Daily Cash Report PDF - ' . $data->report_date,
				'report_date' => $data->report_date,
				'from' => ECash::getConfig()->MANAGER_EMAIL_ADDRESS);
			$contents = file_get_contents($data->filename);
			$attachments = array(
				array(
					'method' => 'ATTACH',
					'filename' => 'daily_cash_report_'.date('Y-m-d', strtotime($data->report_date)).'.pdf',
					'mime_type' => 'application/pdf',
					'file_data' => gzcompress($contents),
					'file_data_size' => strlen($contents),
				)
			);

			require_once(LIB_DIR . '/Mail.class.php');
			eCash_Mail::sendMessage('ECASH_DAILY_CASH_REPORT', $recipients, $tokens, $attachments);

			ECash::getTransport()->Set_Levels("close_pop_up");
			ECash::getTransport()->Set_Data($data);
		}
	}
}

class Daily_Cash_Report_Query extends Base_Report_Query
{
	const TIMER_NAME = "Combined Cash Report";
	const ARCHIVE_TIMER = "Archived Daily Cash Report";

	private $server;
	private $today;
	private $next_business_day;
	private $order_monthly = array(
		'new customers' => 'new_customers',
		'reactivated customers' => 'reactivated_customers',
		'refunded customers' => 'refunded_customers',
		'resend customers' => 'resend_customers',
		'cancelled customers' => 'cancelled_customers',
		'paid out customers (ach)' => 'paid_out_customers_ach',
		'paid out customers (non-ach)' => 'paid_out_customers_non_ach',
		'nsf$' => 'nsf',
		'returns' => 'returns',
		'total debited' => 'total_debited',
		'net cash collected' => 'net_cash_collected',
		'credit card payments' => 'credit_card_payments',
		'western union deposit' => 'western_union_deposit',
		'money order deposit' => 'money_order_deposit',
		'quick check deposit' => 'quick_check_deposit',
		'quick checks returned' => 'quick_check_returns',
		'moneygram deposit' => 'moneygram_deposit',
		'crsi recovery' => 'crsi_recovery',
		'pinion recovery' => 'pinion_recovery',
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
	);
	private $order_future = array(
		'active' => 'Active',
		'bankruptcy notification' => 'Bankruptcy Notification',
		'bankruptcy verification' => 'Bankruptcy Verified',
		'amortization' => 'Amortization',
		'collections' => 'Collections',
		'collections new' => 'Collections New',
		'collections contact' => 'Collections Contact',
		'contact follow up' => 'Contact Followup',
		'funding failed' => 'Funding Failed',
		'past due' => 'Past Due',
		'made arrangements' => 'Made Arrangements',
		'qc arrangements' => 'QC Arrangements',
		'qc ready' => 'QC Ready',
		'qc returned' => 'QC Returned',
		'qc sent' => 'QC Sent',
		'inactive paid' => 'Inactive (Paid)',
		'second tier ready' => 'Second Tier (Pending)',
		'second tier sent' => 'Second Tier (Sent)',
		'inactive recovered' => 'Inactive (Recovered)',
	);
	private $status_map;


	static private function Test_Database() {
		return new PDO('sqlite:/virtualhosts/ecash_development/daily_cash.sq3');
	}

	public function __construct(Server $server)
	{
		parent::__construct($server);
		$this->server = $server;

		$this->today = date('Y-m-d');
		$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		$this->next_business_day = $pdc->Get_Next_Business_Day($this->today);
		$this->status_map = Fetch_Status_Map();
	}

	public function Fetch_Daily_Cash_Report($specific_date)
	{
		$data = $this->Fetch_Past_Data($specific_date, self::ARCHIVE_TIMER);
		return $data;
	}


	public function Fetch_Past_Data($specific_date, $timer)
	{
		$this->timer->startTimer( $timer );
		$query= "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				serialized_data
			FROM
				resolve_daily_cash_report
			WHERE
				report_date = '{$specific_date}'
		";
		$st = $this->db->query($query);

		$data = array();
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$tmp_data = unserialize($row['serialized_data']);
			if($tmp_data)
			{
				foreach ($tmp_data as $key => $value)
				{
					if(is_array($value))
					{
						foreach ($value as $key2 => $value2)
						{
							if(is_array($value2))
							{
								foreach ($value2 as $key3 => $value3)
								{
									$data[$key][$key2][$key3] =+ $value3;
								}
							}
							else
							{
								$data[$key][$key2] =+ $value2;
							}
						}
					}
					else
					{
						$data[$key] =+ $value;
					}
				}
			}

		}
		return $data;
	}
}

?>
