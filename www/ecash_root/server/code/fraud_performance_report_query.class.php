<?php

require_once(SERVER_CODE_DIR . "base_report_query.class.php");

class Fraud_Performance_Report_Query extends Base_Report_Query
{

	private static $TIMER_NAME = "Fraud Performance Report Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;

		$this->Add_Status_Id('withdrawn', array('withdrawn', 'applicant', '*root'));
		$this->Add_Status_Id('denied',    array('denied',    'applicant', '*root'));
	}

	public function Fetch_Fraud_Performance_Data($date_start, $date_end, $queue_type, $company_id)
	{

		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list 				= $this->Format_Company_IDs($company_id);
		$max_report_retrieval_rows 	= $this->max_display_rows + 1;
		
		$qm = ECash::getFactory()->getQueueManager();
		$queue = $qm->getQueue($queue_type . '_queue');

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$query = "
				SELECT 
					upper(c.name_short) company_name,
					concat(a.name_first, ' ',a.name_last) agent_name,
					IF(qh.num_pulled IS NOT NULL, qh.num_pulled, 0) AS num_pulled,
					IF(lah.num_in_moved IS NOT NULL, lah.num_in_moved, 0) AS num_in_moved,
					IF(lah.num_withdrawn IS NOT NULL, lah.num_withdrawn, 0) AS num_withdrawn,
					IF(lah.num_denied IS NOT NULL, lah.num_denied, 0) AS num_denied
				FROM 
					agent a
				JOIN 
					company c
					
				LEFT JOIN 
					(
						SELECT a.company_id, qh.removal_agent_id, count(*) as num_pulled
						FROM n_queue_history qh
						INNER JOIN application a ON qh.related_id=a.application_id
						WHERE qh.date_removed BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
						AND qh.queue_id={$queue->model->queue_id}
						GROUP BY a.company_id, qh.removal_agent_id
					) qh 
					ON (qh.removal_agent_id=a.agent_id AND qh.company_id=c.company_id)
					
				LEFT JOIN 
					(
		
						SELECT 
							lah.agent_id, 
							COUNT(lah.loan_action_history_id) AS num_in_moved, 
							SUM(IF(sh.application_status_id = {$this->withdrawn}, 1, 0)) AS num_withdrawn,
							SUM(IF(sh.application_status_id = {$this->denied}, 1, 0)) AS num_denied
						FROM 
							loan_action_history lah
							JOIN loan_actions la ON lah.loan_action_id=la.loan_action_id
							LEFT JOIN status_history sh ON
								(
									sh.agent_id=lah.agent_id
									AND sh.application_id=lah.application_id
									AND sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
									AND sh.application_status_id IN ({$this->withdrawn}, {$this->denied})
								)
						WHERE 
							lah.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
							AND la.type=UPPER('{$queue_type}')
						GROUP BY
							lah.agent_id
						
					) lah 
					ON (lah.agent_id=a.agent_id)
				
				WHERE c.company_id IN {$company_list}
					AND qh.num_pulled IS NOT NULL
		        ORDER BY company_name, a.name_first 
		        LIMIT {$max_report_retrieval_rows}
		        ";

		$st = $this->db->query($query);

		if($st->rowCount() == $max_report_retrieval_rows)
		return false;

		$data = array();

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array(Company => array('colname' => 'data'))
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