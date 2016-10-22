<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");

//This report was created for ticket [#21831].  It uses the data from resolve_flash_report, and then it generates a 30+ column abomination.
class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{

		try
		{
			$this->search_query = new All_Statuses_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'      => $this->request->company_id,
			 // 'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['all_statuses']['report_data'] = new stdClass();
			$_SESSION['reports']['all_statuses']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['all_statuses']['url_data'] = array('name' => 'All Statuses', 'link' => '/?module=reporting&mode=all_statuses');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM   = $this->request->start_date_month;
			$start_date_DD   = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY = $this->request->end_date_year;
			$end_date_MM   = $this->request->end_date_month;
			$end_date_DD   = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
			
			$start_date_YYYYMMDD = 10000 * $start_date_YYYY + 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD   = 10000 * $end_date_YYYY   + 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_All_Statuses_Data( $start_date_YYYYMMDD,
											     $end_date_YYYYMMDD,
											     'all',
											     $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['all_statuses']['report_data'] = $data;
	}
}

class All_Statuses_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "All Statuses Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function sanitizeName($name)
	{
		$fixed = str_replace(' ','_',$name);
		$fixed = str_replace('(','',$fixed);
		$fixed = str_replace(')','',$fixed);
		return $fixed;
	}
	
	public function Fetch_All_Statuses_Data($date_start, $date_end, $loan_type = 'all', $company_id)
	{
		$hardcoded_status_array_because_jared_kleinman_isnt_a_bsa = array(
		'Pending',
		'Prospect Confirmed',
		'Confirm Declined',
		'Disagree',
		'Agree',
		'Withdrawn',
		'Denied',
		'Confirmed',
		'Confirmed Followup',
		'Approved',
		'Approved Followup',
		'Pending Expiration',
		'Pre-Fund',
		'Active',
		'Funding Failed',
		'Amortization',
		'Bankruptcy Notified',
		'Bankruptcy Verified',
		'Servicing Hold',
		'Past Due',
		'Collections New',
		'Collections Contact',
		'Contact Followup',
		'Made Arrangements',
		'Arrangements Failed',
		'Arrangements Hold',
		'Inactive (Paid)',
		'Chargeoff',
		'Second Tier (Pending)',
		'Second Tier (Sent)',
		'Inactive (Recovered)');
		
		$this->timer->startTimer( self::$TIMER_NAME );
	
		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';
		$statuses_we_care_about = array();
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
			resolve_flash_report.date					AS date,
				UPPER(resolve_flash_report.company_name) AS company_name,
				resolve_flash_report.status		       AS status,
				SUM(resolve_flash_report.count)          AS count
			 FROM
				resolve_flash_report
			 WHERE
				resolve_flash_report.date  BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			  AND	resolve_flash_report.company_id IN ({$company_list})
			  AND	resolve_flash_report.loan_type  IN ({$loan_type_list})
			GROUP BY  date, status
		";


		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		$st = $db->query($query);
		$results = array();
		
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			//I know this looks totally wrong, but before you say that, look at HMS #21831 and then look at the resolve_flash_report table
			//If you've got a better way to generate that data and group it appropriately, go for it [W!-12-03-2008]
			$date = $row['date'];
						
			$results[$date][$row['status']] = $row['count'];
		}
		
		//Now we're going through that hideous array and we're putting it together into the format that the display classes need
	
		
		foreach ($results as $date => $row)
		{
			$record = array();
			$record['date'] = $date;
			foreach ($hardcoded_status_array_because_jared_kleinman_isnt_a_bsa as $status)
			{
				$record[strtolower($this->sanitizeName($status))] = 0;
			}
			foreach ($row as $status => $count)
			{
				if(in_array($status,$hardcoded_status_array_because_jared_kleinman_isnt_a_bsa))
				{
					$record[strtolower($this->sanitizeName($status))] = $count;
				}
			}
			$data['record'][] = $record;
		}

		
		$this->timer->stopTimer( self::$TIMER_NAME );
		return $data;
	}
}

?>
