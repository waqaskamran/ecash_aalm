<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Affiliation_Report extends Report_Parent
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

		$this->report_title = "Agent Affiliation Report";

		$this->column_names = array( 'company_name' => 'Company',
                                                'agent_name' => 'Affiliated Agent',
						'created_by' => 'Created By',
		                                'application_id' => 'Application ID',
                                                'application_status_payment' => 'App Status Before Payment',
						'application_status' => 'Current App Status',
		                                'customer_name'  => 'Customer Name',
						'event_schedule_id' => 'ES ID',
						'created_date'  => 'Created Date',
                                                'context'  => 'Context',
						'payment_type' => 'Payment Type',
		                                //'return_reason'  => 'Return Reason',
		                                'amount' => 'Amount',
		                                'principal' => 'Principal',
		                                'amount_non_principal' => 'Interest/Fees',
						'date_effective' => 'Effective Date',
						'event_status' => 'Schedule Status' ,
						'transaction_status' => 'Payment Status',
						'clearing_type' => 'Clearing Type',
						'ach_provider' => 'Ach Provider'
		);

		$this->column_format      = array(
						'created_date'   => self::FORMAT_DATE,
		                                'amount'         => self::FORMAT_CURRENCY,
		                                'principal'		=> self::FORMAT_CURRENCY,
		                                'amount_non_principal' => self::FORMAT_CURRENCY,
						'date_effective'   => self::FORMAT_DATE
		);

		$this->sort_columns       = array(
                                                'agent_name',
						'created_by',
						'application_id',
                                                'application_status_payment',
						'application_status',
						'customer_name',
						'event_schedule_id',
						'created_date',
                                                'context',
						'payment_type',
						'amount',
						'date_effective',
						'event_status',
						'transaction_status',
						'clearing_type',
						'ach_provider'
		);
		

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'amount' => Report_Parent::$TOTAL_AS_SUM ),
		                                   'grand'   => array( 'rows',
		                                                       'amount' => Report_Parent::$TOTAL_AS_SUM ) );

		$this->totals_conditions  = null;
		$this->ajax_reporting     = true;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		//$this->loan_type          = true;
		$this->download_file_name = null;
		$this->payment_arrange_type = true;
                $this->collection_level = true;
		$this->batch_type           	= TRUE;
		$this->ach_batch_company        = TRUE;

		parent::__construct($transport, $module_name);
	}

	// Pulled out from the parent, as to not effect other reports.
	// Added date_completed field for [#18625] [VT]
	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		parent::Get_Form_Options_HTML($substitutions);
		
		if( $this->payment_arrange_type === true )
		{
			$substitutions->reptype_select_list = '<span>Date Search : </span><span><select name="payment_arrange_type" size="1" style="width:auto;"></span>';

			switch( $this->search_criteria['payment_arrange_type'] )
			{
				case 'date_created':
					$substitutions->reptype_select_list .= '<option value="date_created" selected>Created Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_effective">Payment Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_completed">Complete Date</option>';
					break;
				case 'date_effective':
					$substitutions->reptype_select_list .= '<option value="date_created">Created Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_effective" selected>Payment Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_completed">Complete Date</option>';
					break;
				case 'date_effective':
					$substitutions->reptype_select_list .= '<option value="date_created">Created Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_effective">Payment Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_completed" selected>Complete Date</option>';
					break;
				default:
					$substitutions->reptype_select_list .= '<option value="date_created">Created Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_effective">Payment Date</option>';
					$substitutions->reptype_select_list .= '<option value="date_completed">Complete Date</option>';
					break;
			}
			$substitutions->reptype_select_list .= '</select>';
		}
		
	}

   protected function Get_Total_HTML($company_name, &$company_totals)
   {
      $line = "";

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
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals ";
            $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th></tr>\n";
         }
         else
         {
            $line .= "    <tr><th class=\"report_foot\" colspan=\"{$this->num_columns}\">$company_name Totals</th></tr>\n";
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
                  $line .= "     <th class=\"report_foot\">$company_name Totals ";
                  $line .= ": " . $company_totals['rows'] . " row" . ($company_totals['rows']!=1?"s":"") . "</th>\n";
               }
               else
               {
                  $line .= "     <th class=\"report_foot\">$company_name Totals</th>\n";
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
}

?>
