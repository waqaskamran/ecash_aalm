<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Call_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Agent Call Report";

		$this->column_names = array( 
									'agent'                  => 'Agent',
		                            'click_dials'            => 'Clicked Calls Init.',
		                            'click_dials_completed'  => 'Clicked Calls Comp.',
		                            'keypad_dials'           => 'Keypad Calls Init.',
		                            'keypad_dials_completed' => 'Keypad Calls Comp.',
 									'more_dials'             => 'Diff Init.',
 									'more_dials_completed'   => 'Diff Comp.',
		                            'time_spent'             => 'Call Time Total'					
									);

		$this->sort_columns = array(
									'agent',
		                            'click_dials',
		                            'click_dials_completed',
		                            'keypad_dials',
		                            'keypad_dials_completed',
 									'more_dials',
 									'more_dials_completed',
		                            'time_spent'	
									);

		$this->link_columns = array();

		$this->totals       = array( 'company' => array(
		                            					'click_dials'            => Report_Parent::$TOTAL_AS_SUM,
		                            					'click_dials_completed'  => Report_Parent::$TOTAL_AS_SUM,
		                            					'keypad_dials'           => Report_Parent::$TOTAL_AS_SUM,
		                           						'keypad_dials_completed' => Report_Parent::$TOTAL_AS_SUM,
 														'more_dials'             => Report_Parent::$TOTAL_AS_SUM,
 														'more_dials_completed'   => Report_Parent::$TOTAL_AS_SUM                                             
		                            					),
		                             'grand'   => array(
		                            					'click_dials'            => Report_Parent::$TOTAL_AS_SUM,
		                            					'click_dials_completed'  => Report_Parent::$TOTAL_AS_SUM,
		                            					'keypad_dials'           => Report_Parent::$TOTAL_AS_SUM,
		                           						'keypad_dials_completed' => Report_Parent::$TOTAL_AS_SUM,
 														'more_dials'             => Report_Parent::$TOTAL_AS_SUM,
 														'more_dials_completed'   => Report_Parent::$TOTAL_AS_SUM  		                                                 
		                                                )
		                             );
		$this->column_format = array(
		                            'click_dials'            => self::FORMAT_NUMBER,
		                            'click_dials_completed'  => self::FORMAT_NUMBER,
		                            'keypad_dials'           => self::FORMAT_NUMBER,
		                           	'keypad_dials_completed' => self::FORMAT_NUMBER,
 									'more_dials'             => self::FORMAT_NUMBER,
 									'more_dials_completed'   => self::FORMAT_NUMBER 
		                             );

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->agent_list = TRUE;
		$this->wrap_header = FALSE;
		$this->agent_list_include_unassigned = false;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
