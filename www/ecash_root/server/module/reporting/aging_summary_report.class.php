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
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Aging_Summary_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'loan_type'       => $this->request->loan_type,
			  'company_id'      => $this->request->company_id,
			);
			
			$date_YYYY	 = $this->request->specific_date_year;
			$date_MM	 = $this->request->specific_date_month;
			$date_DD	 = $this->request->specific_date_day;
			
			$date_YYYYMMDD = $date_YYYY ."-". $date_MM ."-". $date_DD;
			
			$_SESSION['reports']['aging_summary']['report_data'] = new stdClass();
			$_SESSION['reports']['aging_summary']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['aging_summary']['url_data'] = array('name' => 'Aging Summary', 'link' => '/?module=reporting&mode=aging_summary');
	
	
			$data->search_results = $this->search_query->Fetch_Aging_Summary_Data($this->request->company_id, $this->request->loan_type, $date_YYYYMMDD);

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
		$_SESSION['reports']['aging_summary']['report_data'] = $data;
	}
}

?>
