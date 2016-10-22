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
			$this->search_query = new Payment_Type_Success_Report_Query($this->server);

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
			);

			$_SESSION['reports']['payment_type_success']['report_data'] = new stdClass();
			$_SESSION['reports']['payment_type_success']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['payment_type_success']['url_data'] = array('name' => 'Payment Type Success', 'link' => '/?module=reporting&mode=payment_type_success');

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

			$data->search_results = $this->search_query->Fetch_Payment_Type_Success_Data($start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
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
		$_SESSION['reports']['payment_type_success']['report_data'] = $data;
	}
}


class Payment_Type_Success_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Payment Type Success Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Payment Type Success Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Payment_Type_Success_Data($date_start, $date_end, $company_id)
	{

		$payment_types = array("ACH" => array('clearing_type' => array('ach')),
							  "Credit Card" => array('name_short' => array('credit_card_fees','credit_card_princ')),
							  "Moneygram" => array('name_short' => array('moneygram_fees','moneygram_princ')),
							  "Money Order" => array('name_short' => array('money_order_fees','money_order_princ')),
							  "Western Union" => array('name_short' => array('western_union_fees','western_union_princ')),
							  "Adjustment" => array('clearing_type' => array('adjustment')),
							  );

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

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



		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';
		$data = array();
		foreach ($payment_types as $pname => $parr)
		{
				$keyuse = array_keys($parr);
				$use_field = $keyuse[0];
				$val_field = "'" . implode("','", $parr[$use_field]) . "'";


				$query = "
					-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
					upper(co.name_short)    AS company_name,
					'{$pname}' 				as Payment_Type,
					SUM(IF(amount < 0 AND transaction_status IN ('complete','failed'), 1,0)) AS Total_Payments,
					SUM(IF(amount < 0 AND transaction_status IN ('complete','failed'), amount,0)) AS Total_Amount,
					SUM(IF(amount < 0 AND transaction_status = 'complete', 1,0)) AS Completed,
					SUM(IF(amount < 0 AND transaction_status = 'complete', amount,0)) AS Completed_Amount,
					SUM(IF(amount < 0 AND transaction_status = 'failed', 1,0)) AS Returned,
					SUM(IF(amount < 0 AND transaction_status = 'failed', amount,0)) AS Returned_Amount
				FROM transaction_register AS tr
				join company as co on (tr.company_id = co.company_id)
				 LEFT JOIN transaction_type AS tt USING (transaction_type_id)
				WHERE tr.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND tt.{$use_field} IN ({$val_field})
				AND tr.amount < 0 -- Debit
				AND tr.company_id IN ({$company_list})
				GROUP BY Payment_Type
				";
				$st = $this->db->query($query);

				while ($row = $st->fetch(PDO::FETCH_ASSOC))
				{
					// Need data as array( Company => array( 'colname' => 'data' ) )
					//   Do all data formatting here
					$company_name = $row['company_name'];
					unset($row['company_name']);

					$data[$company_name][] = $row;
				}
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
