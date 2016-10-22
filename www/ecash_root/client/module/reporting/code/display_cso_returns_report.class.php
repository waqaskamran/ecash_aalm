<?php

/**
 * @package Reporting
 * @category Display
 */
class CSO_Returns_Report extends Report_Parent
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

		$this->report_title       = "CSO Returns Report";

		$this->column_names       = array( 
				'company_name'               => 'Company',
				'return_date'                => 'Return Date',
				'total_principal'            => 'Total Principal',
				'total_cso_fees'             => 'Total CSO Fee',
				'total_interest'             => 'Total Interest',
				'total_nsf_fees'             => 'Total NSF',
				'total'                      => 'Total' 
				);
										
		$this->column_format       = array( 
				'return_date'      => self::FORMAT_DATE,
				'total_principal'  => self::FORMAT_CURRENCY,
				'total_cso_fees'   => self::FORMAT_CURRENCY,
				'total_interest'   => self::FORMAT_CURRENCY, 
				'total_nsf_fees'   => self::FORMAT_CURRENCY, 
				'total'            => self::FORMAT_CURRENCY, 
				);

		$this->sort_columns       = array_keys($this->column_names);

        $this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 			   = null;
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = true;
		parent::__construct($transport, $module_name);
	}

	// Sorting overrides
    public function Download_Data()
    {
        foreach( $this->search_results as $company_name => $company_data )
        {
            $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, "unf_date", SORT_ASC);
        }

        parent::Download_Data();
    }

    public function Download_XML_Data()
    {
        // This is a hack the reporting framework doesn't seem to have a way to specify
        // a default arbitrary sort column in the initial display of the report. Apparently
        // CLK has a new framework so I'm not going to worry about it much now. [benb]
        if ($_REQUEST['sort'] == "undefined")
        {
            foreach( $this->search_results as $company_name => $company_data )
            {
                $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, "unf_date", SORT_ASC);
            }
        }

        parent::Download_XML_Data();
    }

	/* I am overriding this because of the special summaries */ 
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$html = "";

        foreach ($this->search_results as $company_name => $company_data)
		{
			// Sane starting values
			$gr           = 0;
			$gi           = 0;
			$gn           = 0;
			$gin          = 0;
			$gt           = 0;	

			// Add up all the totals
			foreach ($company_data as $key => $value)
			{
				$interest = $value['total_interest'];
				$nsf      = $value['total_nsf_fees'];
				$total    = $value['total_interest'] + $value['total_nsf_fees'];
				$gtotal   = $value['total'];

				$gr++;
				$gi  += $interest;
				$gn  += $nsf;
				$gin += $total;
				$gt  += $gtotal;
			}

			// HEADERS
			$tsv .= "\n";
			$tsv .= "Company\t";
			$tsv .= "Rows\t";
			$tsv .= "Total Interest\t";
			$tsv .= "Total NSF\t";
			$tsv .= "Total (Int + NSF)\t";
			$tsv .= "Total\n";

			$tsv .= "{$company_name}\t";
			$tsv .= "{$gr}\t";
			$tsv .= '$' . number_format($gi, 2)  . "\t";
			$tsv .= '$' . number_format($gn, 2)  . "\t";
			$tsv .= '$' . number_format($gin, 2) . "\t";
			$tsv .= '$' . number_format($gt, 2)  . "\n";
		}

		return $tsv;
	}


	// I am overriding this because of the special summaries
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$html = "";

        foreach ($this->search_results as $company_name => $company_data)
		{
			// Sane starting values
			$gr           = 0;
			$gi           = 0;
			$gn           = 0;
			$gin          = 0;
			$gt           = 0;	

			// Add up all the totals
			foreach ($company_data as $key => $value)
			{
				$interest = $value['total_interest'];
				$nsf      = $value['total_nsf_fees'];
				$total    = $value['total_interest'] + $value['total_nsf_fees'];
				$gtotal   = $value['total'];

				$gr++;
				$gi  += $interest;
				$gn  += $nsf;
				$gin += $total;
				$gt  += $gtotal;
			}


			// HEADERS
			$html .= "<tr>\n";
			$html .= "  <th class='report_head'>Company</th>\n";
			$html .= "  <th class='report_head'>Rows</th>\n";
			$html .= "  <th class='report_head'>Total Interest</th>\n";
			$html .= "  <th class='report_head'>Total NSF</th>\n";
			$html .= "  <th class='report_head'>Total (Int + NSF)</th>\n";
			$html .= "  <th class='report_head'>Total</th>\n";
			$html .= "</tr>\n";

			$html .= "<tr>\n";
			$html .= "  <th class='report_foot'>$company_name</th>\n";
			$html .= "  <th class='report_foot'>" .  $gr . "</th>\n";
			$html .= "  <th class='report_foot'>" . number_format($gi,  2) . "</th>\n";
			$html .= "  <th class='report_foot'>" . number_format($gn,  2) . "</th>\n";
			$html .= "  <th class='report_foot'>" . number_format($gin, 2) . "</th>\n";
			$html .= "  <th class='report_foot'>" . number_format($gt, 2)  . "</th>\n";
			$html .= "</tr>\n";
		}

		return $html;
	}

}

?>
