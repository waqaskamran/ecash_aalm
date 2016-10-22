<?php

/**
 * @package Reporting
 * @category Display
 */
class Conversion_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Conversion Report";
		$this->column_names = array();
		$this->sort_columns = array();
		$this->link_columns = array();
		$this->totals = array(
				'company' => array(),
				'grand' => array(),
				);

		$this->totals_conditions = null;
		$this->date_dropdown = null;
		$this->loan_type = FALSE;
		$this->agent_list = FALSE;
		$this->company_list = FALSE;
		$this->download_file_name = null;

		parent::__construct($transport, $module_name);
	}
}

?>
