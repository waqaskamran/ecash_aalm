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
			$this->search_query = new Agent_Tracking_Report_Query($this->server);

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

			$_SESSION['reports']['agent_tracking']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_tracking']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_tracking']['url_data'] = array('name' => 'Agent Tracking', 'link' => '/?module=reporting&mode=agent_tracking');

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


			$data->search_results = $this->search_query->Fetch_Agent_Tracking_Data($start_date_YYYYMMDD,
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
		$_SESSION['reports']['agent_tracking']['report_data'] = $data;
	}
}

class Agent_Tracking_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Agent Tracking Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Agent_Tracking_Data($date_start, $date_end, $company_id)
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
	upper(co.name_short)        AS company_name,
	concat(lower(ag.name_first), ' ', lower(ag.name_last)) 	AS agent_name,
	sum(act.name_short LIKE 'search_%') as num_action_search,
	sum(act.name_short = 'reactivate') as num_action_reactivate,
	sum(act.name_short = 'react_offer') as num_action_react_offer,
	sum(act.name_short = 'verification_react') as num_verification_react,
	sum(act.name_short = 'verification') as num_verification_non_react,
	sum(act.name_short = 'underwriting_react') as num_underwriting_react,
	sum(act.name_short = 'Underwriting') as num_underwriting_non_react,
	sum(act.name_short = 'Watch') as num_watch,
	sum(act.name_short = 'collections_new') as num_collections_new,
	sum(act.name_short = 'Collections Returned QC') as num_collections_returned_qc,
	sum(act.name_short = 'collections_general') as num_collections_general
from
	agent_action as aa
join agent as ag on (ag.agent_id = aa.agent_id)
join action as act on (act.action_id = aa.action_id)
join company as co on (aa.company_id = co.company_id)
where
	aa.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
AND
	aa.company_id IN ({$company_list})
AND ag.active_status = 'active'
group by aa.agent_id
LIMIT {$max_report_retrieval_rows}
			";

		$st = $this->db->query($query);
		$data = array();

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$row['agent_name'] = ucwords($row['agent_name']);
			$data[$company_name][] = $row;
		}
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
