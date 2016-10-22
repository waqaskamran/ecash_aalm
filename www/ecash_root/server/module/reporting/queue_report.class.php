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
			$this->search_query = new Queue_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'queue_name'      => $this->request->queue_name,
			  'min_hours'       => $this->request->min_hours,
			  'max_hours'       => $this->request->max_hours,
			  'attributes'	  	=> $this->request->attributes,
			);
	
			$_SESSION['reports']['queue']['report_data'] = new stdClass();
			$_SESSION['reports']['queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['queue']['url_data'] = array('name' => 'Queue Summary', 'link' => '/?module=reporting&mode=queue');
	
			$data->search_results = $this->search_query->Fetch_Queue_Data( $this->request->queue_name, $this->request->company_id, $this->request->min_hours, $this->request->max_hours, $this->request->attributes);
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

		// This is messing things up
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['queue']['report_data'] = $data;
	}
}

?>
