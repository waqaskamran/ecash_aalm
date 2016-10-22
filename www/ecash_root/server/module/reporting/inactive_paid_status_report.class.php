<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{

		try
		{
			$this->search_query = new Inactive_Paid_Status_Report_Query($this->server);

			// Generate_Report() expects the following from the request form:
			//
			// criteria start_date YYYYMMDD
			// criteria end_date   YYYYMMDD
			// company_id
			//

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

			$_SESSION['reports']['inactive_paid_status']['report_data'] = new stdClass();
			$_SESSION['reports']['inactive_paid_status']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['inactive_paid_status']['url_data'] = array('name' => 'Inactive Paid Status', 'link' => '/?module=reporting&mode=inactive_paid_status');

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

			$data->search_results = $this->search_query->Fetch_Inactive_Paid_Data( $start_date_YYYYMMDD,
			                                                                  $end_date_YYYYMMDD,
			                                                  	               $this->request->company_id,
																				$this->request->loan_type
			);
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
		$_SESSION['reports'][$this->report_name]['report_data'] = $data;
	}
}

class Inactive_Paid_Status_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Inactive Paid Status Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Inactive Paid Status Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   int  $company_id company_id
	 * @returns array
	 */
	public function Fetch_Inactive_Paid_Data($start_date, $end_date, $company_id, $loan_type)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$FILE = __FILE__;
		$METHOD = __METHOD__;
		$LINE = __LINE__;

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
			
		$disallowed_statuses_array = array(
			"pending::external_collections::*root",
			"recovered::external_collections::*root",
			"sent::external_collections::*root",
			"indef_dequeue::collections::customer::*root",
			"new::collections::customer::*root",
			"active::servicing::customer::*root",
			"approved::servicing::customer::*root",
			"past_due::servicing::customer::*root",
			"arrangements_failed::arrangements::collections::customer::*root",
			"current::arrangements::collections::customer::*root",
			"hold::arrangements::collections::customer::*root",
			"unverified::bankruptcy::collections::customer::*root",
			"verified::bankruptcy::collections::customer::*root",
			"dequeued::contact::collections::customer::*root",
			"follow_up::contact::collections::customer::*root",
			"queued::contact::collections::customer::*root",
			"ready::quickcheck::collections::customer::*root",
			"sent::quickcheck::collections::customer::*root",
		);

		$status_map = Fetch_Status_Map();
		$inactive_paid_status = Search_Status_Map('paid::customer::*root', $status_map);

		foreach ($disallowed_statuses_array as $status) {
			$disallowed_status_ids_array[] = Search_Status_Map($status, $status_map);
		}

		$disallowed_status_ids = implode (',', $disallowed_status_ids_array);

		$query = <<<END_SQL
-- eCash 3.0, File: $FILE , Method: $METHOD, Line: $LINE
SELECT
		UPPER(co.name_short) as name_short, a.application_status_id, a.company_id,
		a.application_id, a.name_last, a.name_first, a.ssn,
		a.date_application_status_set, a.fund_actual, lt.name_short as loan_type
	FROM
		application a
		LEFT JOIN application a2 USING (ssn)
		LEFT JOIN loan_type lt ON (lt.loan_type_id = a.loan_type_id)
		LEFT JOIN company co ON a.company_id = co.company_id
	WHERE
		a.application_status_id = $inactive_paid_status
		AND a.company_id in ({$company_list})
		AND a.date_application_status_set BETWEEN {$start_date}000000 AND {$end_date}235959
		AND a.application_status_id NOT IN (
			$disallowed_status_ids
		) AND
		a.application_id = (
			SELECT application_id
				FROM application a3
				WHERE application_id = a.application_id
				ORDER BY a.date_application_status_set
				LIMIT 1
		)
		{$loan_type_sql}
	GROUP BY a.ssn
	ORDER BY a.date_application_status_set
END_SQL;

		$db = ECash::getSlaveDb();
		$st = $db->query($query);

		$order = 1;
		$data = array();
		while( $row = $st->fetch(PDO::FETCH_ASSOC))
		{
			if (!isset($data[$row['name_short']])) {
				$data[$row['name_short']] = array();
			}
			$this->Get_Module_Mode($row);
			$data[$row['name_short']][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
