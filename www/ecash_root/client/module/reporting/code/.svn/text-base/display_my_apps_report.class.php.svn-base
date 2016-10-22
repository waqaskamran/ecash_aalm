<?php

/**
 * @package Reporting
 * @category Display
 */
class My_Apps_Report extends Report_Parent
{
    public function __construct(ECash_Transport $transport, $module_name)
    {
        $this->report_title = "My Queue";
        $this->column_names = array(
				'company_name'   => 'Company',
                "application_id" => "Application Id" ,
                'availability'	=>	'Available',
                "name_first" => "First Name" ,
                "name_last" => "Last Name" ,
                "date_expiration" => "Expiration Date" ,
                "date_next_contact" => "Follow-up Date" ,
                "affiliation_area" => "Location" ,
                "follow_up_type" => "Type" ,
                "agent_full_name" => "Controlling Agent" ,
                );
        $this->sort_columns = array(
                "application_id" ,
                "name_first" ,
                "name_last" ,
                "date_expiration" ,
                "date_next_contact" ,
                "affiliation_area" ,
                "agent_full_name" ,
                );
        $this->link_columns = array(
                "application_id" => "?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%" ,
                );
		$this->column_format      = array( 'date_expiration' => self::FORMAT_DATE,
										   'date_next_contact' => self::FORMAT_DATE );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array() );
        $this->totals_conditions = null;
        $this->date_dropdown = null;
        $this->loan_type = true;
        $this->agent_list = TRUE;
        $this->agent_list_include_unassigned = FALSE;
        $this->download_file_name = null;
		$this->my_apps_report  = true; //mantis:5064
		
		// Turns on AJAX Reporting
		$this->ajax_reporting 	  = true;
		
        parent::__construct($transport, $module_name);
    }


	protected function Format_Field( $name, $data, $totals = false, $html = true, &$align = null)
	{
		switch ($name)
		{
			case 'date_expiration':
				/* May be NULL, account for that */
				if ($data == "Never")
					return "Never";
				break;
			default:
				break;
		}

		return parent::Format_Field( $name, $data, $totals, $html, $align );
	}
}

?>
