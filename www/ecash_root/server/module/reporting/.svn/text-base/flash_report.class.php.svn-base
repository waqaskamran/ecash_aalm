<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		// company_id

		try
		{
			$this->search_query = new Flash_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id'         => $this->request->company_id,
			  'loan_type'          => $this->request->loan_type
			);

			$_SESSION['reports']['flash']['report_data'] = new stdClass();
			$_SESSION['reports']['flash']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['flash']['url_data'] = array('name' => 'Flash', 'link' => '/?module=reporting&mode=flash');

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

			$data->search_results = $this->search_query->Fetch_Flash_Data( $specific_date_YYYYMMDD,
									       $this->request->loan_type,
									       $this->request->company_id );
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
		$_SESSION['reports']['flash']['report_data'] = $data;
	}
}

class Flash_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Flash Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Flash_Data($specific_date, $loan_type, $company_id)
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

		$data = array();

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		if ($specific_date == date("Ymd"))
		{
			$query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
					c.name_short              AS company_name,
					a.income_frequency        AS model,
					aps.name                  AS status,
					count(DISTINCT a.application_id) AS count
				FROM
					application               AS a,
					company                   AS c,
					application_status        AS aps,
					loan_type                 AS lt
				WHERE
					a.company_id             =  c.company_id
				 AND	a.application_status_id  =  aps.application_status_id
				 AND	a.loan_type_id           =  lt.loan_type_id
				 AND	a.date_created           <=  CURDATE()
				 AND	lt.name_short            IN ({$loan_type_list})
				 AND	c.company_id             IN ({$company_list})
				GROUP BY c.name_short, a.income_frequency, aps.name
				ORDER BY company_name, model, status
			";
		}
		else
		{
			$query = "
				-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
				SELECT
					UPPER(resolve_flash_report.company_name) AS company_name,
					UPPER(resolve_flash_report.model)        AS model,
					UPPER(resolve_flash_report.status)       AS status,
					SUM(resolve_flash_report.count)          AS count
				 FROM
					resolve_flash_report
				 WHERE
					resolve_flash_report.date        = '$specific_date'
				  AND	resolve_flash_report.company_id IN ({$company_list})
				  AND	resolve_flash_report.loan_type  IN ({$loan_type_list})
				GROUP BY company_name, model, status
			";
		}

		//echo "<pre>query: " . str_replace("\t","  ",$query) . "\n</pre>\n";
		//exit;

		// This report should ALWAYS hit the master.
		$db = ECash::getMasterDb();
		$st = $db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$company_name = $row['company_name'];
			unset($row['company_name']);

			if( ! isset($data[$company_name]) )
				$data[$company_name] = array();

			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}
}

?>
