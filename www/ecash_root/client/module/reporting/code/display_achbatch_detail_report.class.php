<?php

/**
 * @package Reporting
 * @category Display
 */
class Achbatch_Detail_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Batch Detail Report";

		$this->column_names = array(
			'ach_batch_id' => 'Batch ID',
			'ach_date' => 'Batch Date',
			'application_id' => 'Application ID',
			'name_first' => 'First Name',
			'name_last' => 'Last Name',
			'bank_aba' => 'ABA #',
			'bank_last4' => 'Acct. # (last 4)',
			'ach_status' => 'Status',
			'ach_type' => 'Credit/Debit',
			'amount' => 'Amount',
			'return_code' => 'Return Code',
			'return_date' => 'Return Date',
			'clearing_type' => 'ACH/Card',
			'ach_provider' => 'ACH Provider'
		);

		$this->column_format = array( 'ach_date' => self::FORMAT_DATE,
			'amount' => self::FORMAT_CURRENCY,
			'return_date' => self::FORMAT_DATE,
		);


		$this->sort_columns = array(
			'ach_batch_id',
			'ach_date',
			'application_id',
			'name_first',
			'name_last',
			'bank_aba',
			'bank_last4',
			'ach_type',
			'ach_status',
			'amount',
			'return_code',
			'return_date',
			'clearing_type',
			'ach_provider',
		);
		
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array('company' => array('rows') ); //just to show the footer
		$this->totals_conditions   = null;
		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = FALSE;
		$this->batch_type		= TRUE;
		$this->ach_batch_company	= TRUE;
		$this->download_file_name  = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	// Get rid of the empty totals line in the csv file report
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		return $this->Get_Company_Foot($company_name, FALSE);
	}

	/**
	* Additional info for the bottom of the company
	*
	* @param string  $company name of the company currently working
	* @param boolean $html should html code be returned?
	* @return string
	* @access protected
	*/
	protected function Get_Company_Foot($company, $html = TRUE)
	{
		$summary = array();
		/*
By Company
 - By ACH Batch ID
   - Total # Credit Transactions
   - Total $ Credit Transactions
   - Total # Debit Transactions
   - Total $ Debit Transactions
		*/
		
		foreach( $this->search_results[$company] as $row => $data )
		{			
			if(!is_array($summary[$data['ach_batch_id']]))
				$summary[$data['ach_batch_id']] = array('credit_num' => 0,
														'credit_amount' => 0,
														'debit_num' => 0,
														'debit_amount' => 0);

			if($data['ach_type'] == 'credit')
			{
				$summary[$data['ach_batch_id']]['credit_num']++;
				$summary[$data['ach_batch_id']]['credit_amount'] += $data['amount'];
			}
			else if($data['ach_type'] == 'debit')
			{
				$summary[$data['ach_batch_id']]['debit_num']++;
				$summary[$data['ach_batch_id']]['debit_amount'] += $data['amount'];
			}
		}

		//return '<pre>'. print_r($summary, TRUE). '<pre>';
		return $this->Output_Totals($summary, $company, $html);
		
	}

	private function Output_Totals( $summary, $company, $html = TRUE )
	{
		if( $html === true )
		{
			$output   = "
<tr><td>
<table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n
<tr><th colspan=\"5\">".strtoupper($company)."</th></tr>\n
<tr><th>Batch ID</th><th>Total # Credit</th><th>Total $ Credit</th><th>Total # Debit</th><th>Total $ Debit</th></tr>\n";
			foreach($summary as $batch_id => $totals)
			{
				$output .= "<tr><td>{$batch_id}</td><td>{$totals['credit_num']}</td>" .
					'<td>\\$' . number_format( $totals['credit_amount'], 2, '.', ',') . '</td>' .
					"<td>{$totals['debit_num']}</td>" .
					(empty($totals['debit_amount']) ? '<td>\\$0.00</td>' : '<td style="color:red;">(\\$' . number_format( abs($totals['debit_amount']), 2, '.', ',' ) . ')</td>') .
				    "</tr>\n";
			}

			$output .= "</table></td></tr>\n";
			return $output;
		}
		else
		{
			$output          = "\n" . strtoupper($company) . " Summary\n";
			$output         .= "Batch ID\tTotal # Credit\tTotal $ Credit\tTotal # Debit\tTotal $ Debit\n";
			foreach($summary as $batch_id => $totals)
			{
				$output .= $batch_id . "\t" . $totals['credit_num'] . "\t" .
					'$' . number_format( $totals['credit_amount'], 2, '.', ',') . "\t" .
					$totals['debit_num'] . "\t" .
					'-$' . number_format( abs($totals['debit_amount']), 2, '.', ',' ) . "\n";
			}

			return $output;
		}
	}
	
}

?>
