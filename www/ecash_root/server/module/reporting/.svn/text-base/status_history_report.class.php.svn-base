<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");
require_once( SQL_LIB_DIR . "application.func.php" );

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
			$this->search_query = new Status_History_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'company_id'      => $this->request->company_id,
			  'status_type'     => $this->request->status_type
			);

			$_SESSION['reports']['status_history']['report_data'] = new stdClass();
			$_SESSION['reports']['status_history']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['status_history']['url_data'] = array('name' => 'Status History', 'link' => '/?module=reporting&mode=status_history');

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


			$data->search_results = $this->search_query->Fetch_Status_History_Data( $start_date_YYYYMMDD,
				                                                                              $this->request->status_type,
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
		$_SESSION['reports']['status_history']['report_data'] = $data;
	}
}

class Status_History_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Status History Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Status History Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $status_type
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Status_History_Data($start_date, $status_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		// Yes, we're only searching within a single day
		$end_date   = "{$start_date}235959";
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

		$status_type = explode(',',$status_type);

		// The status type actually is a comma separated list of status ids!
		$status = "'" . implode("','", $status_type) . "'";

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				sh.application_id						 AS application_id,
				sh.date_created							 AS time_modified,
				(
					SELECT
						pas.name
					FROM
						status_history psh
					JOIN
						application_status pas ON (pas.application_status_id = psh.application_status_id)
					WHERE
						psh.date_created < sh.date_created
					AND
						psh.application_id = sh.application_id
					ORDER BY
						psh.status_history_id DESC
					LIMIT 1
				) AS previous_status,					
				ass.name 								 AS new_status,
				app.application_status_id,
				CONCAT(ag.name_first, ' ', ag.name_last) AS agent_name,
				c.company_id,
				UPPER(c.name_short) as company_name,
				c.name,
				lt.name_short AS loan_type
			FROM
				status_history sh
			JOIN
				application_status ass ON (ass.application_status_id = sh.application_status_id)
			LEFT JOIN
				agent ag ON (sh.agent_id = ag.agent_id)
			JOIN
				application app ON (app.application_id = sh.application_id)
			JOIN
				loan_type AS lt ON app.loan_type_id = lt.loan_type_id
			JOIN
				company c ON (c.company_id = app.company_id)
            WHERE 
				sh.date_created BETWEEN {$start_date} AND {$end_date}
            AND
				sh.application_status_id IN ({$status})
            AND
				sh.company_id IN ({$company_list})
		";
				
		$data = array();
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];

			$this->Get_Module_Mode($row);

			$data[$co][] = $row;
			
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
