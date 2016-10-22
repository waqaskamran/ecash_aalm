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
			$this->search_query = new Status_Overview_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'status_type'    	=> $this->request->status_type,
			  'balance_type'	=> $this->request->balance_type,
			  'attributes'	  	=> $this->request->attributes,
			);
	
			if(isset($this->request->date))
			{
				// Dates before the end of the requested date
				$date = $this->request->date;
			}
			else
			{
				// Dates before the end of today
				$date = date('Ymd') . "235959";
			}
	
			$_SESSION['reports']['status_overview']['report_data'] = new stdClass();
			$_SESSION['reports']['status_overview']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['status_overview']['url_data'] = array('name' => 'Status Overview', 'link' => '/?module=reporting&mode=status_overview');
	
	
			$data->search_results = $this->search_query->Fetch_Status_Overview_Data( $this->request->status_type, $this->request->balance_type, $date, $this->request->company_id, $this->request->attributes);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// This doesn't work. I'd fix it, but I'm not sure we're supposed to limit this [benb]
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
		$_SESSION['reports']['status_overview']['report_data'] = $data;
	}
}

?>
