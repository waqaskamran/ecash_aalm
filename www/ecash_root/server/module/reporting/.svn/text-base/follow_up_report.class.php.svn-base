<?php

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		try
		{
                        $this->search_query = new Follow_Up_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   		=> $this->request->start_date_month,
			  'start_date_DD'   		=> $this->request->start_date_day,
			  'start_date_YYYY' 		=> $this->request->start_date_year,
			  'end_date_MM'     		=> $this->request->end_date_month,
			  'end_date_DD'     		=> $this->request->end_date_day,
			  'end_date_YYYY'   		=> $this->request->end_date_year,
			  'search_follow_up_by'      	=> $this->request->search_follow_up_by,
			  'company_id'      		=> $this->request->company_id
			);

			$_SESSION['reports']['follow_up']['report_data'] = new stdClass();
			$_SESSION['reports']['follow_up']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['follow_up']['url_data'] = array('name' => 'Follow Up', 'link' => '/?module=reporting&mode=follow_up');

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
			
			$data->search_results = $this->search_query->Fetch_Follow_Up_Data($start_date_YYYYMMDD,
											  $end_date_YYYYMMDD,
											  $this->request->search_follow_up_by,
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
		$_SESSION['reports']['follow_up']['report_data'] = $data;
	}
}


class Follow_Up_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Follow Up Report Query";

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
	public function Fetch_Follow_Up_Data($start_date, $end_date, $search_follow_up_by, $company_id)
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

		//$agent_queue_reason_model = ECash::getFactory()->getReferenceList('AgentQueueReason');
		//$agent_queue_reason_model->loadBy(array('name_short' => 'follow up',));
		//var_dump($agent_queue_reason_model);
		//$agent_queue_reason_id = $agent_queue_reason_model->agent_queue_reason_id; var_dump($agent_queue_reason_id);
		
		$db = ECash::getMasterDb();
		$sql = "
			SELECT agent_queue_reason_id
			FROM n_agent_queue_reason
			WHERE name_short = 'follow up'
		";
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$agent_queue_reason_id = $row->agent_queue_reason_id;

		$sql = "
			SELECT queue_id
			FROM n_queue
			WHERE company_id IN ({$company_list})
			AND name_short = 'follow_up'
		";
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$follow_up_queue_id = $row->queue_id;

		$sql = "
		  	SELECT queue_id
			FROM n_queue
			WHERE company_id IN ({$company_list})
			AND name_short = 'Agent'
		";
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$my_queue_id = $row->queue_id;
		
		$fetch_query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
					app.company_id,
					UPPER(c.name_short) AS 'company_name',
                                        CONCAT(ag.name_last,' ,', ag.name_first) AS 'originator',
					
					(CASE 
					WHEN mq.queue_entry_id IS NOT NULL THEN CONCAT(ag1.name_last,' ,', ag1.name_first)
					WHEN qe.queue_entry_id IS NOT NULL THEN 'Follow Up Queue'
					WHEN qh.queue_entry_id IS NOT NULL THEN CONCAT(ag2.name_last,' ,', ag2.name_first)
					WHEN qh1.queue_entry_id IS NOT NULL THEN 'Follow Up Queue'
					ELSE 'N/A'
					END) AS 'sent_to',

					CONCAT(ag3.name_last,' ,', ag3.name_first) AS 'removal_agent',
					app.application_id,
					app.application_status_id,
					aps.name AS 'application_status',
					CONCAT(app.name_last, ', ', app.name_first) AS 'customer_name',
					com.comment AS 'comment',
					fu.category,
					fut.name AS 'follow_up_type',
					fu.date_created AS 'created_on',
					fu.follow_up_time,
					fu.status AS 'follow_up_status'
				FROM
					application AS app
				JOIN
					company c ON (c.company_id = app.company_id)
				JOIN
					application_status AS aps ON (aps.application_status_id = app.application_status_id)
				JOIN
					follow_up AS fu ON (fu.company_id = app.company_id
								AND fu.application_id = app.application_id)
				JOIN
					follow_up_type AS fut ON (fut.follow_up_type_id = fu.follow_up_type_id)
                                LEFT JOIN
                                       agent AS ag ON (ag.agent_id = fu.agent_id)
				LEFT JOIN
					comment AS com ON (com.company_id = fu.company_id
								AND com.application_id = fu.application_id
								AND com.comment_id = fu.comment_id)
				
				-- if currently in my queue
				LEFT JOIN
					n_agent_queue_entry AS mq ON (mq.related_id = fu.application_id
									AND mq.agent_queue_reason_id = {$agent_queue_reason_id}
									AND mq.date_available = fu.follow_up_time)
				LEFT JOIN
					agent AS ag1 ON (ag1.agent_id = mq.agent_id)

				-- if currently in follow up queue
				LEFT JOIN
					n_time_sensitive_queue_entry AS qe ON (qe.related_id = fu.application_id
										AND qe.queue_id = {$follow_up_queue_id}
										AND qe.date_available = fu.follow_up_time)
				
				-- queue history my queue
				LEFT JOIN
					n_queue_history AS qh ON (qh.related_id = fu.application_id
					                          AND qh.queue_id = {$my_queue_id}
								  AND qh.date_queued = fu.follow_up_time)
				LEFT JOIN
					agent AS ag2 ON (ag2.agent_id = qh.original_agent_id)

				
				-- queue history follow up queue
				LEFT JOIN
					n_queue_history AS qh1 ON (qh1.related_id = fu.application_id
								AND qh1.queue_id = {$follow_up_queue_id}
								AND qh1.date_queued = fu.follow_up_time)
				LEFT JOIN
					agent AS ag3 ON (ag3.agent_id = qh1.removal_agent_id)
				WHERE
					app.company_id IN ({$company_list})
				AND
					fu.{$search_follow_up_by} BETWEEN {$start_date} AND {$end_date}
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
