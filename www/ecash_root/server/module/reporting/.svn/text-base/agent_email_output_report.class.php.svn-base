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

	public function Generate_Report() {
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//

		try {
			$this->search_query = new Agent_Email_Output_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
				'start_date_MM' => $this->request->start_date_month,
				'start_date_DD' => $this->request->start_date_day,
				'start_date_YYYY' => $this->request->start_date_year,
				'end_date_MM' => $this->request->end_date_month,
				'end_date_DD' => $this->request->end_date_day,
				'end_date_YYYY' => $this->request->end_date_year,
				'company_id' => $this->request->company_id,
				'loan_type' => $this->request->loan_type,
				'agent' => $this->request->loan_type
			);

			$_SESSION['reports']['agent_email_output']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_email_output']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_email_output']['url_data'] = array('name' => 'Outgoing Email', 'link' => '/?module=reporting&mode=reporting&mode=agent_email_output');

			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM = $this->request->start_date_month;
			$start_date_DD = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY)) {
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
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY)) {
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD = 10000 * $end_date_YYYY + 100 * $end_date_MM + $end_date_DD;

			if($end_date_YYYYMMDD < $start_date_YYYYMMDD) {
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
			$start_date = $start_date_YYYY.'-'.$start_date_MM.'-'.$start_date_DD;
			$end_date = $end_date_YYYY.'-'.$end_date_MM.'-'.$end_date_DD;

			$data->search_results = $this->search_query->Fetch_Agent_Email_Output_Data($start_date,
				$end_date,
				$this->request->company_id,
				$this->request->loan_type);
		}
		catch (Exception $e) {
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		} 
		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows) {
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}


		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['agent_email_output']['report_data'] = $data;
	}
}

class Agent_Email_Output_Report_Query extends Base_Report_Query {
	private static $TIMER_NAME = "Incoming Email Report Query";

	public function __construct(Server $server) {
		parent::__construct($server);
	}

	public function Fetch_Agent_Email_Output_Data($date_start, $date_end, $company_id, $loan_type) {
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

		// I hate this
		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0) {
			$auth_company_ids = $_SESSION['auth_company']['id'];
		} else {
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 ) $company_list = "'{$company_id}'";
		else $company_list = "'" . implode("','", $auth_company_ids) . "'";

		if ($loan_type == 'all') $loan_type_sql = "";
		else $loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . ' 00:00:00';
		$timestamp_end	 = $date_end   . ' 23:59:59';

		// Construct dynamic column names
		$doc_id_list = array();
		$column_names = array('company_name' => 'Company','agent' => 'Agent','sent' => 'Sent');
		$sort_columns = array('agent', 'sent');
		$column_totals = array('sent' => 1);
		$column_format = array('sent' => 'number');

		$query = "
			SELECT DISTINCT dl.name, dl.document_list_id AS doc_id
			FROM document_list dl
				JOIN document doc USING (document_list_id)
				LEFT JOIN company co ON (doc.company_id = co.company_id)
			WHERE
				doc.document_event_type = 'sent'
				AND doc.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			order by name
		";

		$st = $this->db->query($query);
			
		while($row = $st->fetch(PDO::FETCH_ASSOC)) {
			$doc_col = 'doc_'.$row['doc_id'];
			$doc_id_list [] = $row['doc_id'];
			$column_names [$doc_col] =  $row['name'];
			$sort_columns [] = $doc_col;
			$column_totals [$doc_col] = 1;
			$column_format [$doc_col] = 'number';
		}
		
		// now gather the data
		$query = "
			SELECT 
				IF(ISNULL(co.name_short), 'MLS',UPPER(co.name_short)) AS company_name,
				IF(ISNULL(co.company_id), 1,co.company_id) AS company_id,
				CONCAT(IF(ag.agent_id < 5 OR ag.system_id != 3, '*',''),ag.name_first,' ',ag.name_last,IF(ag.agent_id < 5 OR ag.system_id != 3, '*','')) as agent,
				dl.name as doc_name,
				dl.document_list_id as doc_id,
				count(doc.document_id) as doc_count
			FROM
				document doc
				JOIN document_list dl USING (document_list_id)
				JOIN agent ag USING (agent_id)
				LEFT JOIN company co ON (doc.company_id = co.company_id)
			WHERE
				doc.document_event_type = 'sent'
				AND doc.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND (co.company_id IN ({$company_list}) OR ISNULL(co.company_id))
				/* Conditional loan type detection */
				${loan_type_sql}
			GROUP BY agent, doc_name
			ORDER BY agent, doc_name
		";
		$st = $this->db->query($query);
			
		$data = array();
		$initial = true;
		while($row = $st->fetch(PDO::FETCH_ASSOC)) {
                        if ($initial || ($cur_agent != $row['agent'])) {
			        $reset = true;
				if (!$initial) {
                        		while(($doc_id_list[$doc_loop] != $row['doc_id']) && ($doc_loop < count($doc_id_list))) {
			                        $docs['doc_'.$doc_id_list[$doc_loop]] = 0;
						$doc_loop ++;
					}
				        $elem['sent'] = $sent;
				        $elem = array_merge($elem, $docs);
					$company_name = $row['company_name'];
                                        $data[$company_name][] = $elem;
                              	}
		              	$initial = false;
			}
			if ($reset) {
				$elem = array();
				$docs = array();
				$elem['company_name'] = $row['company_name']; 
				$elem['company_id'] = $row['company_id']; 
				$elem['agent'] = $row['agent'];
				$cur_agent = $elem['agent'];
				$reset = false;
				$doc_loop = 0;
				$sent = 0;
			}
			while(($doc_id_list[$doc_loop] != $row['doc_id']) && ($doc_loop < count($doc_id_list))) {
				$docs['doc_'.$doc_id_list[$doc_loop]] = 0;
				$doc_loop ++;
			}
			$doc_loop ++;
			$docs['doc_'.$row['doc_id']] = $row['doc_count'];
			$sent += $row['doc_count'];
			
			if ($initial or ($cur_agent != $row['agent'])) {
				$reset = true;
				if ($cur_agent != $row['agent']) {
					$elem['sent'] = $sent;
					$elem = array_merge($elem, $docs);
					$company_name = $row['company_name'];
					$data[$company_name][] = $elem;
				}
				$initial = false;
			}
		}
	        if (! reset) {
			$company_name = $row['company_name'];
		        $data[$company_name][] = $row;
		}
		//error_log(print_r($column_names,true));

		$columns['column_names'] = $column_names;
		$columns['sort_columns'] = $sort_columns;
		$columns['column_totals'] = array('company' => $column_totals, 'grand' => $column_totals);
		$columns['column_format'] = $column_format;
		$data['columns'] = $columns;

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
