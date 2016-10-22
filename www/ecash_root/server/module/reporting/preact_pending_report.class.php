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
			$this->search_query = new Preact_Pending_Report_Query($this->server);

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
			);

			$_SESSION['reports']['preact_pending']['report_data'] = new stdClass();
			$_SESSION['reports']['preact_pending']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['preact_pending']['url_data'] = array('name' => 'Preact Pending', 'link' => '/?module=reporting&mode=preact_pending');

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

			$data->search_results = $this->search_query->Fetch_Preact_Pending_Data($start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
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
		$_SESSION['reports']['preact_pending']['report_data'] = $data;
	}
}

class Preact_Pending_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Preact Pending Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Preact Pending Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Preact_Pending_Data($date_start, $date_end, $company_id)
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

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
select
	ra.date_created,
	com.company_id					AS	company_id,
	upper(com.name_short)        	AS company_name,
	concat(lower(ag.name_last),
	       ', ',
	       lower(ag.name_first)) 	AS agent_name,
	ra.application_id 				AS parent_application_id,
	p_app.application_status_id 	AS parent_status_id,
	ra.react_application_id 		AS preact_application_id,
	r_app.application_status_id 	AS preact_status_id,
	r_app.date_fund_estimated 		AS preact_date_fund_estimated
	from react_affiliation as ra
	join application as p_app on (p_app.application_id = ra.application_id)
	join application as r_app on (r_app.application_id = ra.react_application_id)
	join company as com on (com.company_id = ra.company_id)
	join agent as ag on (ag.agent_id = ra.agent_id)
where
	r_app.olp_process = 'ecashapp_preact'
and
	ra.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
and
	ra.company_id IN ({$company_list})
LIMIT {$max_report_retrieval_rows}
			";
		//$this->log->Write($query);
//print($query);
//die();
		$data = array();
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$row['application_id'] 			= $row['parent_application_id'];
			$row['application_status_id'] 	= $row['parent_status_id'];
			$this->Get_Module_Mode($row, $row['company_id']);
			$row['parent_mode'] 			= $row['mode'];
			$row['parent_module'] 			= $row['module'];
			unset($row['application_id']);unset($row['mode']);unset($row['module']);
			unset($row['application_status_id']);

			$row['application_id'] 			= $row['preact_application_id'];
			$row['application_status_id'] 	= $row['preact_status_id'];
			$this->Get_Module_Mode($row, $row['company_id']);
			$row['preact_mode'] 			= $row['mode'];
			$row['preact_module'] 			= $row['module'];
			unset($row['application_id']);
			unset($row['application_status_id']);

			$row['agent_name'] = ucwords($row['agent_name']);
			$data[$company_name][] = $row;
		}
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
