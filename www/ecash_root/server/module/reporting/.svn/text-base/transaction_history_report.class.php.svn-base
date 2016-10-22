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
			$this->search_query = new Transaction_History_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   		=> $this->request->start_date_month,
			  'start_date_DD'   		=> $this->request->start_date_day,
			  'start_date_YYYY' 		=> $this->request->start_date_year,
			  'end_date_MM'     		=> $this->request->end_date_month,
			  'end_date_DD'     		=> $this->request->end_date_day,
			  'end_date_YYYY'   		=> $this->request->end_date_year,
			  'company_id'      		=> $this->request->company_id,
			  'loan_type'       		=> $this->request->loan_type,
			  'date_search_by'		=> $this->request->date_search_by,
			  'batch_type'       => $this->request->batch_type,
			  'ach_batch_company'       => $this->request->ach_batch_company,
			);

			$_SESSION['reports']['transaction_history']['report_data'] = new stdClass();
			$_SESSION['reports']['transaction_history']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['transaction_history']['url_data'] = array('name' => 'Transaction History', 'link' => '/?module=reporting&mode=transaction_history');

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

			$data->search_results = $this->search_query->Fetch_Transaction_History_Data($start_date_YYYYMMDD,
														$end_date_YYYYMMDD,
				                                                                              	$this->request->loan_type,
														$this->request->date_search_by,
				                                                                              	$this->request->company_id,
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
		$_SESSION['reports']['transaction_history']['report_data'] = $data;
	}
}

class Transaction_History_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Transaction History Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Transaction History Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Transaction_History_Data($start_date, $end_date, $loan_type, $date_search_by, $company_id, $batch_type, $ach_batch_company)
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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		
		if ($date_search_by == "application_date")
		{
			$date_clause = "
				AND app.date_created
			";
		}
		elseif ($date_search_by == "fund_date")
		{
			$date_clause = "
				AND app.date_fund_actual
			";
		}
		elseif ($date_search_by == "due_date")
		{
			$date_clause = "
				AND tr.date_effective
			";
		}
		elseif ($date_search_by == "return_date")
		{
			/*
			$date_clause = "
                                AND tr.transaction_status = 'failed'
                                AND tr.date_modified
                        ";
			*/
			$date_clause = "
				AND tr.transaction_status = 'failed'
				GROUP BY tr.transaction_register_id
				HAVING return_date
			";
		}
		else
		{
			$date_clause = "
				AND tr.date_effective
			";
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
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
            		SELECT
				app.application_id,
				app.date_created AS app_date_created,
				UPPER(app.name_last) AS name_last,
				UPPER(app.name_first) AS name_first,
				app.income_monthly,
				app.income_source,
				app.income_frequency,
				app.bank_aba,
				app.bank_account,
				app.bank_account_type,
				IF(app.is_react = 'yes', 'React', 'New') AS new_react,
				(SELECT COUNT(DISTINCT ia.application_id)
				FROM application ia
				JOIN application_status alf ON (alf.application_status_id = ia.application_status_id)
				WHERE ia.ssn = app.ssn
				-- AND ia.application_id != app.application_id
				AND alf.name_short = 'paid'
				-- AND ia.date_created < app.date_created
				) AS 'loans_repaid',
				app.date_fund_actual,
				aps.name AS app_status,
				app.fund_actual,

				(SELECT SUM(ea1.amount)
				FROM transaction_register AS tr1
				JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
				JOIN event_amount_type eat1 USING (event_amount_type_id)
				WHERE ea1.application_id = app.application_id
				AND eat1.name_short <> 'irrecoverable'
				AND tr1.transaction_status = 'complete') AS balance,
				
				-- payment_sequence,
				(CASE

				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 0,1
				) = tr.date_effective) THEN '1'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 1,1
				) = tr.date_effective) THEN '2'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 2,1
				) = tr.date_effective) THEN '3'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 3,1
				) = tr.date_effective) THEN '4'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 4,1
				) = tr.date_effective) THEN '5'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 5,1
				) = tr.date_effective) THEN '6'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 6,1
				) = tr.date_effective) THEN '7'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 7,1
				) = tr.date_effective) THEN '8'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 8,1
				) = tr.date_effective) THEN '9'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 9,1
				) = tr.date_effective) THEN '10'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 10,1
				) = tr.date_effective) THEN '11'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 11,1
				) = tr.date_effective) THEN '12'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 12,1
				) = tr.date_effective) THEN '13'
				
				WHEN (
				(SELECT tr1.date_effective
				FROM event_schedule AS es1
				JOIN transaction_register AS tr1 ON (tr1.event_schedule_id = es1.event_schedule_id)
				JOIN transaction_type AS tt1 ON (tt1.company_id = tr1.company_id AND tt1.transaction_type_id = tr1.transaction_type_id)
				WHERE tr1.company_id = app.company_id
				AND tr1.application_id = app.application_id
				AND
					(
						(tt1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				AND tr1.amount < 0
				GROUP BY tr1.date_effective
				ORDER BY tr1.date_effective
				LIMIT 13,1
				) = tr.date_effective) THEN '14'

				ELSE NULL
				END) AS payment_sequence,
				
                    		tr.date_effective,
				-- DAYOFWEEK(tr.date_effective) AS day_of_week,
				DAYNAME(tr.date_effective) AS day_of_week,
				(SELECT SUM(ea1.amount)
				FROM transaction_register AS tr1
				JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
				JOIN event_amount_type eat1 USING (event_amount_type_id)
				WHERE ea1.application_id = app.application_id
				AND eat1.name_short <> 'irrecoverable'
				AND tr1.transaction_status = 'complete'
				AND tr1.date_created < tr.date_created) AS balance_txn,
				(SELECT aps1.name
				FROM status_history AS sh1
				JOIN application_status AS aps1 ON (aps1.application_status_id = sh1.application_status_id)
				WHERE sh1.application_id = app.application_id
				AND sh1.date_created < tr.date_created
				ORDER BY sh1.status_history_id DESC LIMIT 1
				) AS app_status_txn,
				tt.name AS transaction_type_name,
				tt.clearing_type,
				apr.name AS ach_provider,
				tr.amount,
				IF (tr.amount < 0, 'Debit', 'Credit') debit_credit,
				tr.transaction_status,
				-- CONCAT(ag.name_first, ' ', ag.name_last) AS agent_name,
				(SELECT CONCAT(ag1.name_first, ' ', ag1.name_last)
				FROM transaction_history AS th1
				JOIN agent AS ag1 ON (ag1.agent_id = th1.agent_id)
				WHERE th1.application_id = app.application_id
				AND th1.transaction_register_id = tr.transaction_register_id
				ORDER BY th1.transaction_history_id DESC LIMIT 1) AS agent_name,
				IF (tr.transaction_status = 'failed',
				(SELECT th1.date_created
				FROM transaction_history AS th1
				WHERE th1.application_id = app.application_id
				AND th1.transaction_register_id = tr.transaction_register_id
				AND th1.status_after = 'failed'
				ORDER BY th1.transaction_history_id DESC LIMIT 1)
				, NULL
				) AS return_date,
				(CASE 
				WHEN tt.clearing_type = 'ach' AND tr.transaction_status = 'failed' THEN arc.name_short
				WHEN tt.clearing_type = 'card' AND tr.transaction_status = 'failed' THEN cpr.response_text 
				ELSE NULL
				END) AS return_code,
				(CASE 
				WHEN tt.clearing_type = 'ach' AND tr.transaction_status = 'failed' THEN arc.name
				WHEN tt.clearing_type = 'card' AND tr.transaction_status = 'failed' THEN cpr.reason_text
				ELSE NULL
				END) AS return_description,
				IF(es.origin_id IS NOT NULL AND es.context = 'reattempt', 'Yes', 'No') AS reattempt,
				(CASE 
				WHEN (tr1.transaction_register_id IS NOT NULL AND tr2.transaction_register_id IS NULL) THEN '1'
				WHEN (tr2.transaction_register_id IS NOT NULL AND tr3.transaction_register_id IS NULL) THEN '2'
				WHEN (tr3.transaction_register_id IS NOT NULL AND tr4.transaction_register_id IS NULL) THEN '3'
				WHEN (tr4.transaction_register_id IS NOT NULL AND tr5.transaction_register_id IS NULL) THEN '4'
				WHEN (tr5.transaction_register_id IS NOT NULL AND tr6.transaction_register_id IS NULL) THEN '5'
				WHEN (tr6.transaction_register_id IS NOT NULL AND tr7.transaction_register_id IS NULL) THEN '6'
				WHEN (tr7.transaction_register_id IS NOT NULL AND tr8.transaction_register_id IS NULL) THEN '7'
				WHEN (tr8.transaction_register_id IS NOT NULL AND tr9.transaction_register_id IS NULL) THEN '8'
				WHEN (tr9.transaction_register_id IS NOT NULL AND tr10.transaction_register_id IS NULL) THEN '9'
				ELSE NULL
				END) AS reattempt_number,
				es.event_schedule_id,
                    		tr.transaction_register_id,
				IFNULL(tr.ach_id, tr.card_process_id) AS ach_card_id,
				(SELECT b.name
				FROM bureau_inquiry AS bi
				JOIN bureau AS b ON (b.bureau_id = bi.bureau_id)
				WHERE bi.application_id = app.application_id
				ORDER BY bi.bureau_inquiry_id ASC LIMIT 1
				) AS bureau_name,
				(SELECT bi.inquiry_type
				FROM bureau_inquiry AS bi
				WHERE bi.application_id = app.application_id
				ORDER BY bi.bureau_inquiry_id ASC LIMIT 1
				) AS inquiry_type,
				ci.campaign_name,
				ci.promo_id,
				ci.promo_sub_code,
				s.name AS source_url,
                    		UPPER(c.name_short) AS company_name,
                    		tr.company_id AS company_id,
                    		app.application_status_id
			FROM
				application AS app
			JOIN
				application_status AS aps ON (aps.application_status_id = app.application_status_id)
			JOIN
				event_schedule AS es ON (es.company_id = app.company_id
								AND es.application_id = app.application_id)
			JOIN
				transaction_register AS tr ON (tr.company_id = app.company_id
								AND tr.application_id = app.application_id
								AND tr.event_schedule_id = es.event_schedule_id)
			JOIN
				transaction_type AS tt ON (tt.company_id = tr.company_id
								AND tt.transaction_type_id = tr.transaction_type_id)
			JOIN
				company AS c ON (c.company_id = tr.company_id)
			-- JOIN
			--	agent AS ag ON (ag.agent_id = tr.modifying_agent_id)
			-- Returns
			LEFT JOIN ach ON (ach.ach_id = tr.ach_id)
			LEFT JOIN ach_return_code AS arc ON (arc.ach_return_code_id = ach.ach_return_code_id)
			LEFT JOIN card_process AS cp ON (cp.card_process_id = tr.card_process_id)
			LEFT JOIN card_process_response AS cpr ON (cpr.reason_code = cp.reason_code)
			LEFT JOIN ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
			LEFT JOIN ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
			-- Reattempts
			LEFT JOIN transaction_register AS tr1 ON (tr1.transaction_register_id = es.origin_id AND es.context = 'reattempt')
			LEFT JOIN event_schedule AS es1 ON (es1.event_schedule_id = tr1.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr2 ON (tr2.transaction_register_id = es1.origin_id AND es1.context = 'reattempt')
			LEFT JOIN event_schedule AS es2 ON (es2.event_schedule_id = tr2.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr3 ON (tr3.transaction_register_id = es2.origin_id AND es2.context = 'reattempt')
			LEFT JOIN event_schedule AS es3 ON (es3.event_schedule_id = tr3.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr4 ON (tr4.transaction_register_id = es3.origin_id AND es3.context = 'reattempt')
			LEFT JOIN event_schedule AS es4 ON (es4.event_schedule_id = tr4.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr5 ON (tr5.transaction_register_id = es4.origin_id AND es4.context = 'reattempt')
			LEFT JOIN event_schedule AS es5 ON (es5.event_schedule_id = tr5.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr6 ON (tr6.transaction_register_id = es5.origin_id AND es5.context = 'reattempt')
			LEFT JOIN event_schedule AS es6 ON (es6.event_schedule_id = tr6.event_schedule_id)

			LEFT JOIN transaction_register AS tr7 ON (tr7.transaction_register_id = es6.origin_id AND es6.context = 'reattempt')
			LEFT JOIN event_schedule AS es7 ON (es7.event_schedule_id = tr7.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr8 ON (tr8.transaction_register_id = es7.origin_id AND es7.context = 'reattempt')
			LEFT JOIN event_schedule AS es8 ON (es8.event_schedule_id = tr8.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr9 ON (tr9.transaction_register_id = es8.origin_id AND es8.context = 'reattempt')
			LEFT JOIN event_schedule AS es9 ON (es9.event_schedule_id = tr9.event_schedule_id)
			
			LEFT JOIN transaction_register AS tr10 ON (tr10.transaction_register_id = es9.origin_id AND es9.context = 'reattempt')
			LEFT JOIN event_schedule AS es10 ON (es10.event_schedule_id = tr10.event_schedule_id)
			-- Campaign
			LEFT JOIN
				campaign_info AS ci ON (ci.company_id = app.company_id
							AND ci.application_id = app.application_id)
			LEFT JOIN
				site AS s ON (s.site_id = ci.site_id)
            		WHERE
				tr.company_id IN ({$company_list})
				{$batch_type_sql}
				{$ach_batch_company_sql}
            		-- AND
			--	tr.amount < 0
            		AND
				app.loan_type_id in (SELECT loan_type_id
                                     			FROM loan_type
                                     			WHERE name_short IN ({$loan_type_list}))
			{$date_clause} BETWEEN '{$start_date}' AND '{$end_date}'
            		ORDER BY tr.application_id, tr.transaction_register_id
			";

		//$this->log->Write($query);
		//var_dump($query);
		$data = array();

		$st = $this->db->query($query);

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
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
