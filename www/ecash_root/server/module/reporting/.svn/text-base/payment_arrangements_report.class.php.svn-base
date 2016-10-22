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
			$this->search_query = new Payment_Arrangements_Report_Query($this->server);

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
			  'payment_arrange_type'	=> $this->request->payment_arrange_type
			);

			$_SESSION['reports']['payment_arrangements']['report_data'] = new stdClass();
			$_SESSION['reports']['payment_arrangements']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['payment_arrangements']['url_data'] = array('name' => 'Payment Arrangements', 'link' => '/?module=reporting&mode=payment_arrangements');

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
			
			$data->search_results = $this->search_query->Fetch_Payment_Arrangements_Data($start_date_YYYYMMDD,
											     $end_date_YYYYMMDD,
											     $this->request->loan_type,
											     $this->request->payment_arrange_type,
											     $this->request->company_id);
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
		$_SESSION['reports']['payment_arrangements']['report_data'] = $data;
	}
}


class Payment_Arrangements_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Payment Arrangements Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Payment Arrangements Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @param   mixed  $mode       'cli' or null (null==default==web)
	 * @return  array
	 */
	public function Fetch_Payment_Arrangements_Data($start_date, $end_date, $loan_type, $payment_arrange_type, $company_id)
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

		// date completed search added for [#18625] [VT]
		if($payment_arrange_type == 'date_completed')
		{
			$payment_arrange_clause = "
				tr.date_effective BETWEEN {$start_date} AND {$end_date}
				AND tr.transaction_status = 'complete'
			";
		}
		else 
		{
			$payment_arrange_clause = "es.{$payment_arrange_type} BETWEEN {$start_date} AND {$end_date}";
		}
		
		$fetch_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				es.date_created                                              	AS 'created_date',
				es.application_id                                               AS application_id,
				CONCAT(app.name_last, ', ', app.name_first)                     AS 'customer_name',
				IFNULL(arc.name,'')                                        	AS 'return_reason',
				es.date_effective                                            	AS 'payment_date',
				-(es.amount_principal + es.amount_non_principal)		AS 'amount',
				et.name                                                        	AS 'method',
				IFNULL(tr.transaction_status,es.event_status)                  	AS 'status',
				app.application_status_id                                      	AS 'application_status_id',
				app.company_id                                                 	AS 'company_id',
				UPPER(c.name_short)                                            	AS 'company_name',
				CONCAT(agent.name_last,' ,', agent.name_first)                 	AS 'agent_name',
				agent.agent_id                                                 	AS 'agent_id',
				eat.name							AS 'event_name',
				es.amount_principal						AS 'principal',
				es.amount_non_principal						AS 'amount_non_principal'
			FROM
				event_schedule es
			JOIN
				application app ON (app.application_id = es.application_id)
			JOIN
				event_type et ON (et.event_type_id = es.event_type_id)
			JOIN
				company c ON (c.company_id = app.company_id)
			LEFT JOIN
				agent_affiliation_event_schedule aaes ON (aaes.event_schedule_id = es.event_schedule_id)
			LEFT JOIN
				agent_affiliation aa ON (aa.agent_affiliation_id = aaes.agent_affiliation_id)
			LEFT JOIN
				agent agent ON (agent.agent_id = aa.agent_id)
			LEFT JOIN 
				transaction_register tr ON (tr.event_schedule_id = es.event_schedule_id)
			LEFT JOIN
				transaction_type tt ON (tt.transaction_type_id = tr.transaction_type_id)
			LEFT JOIN 
				ach ON (ach.ach_id = tr.ach_id)
			LEFT JOIN 
				ach_return_code AS arc ON (arc.ach_return_code_id = ach.ach_return_code_id)
			LEFT JOIN
                                event_amount ea ON (ea.event_schedule_id = es.event_schedule_id
							AND ea.transaction_register_id = tr.transaction_register_id)
			LEFT JOIN 
				event_amount_type eat ON (eat.event_amount_type_id = ea.event_amount_type_id)						
			WHERE
				$payment_arrange_clause
			AND 
			(
				( es.context in ('arrangement','partial'))
				OR
				( et.name_short in ('full_balance', 'payment_manual', 'manual_ach', 'paydown', 'payout') )
			)
			AND 
				app.company_id IN ({$company_list})
			AND 
				app.loan_type_id IN (SELECT loan_type_id FROM loan_type WHERE name_short IN ({$loan_type_list}))
			GROUP BY
				es.event_schedule_id
			";
		
		$data = array();
		$st = $this->db->query($fetch_query);		
		
		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);
						
			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
