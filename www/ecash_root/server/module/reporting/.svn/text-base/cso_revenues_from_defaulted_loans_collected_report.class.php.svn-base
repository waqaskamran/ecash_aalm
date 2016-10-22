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
			$this->search_query = new CSO_Revenues_From_Defaulted_Loans_Collected_Report_Query($this->server);
	
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
			  'loan_type'       => $this->request->loan_type
			);
	
			$_SESSION['reports']['cso_revenues_from_defaulted_loans_collected']['report_data'] = new stdClass();
			$_SESSION['reports']['cso_revenues_from_defaulted_loans_collected']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['cso_revenues_from_defaulted_loans_collected']['url_data'] = array('name' => 'CSO Revenues From Defaulted Loans Collected', 'link' => '/?module=reporting&mode=cso_revenues_from_defaulted_loans_collected');
	
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

	
			$data->search_results = $this->search_query->Fetch_CSO_Revenues_From_Defaulted_Loans_Collected_Data( $start_date_YYYYMMDD,
																				   $end_date_YYYYMMDD,
				                                                                   $this->request->loan_type,
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
		$_SESSION['reports']['cso_revenues_from_defaulted_loans_collected']['report_data'] = $data;
	}
}

class CSO_Revenues_From_Defaulted_Loans_Collected_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "CSO Revenues from Defaulted Loans Collected Report Query";

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
	public function Fetch_CSO_Revenues_From_Defaulted_Loans_Collected_Data($start_date, $end_date, $loan_type, $company_id)
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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(co.name_short)                      AS company_name,
				co.company_id                             AS company_id,
				tr.date_effective                         AS payment_date,
				tr.application_id                         AS application_id,
				app.application_status_id                 AS application_status_id,
				CONCAT(ag.name_last, ', ', ag.name_first) AS agent,
				tt.name                                   AS payment_type,  
				(
					SELECT
						ABS(SUM(amount)) /* Yeah yeah, I'm doing absolute values */
					FROM
						event_amount ea
					WHERE
						ea.transaction_register_id = tr.transaction_register_id
				)                                         AS payment_amount
			FROM
				transaction_register tr
			JOIN
				application app ON (app.application_id = tr.application_id)
			JOIN
				loan_type lt ON (app.loan_type_id = lt.loan_type_id)
			JOIN
				company co ON (co.company_id = app.company_id)
			JOIN
				agent ag ON (ag.agent_id = tr.modifying_agent_id)
			JOIN
				transaction_type tt ON (tt.transaction_type_id = tr.transaction_type_id)
			WHERE
				tr.transaction_status = 'complete'
			AND
				/* Make sure it's a debit */
				(
					SELECT
						SUM(amount)
					FROM
						event_amount ea
					WHERE
						ea.transaction_register_id = tr.transaction_register_id
				) < 0
			AND
				/* They've defaulted */
				(
					SELECT
						COUNT(*)
					FROM
						status_history sh
					JOIN
						application_status ass ON (ass.application_status_id = sh.application_status_id)
					WHERE
						sh.application_id = tr.application_id
					AND
						ass.name = 'Default'
				) > 0
			AND
				tr.date_effective BETWEEN '{$start_date}' AND '{$end_date}'
			AND
				app.company_id IN ({$company_list})
			AND
				lt.name_short IN ({$loan_type_list})
		";

		$data = array();

		$fetch_result = $this->db->query($query);

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];

			$this->Get_Module_Mode($row, $row['company_id']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
