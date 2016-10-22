<?php

/**
 * @package Reporting
 * @category Display
 */
class Manual_Payment_Report extends Report_Parent
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

		$this->report_title       = "Manual Payment Report";

		$this->column_names       = array( 
                                                   // 'agent_id'       => 'Agent ID',
                                                   'company_name'		=> 'Company',
                                                   'agent_name'     => 'Agent Name',
		                                   'controlling_agent' => 'Controlling Agent',
		                                   'application_id' => 'Application ID',
		                                   'customer_name'  => 'Customer Name',
		                                   'payment_type'   => 'Payment Type',
		                                   'payment_date'   => 'Payment Date',
		                                   'amount'         => 'Amount' );

		$this->column_format       = array(
		                                   'payment_date'   => self::FORMAT_DATE,
		                                   'amount'         => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'agent_id',       'agent_name',
		                                   'application_id', 'customer_name',
		                                   'controlling_agent',
		                                   'return_reason',  'payment_type',
                                                   'payment_date',   'amount',
                                                   'method',         'status' );

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

	//	$this->totals             = array( 'company' => array( 'rows',
	//	                                                       'amount' => Report_Parent::$TOTAL_AS_SUM ));

		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;

		// Turns on AJAX Reporting
		$this->ajax_reporting 	  = true;


		parent::__construct($transport, $module_name);
	}

	/*
   protected function Get_Total_HTML($company_name, &$company_totals)
   {
      $line = "";

      // Show company name in upper case
      $show_company_name = strtoupper($company_name);

      // If column 1 has no totals, totals header will go in column 1
      //    else, put totals header on own line
      reset($this->column_names);
      $total_own_line = (! empty($this->totals['company'][$this->column_names[key($this->column_names)]]) ? true : false);

      // If the total header should be on its own line,
      //    or only the # of rows is desired
      if( $total_own_line ||
         ( count($this->totals['company']) == 1 &&
           ! empty($this->totals['company']['rows'])))
      {
         if( ! empty($this->totals['company']['rows']) )
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$show_company_name&nbsp;Totals";
            $line .= ":&nbsp;" . $company_totals['rows'] . "&nbsp;row" . ($company_totals['rows']!=1?"s":"") . "</th></tr>\n";
         }
         else
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$show_company_name&nbsp;Totals</th></tr>\n";
         }
      }

      if( (! empty($this->totals['company']['rows']) && count($this->totals['company']) > 1 ) ||
         (empty($this->totals['company']['rows']) && count($this->totals['company']) > 0 ))
      {
         $line .= "    <tr>\n";
         foreach( $this->column_names as $data_name => $column_name )
         {
            if( ! empty($this->totals['company'][$data_name]) )
            {
               $line .= "     <th class=\"report_foot\"><div style=\"text-align:right\">" . $this->Format_Field($data_name, $company_totals[$data_name], true) . "</div></th>\n";
            }
            else if( ! $total_own_line )
            {
               if( ! empty($this->totals['company']['rows']) )
               {
                  $line .= "<th class=\"report_foot\">$show_company_name&nbsp;Totals";
                  $line .= ":&nbsp;" . $company_totals['rows'] . "&nbsp;row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
               }
               else
               {
                  $line .= "<th class=\"report_foot\">$show_company_name&nbsp;Totals</th>\n";
               }

               // Don't put the total header field again
               $total_own_line = ! $total_own_line;
            }
            else
            {
               $line .= "     <th class=\"report_foot\"></th>\n";
            }
         }
         $line .= "    </tr>\n";
      }

      return $line;
   }
   */
}

?>
