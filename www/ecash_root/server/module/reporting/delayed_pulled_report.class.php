<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once(LIB_DIR . "status_utility.class.php");

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
			$this->search_query = new Delayed_Pulled_Report_Query($this->server);

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

			$_SESSION['reports']['delayed_pulled']['report_data'] = new stdClass();
			$_SESSION['reports']['delayed_pulled']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['delayed_pulled']['url_data'] = array('name' => 'Unpulled Audit Report', 'link' => '/?module=reporting&mode=delayed_pulled');

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

			$data->search_results = $this->search_query->Fetch_Delayed_Pulled_Data($start_date_YYYYMMDD,
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
		$_SESSION['reports']['delayed_pulled']['report_data'] = $data;
	}
}

class Delayed_Pulled_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Unpulled Audit Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Unpulled Audit Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Delayed_Pulled_Data($date_start, $date_end, $company_id)
	{
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
		
		$agree_status = Status_Utility::Get_Status_ID_By_Chain('agree::prospect::*root');

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
			    app.date_created,
				da.date_agreed,
				app.application_id,
			    UPPER(com.name_short) AS company_name,
			    com.company_id AS company_id,
			    CONCAT(app.name_last, ', ', app.name_first) AS full_name,
			    IF(app.is_react = 'yes','React','New Loan') AS type,
			    lt.name AS loan_type,
			    app.application_status_id AS application_status_id
			FROM
				application AS app
				JOIN status_history AS sh ON (sh.application_id = app.application_id AND sh.application_status_id = app.application_status_id)
				JOIN company AS com ON (com.company_id = app.company_id)
				JOIN loan_type AS lt ON (lt.loan_type_id = app.loan_type_id)
				JOIN (
					SELECT  MAX(date_created) AS date_agreed, application_status_id, application_id
			        FROM    status_history
			        GROUP BY application_status_id, application_id
				) da ON (da.application_status_id = app.application_status_id AND da.application_id = app.application_id)
			WHERE app.application_status_id = {$agree_status}
			AND	  app.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			AND   app.company_id IN ({$company_list})
            GROUP BY application_id
			LIMIT {$max_report_retrieval_rows}
			";

		$data = array();

		$db = ECash::getMasterDb();
		$st = $db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here

			$company_name = $row['company_name'];

			$row['full_name'] = ucwords($row['full_name']);

			$this->Get_Module_Mode($row);
			//$data[$company_name][$return_name][] = $row;
			$data[$company_name][] = $row;

		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
