<?php

/**
 * @package Reporting
 * @category Display
 */
class Verification_Performance_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Verification Performance Report";
		$this->column_names = array( 'company_name'        => 'Company',
									 'agent_name'          => 'Agent',
									 'num_approved'        => 'Approved',
		                             'num_pull_uw'         => 'Received UW',
		                             'num_funded'          => 'Funded',
		                             'num_withdrawn'       => 'Withdrawn',
		                             'num_denied'          => 'Denied',
		                             'num_reverified'      => 'Reverified' );

		$this->sort_columns = array( 'agent_name',         'company_name',
									  'num_approved',
		                             'num_pull_uw', 'num_funded',
		                             'num_withdrawn',       'num_denied',
		                             'num_reverified' );
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'num_approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_pull_uw'         => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_reverified'      => Report_Parent::$TOTAL_AS_SUM),
		                             'grand'   => array( 'num_approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_pull_uw'         => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_reverified'      => Report_Parent::$TOTAL_AS_SUM) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	/**
	 * Gets the text for the grand totals
	 * used only by Download_Data()
	 *
	 * @param  array  the grand totals for the entire report
	 * @return string
	 * @access protected
	 */
	protected function Get_Grand_Total_Line($grand_totals)
	{
		$line = "";

		if( ! empty($this->totals['grand']['rows']) )
		{
			$line .= "Grand Totals : " . $grand_totals['rows'] . " rows\n";
		}
		else
		{
			$line .= "Grand Totals\n";
		}

		// Column grand totals
		if( (! empty($this->totals['grand']['rows']) && count($this->totals['grand']) > 1 ) ||
			(empty($this->totals['grand']['rows']) && count($this->totals['grand']) > 0 ))
		{
			// Column names again for eash reference
			$line .= $this->Get_Column_Headers( false, $grand_totals );

			// An extra tab to skip the company name field which is usually first
		//	$line .= "\t";
			foreach( $this->column_names as $data_name => $column_name )
			{
				if( ! empty($this->totals['grand'][$data_name]) )
				{
					$line .= $this->Format_Field($data_name, $grand_totals[$data_name],false,false) . "\t";
				}
				else
				{
					$line .= "\t";
				}
			}
			// removes the last tab if we're at the end of the loop and replaces it with a newline
			$line = substr( $line, 0, -1 ) . "\n";
		} // end column grand totals

		return $line;
	}

	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		if ($data == NULL)
			return 0;
		else
			return parent::Format_Field( $name, $data, $totals, $html);
	}

}

?>
