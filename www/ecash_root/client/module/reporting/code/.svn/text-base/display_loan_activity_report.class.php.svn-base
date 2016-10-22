<?php

/**
 * @package Reporting
 * @category Display
 */
class Loan_Activity_Report extends Report_Parent
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

		$this->report_title       = "Loan Activity Report";

		$this->column_names       = array( 'company'					=> 'Company',
										   'payment_date'				=> 'Payment Date',
										   'fund_date'                  => 'Fund Date',
										   'application_id' 			=> 'Application ID',
										   'last_name'                  => 'Last Name',
										   'first_name'                 => 'First Name',
										   'ach_id'                     => 'ACH ID',
										   'trans_id'					=> 'Transaction ID',
										   'original_loan_amount'       => 'Original Loan Amount',
										   'payoff_amount'              => 'Payoff Amount',
										   'tran_amount'				=> 'Transaction Amount',
										   't_type'                     => 'Transaction Type',
										   'c_or_d'						=> 'Credit/Debit',
										   'status'						=> 'Current Status',
										   'clearing_type' => 'Clearing Type',
										   'ach_provider' => 'Ach Provider',
										   'agent_name'					=> 'Agent Name',
										   'new_vs_react'				=> 'New/React',
										   'application_status'			=> 'Application Status' );
										
		$this->column_format       = array( 'payment_date'				=> self::FORMAT_DATE,
											'fund_date'					=> self::FORMAT_DATE,
											'application_id'			=> self::FORMAT_ID,
											'ach_id'                    => self::FORMAT_ID,
											'trans_id'					=> self::FORMAT_ID,
											'original_loan_amount'		=> self::FORMAT_CURRENCY,
											'tran_amount'				=> self::FORMAT_CURRENCY,
											'payoff_amount'				=> self::FORMAT_CURRENCY );

		$this->sort_columns       = array(	'payment_date',	
											'fund_date', 
											'application_id',
											'last_name', 
											'first_name',
											'time_modified',
											'ach_id',
											'trans_id', 
											'original_loan_amount', 
											'tran_amount', 
											'payoff_amount', 
											't_type', 
											'c_or_d',
											'status',
											'clearing_type',
											'ach_provider',
											'agent_name' );

        $this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 			   = array('company' => array('rows') ); //just to show the footer
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = TRUE;
		$this->batch_type           	= TRUE;
		$this->ach_batch_company        = TRUE;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = true;
		parent::__construct($transport, $module_name);
	}

	// Custom Dropdown for this report 
    protected function Get_Form_Options_HTML(stdClass &$substitutions)
    {
        $funding      = ($this->search_criteria['date_type'] == "funding_date")     ? "selected" : "";
        $transaction  = ($this->search_criteria['date_type'] == "transaction_date") ? "selected" : "";

        $substitutions->date_type_list  = '<span>Date Search : </span><span><select name="date_type" size="1" style="width:auto;;"></span>';
		$substitutions->date_type_list .= '<option value="transaction_date"' . $transaction . '>Transaction Date</option>';
        $substitutions->date_type_list .= '<option value="funding_date"'     . $funding . '>Fund Date</option>';
        $substitutions->date_type_list .= '</select>';

		//[#55552] Add transaction type
        $substitutions->transaction_type_list .= '<span>Transaction Type : </span><span><select name="transaction_type" size="1" style="width:auto;;"></span>';
		$substitutions->transaction_type_list .= '<option value="all">All</option>';

		$ttlist = ECash::getFactory()->getReferenceList('TransactionType', NULL, array('company_id' => ECash::getCompany()->company_id));
		foreach($ttlist as $trans_type)
		{
			$substitutions->transaction_type_list .= "<option value=\"{$trans_type->name_short}\">{$trans_type->name}</option>";
		}
		
        $substitutions->transaction_type_list .= '</select>';
		
        return parent::Get_Form_Options_HTML($substitutions);
    }

	/* Sort the csv file report by funding date or payment date */
    public function Download_Data()
    {
        if ($this->search_criteria['date_type'] == 'funding_date')
            $ordercol = "fund_date";
        else
            $ordercol = "payment_date";

        foreach( $this->search_results as $company_name => $company_data )
        {
            $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $ordercol, SORT_ASC);
        }

        parent::Download_Data();
    }

	/* Sort by fund date or payment date for AJAX report */
    public function Download_XML_Data()
    {
		if ($this->search_criteria['date_type'] == 'funding_date')
			$ordercol = "fund_date";
		else
			$ordercol = "payment_date";

        // This is a hack the reporting framework doesn't seem to have a way to specify
        // a default arbitrary sort column in the initial display of the report. Apparently
        // CLK has a new framework so I'm not going to worry about it much now. [benb]
		if ($_REQUEST['sort'] == "undefined")
		{
			foreach( $this->search_results as $company_name => $company_data )
			{
                $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $ordercol, SORT_ASC);
            }
        }

        parent::Download_XML_Data();
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
		$summary = array('total' => array('count' => 0),
						 'payoff' => 0);
		
		foreach( $this->search_results[$company] as $row => $data )
		{			
			if(!is_array($summary[$data['t_type']]))
				$summary[$data['t_type']] = array('count' => 0);
			
			if(!isset($summary[$data['t_type']][$data['c_or_d']]))
				$summary[$data['t_type']][$data['c_or_d']] = 0;

			if(!isset($summary['total'][$data['c_or_d']]))
				$summary['total'][$data['c_or_d']] = 0;
			
			$summary[$data['t_type']][$data['c_or_d']] += $data['tran_amount'];
			$summary[$data['t_type']]['count']++;
			$summary['total'][$data['c_or_d']] += $data['tran_amount'];
			$summary['total']['count']++;

			$summary['payoff'] += $data['payoff_amount'];
		}

		//return '<pre>'. print_r($summary, TRUE). '<pre>';
		return $this->Output_Totals($summary, $company, $html);
		
	}

	private function Output_Totals( $summary, $company, $html = TRUE )
	{

		$total = $summary['total'];
		unset($summary['total']);
		$payoff = $summary['payoff'];
		unset($summary['payoff']);
		
		$negative = $payoff < 0;
		
		if( $html === true )
		{
			$output   = "
<table cellpadding=\"0\" class=\"report_company_foot\" width=\"50%\">\n
<tr><th>".strtoupper($company)."</th><th>Count</th><th>Debit</th><th>Credit</th></tr>\n";
			foreach($summary as $ttype => $amounts)
			{
				$output .= '<tr><td>' . $ttype . '</td><td>' .
					(empty($amounts['count']) ? 0 : $amounts['count']) . '</td>' .
					(empty($amounts['Debit']) ? '<td>\\$0.00</td>' : '<td style="color:red;">(\\$' . number_format( abs($amounts['Debit']), 2, '.', ',' ) . ')</td>') .
					(empty($amounts['Credit']) ? '<td>\\$0.00' : '<td>\\$' . number_format( $amounts['Credit'], 2, '.', ',' )) . "</td></tr>\n";
			}
			$output .= '<tr><td>Total</td><td>' .
				(empty($total['count']) ? 0 : $total['count']) . '</td>' .
				(empty($total['Debit']) ? '<td>\\$0.00</td>' : '<td style="color:red;">(\\$' . number_format( abs($total['Debit']), 2, '.', ',' ) . ')</td>') .
				(empty($total['Credit']) ? '<td>\\$0.00' : '<td>\\$' . number_format( $total['Credit'], 2, '.', ',' )) . "</td></tr>\n";


			$output .= '<tr><td colspan="4"></td><tr><td colspan="4"'.($negative ? ' style="color:red;"' : '')
				.'>Payoff Total ' . ($negative ? '(' : '') . '\\$' . number_format(abs($payoff), 2, '.', ',') . ($negative ? ')' : '') . "</td></tr></table>\n";
			return $output;
		}
		else
		{
			$output          = "\nSummary\n";
			$output         .= "Transaction Type\tCount\tCredit\tDebit\n";
			foreach($summary as $ttype => $amounts)
			{
				$output .= $ttype . "\t" .
					(empty($amounts['count']) ? 0 : $amounts['count']) . "\t" .
					(empty($amounts['Debit']) ? '$0.00' : '($' . number_format( abs($amounts['Debit']), 2, '.', ',' ) . ')') . "\t" .
					(empty($amounts['Credit']) ? '$0.00' : '$' . number_format( $amounts['Credit'], 2, '.', ',' )) . "\n";				
			}
			$output .= "Total\t" .
				(empty($total['count']) ? 0 : $total['count']) . "\t" .
				(empty($total['Debit']) ? '$0.00' : '($' . number_format( abs($total['Debit']), 2, '.', ',' ) . ')') . "\t" .
				(empty($total['Credit']) ? '$0.00' : '$' . number_format( $total['Credit'], 2, '.', ',' )) . "\n";


			$output .= "Payoff Total\t" . ($negative ? '(' : '') . '$' . number_format(abs($payoff), 2, '.', ',') . ($negative ? ')' : '') . "\n";

			return $output;
		}
	}
}

?>
