<?php

/**
 * @package Reporting
 * @category Display
 */
class Status_Overview_Report extends Report_Parent
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
		//$this->company_totals = array();

		$this->report_title       = "Status Overview Report";

		$this->column_names       = array( 
										'company_name'      => 'Company',
										'application_id'    => 'Application ID',
										'name_first'        => 'First Name',
										'name_last'         => 'Last Name',
										'phone_home'        => 'Home Phone',
										'phone_work'        => 'Work Phone',
										'phone_cell'        => 'Cell Phone',
										'street'            => 'Street',
										'city'              => 'City',
										'state'             => 'State',
										'principal_balance' => 'Principal Balance',
										'interest_balance'  => 'Interest Balance',
										'fee_balance'       => 'Fee Balance',
										'loan_balance'      => 'Loan Balance');

		$this->sort_columns       = array( 
										'application_id', 
										'name_first',
										'name_last',    	
										'street',  		
										'city',        
										'state',			
										'balance');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 	= array('company' => array( 'balance','rows'),
        						'grand' =>  array( 'balance','rows'));
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}

	/**
	 * Definition of abstract method in Report_Parent
	 * Used to format field data for printing
	 *
	 * @param  string  $name   column name to format
	 * @param  string  $data   field data
	 * @param  boolean $totals formatting totals or data?
	 * @param  boolean $html   format for html?
	 * @return string          formatted field
	 * @access protected
	 */
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'name_first':
			case 'name_last':
			case 'status':
			case 'street':
			case 'city':
				return ucwords($data);
			case 'state':
				return strtoupper($data);
			case 'balance':
				if( $html === true )
				{
					$markup = ($data < 0 ? 'color: red;' : '');
					$open = ($data < 0 ? '(' : '');
					$close = ($data < 0 ? ')' : '');
					$data = abs($data);
					return '<div style="text-align:right;'. $markup . '">' .$open.'\\$' . number_format($data, 2, '.', ',') . $close . '</div>';
				}
				else
				{
					return '$' . number_format($data, 2, '.', ',');
				}
			case 'time_in_queue':
			case 'order':
				if( $html === true )
					return "<div style='text-align:right;'>$data</div>";
				else
					return $data;
			case 'application_id':
			default:
				return $data;
		}
	}
}

?>
