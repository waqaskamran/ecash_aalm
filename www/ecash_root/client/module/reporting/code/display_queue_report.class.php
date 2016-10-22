<?php

/**
 * @package Reporting
 * @category Display
 */
class Queue_Report extends Report_Parent
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

		$this->report_title       = "Queue Summary Report";

		$this->column_names       = array( 'order' 			=> '#',
										   'time_in_queue' 	=> 'Hours',
										   'total_time'     => 'DD:HH:MM:SS',
										   'application_id' => 'Application ID',
										   'submission_date'  => 'Application Date',
										   'last_action_date' => 'Last Action Date',
										   'status'			  => 'Status',
										   'balance'   		  => 'Balance',
										   'availablity' 	=> 'Available',
										   'name_first'		=> 'First Name',
										   'name_last'		=> 'Last Name',
										   'phone_home'       => 'Home Phone',
										   'phone_cell'       => 'Cell Phone',
										   'phone_work'       => 'Work Phone',
		                                   'street' 		=> 'Address',
		                                   'city'  			=> 'City',
		                                   'state'  		=> 'State',
										   'zip_code'		  => 'Zip Code'
										   );

		$this->sort_columns       = array( 'order',			'time_in_queue',	'application_id',	
											'name_first',	'name_last',    	
											'home_phone', 'cell_phone', 'work_phone',
											'street',  		'city',         	'state',
											'balance', 'status');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_NONE;
		$this->loan_type          = false;
		$this->ajax_reporting     = true;
		$this->download_file_name = null;
		$this->queue_dropdown = true;

		// We cant use All companies for this report
		$this->company_list_no_all = false;
				
		parent::__construct($transport, $module_name);
	}

    // Custom Dropdown for this report
    protected function Get_Form_Options_HTML(stdClass &$substitutions)
    {
		$min_hours    = (!empty($this->search_criteria['min_hours'])) ? $this->search_criteria['min_hours'] : "";
		$max_hours    = (!empty($this->search_criteria['max_hours'])) ? $this->search_criteria['max_hours'] : "";

		$substitutions->min_hours_title = '<span>Min. Hours :</span>';
		$substitutions->max_hours_title = '<span>Max. Hours :</span>';

		$substitutions->min_hours_input = '<input type="text" name="min_hours" value="' . $min_hours . '">';
		$substitutions->max_hours_input = '<input type="text" name="max_hours" value="' . $max_hours . '">';
	
        return parent::Get_Form_Options_HTML($substitutions);
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
					return '<div style="text-align:right;">\\$' . number_format($data, 2, '.', ',') . '</div>';
				else
					return '$' . number_format($data, 2, '.', ',');
			case 'time_in_queue':
				$data = round($data / (60 * 60));
				if( $html === true )
					return "<div style='text-align:right;'>$data</div>";
				else
					return $data;
			case 'total_time':
				$time = sprintf('%02d:%02d:%02d:%02d',
								$data / 86400,     //days
								$data / 3600 % 24, //hours
								$data / 60 % 60,   //minutes
								$data % 60);       //seconds
				if( $html === true )
					return "<div style='text-align:right;'>$time</div>";
				else
					return $time;
			case 'order':
				if( $html === true )
					return "<div style='text-align:right;'>$data</div>";
				else
					return $data;
			case 'submission_date':
			case 'last_action_date':
					return date("m/d/Y", strtotime($data));
				break;
			case 'application_id':
			default:
				return $data;
		}
	}

	public function Download_Data()
	{
		foreach( $this->search_results as $company_name => $company_data )
		{
			$this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, "order", SORT_ASC);
		}

		parent::Download_Data();
	}

	public function Download_XML_Data()
	{
		// This is a hack the reporting framework doesn't seem to have a way to specify
		// a default arbitrary sort column in the initial display of the report. Apparently
		// CLK has a new framework so I'm not going to worry about it much now. [benb]
		if ($_REQUEST['sort'] == "undefined")
		{
			foreach( $this->search_results as $company_name => $company_data )
			{
				$this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, "order", SORT_ASC);
			}
		}

		parent::Download_XML_Data();
	}

}

?>
