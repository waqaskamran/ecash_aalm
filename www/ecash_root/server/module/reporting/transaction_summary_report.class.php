<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 19111 $
 */

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Transaction_Summary_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['transaction_summary']['report_data'] = new stdClass();
			$_SESSION['reports']['transaction_summary']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['transaction_summary']['url_data'] = array('name' => 'Transaction Summary', 'link' => '/?module=reporting&mode=transaction_summary');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM	 = $this->request->start_date_month;
			$start_date_DD	 = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			// End date
			$end_date_YYYY	 = $this->request->end_date_year;
			$end_date_MM	 = $this->request->end_date_month;
			$end_date_DD	 = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Transaction_Summary_Data( $start_date_YYYYMMDD,
											   $end_date_YYYYMMDD,
											   $this->request->loan_type,
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
		if( $data->search_results === false )
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary

		$_SESSION['reports'][$this->report_name]['last_sort']['col'] = 'order';
		$_SESSION['reports'][$this->report_name]['last_sort']['direction'] = SORT_ASC;

		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['transaction_summary']['report_data'] = $data;
	}
}

class Transaction_Summary_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Transaction Summary Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);

		// These status ids are not in the default list
		$this->Add_Status_Id('withdrawn', array('withdrawn','applicant','*root'));
		$this->Add_Status_Id('denied',    array('denied',   'applicant','*root'));
		$this->Add_Status_Id('cfc_queue',  array('queued','verification','applicant','*root'));
		$this->Add_Status_Id('cfc_dequeue',  array('dequeued','verification','applicant','*root'));
	}

	public function Fetch_Transaction_Summary_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		//echo "\n<br><pre>" . print_r($_SESSION,true) . "</pre><br>\n";
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();
		$company_name = null;
		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
		{
			$company_list = "'{$company_id}'";
		}
		else
		{
			$company_list = "'" . implode("','", $auth_company_ids) . "'";
		}


		//Get totals of approved, denied, and pending applications, along with grand total
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				upper(c.name_short) AS company_name,
				sh.company_id       AS company_id,
				sh.application_status_id,

				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['funded']}
						THEN
							1
						ELSE
							0
					END
				) as approved,
				sum(
					CASE
						WHEN
							sh.application_status_id = {$this->status_ids['denied']}
						THEN
							1
						ELSE
							0
					END
				) as denied,
				sum(
					CASE
						WHEN
							sh.application_status_id in ({$this->status_ids['cfc_queue']},{$this->status_ids['cfc_dequeue']})
						THEN
							1
						ELSE
							0
					END
				) as pending,
				count(sh.application_status_id) as count

			 FROM
				company                 c,
				loan_type               lt,
				status_history          sh
			 WHERE
				sh.date_created BETWEEN '{$timestamp_start}'
				                    AND '{$timestamp_end}'
			  AND	c.company_id             =  lt.company_id
			  AND	sh.company_id            =  c.company_id


			  AND sh.application_status_id IN ({$this->status_ids['denied']},{$this->status_ids['funded']},
			  								{$this->status_ids['cfc_queue']},{$this->status_ids['cfc_dequeue']})
			  AND	sh.company_id            IN ({$company_list})
			  AND	lt.name_short            IN ({$loan_type_list})
			 GROUP BY
			 	sh.company_id
			";

		$totals = array();
		$st = $this->db->query($query);

    $row = $st->fetch(PDO::FETCH_ASSOC);
		$company_name = $row['company_name'];
		if($total=$row['count'])
		{

		$totals[0] = array(
						'application_status'	=>	"Total Approved",
						'order'					=>	0,
						'count'					=>	$row['approved'],
						'pct'					=>	($row['approved']/$total)*100,
						'description'			=>	"Total approved applications");
		$totals[1] = array(
						'application_status'	=>	"Total Pending",
						'order'					=>	1,
						'count'					=>	$row['pending'],
						'pct'					=>	($row['pending']/$total)*100,
						'description'			=>	"Total pending applications");
		$totals[2] = array(
						'application_status'	=>	"Total Denied",
						'order'					=>	4,
						'count'					=>	$row['denied'],
						'pct'					=>	($row['denied']/$total)*100,
						'description'			=>	"Total denied applications");
		$totals[3] = array(
						'application_status'	=>	"Grand Total",
						'order'					=>	10,
						'count'					=>	$row['count'],
						'pct'					=>	($row['count']/$total)*100,
						'description'			=>	"Total applications");
		}



	//get denied reasons
	//gets denied code from the loan action
	//note: Loan actions aren't necessarily 1-1 with status changes.
	$query = "
		-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				loan_actions.description,
				loan_actions.name_short as application_status ,
				company.company_id,
				company.name as company_name
			FROM
				company,loan_actions
			JOIN
				loan_action_history ON (loan_actions.loan_action_id = loan_action_history.loan_action_id
			AND loan_action_history.application_status_id = {$this->status_ids['denied']})
			WHERE
				loan_action_history.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'";
//	echo $query;
		$st = $this->db->query($query);

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{

			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);
			$row['order'] = 3;
			$data[$company_name][] = $row;

		}
		//add the denial reasons to the totals to make up the report.
		$data[$company_name] = $data[$company_name]?array_merge_recursive($totals,$data[$company_name]):$totals;


		return $data;
	}
}

?>
