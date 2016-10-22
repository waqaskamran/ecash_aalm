<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 17164 $
 */
require_once("report_generic.class.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{

		try
		{		
			$this->search_query = new Reactivation_Marketing_Report_Query($this->server);

			// Generate_Report() expects the following from the request form:
			//
			// criteria start_date YYYYMMDD
			// criteria end_date   YYYYMMDD
			// company_id
			//

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
					'min_days'   => $this->request->min_days,
					'max_days'   => $this->request->max_days,
					'status' => $this->request->status,
					'loan_type'     => $this->request->loan_type,
					'company_id'      => $this->request->company_id,
					'attributes'	  => $this->request->attributes,
					);

			$_SESSION['reports']['reactivation_marketing']['report_data'] = new stdClass();
			$_SESSION['reports']['reactivation_marketing']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reactivation_marketing']['url_data'] = array('name' => 'Reactivation Marketing', 'link' => '/?module=reporting&mode=reactivation_marketing');

			$min_days = $this->request->min_days;
			$max_days = $this->request->max_days;
			if(!is_numeric($min_days))
			{
				$min_days = 0;
			}
			if(!is_numeric($max_days))
			{
				$max_days = 0;
			}
			$start_date_YYYYMMDD = date('Ymd', strtotime("-$max_days days"));
			$end_date_YYYYMMDD	 = date('Ymd', strtotime("-$min_days days"));
			

			$data->search_results = $this->search_query->Fetch_Reactivation_Marketing_Data( $start_date_YYYYMMDD,
					$end_date_YYYYMMDD,
					$this->request->company_id,
					$this->request->loan_type,
					$this->request->status,
					$this->request->attributes
					);
		}
		catch (Exception $e)
		{
			echo '<pre>' . print_r($e,true);
			//$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		$num_results = 0;
		foreach ($data->search_results as $company => $results)
		{
			$num_results += count($results);

			if ($num_results >= $this->max_display_rows)
			{
				$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}			
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['reactivation_marketing']['report_data'] = $data;
	}
}


?>