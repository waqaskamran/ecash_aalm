<?php

/**
 * @package Reporting
 * @category Display
 */
class Cashline_Payments_Due_Report extends Report_Parent
{
	/**
	* count of customers in each pay period
	* @var array
	* @access private
	*/
	private $run_totals;

	/**
	* Grand totals for all companies
	* @var array
	* @access private
	*/
	private $grand_totals;

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
		$this->report_title       = "Cashline Payments Due";

		$this->column_names       = array( 'customer' => 'Customer',
										   'application_id' => 'Application ID',
		                                   'cashline_id'      => 'Cashline ID',
		                                   'ecash_status'      => 'eCash Status',
		                                   'cashline_status'      => 'CL Status',
		                                   'pay_period'     => 'Pay Period',
		                                   'bal'         => 'Balance',
		                                   'amt'      => 'Amount',
		                                   'date'             => 'Date',
		                                   'ecash_debit'      => 'eCash Amount',
		                                   'difference' => 'Difference'
										 );

		$this->sort_columns       = array( 'customer',
										   'application_id',
		                                   'cashline_id',
		                                   'ecash_status',
		                                   'cashline_status',
		                                   'pay_period',
		                                   'bal',
		                                   'amt',
		                                   'date',
		                                   'ecash_debit',
		                                   'difference'
										 );

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows',
		                                                       'bal'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'amt'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'ecash_debit'           => Report_Parent::$TOTAL_AS_SUM,
		                                   'grand'   => array( 'rows',
		                                                       'bal'      => Report_Parent::$TOTAL_AS_SUM,
		                                                       'amt'           => Report_Parent::$TOTAL_AS_SUM,
		                                                       'ecash_debit'           => Report_Parent::$TOTAL_AS_SUM,
		                              ) ));

		$this->totals_conditions  = null; 

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = false;
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
	protected function Format_Field( $name, $data, $totals = false, $html = true, &$align = null)
	{
		switch( $name )
		{
			case 'totals':
				if( $html === true )
				{
					$align = 'right';
					return ' &nbsp;(\\$' . $data . ')';
				}
				else
				{
					return '  ($' . $data . ')';
				}
				break;
			case 'amt':
			case 'ecash_debit':
			case 'difference':
			case 'bal':
				if( $html === true )
				{
					$align = 'right';
					return '\\$' . number_format( $data, 2, '.', ',' );
				}
				else
				{
					return '$' . number_format( $data, 2, '.', ',' );
				}
				break;

			default:
				$align = 'left';
				return $data;
		}
	}

}

?>
