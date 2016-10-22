<?php

/**
 * @package Reporting
 * @category Display
 */
class Aging_Summary_Report extends Report_Parent
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

		$this->report_title       = "Aging Summary Report";

		$this->column_names       = array( 'num_days' => '# Days',
											'num_loans' 	=> '# Loans',
											'pct_loans'		=> '% of Loans',
		                                   'balance'   		=> 'Principal',
		                                   'pct_balance'	=> '% of Principal',
		                                   'interest'   	=> 'Interest',
		                                   'fee'   			=> 'Fee',
		                                   'total'   		=> 'Total',
		                                   );

		$this->sort_columns       = array(  'num_days',
											'num_loans',    				
											'balance',
											'interest',
											'fee',
											'total');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

      	$this->totals 	= array('company' =>  array( 'num_loans', 'balance', 'interest','fee', 'total'),
      							'grand' =>  array( 'num_loans', 'balance', 'interest','fee', 'total'));
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;

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
			case 'num_days':
				if ( $html === true )
				{
					return '<div style="text-align:center;margin-left:3px;font-weight:bold;">'.$data.'</div>';
				}else{
					return $data;
				}
			case 'num_loans':
				if ( $html === true )
				{
					return '<div style="text-align:right;margin-left:3px;">'.$data.'</div>';
				}else{
					return $data;
				}
			case 'pct_loans':
			case 'pct_balance':
				if ( $html === true )
				{
					return '<div style="text-align:right;margin-left:3px;">'.round($data, 2).'%</div>';
				}else{
					return round($data, 4);
				}
			case 'balance':
			case 'interest':
			case 'fee':
			case 'total':
				if( $html === true )
				{
					$markup = ($data < 0 ? 'color: red;' : '');
					$open = ($data < 0 ? '(' : '');
					$close = ($data < 0 ? ')' : '');
					$data = abs($data);
					return '<div style="text-align:right;margin-left:15px;'. $markup . '">' .$open.'\\$' . number_format($data, 2, '.', ',') . $close . '</div>';
				}
				else
				{
					return '$' . number_format($data, 2, '.', ',');
				}
			case 'application_id':
			default:
				return $data;
		}
	}
	
	protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{
		$substitutions->start_date_title = '';
		parent::Get_Form_Options_HTML($substitutions);		
	}
}

?>
