<?php

/**
 * ACH Batch Report
 * 
 * @package Reporting
 * @category Display
 */
class Achbatch_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "Batch Report";

		$this->column_names       = array( 
				'report_date'                   	=> 'Date',
				'clearing_type'         		=> 'ACH/Card',
				'batch_company_name'         		=> 'Batch Company',
				'debit_num_attempted'           	=> '# Debits',
				'debit_total_attempted'        		=> '$ Debits',
				'credit_num_attempted'         		=> '# Credits',
				'credit_total_attempted'       		=> '$ Credits',
				'file_attempted'   			=> '# File Total',
				'file_total'				=> '$ File Total',
				'net_total'				=> 'Cash Flow (Debts less Credits)',
				'num_returned_debit_actual_day'       	=> '# Debits Returned (batch date)',
				'total_returned_debit_actual_day'     	=> '$ Debits Returned (batch date)',
				'num_returned_credit_actual_day'       	=> '# Credits Returned (batch date)',
				'total_returned_credit_actual_day'     	=> '$ Credits Returned (batch date)',
				'num_returned_actual_day'       	=> '# Total Returned (batch date)',
				'total_returned_actual_day'     	=> '$ Total Returned (batch date)',
				'net_after_returned'        		=> '$ Net Cash Flow after Returns (batch date)',
				'num_returned_debit_adj_day'           	=> '# Debits Returned (post date)',
				'total_returned_debit_adj_day'         	=> '$ Debits Returned (post date)',
				'num_returned_credit_adj_day'           => '# Credits Returned (post date)',
				'total_returned_credit_adj_day'         => '$ Credits Returned (post date)',
				'num_returned_adj_day'           	=> '# Total Returned (post date)',
				'total_returned_adj_day'         	=> '$ Total Returned (post date)',
				'net_after_returned_adj_day'        	=> '$ Net Cash Flow after Returns (post date)',
		);
		$this->column_format = array( 
				'report_date' 				=> self::FORMAT_DATE,
				'credit_total_attempted' 		=> self::FORMAT_CURRENCY,
				'debit_total_attempted' 		=> self::FORMAT_CURRENCY,
				'batch_company_name'			=> self::FORMAT_TEXT,
				'net_total' 				=> self::FORMAT_CURRENCY,
				'file_total' 				=> self::FORMAT_CURRENCY,
				'total_returned_credit_actual_day' 	=> self::FORMAT_CURRENCY,
				'total_returned_credit_adj_day' 	=> self::FORMAT_CURRENCY,
				'total_returned_debit_actual_day' 	=> self::FORMAT_CURRENCY,
				'total_returned_debit_adj_day' 		=> self::FORMAT_CURRENCY,
				'total_returned_actual_day' 		=> self::FORMAT_CURRENCY,
				'total_returned_adj_day' 		=> self::FORMAT_CURRENCY,
				'net_after_returned_adj_day'		=> self::FORMAT_CURRENCY,
				'net_after_returned'			=> self::FORMAT_CURRENCY,
		);


		$this->sort_columns = array( 
				'report_date',
				'clearing_type',
				'batch_company_name',
				'credit_num_attempted',
				'credit_total_attempted',
				'debit_total_attempted',
				'debit_num_attempted',
				'file_attempted',
				'file_total',
				'net_total',
				'num_returned_actual_day',
				'total_returned_actual_day',
				'num_returned_adj_day',
				'total_returned_adj_day',
				'net_after_returned',
				'net_after_returned_adj_day',
				'total_returned_credit_actual_day',
				'total_returned_credit_adj_day',
				'total_returned_debit_actual_day',
				'total_returned_debit_adj_day',
		);
		
		$this->link_columns       = array();
		$this->totals             = array(
			'company' =>
			array( 'credit_num_attempted'        		=> Report_Parent::$TOTAL_AS_SUM,
					'credit_total_attempted'       		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_num_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_total_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'file_attempted'                         => Report_Parent::$TOTAL_AS_SUM,
					'file_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'net_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'num_returned_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_debit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_debit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_debit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returned'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_debit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_credit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_credit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_credit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_credit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returned_adj_day'       		=> Report_Parent::$TOTAL_AS_SUM,
					),

			'grand'   =>
			array( 'credit_num_attempted'        		=> Report_Parent::$TOTAL_AS_SUM,
					'credit_total_attempted'       		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_num_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_total_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'file_attempted'                         => Report_Parent::$TOTAL_AS_SUM,
					'file_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'net_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'num_returned_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_debit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_debit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returned'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_debit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_debit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_credit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_credit_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'num_returned_credit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returned_credit_adj_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returned_adj_day'       		=> Report_Parent::$TOTAL_AS_SUM,
		));
		
				   //$this->report_table_height = 276;
		
		$this->totals_conditions   	= NULL;
		$this->date_dropdown       	= Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->batch_type           	= TRUE;
		$this->ach_batch_company        = TRUE;
		$this->loan_type           	= TRUE;
		$this->download_file_name  	= NULL;
		$this->ajax_reporting 	  	= TRUE;
		
		parent::__construct($transport, $module_name);
	}

	protected function Format_Field( $name, $data, $totals = FALSE, $html = TRUE )
	{
		if ($data == NULL)
			return 0;
		else
			return parent::Format_Field( $name, $data, $totals, $html);
	}

}

?>
