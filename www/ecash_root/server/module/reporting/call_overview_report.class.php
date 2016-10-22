<?php
/**
 * Display a summary list of calls made by an agent.
 *
 * @package Reporting
 * @subpackage PBX
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// company_id
		//
		try
		{
			$this->search_query = new Call_Overview_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'agent_id'        => $this->request->agent_id
			);

			$_SESSION['reports']['call_overview']['report_data'] = new stdClass();
			$_SESSION['reports']['call_overview']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['call_overview']['url_data'] = array('name' => 'Call Overview', 'link' => '/?module=reporting&mode=call_overview');

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

			$data->search_results = $this->search_query->Fetch_Call_Overview_Report_Data(	$this->request->company_id,
																						$this->request->agent_id,
																						$start_date_YYYYMMDD,
																						$end_date_YYYYMMDD);
		}
		catch (Exception $e)
		{
			ECash::getLog()->Write(var_export($e,true));
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
		$_SESSION['reports']['call_overview']['report_data'] = $data;
	}
}

class Call_Overview_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Call Overview Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Call_Overview_Report_Data($company_id,$agent_id,$date_start, $date_end)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);

		// If they want an affiliated agent
		$agents_selected = FALSE;
		$unassigned_selected = FALSE;
		if(!is_array($agent_id) || 0 == count($agent_id))
		{
			$agent_id = array(0);
		}
		foreach($agent_id as $id)
		{
			if(0 == $id)
			{
				$unassigned_selected = TRUE;
			}
			else
			{
				$agents_selected = TRUE;
			}
		}
		$agent_id_list = join(",",$agent_id);
		$sql_agent_id = ($agents_selected) ? "AND ag.agent_id IN ({$agent_id_list})" : "";

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		// Build a SQL list
		$fetch_query = "
				SELECT

					ph.company_id,
					upper(c.name_short)        AS company_name,
					a.application_status_id    AS application_status_id,
                    ph.date_created,
					UNIX_TIMESTAMP(ph.date_created) as pbx_ts_created,
					(
						SELECT
							UNIX_TIMESTAMP(aa.date_created)
						FROM
							agent_action as aa
						WHERE
							aa.agent_id = ph.agent_id
							AND aa.application_id = ph.application_id
							AND aa.date_created < ph.date_created
						ORDER BY
							aa.date_created DESC
						LIMIT 1
					) 				as pull_date_created,
					concat(
							lower(ag.name_last),
							', ',
							lower(ag.name_first)
					) AS agent_name,
					ph.application_id,
					if(ac.type IS NULL, 'Phone', ac.type) 		as contact_type,
					if(ac.category IS NULL, 'Manual Dial', ac.category) 	as category_type,
					ac.value 		as contact_phone,
                    ph.pbx_event,
					ph.application_contact_id,
					ph.result 		as pbx_result
				FROM
					pbx_history as ph
					join agent as ag on (ag.agent_id = ph.agent_id)
					join company as c on (c.company_id = ph.company_id)
					left join application_contact as ac on (ac.application_contact_id = ph.application_contact_id)
					left join application as a on (a.application_id = ac.application_id)
				WHERE
					ph.pbx_event in ('Dial','CDR Import')
					AND ph.company_id IN {$company_list}
					AND ph.date_created between {$timestamp_start} AND {$timestamp_end}
					{$sql_agent_id}
				ORDER BY
					ph.date_created
			";

//		$this->log->Write($fetch_query);
		//echo "<!-- {$fetch_query} -->";
		$citems = array();

		$st = $this->db->query($fetch_query);

		$callitems = array();
		$data = array();

		for ($calls = array() , $tres = array() ; $row = $st->fetch(PDO::FETCH_ASSOC) ; $prow = $row)
		{
			if (is_array($prow) && $row['application_contact_id'] != $prow['application_contact_id'])
			{
				$calls[] = $tres;
				$tres = array();
			}

			if(!$tres['company_name']) 			$tres['company_name'] = 		$row["company_name"];
			if(!$tres['company_id']) 			$tres['company_id'] = 			$row["company_id"];
			if(!$tres['application_status_id']) $tres['application_status_id'] = $row["application_status_id"];
			if(!$tres['agent_name']) 			$tres['agent_name'] = 			$row["agent_name"];
			if(!$tres['application_id']) 		$tres['application_id'] = 		$row["application_id"];
			if(!$tres['contact_type']) 			$tres['contact_type'] = 		$row["contact_type"];
			if(!$tres['category_type']) 		$tres['category_type'] = 		$row["category_type"];
			if(!$tres['contact_phone']) 		$tres['contact_phone'] = 		$row["contact_phone"];
			if(!$tres['date_created'])			$tres['date_created'] = 		$row["date_created"];
			if(!$tres['CallLatency']) 			$tres["CallLatency"] =			(is_null($row["pull_date_created"])) ? "Unknown" : ($row["pbx_ts_created"] - $row["pull_date_created"]);

			$row['pbx_result'] = unserialize($row['pbx_result']);

			$tres['call_parts'][] = $row;
			$tres['part_freq'][$row['pbx_event']]++;
			
			switch ($row['pbx_event']) 
			{
				case "CDR Import":
					$tres['contact_phone'] = substr($row['pbx_result']["dst"], -10);
					$tres['date_created'] = $row["pbx_result"]["calldate"];
					$tres['CallTime'] = $row['pbx_result']['duration'];
					break;

			}

		}

		foreach ($calls as $row)
		{

			// if there are multiple calls on the same contact id in a row (or no contact id).. split them up
			if ($row['part_freq']['Dial'] > 1 || $row['part_freq']['CDR Import'] > 1 )
			{

				$rproc = array();
				$sub_rows = array();
				foreach ($row['call_parts'] as $trow)
				{

					$current_sub_row = ( $rproc[$trow['pbx_event']]++ - 1 );

					if(!$sub_rows[$current_sub_row]['company_name']) 		$sub_rows[$current_sub_row]['company_name'] = 			$trow["company_name"];
					if(!$sub_rows[$current_sub_row]['company_id']) 			$sub_rows[$current_sub_row]['company_id'] = 			$trow["company_id"];
					if(!$sub_rows[$current_sub_row]['application_status_id']) $sub_rows[$current_sub_row]['application_status_id'] = $trow["application_status_id"];
					if(!$sub_rows[$current_sub_row]['agent_name']) 			$sub_rows[$current_sub_row]['agent_name'] = 			$trow["agent_name"];
					if(!$sub_rows[$current_sub_row]['application_id']) 		$sub_rows[$current_sub_row]['application_id'] = 		$trow["application_id"];
					if(!$sub_rows[$current_sub_row]['contact_type']) 		$sub_rows[$current_sub_row]['contact_type'] = 			$trow["contact_type"];
					if(!$sub_rows[$current_sub_row]['category_type']) 		$sub_rows[$current_sub_row]['category_type'] = 			$trow["category_type"];
					if(!$sub_rows[$current_sub_row]['contact_phone']) 		$sub_rows[$current_sub_row]['contact_phone'] = 			$trow["contact_phone"];
					if(!$sub_rows[$current_sub_row]["CallLatency"])			$sub_rows[$current_sub_row]["CallLatency"] =			(is_null($trow["pull_date_created"])) ? "Unknown" : ($trow["pbx_ts_created"] - $trow["pull_date_created"]);
					if(!$sub_rows[$current_sub_row]['date_created'])		$sub_rows[$current_sub_row]['date_created'] = 			$trow["date_created"];
					
					switch ($trow['pbx_event']) 
					{
						case "CDR Import":				
							$sub_rows[$current_sub_row]['contact_phone'] = substr($trow['pbx_result']["dst"], -10);
							$sub_rows[$current_sub_row]['date_created'] = $trow["pbx_result"]["calldate"];
							$sub_rows[$current_sub_row]['CallTime'] = $trow['pbx_result']['duration'];
							break;

					}
				}

				foreach ($sub_rows as $ttrow)
				{
					if ($ttrow['application_id']) $this->Get_Module_Mode($ttrow);

					if ($ttrow['date_created'] && $ttrow['contact_phone']) $data[$ttrow['company_name']][] = $ttrow;

				}
				
			} 
			else 
			{

				if ($row['application_id']) $this->Get_Module_Mode($row);

				if ($row['date_created'] && $ttrow['contact_phone']) $data[$row['company_name']][] = $row;
			}
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
