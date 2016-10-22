<?php

/**
 * @package Reporting
 * @category Display
 */
class Queue_Overview_Report extends Report_Parent
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

		$this->report_title       = "Queue Overview Report";

		//mantis:7034 - added date_removed
		$this->column_names       = array( 'company_name'			=> 'Company',
											'application_id' 			=> 'Application ID',
										   'queue_name' 	=> 'Queue',
										   'pulling_agent' => 'Pulling Agent',
										   'date_created'		=> 'Date Inserted',
		                                   'date_unavailable' 			=> 'Date Unavailable',
										   'date_removed'		=> 'Date Removed',
										   'total_time'     => 'DD:HH:MM:SS',
										   );

		$this->sort_columns       = array( 'application_id',			'queue_name',	'pulling_agent',	
											'date_created',	 	'date_unavailable', 'date_removed');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

		$this->column_format      = array( 'date_created' => self::FORMAT_DATE,
										   'date_unavailable' => self::FORMAT_DATE,
										   'date_removed' => self::FORMAT_DATE );

        $this->totals = array('company' => array('rows'));
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->queue_dropdown = true;
		$this->ajax_reporting = true;
		// We cant use All companies for this report
		$this->company_list_no_all = FALSE;
		
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
			case 'pulling_agent':
				return ucwords($data);
			case 'date_created':
			case 'date_available':
			case 'date_unavailable':
				if (!empty($data) && is_numeric(strtotime($data))) 
				{
					return date('m/d/Y H:i:s', strtotime($data));
				} 
				else 
				{
					return 'N/A';
				}
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
			default:
				return $data;
		}
	}
}

?>
