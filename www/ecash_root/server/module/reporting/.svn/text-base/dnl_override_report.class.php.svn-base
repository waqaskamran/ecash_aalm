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
		try
		{
			$this->search_query = new DNL_Override_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type,
			);

			$_SESSION['reports']['dnl_override']['report_data'] = new stdClass();
			$_SESSION['reports']['dnl_override']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['dnl_override']['url_data'] = array('name' => 'DNL Override Report', 'link' => '/?module=reporting&mode=dnl_override');

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

			$data->search_results = $this->search_query->Fetch_DNL_Override_Data($start_date_YYYYMMDD,
										  	$end_date_YYYYMMDD,
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
		//var_dump($data->search_results);
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
		$_SESSION['reports']['dnl_override']['report_data'] = $data;
	}
}

class DNL_Override_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "DNL Override Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_DNL_Override_Data($date_start, $date_end, $company_id, $loan_type)
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

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";


		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		select
	ovr.agent_id,
	ovr.date_created,
	com.name_short as company_name,
	com.company_id as company_id,
	app.name_first,
	app.name_last,
	app.application_id,
	app.application_status_id as application_status_id,
	aps.name as app_status,
	(select count(*) from do_not_loan_flag where do_not_loan_flag.ssn=ovr.ssn and do_not_loan_flag.active_status = 'active') as dnl_total,
			(
				select
		com2.name_short
	from
		do_not_loan_flag
	join
		company as com2 on (do_not_loan_flag.company_id = com2.company_id)
	where
		do_not_loan_flag.ssn=ovr.ssn and do_not_loan_flag.active_status = 'active'
	order by
		do_not_loan_flag.date_created desc
	limit 1
	) as dnl_company_name,
	com1.name_short as company_owner,
	if(ag.name_last is null, 'Unknown', concat(lower(ag.name_first), ' ', lower(ag.name_last))) as agent_name
		from
	do_not_loan_flag_override as ovr
		join company as com on (ovr.company_id = com.company_id)
		join application as app on (ovr.ssn = app.ssn)
		join loan_type lt ON (lt.loan_type_id = app.loan_type_id)
		join application_status as aps on (app.application_status_id = aps.application_status_id)
		join company as com1 on (app.company_id = com1.company_id)
		left join agent as ag on (ovr.agent_id = ag.agent_id)
		where
	ovr.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
		and
	ovr.company_id IN ({$company_list})
		{$loan_type_sql}
		limit {$max_report_retrieval_rows}
		";

		$data = array();

		$db = ECash::getMasterDb();
		$st = $db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$company_name = $row['company_name'];

			$this->Get_Module_Mode($row);

			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
