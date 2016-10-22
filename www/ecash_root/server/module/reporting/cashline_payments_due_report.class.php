<?php

require_once("report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

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
			$this->search_query = new Cashline_Payments_Due_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id'         => $this->request->company_id
			);

			$_SESSION['reports']['cashline_payments_due']['report_data'] = new stdClass();
			$_SESSION['reports']['cashline_payments_due']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['cashline_payments_due']['url_data'] = array('name' => 'Cashline Payments Due', 'link' => '/?module=reporting&mode=cashline_payments_due');


			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$specific_date_YYYYMMDD = 10000 * $data->search_criteria['specific_date_YYYY'] +
			                          100   * $data->search_criteria['specific_date_MM'] +
			                                  $data->search_criteria['specific_date_DD'];

			$data->search_results = $this->search_query->Fetch_Current_Data($specific_date_YYYYMMDD,
										     $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
		if( $data->search_results === 'invalid date' )
		{
			$data->search_message = "Invalid date.  Please select a date no earlier than " . date("m/d/Y", strtotime(Payments_Due_Report_Query::MAX_SAVE_DAYS . " days ago"));
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['cashline_payments_due']['report_data'] = $data;

	}
}

class Cashline_Payments_Due_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "Cashline Payments Due Report Query - New";

	public function Fetch_Current_Data($specific_date, $company_id)
	{
		$this->timer->startTimer(self::TIMER_NAME);

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$ach_event_type_ids = implode(', ', Load_ACH_Event_Types($company_id));

		// For each Application Id
		$query = "
			SELECT
				company.name_short company_name,
				application_status_id,
				acc.account_status cashline_status,
				aps.name ecash_status,
				CONCAT(p.name_last, ', ', p.name_first) customer,
				a.application_id,
				c.cashline_id cashline_id,
				e.work_pay_frequency pay_period,
				t.transaction_amount bal,
				t.transaction_amount +
				CASE
					WHEN
						t.transaction_next_due_date = 0000-00-00 AND
						t.transaction_effective_date = 0000-00-00 AND
						t.transaction_payment_amount = 0 AND
						t.transaction_amount <> 15
					THEN
						(
							SELECT transaction_balance
							FROM cashline_conv.cl_transaction
							WHERE
								transaction_type = 'advance' AND
								customer_id = c.customer_id
							ORDER BY transaction_date DESC
							LIMIT 1
						)

					WHEN (
						SELECT COUNT(*)
						FROM cashline_conv.cl_transaction tsub
						WHERE
							customer_id = c.customer_id AND
							transaction_date >= (
								SELECT MAX(transaction_date)
								FROM cashline_conv.cl_transaction
								WHERE
									transaction_type = 'advance' AND
									customer_id = tsub.customer_id
							) AND
							transaction_type = 'service charge' AND
							transaction_balance = 0
					) >= 4
					THEN 50

					ELSE 0
				END
				amt,
				t.transaction_due_date date,
				(
					SELECT SUM(-amount_principal - amount_non_principal)
					FROM event_schedule
					WHERE
						application_id = a.application_id AND
						date_effective = '{$specific_date}' AND
						event_type_id IN ({$ach_event_type_ids}) AND
						(amount_principal + amount_non_principal) < 0
				) ecash_debit
			FROM
				cashline_conv.cl_customer c
				JOIN company USING (company_id)
				JOIN cashline_conv.cl_employment e USING (employment_id)
				JOIN cashline_conv.cl_personal p USING (personal_id)
				JOIN cashline_conv.cl_transaction t USING (customer_id)
				JOIN cashline_conv.cl_account acc USING (account_id)
				JOIN cl_customer a USING (customer_id)
				JOIN application USING (application_id)
				JOIN application_status aps USING (application_status_id)
			WHERE
				c.company_id IN ({$company_list}) AND
				t.transaction_type IN ('ach return', 'service charge') AND
				t.transaction_due_date = '{$specific_date}'
			ORDER BY
				p.name_last, p.name_first
		";

		// This report should ALWAYS hit the master.

		$st = $this->db->query($query);

		$data = array();
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			$row['module'] = 'loan_servicing';
			$row['mode']   = 'customer_service';
			$row['difference'] = $row['ecash_debit'] - $row['amt'];
			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::TIMER_NAME);

		return $data;
	}
}

?>
