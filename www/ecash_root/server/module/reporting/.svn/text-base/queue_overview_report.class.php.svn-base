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
			$this->search_query = new Queue_Overview_Query($this->server);

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
			  'queue_name'      => $this->request->queue_name,
			);

			$_SESSION['reports']['queue_overview']['report_data'] = new stdClass();
			$_SESSION['reports']['queue_overview']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['queue_overview']['url_data'] = array('name' => 'Queue History Overview', 'link' => '/?module=reporting&mode=queue_overview');

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

			$data->search_results = $this->search_query->Fetch_Queue_Overview_Data( $this->request->queue_name, $start_date_YYYYMMDD,
					$end_date_YYYYMMDD, $this->request->company_id);
		}
		catch (Exception $e)
		{
			echo "<pre>"; print_r($e); die();
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
		$_SESSION['reports']['queue_overview']['report_data'] = $data;
	}
}

class Queue_Overview_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Queue Overview Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Queue Overview Report
	 * @param   string $queue name 'Underwriting (non-react)'
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Queue_Overview_Data($queue_name, $date_start, $date_end, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);
		$db = ECash::getSlaveDb();

		$date_start .= '000000';
		$date_end .= '235959';

		$qm         = ECash::getFactory()->getQueueManager();
		$vqueue     = $qm->getQueue($queue_name);
		$table_name = $vqueue->getQueueEntryTableName();

		//mantis:7034 - added date_removed
		
		$query = "
			(SELECT
				c.company_id 								AS company_id,
				upper(c.name_short)							AS company_name,
				nqh.related_id								AS application_id,
				nq.name										AS queue_name,
				CONCAT(ag.name_first, ' ', ag.name_last) 	AS pulling_agent,
				nqh.date_queued								AS date_created,
				'N/A'										AS date_available,
				'N/A'										AS date_unavailable,
				nqh.date_removed							AS date_removed,
				(UNIX_TIMESTAMP(nqh.date_removed) - UNIX_TIMESTAMP(nqh.date_queued)) AS total_time,
				application_status_id							
			FROM
				n_queue_history nqh
			JOIN
				agent ag ON (nqh.removal_agent_id = ag.agent_id)
			JOIN
				application app ON (nqh.related_id = app.application_id)
			JOIN
				company c ON (app.company_id = c.company_id)
			JOIN
				n_queue nq ON (nqh.queue_id = nq.queue_id)
			WHERE
				nq.name_short = {$db->quote($queue_name)}
			AND
				nqh.date_queued BETWEEN {$date_start} AND {$date_end}
			AND
				c.company_id IN $company_list)
		UNION
			(SELECT
				c.company_id								AS company_id,
				upper(c.name_short)							AS company_name,
				nqe.related_id								AS application_id,
				nq.name										AS queue_name,
				CONCAT(ag.name_first, ' ', ag.name_last)	AS pulling_agent,
				nqe.date_queued								AS date_created,
				nqe.date_available							AS date_available,
				nqe.date_expire								AS date_unavailable,
				'N/A'										AS date_removed,
				(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) AS total_time,
				app.application_status_id
			FROM
				{$table_name} nqe
			JOIN
				agent ag ON (nqe.agent_id = ag.agent_id)
			JOIN
				application app ON (nqe.related_id = app.application_id)
			JOIN
				company c ON (app.company_id = c.company_id)
			JOIN
				n_queue nq ON (nqe.queue_id = nq.queue_id)
			WHERE
				nq.name_short = {$db->quote($queue_name)}
			AND
				nqe.date_queued BETWEEN {$date_start} AND {$date_end}
			AND
				c.company_id IN $company_list)
		";
						
		$st = $db->query($query);

		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($row, $row["company_id"]);
			$data[$row["company_name"]][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
