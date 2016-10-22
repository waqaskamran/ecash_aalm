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

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		parent::__construct($server, $request, $module_name, $report_name);

	}

	// Put the data to be displayed in the Transport Class
	public function Generate_Report()
	{
		try
		{
			$this->search_query = new Corrections_Report_Query($this->server);
			$data = new stdClass();

			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id' => $this->request->company_id,
			  'loan_type'  => $this->request->loan_type
			);

			$_SESSION['reports']['corrections']['report_data'] = new stdClass();
			$_SESSION['reports']['corrections']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['corrections']['url_data'] = array('name' => 'Corrections', 'link' => '/?module=reporting&mode=corrections');

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

			$data->search_results = $this->search_query->Fetch_Agent_Comment_Data ($start_date_YYYYMMDD,
										       $end_date_YYYYMMDD,
										       $this->request->company_id,
										       $this->request->loan_type);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		ECash::getTransport()->Add_Levels("report_results");

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['corrections']['report_data'] = $data;
	}
}


class Corrections_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Corrections Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Agent_Comment_Data($date_start, $date_end, $company_id, $loan_type)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$comment_data    = array();

		if( is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0 )
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";


		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		// Get all corrections and app_id's
		$query  = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				c.application_id        AS application_id,
				a.application_status_id AS application_status_id,
				c.comment               AS correction,
				co.name_short           AS company_name,
				a.company_id            AS company_id
			 FROM
				application             AS a,
				comment                 AS c
			 JOIN	company                 AS co ON c.company_id = co.company_id
			 JOIN	loan_type               AS lt ON c.company_id = lt.company_id
			 WHERE
				c.type = 'ach_correction'
			  AND	c.application_id = a.application_id
			  AND	c.company_id   IN ($company_list)
			  AND	lt.name_short  IN ({$loan_type_list})
			  AND	c.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			 ORDER BY application_id
		";
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$this->Get_Module_Mode($row, $row['company_id']);

			$comment_data[$company_name][] = $row;
		}

		$this->timer->startTimer( self::$TIMER_NAME );

		return $comment_data;
	}
}

?>
