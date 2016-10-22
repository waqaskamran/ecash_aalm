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
		//

					
		try
		{		
			$this->search_query = new Fraud_Deny_Report_Query($this->server);
	
			$data = new stdClass();
			//$data->search_criteria = array();
			
						$data->search_criteria = array(
			'company_id'		=> $this->request->company_id,
			);
			
			
			$company_id = $data->search_criteria['company_id'];
			$_SESSION['reports']['fraud_proposition']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud_proposition']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['fraud_proposition']['url_data'] = array('name' => 'Fraud Proposition', 'link' => '/?module=reporting&mode=fraud_proposition');
			$data->search_results = $this->search_query->Fetch_Fraud_Proposition_Data($company_id);
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
		$_SESSION['reports']['fraud_proposition']['report_data'] = $data;
	}
}

?>
