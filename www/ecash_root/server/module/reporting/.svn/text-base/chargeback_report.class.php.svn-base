<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
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
			$this->search_query = new Chargeback_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'chargeback_type'   => $this->request->chargeback_type,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['chargeback']['report_data'] = new stdClass();
			$_SESSION['reports']['chargeback']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['chargeback']['url_data'] = array('name' => 'Chargeback Report', 'link' => '/?module=reporting&mode=chargeback');

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

			$data->search_results = $this->search_query->Fetch_Chargeback_Data($start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
												 $this->request->chargeback_type,
												 $this->request->company_id,
												 $this->request->loan_type);
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
		$_SESSION['reports']['chargeback']['report_data'] = $data;
	}
}

class Chargeback_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Chargeback Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Chargeback_Data($date_start, $date_end, $chargeback_type, $company_id, $loan_type)
	{
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		switch($chargeback_type)
		{
			case 'all':
				$chargeback_str = "'chargeback','chargeback_reversal'";
				break;
			default:
				$chargeback_str = "'$chargeback_type'";
				break;
		}

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";


		$query = "
			select
				UPPER(com.name_short) as company_name,
				com.company_id as company_id,
				tr.date_created,
				tr.application_id,
				concat(app.name_last, ', ', app.name_first) as full_name,
				tt.name as chargeback_type,
				tr.amount,
				app.application_status_id as application_status_id
			 from
			transaction_register as tr
			join transaction_type as tt on (tt.transaction_type_id = tr.transaction_type_id)
			join application as app on (app.application_id = tr.application_id)
			JOIN loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			join company as com on (com.company_id = app.company_id)
			where
				tt.name_short IN ({$chargeback_str})
			and
				tr.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			and
				app.company_id IN ({$company_list})
			{$loan_type_sql}
			LIMIT {$max_report_retrieval_rows}
		";

		$data = array();
		$st = $this->db->query($query);
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];

			$row['full_name'] = ucwords($row['full_name']);
			$this->Get_Module_Mode($row);

			$data[$company_name][] = $row;
		}


		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}
?>
