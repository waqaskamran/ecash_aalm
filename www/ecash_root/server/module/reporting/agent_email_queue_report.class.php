<?php
/**
 * Display summary information about an agent's email statistics (number received, responded, etc)
 *
 * @package Reporting
 * @subpackage Email
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
			$this->search_query = new Agent_Email_Queue_Report_Query($this->server);

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
			  'agent'       => $this->request->loan_type
			);

			$_SESSION['reports']['agent_email_queue']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_email_queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_email_queue']['url_data'] = array('name' => 'Incomming Email', 'link' => '/?module=reporting&mode=reporting&mode=agent_email_queue');

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
			$end_date_YYYYMMDD = 10000 * $end_date_YYYY + 100 * $end_date_MM + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
			$start_date = $start_date_YYYY.'-'.$start_date_MM.'-'.$start_date_DD;
			$end_date = $end_date_YYYY.'-'.$end_date_MM.'-'.$end_date_DD;

			$data->search_results = $this->search_query->Fetch_Agent_Email_Queue_Data($start_date,
												 $end_date,
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
		$_SESSION['reports']['agent_email_queue']['report_data'] = $data;
	}
}

class Agent_Email_Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Incoming Email Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Agent_Email_Queue_Data($date_start, $date_end, $company_id, $loan_type)
	{
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

		// I hate this
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

		if ($loan_type == 'all')
			$loan_type_sql = "";
		else
			$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . ' 00:00:00';
		$timestamp_end	 = $date_end   . ' 23:59:59';

		$query = "
			SELECT
				IF(ISNULL(co.name_short), 'MLS',UPPER(co.name_short)) AS company_name,
				IF(ISNULL(co.company_id), 1,co.company_id) AS company_id,
				IF(ISNULL(ag.name_first), '-none-', CONCAT(ag.name_first, ' ', ag.name_last)) AS agent,
				SUM(IF(eq.action = 'receive', 1,0)) AS opened,
				SUM(IF(eq.action = 'associate', 1,0)) AS associated,
				SUM(IF(eq.action = 'respond', 1,0)) AS responded,
				SUM(IF(eq.action = 'followup', 2,0))  AS followups,
				SUM(IF(eq.action = 'file', 1,0)) AS filed,
				SUM(IF(eq.action = 'queue', 1,0)) AS queued,
				SUM(IF(eq.action = 'canned', 1,0)) AS canned,
				SUM(IF(eq.action = 'remove', 1,0)) AS removed,
				SUM(IF((ISNULL(eq.action) OR eq.action = 'receive'), 1,0)) AS received,
				tot.total
			FROM document doc
			JOIN (
				SELECT count(document_id) total FROM document doc
				WHERE (doc.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}')
					AND doc.document_event_type = 'received'
			) tot
			LEFT JOIN email_queue_report as eq 
				ON (doc.archive_id = eq.archive_id)
			LEFT JOIN company co 
				ON (eq.company_id = co.company_id)
			LEFT JOIN agent ag 
				ON (eq.agent_id = ag.agent_id)
			LEFT JOIN application app 
				ON (app.application_id = doc.application_id)
			LEFT JOIN loan_type lt 
				ON (lt.loan_type_id = app.loan_type_id)
			WHERE (doc.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}')
				AND doc.document_event_type = 'received'
				AND (co.company_id IN ({$company_list}) OR ISNULL(co.company_id))
				/* Conditional loan type detection */
				${loan_type_sql}
			GROUP BY 
				company_name,agent
		";

		$st = $this->db->query($query);
			
		$data = array();
		while($row = $st->fetch(PDO::FETCH_ASSOC)) {
			if ($row['total'] == 0) {
				$service_level = "0%";
				$reply_rate = "0.00%";
				$file_rate = "0.00%";
			} else {
				$service_level = sprintf("%1.2f",($row['opened']/$row['total'])*100)."%";
				if ($row['opened'] == 0) {
					$reply_rate = "0.00%";
					$file_rate = "0.00%";
				} else {
					$reply_rate = sprintf("%1.2f",($row['responded']/$row['opened'])*100)."%";
					$file_rate = sprintf("%1.2f",($row['filed']/$row['opened'])*100)."%";
				}				
			}
			$row['service_level'] = $service_level;
			$row['reply_rate'] = $reply_rate;
			$row['file_rate'] = $file_rate;

			$company_name = $row['company_name'];
			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
