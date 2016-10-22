<?php

/**
 * @package Reporting
 * @category Display
 */
class Rouge_Signed_Docs_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Tiff Funded Report";
		$this->column_names       = array( 'application_id'	=> 'Application ID',
						   'app_created'	=> 'Application Created',
		                                   'status_name'	=> 'Current Status',
		                                  );
		$this->column_width 	  = array("app_created" => 200);
		$this->sort_columns       = array('application_id', 'app_created', 'status_name');
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array( 'rows' )
		                                 );
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = "rouge_signed_docs.txt";
		$this->ajax_reporting 	  = true;

		parent::__construct($transport, $module_name);
	}


}

?>
