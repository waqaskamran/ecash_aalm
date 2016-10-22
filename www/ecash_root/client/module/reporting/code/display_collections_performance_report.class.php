<?php

/**
 * @package Reporting
 * @category Display
 */
class Collections_Performance_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Collection Agent Actions Report";
		$this->column_names       = array(
				'company_name'                => 'Company',
				'agent_name'                  => 'Agent',
				'agent_flag'                  => 'Inactive Agent',
				'num_collections_new'         => 'Collections New',
				'num_collections_returned_qc' => 'Collections Returned QC',
				'num_collections_general'     => 'Collections General',
				'num_collections_count'       => 'Received Collections',
				'num_follow_up'               => 'Follow Up',
				'num_uvbankruptcy'            => 'Bankruptcy Notified',
				'num_vbankruptcy'             => 'Bankruptcy Verified',
				'num_arrangements'            => 'Made Arrangements',
				'num_qc_ready'                => 'QC Ready',
				'num_affiliation'             => 'Personal Queue Count',
				'num_search_collections'      => 'Searched'
		);

		$this->sort_columns       = array( 
				'agent_name',        	
				'num_arrangements',
				'num_vbankruptcy', 		
				'num_uvbankruptcy',
				'num_collections_count',     
				'num_to_collections',
				'num_qc_ready',       	
				'num_follow_up', 
				'num_collections_new' , 	
				'num_collections_returned_qc',
				'num_collections_general', 	
				'agent_flag',
				'num_affiliation',
				'num_searched_collections'
		);
		
		//GF #18422
		$this->column_width = array(
				'num_affiliation' => '150'
		);

		$this->link_columns       = array();
		$this->totals             = array( 
					'company' => array( 
						'num_arrangements'            => Report_Parent::$TOTAL_AS_SUM,
						'num_vbankruptcy'             => Report_Parent::$TOTAL_AS_SUM,
						'num_uvbankruptcy'            => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_count'       => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_returned_qc' => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_general'     => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_new'         => Report_Parent::$TOTAL_AS_SUM,
						'num_qc_ready'                => Report_Parent::$TOTAL_AS_SUM,
						'num_follow_up'               => Report_Parent::$TOTAL_AS_SUM,
						'num_affiliation'             => Report_Parent::$TOTAL_AS_SUM,
						'num_searched_collections'    => Report_Parent::$TOTAL_AS_SUM,
						'agent_name'                  => Report_Parent::$TOTAL_AS_COUNT
					),
					'grand'   => array( 
						'num_arrangements'            => Report_Parent::$TOTAL_AS_SUM,
						'num_vbankruptcy'             => Report_Parent::$TOTAL_AS_SUM,
						'num_uvbankruptcy'            => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_count'       => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_returned_qc' => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_general'     => Report_Parent::$TOTAL_AS_SUM,
						'num_collections_new'         => Report_Parent::$TOTAL_AS_SUM,		                                                       
						'num_qc_ready'                => Report_Parent::$TOTAL_AS_SUM,
						'num_follow_up'               => Report_Parent::$TOTAL_AS_SUM,
						'num_affiliation'             => Report_Parent::$TOTAL_AS_SUM,
						'num_searched_collections'    => Report_Parent::$TOTAL_AS_SUM,
					)
		);

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;

		parent::__construct($transport, $module_name);
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
