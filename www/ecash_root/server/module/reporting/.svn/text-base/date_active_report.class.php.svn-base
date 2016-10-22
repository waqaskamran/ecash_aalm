<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 19111 $
 */

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{

		try
		{
			$this->search_query = new Date_Active_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			'start_date_MM'   => $this->request->start_date_month,
			'start_date_DD'   => $this->request->start_date_day,
			'start_date_YYYY' => $this->request->start_date_year,
			'end_date_MM'     => $this->request->end_date_month,
			'end_date_DD'     => $this->request->end_date_day,
			'end_date_YYYY'   => $this->request->end_date_year,
			);

			$_SESSION['reports']['date_active']['report_data'] = new stdClass();
			$_SESSION['reports']['date_active']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['date_active']['url_data'] = array('name' => 'Certegy Fulfilled', 'link' => '/?module=reporting&mode=date_active');

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

			$data->search_results = $this->search_query->Fetch_Data( $start_date_YYYYMMDD, $end_date_YYYYMMDD, Status_Utility::Get_Status_ID_By_Chain("active::servicing::customer::*root")); 
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

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['date_active']['report_data'] = $data;
	}
}
class Date_Active_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Date Active Report Query";

	public function Fetch_Data($date_start, $date_end, $application_status_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$data = array();
		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		//Get totals of approved, denied, and pending applications, along with grand total
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT  application_id,
					date_application_status_set
			FROM application
			WHERE date_application_status_set BETWEEN '{$date_start}' AND '{$date_end}'
			AND application_status_id={$application_status_id}
			ORDER BY date_application_status_set
			";
		$totals = array();
		$result = $this->mysqli->Query($query);
		$data = array();

		while($row = $result->Fetch_Array_Row())
		{
			$data['FBOD'][] = $row;
		}
		return $data;
	}
}

?>
