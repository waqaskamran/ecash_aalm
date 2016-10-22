<?php

/**
 * @package Reporting
 * @category Display
 */
class Export_Application_Data_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Export Application Data";
				
		
		$this->column_names       = array( 'name_first'  => 'First Name',
		                                   'name_middle' => 'MI',
		                                   'name_last'          => 'Last Name',
		                                   'name_suffix' => 'Suffix',
		                                   'street' => 'Address',
		                                   'unit'          => 'Unit',
		                                   'city' => 'City',
		                                   'state'          => 'State',
		                                   'zip_code' => 'Zip',
		                                   'dob'          => 'DOB',
		                                   'email'          => 'E-Mail',
		                                   'phone_home' => 'Home Phone',
		                                   'phone_work'          => 'Work Phone',
		                                   'phone_cell' => 'Mobile Phone',
		                                   'bank_aba'          => 'ABA',
		                                   'bank_account'	=> 'Account #',
		                                   'ip_address' => 'IP',
		                                   'application_id'          => 'APP ID',
		                                   'promo_id' => 'Promo ID'       
		                                    );
        $this->sort_columns       = array( 'application_id');
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->company_list		  = false;
		$this->download_file_name = preg_replace("/\s/", "_", $this->report_title) . date('Ymd') . ".txt" ;
		$this->ajax_reporting 	  = false;
		parent::__construct($transport, $module_name);
	}


	public function Download_Data()
	{
		// Holds output
		$dl_data = "";



		// Sort through each company's data
		foreach ($this->search_results as $company_name => $company_data)
		{
			// Short-circuit the loop if this is the "summary" data.
			if ($company_name == 'summary')
			{
				continue;
			}

			// An array of company totals which gets added to grand_totals
			$company_totals = array();
			foreach ($this->column_names as $data_name => $column_name)
			{
				$company_totals[$data_name] = 0;
			}

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if (isset($x))
			{
				$dl_data .= "\n";
			}

			foreach (array_keys($this->column_names) as $data_col_name => $name)
			{
				$dl_data .= $name . ",";	
			}
			$dl_data = substr($dl_data, 0, strlen($dl_data)-1) . "\n";
			foreach (array_keys($company_data) as $x)
			{
				foreach (array_keys($this->column_names) as $data_col_name)
				{
					$dl_data .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . ", ";
					$company_totals[$data_col_name] += $company_data[$x][$data_col_name];
				}

				// removes the last tab if we're at the end of the loop and replaces it with a newline
				$dl_data = substr($dl_data, 0, strlen($dl_data)-2) . "\n";
			}

			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

		}
		// for the html headers
		$data_length = strlen($dl_data);

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");

		//mantis:4324
		$generic_data = ECash::getTransport()->Get_Data();

		if($generic_data->is_upper_case)
			$dl_data = strtoupper($dl_data);
		else
			$dl_data = strtolower($dl_data);
		//end mantis:4324

		echo $dl_data;
	}
	
	
		
	public function Get_Module_HTML_Data()
	{

		$mode = ECash::getTransport()->page_array[2];

		$substitutions = new stdClass();

		$substitutions->report_title = $this->report_title;

		// Get the date dropdown & loan type html stuff
		$this->Get_Form_Options_HTML( $substitutions );

		$substitutions->search_message    = "<tr><td>&nbsp;</td></tr>";
		$substitutions->search_result_set = "<tr><td><div id=\"report_result\" class=\"reporting\"></div></td></tr>";

		while (!is_null($next_level = ECash::getTransport()->Get_Next_Level()))
		{
			if ($next_level == 'message')
			{
				$substitutions->search_message = "<tr><td class='align_left' style='color: red'>{$this->search_message}</td></tr>\n";
			}
			else if ($next_level == 'report_results' && $this->num_companies > 0)
			{
				// First turn on the download link
				$substitutions->download_link = "[ <a href=\"?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download Data to CSV File</a> ]";
				// for each company
				foreach( $this->search_results as $company_name => $company_data )
				{



					$company_totals = array();


					$company_totals['rows'] = 0;


					// Company data

						$this->Get_Data_HTML($company_data, $company_totals);

					
				} // end foreach company

				$message = "{$company_totals['rows']} Records returned";
				
				$substitutions->search_message = "<span style=\"color: darkblue\">$message</span>\n";
			}
			else if ($next_level == 'report_results')
			{
				$message = "No application data was found that meets the specified report criteria.";
				$substitutions->search_message = "<span style=\"color: darkblue\">$message</span>\n";
			}
		}

		return $substitutions;		
	}
	
}

?>
