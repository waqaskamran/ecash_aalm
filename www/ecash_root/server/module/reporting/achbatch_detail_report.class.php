<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");
require_once(SERVER_CODE_DIR . "base_report_query.class.php" );
require_once(LIB_DIR . 'Payment_Card.class.php');

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
			$this->search_query = new ACH_Batch_Detail_Query($this->server);
	
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
			  'clearing_type'   => $this->request->clearing_type,
			  'batch_type'       => $this->request->batch_type,
			  'ach_batch_company'       => $this->request->ach_batch_company
			);
	
			$_SESSION['reports']['achbatch_detail']['report_data'] = new stdClass();
			$_SESSION['reports']['achbatch_detail']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['achbatch_detail']['url_data'] = array('name' => 'Batch', 'link' => '/?module=reporting&mode=achbatch_detail');
	
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

			$data->search_results = $this->search_query->Fetch_ACH_Data($start_date_YYYYMMDD,
											    $end_date_YYYYMMDD,
											    $this->request->company_id,
											    $this->request->batch_type,
											    $this->request->ach_batch_company);
		}
		catch (Exception $e)
		{
			$data->search_message = $e->getMessage();
//			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if( $data->search_results === false )
		{
			$data->search_message = $this->max_display_rows_error;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['achbatch_detail']['report_data'] = $data;
	}
}

class ACH_Batch_Detail_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Batch Detail Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;

	}

	public function Fetch_ACH_Data($date_start, $date_end, $company_id, $batch_type, $ach_batch_company)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$company_list = $this->Format_Company_IDs($company_id);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end   = $date_end   . '235959';
		
		if (empty($ach_batch_company))
			$ach_batch_company_sql = "";
		else
			$ach_batch_company_sql = " AND ab.ach_provider_id = '{$ach_batch_company}'\n";

		$query = "
			SELECT
				ach.ach_batch_id,
				upper(co.name_short) AS company_name,
			DATE_FORMAT(ach.ach_date, '%m/%d/%Y') AS ach_date,
				ach.company_id,
				ach.application_id,
				app.name_first,
				app.name_last,
				app.bank_aba,
				right(app.bank_account, 4) as bank_last4,
				ach.ach_type,
				ach.ach_status,
				ach.amount,
				rc.name_short as return_code,
				DATE_FORMAT(rc.date_created, '%m/%d/%Y') AS return_date,
				app.application_status_id,
				tr1.clearing_type,
				apr.name AS ach_provider
			FROM ach
			JOIN ach_batch ab ON (ab.ach_batch_id = ach.ach_batch_id)
			JOIN ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
			JOIN application app ON (app.application_id = ach.application_id)
			JOIN company co on (co.company_id = app.company_id)
			JOIN (
				SELECT tr.ach_id,
					es.context,
					tt.clearing_type
				FROM transaction_register AS tr
				JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
				JOIN transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
				GROUP BY tr.ach_id
			) AS tr1 ON tr1.ach_id = ach.ach_id
			LEFT JOIN ach_return_code rc on (rc.ach_return_code_id = ach.ach_return_code_id)
			LEFT JOIN ach_report r on (r.ach_report_id = ach.ach_report_id)
			WHERE ach.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
				{$ach_batch_company_sql}
				AND ach.company_id in $company_list	
                ";

		$data = array();

		if (empty($batch_type) || ($batch_type == "ach"))
		{
			$st = $this->db->query($query);
			while($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				$company_name = $row['company_name'];
				unset($row['company_name']);
	
				$this->Get_Module_Mode($row);
	
				$data[$company_name][] = $row;
			}
		}
		
		$query = "
			SELECT
				CONCAT('C',DATE_FORMAT(cp.date_created, '%y%m%d')) AS ach_batch_id,
				upper(co.name_short) AS company_name,
				DATE_FORMAT(cp.date_created, '%m/%d/%Y') AS ach_date,
				app.company_id,
				cp.application_id,
				app.name_first,
				app.name_last,
				'card' as bank_aba,
				ci.card_number as bank_last4,
				'debit' as ach_type,
				cp.process_status as ach_status,
				cp.amount,
				IF (cp.result_code != 'approved', CONCAT(cpr.response,'-',cpr.reason_code), null) as return_code,
				IF (cp.result_code != 'approved', DATE_FORMAT(cp.date_created, '%m/%d/%Y'), null) AS return_date,
				app.application_status_id,
				tr1.clearing_type
			FROM card_process cp
			JOIN application app ON (app.application_id = cp.application_id)
			JOIN company co on (co.company_id = app.company_id)
			JOIN card_info ci on (cp.card_info_id = ci.card_info_id)
			JOIN (
				SELECT tr.card_process_id,
					es.context,
					tt.clearing_type
				FROM transaction_register AS tr
				JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
				JOIN transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
				GROUP BY tr.card_process_id
			    ) AS tr1 ON tr1.card_process_id = cp.card_process_id
			LEFT JOIN card_process_response cpr on (cpr.reason_code = cp.reason_code)
			WHERE cp.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
			AND cp.amount > 0
                ";

		if (empty($batch_type) || ($batch_type == "card"))
		{
			$st = $this->db->query($query);
			while($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				try
				{
					$row['bank_last4'] = substr(Payment_Card::Format_Payment_Card(Payment_Card::decrypt($row['bank_last4']), TRUE),-4);
				}
				catch (Exception $e)
				{
					$row['bank_last4'] = "XXXX";
				}
	
				$company_name = $row['company_name'];
				unset($row['company_name']);
	
				$this->Get_Module_Mode($row);
	
				$data[$company_name][] = $row;
			}
		}
		
		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}

}

?>
