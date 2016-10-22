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

		try
		{		
			$this->search_query = new Fraud_Deny_Report_Query($this->server);
		
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'		=> $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);
	
			$_SESSION['reports']['fraud_deny']['report_data'] = new stdClass();
			$_SESSION['reports']['fraud_deny']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['fraud_deny']['url_data'] = array('name' => 'Fraud Deny', 'link' => '/?module=reporting&mode=fraud_deny');	
			
			$results = $this->Get_Dates(&$data);
	
			if(!$results)
				return;
			else
				list($start_date_YYYYMMDD, $end_date_YYYYMMDD) = $results;
			$company_id = $data->search_criteria['company_id'];
			$data->search_results = $this->search_query->Fetch_Fraud_Denied_Data($start_date_YYYYMMDD, $end_date_YYYYMMDD,$company_id, $this->request->loan_type);
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
		$_SESSION['reports']['fraud_deny']['report_data'] = $data;
	}
}

?>
