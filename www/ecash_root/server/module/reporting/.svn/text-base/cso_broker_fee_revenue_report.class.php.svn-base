<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 1.1.2.1 $
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
			$this->search_query = new CSO_Broker_Fee_Revenue_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
              'end_date_MM'   	=> $this->request->end_date_month,
              'end_date_DD'   	=> $this->request->end_date_day,
              'end_date_YYYY' 	=> $this->request->end_date_year,
			  'company_id'      => $this->request->company_id,
			);
	
			$_SESSION['reports']['cso_broker_fee_revenue']['report_data'] = new stdClass();
			$_SESSION['reports']['cso_broker_fee_revenue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['cso_broker_fee_revenue']['url_data'] = array('name' => 'CSO Broker Fee Revenue', 'link' => '/?module=reporting&mode=cso_broker_fee_revenue');
	
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
	
			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;

            // End date
            $end_date_YYYY = $this->request->end_date_year;
            $end_date_MM   = $this->request->end_date_month;
            $end_date_DD   = $this->request->end_date_day;
            if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
            {
                //return with no data
                $data->search_message = "End Date invalid or not specified.";
                ECash::getTransport()->Set_Data($data);
                ECash::getTransport()->Add_Levels("message");
                return;
            }

            $end_date_YYYYMMDD = 10000 * $end_date_YYYY + 100 * $end_date_MM + $end_date_DD;

	
			$data->search_results = $this->search_query->Fetch_CSO_Broker_Fees( $start_date_YYYYMMDD,
																				   $end_date_YYYYMMDD,
				                                                                   $this->request->company_id,
																				   $this->request->date_type);
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
		$num_results = 0;
		foreach ($data->search_results as $company => $results)
		{
			$num_results += count($results);

			if ($num_results > $this->max_display_rows)
			{
				$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}			
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['cso_broker_fee_revenue']['report_data'] = $data;
	}
}

class CSO_Broker_Fee_Revenue_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "CSO Broker Fee Revenue Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Loan Activity Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_CSO_Broker_Fees($start_date, $end_date, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		// Search from the beginning of start date to the end of end date
		$end_date   = "{$end_date}235959";
		$start_date = "{$start_date}000000";

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

		$active_id = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId(
			'active::servicing::customer::*root');
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
SELECT
	UPPER(co.name_short) AS company_name,
    tr.date_effective, 
    ABS(SUM(tr.amount)) as amount
FROM transaction_register tr
JOIN transaction_type tt on (tt.transaction_type_id = tr.transaction_type_id)
JOIN event_amount ea on (ea.transaction_register_id = tr.transaction_register_id)
JOIN event_amount_type eat on (eat.event_amount_type_id = ea.event_amount_type_id)
JOIN application app ON (app.application_id = tr.application_id)
JOIN company co ON (co.company_id = app.company_id)
JOIN loan_type lt ON (app.loan_type_id = lt.loan_type_id)
JOIN status_history AS current_status ON (current_status.application_id = tr.application_id)
WHERE tr.transaction_status = 'complete'
AND tt.clearing_type != 'adjustment'
AND eat.name_short = 'fee'
AND tr.date_effective BETWEEN '{$start_date}' AND '{$end_date}'
AND lt.name_short = 'cso_loan'
AND tr.amount < 0
AND current_status.status_history_id = (
                    SELECT
						status_history_id
					FROM
						status_history
					WHERE status_history.application_id = tr.application_id 
					AND status_history.date_created < tr.date_effective
					ORDER BY
						status_history.date_created DESC
					LIMIT 1
				)
AND current_status.application_status_id = {$active_id}
AND app.company_id IN ({$company_list})
GROUP BY company_name, date_effective
		";

		$data = array();

		$fetch_result = $this->db->query($query);

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>