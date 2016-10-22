<?php

class Collections_Projected_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "Collections Projected Report";

		// GF 12733: Changed some of the column names to better represent what the column represents. [benb]
		$this->column_names       = array( 
				'amount_delinquient_first_returns'                    => '$ Delinquent First Returns',
				'amount_delinquent_all_else'         => '$ Delinquent All Else',
				'total_delinquent'       => 'Total Delinquent',
				'delinquent_first_returns_attempted'               => '$ Delinquent 1st Returns Attempted',
				'delinquent_all_other_arranged_today'                 => '$ Delinquent All Other Arranged Today',
				'delinquent_previously_arranged'   => '$ Delinquent Previously Arranged',
				'total'	=> 'Total $',
				'projected_cleared'           => 'Projected Cleared $ (35%)',
				
		);
        $this->column_format       = array( 'amount_delinquient_first_returns' => self::FORMAT_CURRENCY,
                        'amount_delinquent_all_else' => self::FORMAT_CURRENCY,
                        'total_delinquent' => self::FORMAT_CURRENCY,
                        'delinquent_first_returns_attempted' => self::FORMAT_CURRENCY,
                        'delinquent_all_other_arranged_today' => self::FORMAT_CURRENCY,
                        'delinquent_previously_arranged' => self::FORMAT_CURRENCY,
                        'total' => self::FORMAT_CURRENCY,
                        'projected_cleared' => self::FORMAT_CURRENCY,
                                                                );


		$this->sort_columns       = array( 'amount_delinquient_first_returns',          
				'amount_delinquent_all_else',
				'total_delinquent',
				'delinquent_first_returns_attempted',
				'delinquent_all_other_arranged_today',
				'delinquent_previously_arranged',
				'total',
				'projected_cleared',
									);
		$this->link_columns       = array();

		//$this->report_table_height = 276;
	//	$this->totals_conditions   = null;
	//	$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
	//	$this->loan_type           = true;
		$this->download_file_name  = null;
		$this->ajax_reporting 	  = true;
		$this->company_list = false;
		parent::__construct($transport, $module_name);
	}

}

?>
