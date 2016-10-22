<?php

/**
 * @package Reporting
 * @category Display
 */
class Post_2nd_Tier_Returns_Report extends Report_Parent
{
	/**
	 * constructor, initializes data used by report_parent
	 *
	 * @param Transport $transport the transport object
	 * @param string $module_name name of the module we're in, not used, but keeps
	 *                            universal constructor call for all modules
	 * @access public
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->company_totals = array();

		$this->report_title       = "Post 2nd Tier Returns Report";

		$this->column_names       = array( 'company_name'               => 'Company',
										   'application_id'             => 'Application ID',
										   'amount'                     => 'Amount',
										   'balance'                    => 'Balance' );
										
		$this->column_format       = array( 'amount'                => self::FORMAT_CURRENCY,
											'balance'               => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'application_id',
										   
										   'amount',
										   'Balance' );

        $this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 			   = null;
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;

		$this->download_file_name = preg_replace("/\s/", "_", $this->report_title) . date('Ymd') . ".txt" ;

		$this->ajax_reporting 	   = true;
		parent::__construct($transport, $module_name);
	}

	// Overridden for CSV, we don't have a way to do this in the framework yet? REALLY?
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

	// Get rid of the empty totals line in the csv file report
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		return;
	}

}

?>
