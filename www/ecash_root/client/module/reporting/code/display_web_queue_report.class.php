<?php

/**
 * @package Reporting
 * @category Display
 */
class Web_Queue_Report extends Report_Parent
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

		$this->report_title       = "Web Queue Summary Report";

		$this->column_names       = array( 'order' 			=> '#',
										   'time_in_queue' 	=> 'Hours',
										   'application_id' => 'Application ID',
										   'name_first'		=> 'First Name',
										   'name_last'		=> 'Last Name',
		                                   'street' 		=> 'Address',
		                                   'city'  			=> 'City',
		                                   'state'  		=> 'State',
		                                   'balance'		=> 'Balance',
										   'status'			=> 'Status',
										   'api_event_name' => 'Event',										   
										   'api_amount'		=> 'Amount',
										   );

		$this->sort_columns       = array( 'order',			'time_in_queue',	'application_id',	
											'name_first',	'name_last',    	
											'street',  		'city',         	'state',
											'api_amount', 'api_event_name','balance','status');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_NONE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->queue_dropdown = true;

		// We cant use All companies for this report
		$this->company_list_no_all = TRUE;
		$this->ajax_reporting 	   = true;
		
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
			case 'ssn':
				return(substr($data,0,3)."-".substr($data,3,2)."-".substr($data,5));
			case 'name_first':
			case 'name_last':
			case 'status':
			case 'street':
			case 'city':
				return ucwords($data);
			case 'state':
				return strtoupper($data);
			case 'balance':
			case 'api_amount':
				if ($data == 'N/A')
					return $data;

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
