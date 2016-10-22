<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once( COMMON_LIB_DIR . "pay_date_calc.3.php");

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
			$this->search_query = new Loan_Posting_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id'         => $this->request->company_id,
			  'loan_type'          => $this->request->loan_type
			);
	
			$_SESSION['reports']['loan_posting']['report_data'] = new stdClass();
			$_SESSION['reports']['loan_posting']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['loan_posting']['url_data'] = array('name' => 'Loan Posting', 'link' => '/?module=reporting&mode=loan_posting');
	
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

			$data->search_results = $this->search_query->Fetch_Loan_Posting_Data($specific_date_YYYYMMDD,
										     $this->request->loan_type,
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
		$_SESSION['reports']['loan_posting']['report_data'] = $data;
	}
}

class Loan_Posting_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Loan Posting Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Loan_Posting_Data($specific_date, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		$tomorrow = $pdc->Get_Next_Business_Day($specific_date);

		$data = array();

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $specific_date . '000000';
		$timestamp_end	 = $specific_date . '235959';

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$event_type_list = "'moneygram_disbursement','check_disbursement','loan_disbursement','adjustment_external','refund','refund_3rd_party'";

		// [#34390] removed 'DISTINCT' from query to show erroneously
		// duplicated rows such as two refunds for the same customer
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(c.name_short)     AS company_name,
				a.application_id        AS application_id,
				a.name_last             AS name_last,
				a.name_first            AS name_first,
				IF (ach.bank_aba IS NULL, a.bank_aba, ach.bank_aba) AS aba,
				IF (ach.bank_account IS NULL, a.bank_account, ach.bank_account) AS account,
				a.company_id            AS company_id,
				a.application_status_id AS application_status_id,
			-- Placeholder until card db is up and running
				\"\"                    AS card_number,
				(es.amount_principal + es.amount_non_principal) AS amount,
				a.date_first_payment AS current_due_date,
				(CASE
			-- Is this an adjustment instead of a loan disbursement?
			              WHEN (SELECT COUNT(*)
			                     FROM  transaction_type          AS tt4,
					     event_schedule                  AS es4,
					     event_transaction               AS et4
			                     WHERE tt4.transaction_type_id    =  et4.transaction_type_id
			                      AND  es4.event_type_id          =  et4.event_type_id
			                      AND  (tt4.clearing_type         =  'adjustment'
			                       OR   tt4.name_short          LIKE 'refund%')
			                      AND  es4.event_schedule_id      =  es.event_schedule_id
			                   ) > 0 AND ((es.amount_principal >  0) OR (es.amount_non_principal >  0))
			              THEN 'Refund'
			-- is there a previous failure for this app?
				 WHEN (SELECT COUNT(*)
				             FROM  transaction_register    AS tr3,
				                   transaction_type        AS tt3
				             WHERE tr3.transaction_type_id =  tt3.transaction_type_id
				             	 AND  tr3.application_id      =  a.application_id
				              AND  tt3.name_short          in ('loan_disbursement', 'check_disbursement')
				              AND  tr3.transaction_status  =  'failed'
					      AND  tr3.date_effective      <  '{$tomorrow}'
				           ) > 0
			              THEN 'Resend'
			-- Is the react column set to yes?
			              WHEN a.is_react = 'yes'
			              THEN 'React'
			-- None of the above
			              ELSE 'New'
				END) AS loan_type,
			lt.name_short AS loan_type_short
			FROM
				(application             AS a,
				company                 AS c,
				loan_type               AS lt,
				event_schedule          AS es,
				application_status      AS aps,
				event_type              AS et)
				LEFT JOIN transaction_register	AS tr USING(event_schedule_id)
				LEFT JOIN ach USING(ach_id)
			WHERE
				a.company_id            =  c.company_id
			 AND	a.loan_type_id          =  lt.loan_type_id
			 AND	a.application_id        =  es.application_id
			 AND	es.event_type_id        =  et.event_type_id
			 AND	a.application_status_id =  aps.application_status_id
			 AND	es.date_event           =  '{$specific_date}'
			 AND	et.name_short           IN ({$event_type_list})
			 AND	lt.name_short           IN ({$loan_type_list})
			 AND	c.company_id            IN ({$company_list})
			GROUP BY es.event_schedule_id
			LIMIT	{$max_report_retrieval_rows}
			";

		//$this->log->Write("{$query}");

		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		$st = $db->query($query);

		if( $st->rowCount() == $max_report_retrieval_rows )
			return false;

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];

			$this->Get_Module_Mode($row);

			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}
}

?>
