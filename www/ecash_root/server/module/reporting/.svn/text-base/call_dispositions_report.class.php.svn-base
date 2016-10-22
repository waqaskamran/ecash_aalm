<?php

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		try
		{
                        $this->search_query = new Call_Dispositions_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   		=> $this->request->start_date_month,
			  'start_date_DD'   		=> $this->request->start_date_day,
			  'start_date_YYYY' 		=> $this->request->start_date_year,
			  'end_date_MM'     		=> $this->request->end_date_month,
			  'end_date_DD'     		=> $this->request->end_date_day,
			  'end_date_YYYY'   		=> $this->request->end_date_year,
			  'comment_flag'      		=> $this->request->comment_flag,
			  'company_id'      		=> $this->request->company_id
			);

			$_SESSION['reports']['call_dispositions']['report_data'] = new stdClass();
			$_SESSION['reports']['call_dispositions']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['call_dispositions']['url_data'] = array('name' => 'Call Dispositions', 'link' => '/?module=reporting&mode=call_dispositions');

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
			
			$data->search_results = $this->search_query->Fetch_Call_Dispositions_Data($start_date_YYYYMMDD,
											  $end_date_YYYYMMDD,
											  $this->request->comment_flag,
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
		$_SESSION['reports']['call_dispositions']['report_data'] = $data;
	}
}


class Call_Dispositions_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Call Dispositions Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Payment Arrangements Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $search_follow_up_by
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @return  array
	 */
	//public function Fetch_Agent_Comments_Data($start_date, $end_date, $search_follow_up_by, $company_id)
	public function Fetch_Call_Dispositions_Data($start_date, $end_date, $comment_flag, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$start_date = $start_date. "000000";
		$end_date   = $end_date. "235959";

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

		if ($comment_flag == "1")
			$comment_flag_query = " AND lah.is_resolved = 1 ";
		else if ($comment_flag == "0")
			$comment_flag_query = " AND lah.is_resolved = 0 ";
		else
			$comment_flag_query = "";
			
		$fetch_query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
					app.company_id,
					UPPER(c.name_short) AS 'company_name',
					app.application_id,
					CONCAT(app.name_last, ', ', app.name_first) AS 'customer_name',
					app.application_status_id,
					aps.name AS 'application_status',
					CONCAT(ag.name_last,' ,', ag.name_first) AS 'agent_name',
					lah.date_created AS 'created_on',
					IF(lah.is_resolved = 1,'Resolved','Unresolved') AS 'comment_flag',
					las.description AS 'call',
					la.description AS 'loan_action'
					
				FROM
					application AS app
				JOIN
					company c ON (c.company_id = app.company_id)
				JOIN
					application_status AS aps ON (aps.application_status_id = app.application_status_id)
				JOIN
					loan_action_history AS lah ON (lah.application_id = app.application_id)
				JOIN
					loan_actions AS la ON (la.loan_action_id = lah.loan_action_id)
				JOIN
					loan_action_section_relation AS lasr ON (lasr.loan_action_id = lah.loan_action_id
										AND lasr.loan_action_section_id = lah.loan_action_section_id)
				JOIN
					loan_action_section AS las ON (las.loan_action_section_id = lasr.loan_action_section_id)
				LEFT JOIN
					agent AS ag ON (ag.agent_id = lah.agent_id)
				WHERE
					app.company_id IN ({$company_list})
                                {$comment_flag_query}
				AND
					lah.date_created BETWEEN {$start_date} AND {$end_date}
				ORDER BY
					app.application_id
		";
		
		$data = array();
		$st = $this->db->query($fetch_query);		
		
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);
						
			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
