<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

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
			$this->search_query = new Fraud_Report_Query($this->server);

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
			  'loan_type'       => $this->request->loan_type,
			);

			$_SESSION['reports']['fraud']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['fraud']['url_data'] = array('name' => 'Fraud Report', 'link' => '/?module=reporting&mode=fraud');

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

			$data->search_results = $this->search_query->Fetch_Fraud_Report_Data($start_date_YYYYMMDD,
										       $end_date_YYYYMMDD,
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
		$_SESSION['reports']['fraud']['report_data'] = $data;
	}
}

class Fraud_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Fraud Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Fraud Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Fraud_Report_Data($start_date, $end_date, $loan_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$start_date = "{$start_date}000000";
		$end_date   = "{$end_date}235959";

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

		$fetch_query = "
			-- eCash 3.5, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				UPPER(co.name_short) as company_name,
				co.company_id as company_id,
				a.application_id AS application_id,
				a.application_status_id AS application_status_id,
				a.name_last as `last_name`,
				a.name_first as `first_name`,
		        a.street AS `home_street`,
		        a.city AS `home_city`,
		        a.county AS `home_county`,
        		a.state AS `home_state`,
		        a.zip_code AS `home_zip`,
        		a.phone_home AS `home_phone`,
		        a.employer_name AS `employer`,
        		(CASE WHEN a.phone_work_ext IS NOT NULL
		              THEN concat(a.phone_work, ' ext. ', a.phone_work_ext)
		              ELSE a.phone_work
         		END) as `employer_phone`,
		        a.income_monthly AS `income`,
		        (CASE WHEN a.paydate_model = 'dwpd'
		              THEN 'Bi-Weekly'
		              WHEN a.paydate_model = 'dw'
		              THEN 'Weekly'
		              WHEN a.paydate_model = 'dmdm'
		              THEN 'Twice Monthly'
		              WHEN a.paydate_model = 'wwdw'
		              THEN 'Twice Monthly'
		              WHEN a.paydate_model = 'dm'
		              THEN 'Monthly'
		              WHEN a.paydate_model = 'wdw'
		              THEN 'Monthly'
		              WHEN a.paydate_model = 'dwdm'
		              THEN 'Monthly'
		              ELSE 'Other'
			         END) as `pay_period`,
			        a.bank_name AS `bank_name`,
			        a.bank_aba AS `bank_aba`,
        			a.fund_actual AS `principal_amount`,
			        a.date_first_payment AS `first_due`,
			        a.email AS `email_address`,
			        a.ip_address AS `ip_address`,
			        sh.date_created AS `timestamp`
			FROM    status_history AS sh
			JOIN    application AS a USING (application_id)
			JOIN    company AS co ON (co.company_id = a.company_id)
			JOIN    application_status_flat AS asf ON (asf.application_status_id = sh.application_status_id)
			WHERE   sh.date_created BETWEEN $start_date
            			                AND $end_date
			AND     (asf.level0 = 'approved' AND asf.level1 = 'servicing' AND asf.level2 = 'customer' AND asf.level3 = '*root')
			AND     a.is_react = 'no'
			AND a.loan_type_id in (	SELECT loan_type_id
									FROM loan_type
									WHERE name_short IN ({$loan_type_list}))
			AND a.company_id IN ( $company_list ) ";

		//$this->log->Write($fetch_query);

		$data = array();

		$st = $this->db->query($fetch_query);

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
