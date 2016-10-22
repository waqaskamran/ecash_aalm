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
			$this->search_query = new Collections_Performance_Report_Query($this->server);

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

			$_SESSION['reports']['collections_performance']['report_data'] = new stdClass();
			$_SESSION['reports']['collections_performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['collections_performance']['url_data'] = array('name' => 'Collection Agent Action', 'link' => '/?module=reporting&mode=collections_performance');

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

			$data->search_results = $this->search_query->Fetch_Collections_Performance_Data($start_date_YYYYMMDD,
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
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['collections_performance']['report_data'] = $data;
	}
}

class Collections_Performance_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Collections Performance Report Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;
	}

	public function Fetch_Collections_Performance_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'$company_id'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		// GF #12425: Rewrote this hellacious query as something that doesn't
		// cause DB servers to choke and die. Removed lower()/upper() because 
		// it's not used here (there's a business rule that's evaluated later). [benb]

		// THIS WILL NEED EVALUATED BEFORE QUICKCHECKS ARE USED (info does not exist, so I guessed) [benb]

		// Start with the agent actions table for queue pulls
		// This query appears to be using indexes correctly
		$query = "
			SELECT
				UPPER(c.name_short)                                     AS company_name,
				CONCAT(a.name_first,
						' ',
						a.name_last)                                    AS agent_name,
				a.active_status                                         AS agent_flag,
				SUM(IF(act.name='collections_new',1,0))                 AS num_collections_new,
				SUM(IF(act.name='collections_general',1,0))             AS num_collections_general,
				SUM(IF(act.name_short='collections_rework',1,0))        AS num_collections_rework,
				SUM(IF(act.name_short = 'Collections Returned QC',1,0)) AS num_collections_returned_qc,
				SUM(IF(act.name_short like 'search_collections%', 1, 0))    AS num_search_collections
			FROM
				agent_action aact
			JOIN
				agent a ON (a.agent_id = aact.agent_id)
			JOIN
				action act ON (act.action_id = aact.action_id)
			JOIN
				application app ON (app.application_id = aact.application_id)
			JOIN
				company c ON (c.company_id = app.company_id)
			WHERE
				aact.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			AND	
				app.company_id IN ({$company_list})
			GROUP BY
				aact.company_id, aact.agent_id
		";

		$st = $this->db->query($query);

		$agents = array();

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$cname = $row['company_name'];
			$aname = $row['agent_name'];

			// Don't make entries for people who have no stats
			if ($row['num_collections_new'] + $row['num_collections_general'] + $row['num_collections_returned_qc'] + $row['num_collections_rework'] + $row['num_search_collections'])
			{
				$agents[$cname][$aname]['num_collections_new']         = $row['num_collections_new'];
				$agents[$cname][$aname]['num_collections_general']     = $row['num_collections_general'];
				$agents[$cname][$aname]['num_collections_rework']      = $row['num_collections_rework'];
				$agents[$cname][$aname]['num_collections_returned_qc'] = $row['num_collections_returned_qc'];
				$agents[$cname][$aname]['num_search_collections']      = $row['num_search_collections'];
				$agents[$cname][$aname]['agent_flag']                  = $row['agent_flag'];
			}
		}

		// Now this query gets status changes
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(c.name_short)                               AS company_name,
				CONCAT(a.name_first,
						' ',
						a.name_last)                              AS agent_name,
				a.active_status                                   AS agent_flag,
				SUM(IF(ass.name='Made Arrangements',1,0))         AS num_arrangements,
				SUM(IF(ass.name='Bankruptcy Notification',1,0))   AS num_uvbankruptcy,
				SUM(IF(ass.name='Bankruptcy Verified',1,0))       AS num_vbankruptcy,
				SUM(IF(ass.name='Contact Followup', 1, 0))        AS num_follow_up,
				SUM(IF(ass.name='QC Ready', 1, 0))                AS num_qc_ready
			FROM
				status_history sh
			JOIN
				agent a ON (a.agent_id = sh.agent_id)
			JOIN
				application_status ass ON (ass.application_status_id = sh.application_status_id)
			JOIN
				application app ON (app.application_id = sh.application_id)
			JOIN
				company c ON (c.company_id = app.company_id)
			WHERE
				sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			AND
				app.company_id IN ({$company_list})
			GROUP BY
				sh.company_id, sh.agent_id
		";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$cname = $row['company_name'];
			$aname = $row['agent_name'];

			// Don't make entries for people who have no stats
			if ($row['num_arrangements'] + $row['num_uvbankruptcy'] + $row['num_vbankruptcy'] + $row['num_follow_up'] + $row['num_qc_ready'])
			{
				$agents[$cname][$aname]['num_arrangements'] = $row['num_arrangements'];
				$agents[$cname][$aname]['num_uvbankruptcy'] = $row['num_uvbankruptcy'];
				$agents[$cname][$aname]['num_vbankruptcy']  = $row['num_vbankruptcy'];
				$agents[$cname][$aname]['num_follow_up']    = $row['num_follow_up'];
				$agents[$cname][$aname]['num_qc_ready']     = $row['num_qc_ready'];
				$agents[$cname][$aname]['agent_flag']       = $row['agent_flag'];
			}
		}

		// Now this query gets agent affiliations
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(c.name_short)                               AS company_name,
				CONCAT(a.name_first,
						' ',
						a.name_last)                              AS agent_name,
				a.active_status                                   AS agent_flag,
				COUNT(DISTINCT aaf.application_id)                AS num_affiliation
			FROM
				agent_affiliation aaf
			JOIN
				agent a ON (a.agent_id = aaf.agent_id)
			JOIN
				application app ON (app.application_id = aaf.application_id)
			JOIN
				company c ON (c.company_id = app.company_id)
			WHERE
				aaf.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			AND
				aaf.company_id IN ({$company_list})
			AND
				aaf.affiliation_area = 'collections'
			AND     
				aaf.affiliation_type = 'owner'
			GROUP BY
				aaf.company_id, aaf.agent_id
		";

		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$cname = $row['company_name'];
			$aname = $row['agent_name'];

			// Don't make entries for people who have no stats
			if ($row['num_affiliation'])
			{
				$agents[$cname][$aname]['num_affiliation'] = $row['num_affiliation'];
				$agents[$cname][$aname]['agent_flag']      = $row['agent_flag'];
			}
		}

		$data = array();

		// Now make it ecash format friendly
		foreach  ($agents as $company_name => $company_data)
		{
			foreach ($company_data as $agent_name => $agent_data)
			{
				$agent_data['agent_name']   = $agent_name;
				$agent_data['company_name'] = $company_name;

				// Make the num_collections_count = num_collections_new + num_collections_general + num_collections_returned_qc
				$agent_data['num_collections_count'] =    $agent_data['num_collections_new'] 
														+ $agent_data['num_collections_general']
														+ $agent_data['num_collections_returned_qc'];
		
				$data[$company_name][] = $agent_data;
			}
		}	

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}
}

?>
