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

		try
		{

			$this->search_query = new Fraud_Performance_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
				'company_id'		=> $this->request->company_id,
				'queue_type' 		=> $this->request->queue_type,
				'start_date_MM'   => $this->request->start_date_month,
				'start_date_DD'   => $this->request->start_date_day,
				'start_date_YYYY' => $this->request->start_date_year,
				'end_date_MM'     => $this->request->end_date_month,
				'end_date_DD'     => $this->request->end_date_day,
				'end_date_YYYY'   => $this->request->end_date_year,
			);

			$_SESSION['reports']['fraud_performance']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud_performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['fraud_performance']['url_data'] = array('name' => 'Fraud Performance', 'link' => '/?module=reporting&mode=fraud_performance');

			$results = $this->Get_Dates(&$data);


			//Get Queues
			if(!$results)
			{
				return;
			}
			else
			{
				list($start_date_YYYYMMDD, $end_date_YYYYMMDD) = $results;
			}

			$data->search_results = $this->search_query->Fetch_Fraud_Performance_Data($start_date_YYYYMMDD,
			$end_date_YYYYMMDD,
			$this->request->queue_type,
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
		$_SESSION['reports']['fraud_performance']['report_data'] = $data;
		
	}
	
}

?>
