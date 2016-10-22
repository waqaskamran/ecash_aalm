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
			$this->search_query = new Landmark_Performance_Report_Query($this->server);
	
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
	
			$_SESSION['reports']['landmark_performance']['report_data'] = new stdClass();
			$_SESSION['reports']['landmark_performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['landmark_performance']['url_data'] = array('name' => 'Landmark Performance', 'link' => '/?module=reporting&mode=landmark_performance');
	
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

			$data->search_results = $this->search_query->Fetch_Data( $start_date_YYYYMMDD,
											   $end_date_YYYYMMDD);
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
		$_SESSION['reports']['landmark_performance']['report_data'] = $data;
	}
	

}
class Landmark_Performance_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Landmark Performance Report Query";

	public function Fetch_Data($date_start, $date_end)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$data = array();
		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		//Get totals of approved, denied, and pending applications, along with grand total		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT  
				COALESCE(SUM(IF(tr.transaction_status = 'complete', 1, 0)),0) as num_completed,
        		COALESCE(SUM(IF(tr.transaction_status = 'failed', 1, 0)),0) as num_failed,
        		COALESCE(SUM(IF(tr.transaction_status = 'pending', 1, 0)),0) as num_pending,
        		COUNT(tr.transaction_register_id) as total
			FROM transaction_register AS tr
				WHERE tr.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
				AND tr.lm_ach_id IS NOT NULL
			";

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
