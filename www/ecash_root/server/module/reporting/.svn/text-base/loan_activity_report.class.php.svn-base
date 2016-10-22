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
			$this->search_query = new Loan_Activity_Report_Query($this->server);
	
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
			  'loan_type'       => $this->request->loan_type,
			  'date_type'		=> $this->request->date_type,
			  'transaction_type' => $this->request->transaction_type,
			  'batch_type'       => $this->request->batch_type,
			  'ach_batch_company'       => $this->request->ach_batch_company,
			);
	
			$_SESSION['reports']['loan_activity']['report_data'] = new stdClass();
			$_SESSION['reports']['loan_activity']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['loan_activity']['url_data'] = array('name' => 'Loan Activity', 'link' => '/?module=reporting&mode=loan_activity');
	
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

	
			$data->search_results = $this->search_query->Fetch_Loan_Activity_Data( $start_date_YYYYMMDD,
												$end_date_YYYYMMDD,
				                                                                $this->request->loan_type,
				                                                                $this->request->company_id,
												$this->request->date_type,
												$this->request->transaction_type,
												$this->request->batch_type,
												$this->request->ach_batch_company);
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
		$_SESSION['reports']['loan_activity']['report_data'] = $data;
	}
}

class Loan_Activity_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Loan Activity Report Query";

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
	public function Fetch_Loan_Activity_Data($start_date, $end_date, $loan_type, $company_id, $date_type = "transaction_date", $transaction_type = NULL, $batch_type, $ach_batch_company)
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

		$extra_sql = "";

		// These values come from client/module/reporting/code/display_loan_actvitity_report.class.php
		if ($date_type == "funding_date")
		{
			$extra_sql = "AND app.date_fund_actual BETWEEN '{$start_date}' AND '{$end_date}'";
		}
		else if ($date_type == "transaction_date")
		{
			$extra_sql = "AND tr.date_effective BETWEEN '{$start_date}' AND '{$end_date}'";
		}

		//[#55552] add transaction type filter
		if($transaction_type != 'all')
		{
			$extra_sql .= "AND tt.name_short = '{$transaction_type}'";
		}

		if (empty($batch_type))
		{
			$batch_type_sql = "";
		}
		else
		{
			if ($batch_type == "ach")
			{
				$batch_type_sql = " AND tt.clearing_type = 'ach'\n";
			}
			elseif ($batch_type == "card")
			{
				$batch_type_sql = " AND tt.clearing_type = 'card'\n";
			}
			else
			{
				$batch_type_sql = "";
			}
		}

		if (empty($ach_batch_company))
			$ach_batch_company_sql = "";
		else
			$ach_batch_company_sql = " AND ab.ach_provider_id = '{$ach_batch_company}'\n";

		// GF #12882, Added " Reattempt" to transaction type field if it's a reattempt. [benb]
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
					tr.date_effective                           AS payment_date,
					tr.application_id                           AS application_id,
					tr.ach_id				    AS ach_id,
					app.name_last                               AS last_name,
					app.name_first                              AS first_name,
					app.ssn_last_four			    AS ssn,
					app.fund_actual                             AS original_loan_amount,
					(
						SELECT
							SUM(
								IF
								(
									tr.transaction_register_id IS NULL, 
									es.amount_principal + es.amount_non_principal, 
									tr.amount
								)
							)
						FROM
							event_schedule es
							LEFT JOIN transaction_register tr USING (event_schedule_id)
						WHERE
							es.application_id = app.application_id 
						AND
							tr.transaction_status IN ('complete', 'pending')
					)                                           AS payoff_amount,
					tr.amount                                   AS tran_amount,
					tr.transaction_register_id                  AS trans_id,
					app.date_fund_actual                        AS fund_date,
					IF(
						es.origin_id IS NULL, 
						tt.name, 
						CONCAT(tt.name, ' Reattempt')
					)                                           AS t_type,
					IF(tr.amount < 0, 'Debit', 'Credit')        AS c_or_d,
					tr.transaction_status                       AS status,
					tt.clearing_type,
					apr.name AS ach_provider,
					tr.company_id                               AS company_id,
					app.application_status_id                   AS application_status_id,
					ass.name                                    AS application_status,
					IF(app.is_react = 'yes','React','New')      AS new_vs_react,
					UPPER(co.name_short)                        AS company,
					UPPER(co.name_short)                        AS company_name,
					CONCAT(ag.name_first, ' ', ag.name_last)    AS agent_name
			FROM
					transaction_register tr
			JOIN
					application app ON (app.application_id = tr.application_id)
			JOIN
					company co ON (co.company_id = app.company_id)
			JOIN
					transaction_type tt ON (tt.company_id = tr.company_id AND tt.transaction_type_id = tr.transaction_type_id)
			JOIN
					loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			JOIN
					application_status ass ON (ass.application_status_id = app.application_status_id)
			JOIN
					event_schedule es ON (es.event_schedule_id = tr.event_schedule_id)
			LEFT JOIN
					agent ag ON (ag.agent_id = tr.modifying_agent_id)
			LEFT JOIN
					ach ON (ach.ach_id = tr.ach_id)
			LEFT JOIN
					ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
			LEFT JOIN
					ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
			WHERE
					tr.transaction_status IN ('complete', 'failed')
					{$batch_type_sql}
					{$ach_batch_company_sql}
			AND
					app.company_id IN ({$company_list})
			AND
					lt.name_short IN ({$loan_type_list})
					{$extra_sql}
				";

		$data = array();

		$fetch_result = $this->db->query($query);

		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);

			$this->Get_Module_Mode($row, $row['company_id']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
