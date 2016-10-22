<?php

/**
 * @package Reporting
 * @category Display
 */
class Payment_Arrangements_Modified_Report extends Report_Parent
{
	/**
	* Contains the summary data for the end of the report
	* @var    array
	* @access private
	*/
	private $summary_data;

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

		$this->report_title       = "Payment Arrangements Modified Report";

		$this->column_names       = array( 'created_date'	=> 'Created Date',
		                                   'agent_name'     => 'Agent Name',
		                                   'application_id' => 'Application ID',
		                                   'customer_name'  => 'Customer Name',
		                                   'number_of_payments' => 'Payments',
		                                   'amount'         => 'Amount',
		                                   'status'         => 'Status',
		                                   'occurance'      => 'Occurance' );

		$this->column_format      = array(
										   'created_date'   => self::FORMAT_DATE,
		                                   'amount'         => self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'created_date',
										   'agent_name',
		                                   'application_id',
		                                   'customer_name',
		                                   'number_of_payments',
		                                   'amount',
		                                   'method',
		                                   'status',
		                                   'occurance' );

		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' ) );

		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
// 		$this->loan_type          = true;
		$this->download_file_name = null;
// 		$this->payment_arrange_type	  = true;
		$this->ajax_reporting     = true;

		$temp                        = ECash::getTransport()->Get_Data();
		if (!empty($temp->search_results['summary']))
		{
			$this->summary_data = $temp->search_results['summary'];
			unset($temp->search_results['summary']);
			
			// In order to reset the transport's data object we have to confuse it by giving it an
			// array first, then it will reset itself when we give it the object again
			ECash::getTransport()->Set_Data(array());
			ECash::getTransport()->Set_Data($temp);
		}

		parent::__construct($transport, $module_name);
	}


	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$line = '<tr><th colspan="' . count($this->column_names) . '"><table class="report_company_foot" style="width: 65%;">';

		$line .= "\n" . '<tr class="report_foot">'
			. '<th style="text-align: left;">' . strtoupper($company_name) . ' Totals</th>'
			. '<td>' . $company_totals['rows'] . '</td>'
			. '</tr>';

		foreach ($this->summary_data[$company_name] as $field => $values)
		{
			$line .= "\n" . '<tr class="report_foot">'
				. '<th style="text-align: left;" colspan="2">' . ucwords(str_replace('_', ' ', $field)) . '</th>'
				. '</tr>';

			$x = true;
			foreach ($values as $key => $value)
			{
				$class = ($x = !$x) ? 'align_left' : 'align_left_alt';
				$line .= "\n" . '<tr>'
					. '<th>' . ucwords(str_replace('_', ' ', $key)) . '</th>'
					. '<td class="' . $class . '">' . $value . '</td>'
					. '</tr>';
			}
		}

		return $line . '</table></th></tr>';
	}
}

?>
