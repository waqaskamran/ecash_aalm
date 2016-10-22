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
			$this->search_query = new Batch_Totals_Report_Query($this->server);
	
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
	
			$_SESSION['reports']['batch_totals']['report_data'] = new stdClass();
			$_SESSION['reports']['batch_totals']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['batch_totals']['url_data'] = array('name' => 'Batch Totals', 'link' => '/?module=reporting&mode=batch_totals');
	
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
		$_SESSION['reports']['batch_totals']['report_data'] = $data;
	}
}
class Batch_Totals_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Batch Totals Report Query";

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
			SELECT  batch_date,
			        COUNT(lm_ach_id) AS batch_total,
			        SUM(amount) AS batch_total_amount,
			        SUM(IF(return_code != '', 1, 0)) AS num_returned,
			        SUM(IF(return_code != '', amount, 0)) AS num_returned_amount,
			        (SUM(IF(return_code != '', 1, 0)) / COUNT(lm_ach_id) * 100) AS return_rate
			FROM landmark_ach
			WHERE ((status = 'returned' AND return_code != '') OR (return_code = '' AND
			status != 'returned'))
			AND batch_date BETWEEN {$date_start} AND {$date_end}
			GROUP BY batch_date
			ORDER BY batch_date
			";

		//var_dump($query);
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
