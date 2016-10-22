<?php

/**
 * @package Reporting
 * @category Display
 */
class Performance_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Agent Actions Report";

		// GF 12733: Changed some of the column names to better represent what the column represents. [benb]
		// GF 17374: Added Unsigned Queue to report. [kb]
		$this->column_names       = array( 
				'company_name'			        => 'Company',
				'agent_name'                    => 'Agent',
				'num_unsigned_new'              => 'Unsigned (New)',
			    'num_unsigned_react'            => 'Unsigned (React)',
				'num_put_in_verify_new'         => 'Verify Queue (New)',
				'num_put_in_verify_react'       => 'Verify Queue (React)',
				'num_put_in_addl'               => 'Verify Additional',
				'num_pull_addl'                 => 'Received Additional',
				'num_put_in_underwriting_new'   => 'UW Queue (New)',
				'num_put_in_underwriting_react'	=> 'UW Queue (React)',
				'num_pull_verify_new'           => 'Received Verify (New)',
				'num_pull_verify_react'         => 'Received Verify (React)',
				'num_approved_new'              => 'Approved (New)',
				'num_approved_react'            => 'Approved (React)',
				'num_pull_underwriting_new'     => 'Received UW (New)',
				'num_pull_underwriting_react'   => 'Received UW (React)',
				'num_reverified_new'            => 'Reverified (New)',
				'num_reverified_react'          => 'Reverified (React)',
				'num_funded_new'                => 'Funded (New)',
				'num_funded_dupe'               => 'Funded (Dupl.)',
				'num_funded_react'              => 'Funded (React)',
				'num_withdrawn_new'             => 'Withdrawn (New)',
				'num_withdrawn_react'           => 'Withdrawn (React)',
				'num_denied_new'                => 'Denied (New)',
				'num_denied_react'              => 'Denied (React)',
				'num_follow_up_new'             => 'Follow Up (New)',
				'num_follow_up_react'           => 'Follow Up (React)',
				'num_search_loan_servicing'     => 'Search (Servicing)',
				'num_search_collections'        => 'Search (Collections)',
				'num_search_funding'            => 'Search (Funding)',
		);

		$this->sort_columns       = array( 'agent_name',          
				'num_approved_new',
				'num_approved_react',
				'num_unsigned_new',
				'num_unsigned_react',
				'num_pull_underwriting_new',
				'num_pull_underwriting_react',
				'num_funded_new',
				'num_funded_dupe',
				'num_funded_react',
				'num_withdrawn_new',
				'num_withdrawn_react',
				'num_denied_new',
				'num_denied_react',
				'num_reverified_new',
				'num_reverified_react',
				'num_pull_verify_new',
				'num_pull_verify_react',
				'num_follow_up_new',
				'num_follow_up_react',
				'num_put_in_verify_new',
				'num_put_in_verify_react',
				'num_put_in_underwriting_new',
			   'num_put_in_underwriting_react',
			    'num_search_loan_servicing',
			    'num_search_collections',
				'num_search_funding',
					);
		$this->link_columns       = array();
		$this->totals             = array( 'company' => array( 'num_approved_new'        		=> Report_Parent::$TOTAL_AS_SUM,
					'num_approved_react'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_unsigned_new'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_unsigned_react'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_pull_underwriting_new' 		=> Report_Parent::$TOTAL_AS_SUM,
					'num_pull_underwriting_react' 		=> Report_Parent::$TOTAL_AS_SUM,
					'num_funded_new'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_funded_dupe'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_funded_react'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_withdrawn_new'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_withdrawn_react'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_denied_new'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_denied_react'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_reverified_new'      		=> Report_Parent::$TOTAL_AS_SUM,
					'num_reverified_react'      		=> Report_Parent::$TOTAL_AS_SUM,
					'num_pull_verify_new'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_pull_verify_react'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_follow_up_new'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_follow_up_react'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_put_in_underwriting_new'	=> Report_Parent::$TOTAL_AS_SUM,
					'num_put_in_underwriting_react'	=> Report_Parent::$TOTAL_AS_SUM,
					'num_put_in_verify_new'       	=> Report_Parent::$TOTAL_AS_SUM,
					'num_put_in_verify_react'       	=> Report_Parent::$TOTAL_AS_SUM,
					'num_put_in_addl' 				=> Report_Parent::$TOTAL_AS_SUM,
				    'num_pull_addl' 				=> Report_Parent::$TOTAL_AS_SUM,
				    'num_search_loan_servicing'		=> Report_Parent::$TOTAL_AS_SUM,
				    'num_search_collections' 		=> Report_Parent::$TOTAL_AS_SUM,
				    'num_search_funding' 			=> Report_Parent::$TOTAL_AS_SUM,
															   ),

					'grand'   => array( 'num_approved_new'        		=> Report_Parent::$TOTAL_AS_SUM,
							'num_approved_react'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_unsigned_new'                  => Report_Parent::$TOTAL_AS_SUM,
							'num_unsigned_react'                => Report_Parent::$TOTAL_AS_SUM,
							'num_pull_underwriting_new' 		=> Report_Parent::$TOTAL_AS_SUM,
							'num_pull_underwriting_react' 		=> Report_Parent::$TOTAL_AS_SUM,
							'num_funded_new'          		=> Report_Parent::$TOTAL_AS_SUM,
							'num_funded_dupe'          		=> Report_Parent::$TOTAL_AS_SUM,
							'num_funded_react'          		=> Report_Parent::$TOTAL_AS_SUM,
							'num_withdrawn_new'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_withdrawn_react'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_denied_new'          		=> Report_Parent::$TOTAL_AS_SUM,
							'num_denied_react'          		=> Report_Parent::$TOTAL_AS_SUM,
							'num_reverified_new'      		=> Report_Parent::$TOTAL_AS_SUM,
							'num_reverified_react'      		=> Report_Parent::$TOTAL_AS_SUM,
							'num_pull_verify_new'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_pull_verify_react'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_follow_up_new'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_follow_up_react'       		=> Report_Parent::$TOTAL_AS_SUM,
							'num_put_in_underwriting_new'	=> Report_Parent::$TOTAL_AS_SUM,
							'num_put_in_underwriting_react'	=> Report_Parent::$TOTAL_AS_SUM,
							'num_put_in_verify_new'       	=> Report_Parent::$TOTAL_AS_SUM,
							'num_put_in_verify_react'       	=> Report_Parent::$TOTAL_AS_SUM,
							'num_put_in_addl' 				=> Report_Parent::$TOTAL_AS_SUM,
							'num_search_loan_servicing'		=> Report_Parent::$TOTAL_AS_SUM,
							'num_search_collections' 		=> Report_Parent::$TOTAL_AS_SUM,
							'num_search_funding' 			=> Report_Parent::$TOTAL_AS_SUM) );
		//$this->report_table_height = 276;
		$this->totals_conditions   = null;
		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;
		$this->react_type		   = true;
		$this->download_file_name  = null;
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
