<?php

/**
 * @package Reporting
 * @category Display
 */
class Report_Wizard_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Report Wizard Report";
		parent::__construct($transport, $module_name);

	}

	public function Get_Module_HTML()
	{
		$form = new Form( CLIENT_MODULE_DIR . "/reporting/view/display_report_wizard.html" );
		$substitutions = new stdClass();
		// Get the date dropdown & loan type html
		$this->Get_Form_Options_HTML( $substitutions );		
		return($form->As_String($substitutions));
	}
	

}

?>
