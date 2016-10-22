<?php

/**
 * @package Reporting
 * @category Display
 */
class Call_Dispositions_Report extends Report_Parent
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

		$this->report_title = "Call Dispositions Report";

		$this->column_names = array( 'company_name' => 'Company',
		                                'application_id' => 'App ID',
						'customer_name'  => 'Customer',
						'application_status' => 'App Status',
						'agent_name'  => 'Agent',
						'created_on' => 'Created On',
						'comment_flag'  => 'Flag',
						'call' => 'Call',
						'loan_action'  => 'Loan Action',
		);

		$this->column_format      = array(
						//'created_on'   => self::FORMAT_TIME,
						//'follow_up_time'   => self::FORMAT_TIME
		);

		$this->sort_columns       = array(
						'application_id',
						'customer_name',
						'application_status',
						'agent_name',
						'created_on',
						'comment_flag',
						'call',
						'loan_action',
		);

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'amount' => Report_Parent::$TOTAL_AS_SUM ),
		                                   'grand'   => array( 'rows',
		                                                       'amount' => Report_Parent::$TOTAL_AS_SUM ) );

		$this->totals_conditions  = null;
		$this->ajax_reporting     = true;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->comment_flag = true;
		
		parent::__construct($transport, $module_name);
	}

	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		parent::Get_Form_Options_HTML($substitutions);

		if( $this->comment_flag === true ) {
			$substitutions->loan_type_select_list = '<span>Flag: </span><span><select name="comment_flag" size="1" style="width:100px;"></span>';
			switch( $this->search_criteria['comment_flag']) {
				case 'all_comment_flag':
					$substitutions->loan_type_select_list .= '<option value="all_comment_flag" selected>All</option>';
					$substitutions->loan_type_select_list .= '<option value="1">Resolved</option>';
					$substitutions->loan_type_select_list .= '<option value="0">Unresolved</option>';
					break;

				case '1':
					$substitutions->loan_type_select_list .= '<option value="all_comment_flag">All</option>';
					$substitutions->loan_type_select_list .= '<option value="1" selected>Resolved</option>';
					$substitutions->loan_type_select_list .= '<option value="0">Unresolved</option>';
					break;

				case '0':
					$substitutions->loan_type_select_list .= '<option value="all_comment_flag">All</option>';
					$substitutions->loan_type_select_list .= '<option value="1">Resolved</option>';
					$substitutions->loan_type_select_list .= '<option value="0" selected>Unresolved</option>';
					break;

				default:
					$substitutions->loan_type_select_list .= '<option value="all_comment_flag">All</option>';
					$substitutions->loan_type_select_list .= '<option value="1">Resolved</option>';
					$substitutions->loan_type_select_list .= '<option value="0">Unresolved</option>';
					break;
			}
			$substitutions->loan_type_select_list .= '</select>';
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
