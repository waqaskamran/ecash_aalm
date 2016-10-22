<?php

require_once( LIB_DIR . "common_functions.php");

/**
 * @package Reporting
 * @category Display
 */
class Daily_Cash_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Daily Cash Report";
		$this->column_names       = array();

		$this->sort_columns       = array();

		$this->link_columns       = array();

		$this->totals             = array();

		$this->totals_conditions  = null;
		$this->date_dropdown = Report_Parent::$DATE_DROPDOWN_RANGE;
		//$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = false;
		$this->download_file_name = "daily_cash_report-".date('Y-m-d').".pdf";
		$this->company_list_no_all = TRUE;

		parent::__construct($transport, $module_name);
		$this->data->filename = ECash::getTransport()->Get_Data()->filename;


	}

	public function Get_Module_HTML()
	{
		//echo "<!-- Module Name: " . ECash::getTransport()->page_array[2] . " -->\n";
		$mode = ECash::getTransport()->page_array[2];
		$form = new Form ( CLIENT_MODULE_DIR . "/reporting/view/display_daily_cash_report.html");

		// substitutions to make in the html template
		$substitutions = new stdClass();

		$substitutions->report_title = $this->report_title;
		$this->Get_Form_Options_HTML( $substitutions );

		$substitutions->search_message    = "<tr><td>&nbsp;</td></tr>";
		$substitutions->search_result_set = "<tr><td><div id=\"report_result\" class=\"reporting\"></div></td></tr>";
		while( ! is_null($next_level = ECash::getTransport()->Get_Next_Level()) )
		{
			if( $next_level == 'message' )
			{
				$substitutions->search_message = "<tr><td class='align_left' style='color: red'>{$this->search_message}</td></tr>\n";
			}
			else if( $next_level == 'report_results' && count($this->search_results))
			{
				$master_domain = ECash::getConfig()->MASTER_DOMAIN;
				$substitutions->download_link = "[ <a href=\"http://$master_domain/?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download PDF File</a> ]<br/>";
				//Commenting out email link because of multiple windows opening in the report tab issues.  If somebody complains, we can spend time properly implementing it.
				//$substitutions->email_link = "[ <a href=\"javascript:Display_Daily_Cash_Report_Email_Form('$master_domain')\" class=\"download\">Email PDF File</a> ]";
				if (count($this->search_results)) 
				{
				$substitutions->search_result_set = '<div style="background: #EEEEEE">' .
					$this->Get_Header_HTML() .
					$this->Get_Balance_Table_HTML() .
					$this->Get_Future_Items_HTML() .
					$this->Get_Monthly_Data_HTML() .
					'</div>';
				} 
				else 
				{
					$message = "A Daily Cash Report has not been generated for this date.";
					$substitutions->search_message = "<tr><td class=\"align_left\" style=\"color: darkblue\">$message</td></tr>\n";
				}
			}
			else if( $next_level == 'report_results' )
			{
				$message = "No application data was found that meets the specified report criteria.";
				$substitutions->search_message = "    <tr><td class=\"align_left\" style=\"color: darkblue\">$message</td></tr>\n";
			}
		}

		return $form->As_String($substitutions);
	}
	
	public function Get_Header_HTML() {
		$html = '';
		
		$html  = '<table style="width: 100%"><tr>';
		$html .= '<td style="text-align: left" class="header">'.htmlentities($this->search_results['company_name']).'</td>';
		$html .= '<td style="text-align: right" class="header">'.htmlentities($this->search_results['start_date']).' to '.htmlentities($this->search_results['end_date']).'</td>';
		$html .= '</tr></table>';
		
		return $html;
	}
	
	public function Format_Float($amount) {
		return number_format($amount, 2);
	}
	
	public function Format_Int($amount) {
		return number_format($amount, 0);
	}
	
	public function Format_Percent($percent) {
		return round($percent * 100, 1).'%';
	}
	
	public function Get_Future_Items_HTML() {
		$totals = array(
			'weekly' => 0,
			'bi_weekly' => 0,
			'twice_monthly' => 0,
			'monthly' => 0,
			'totals' => 0
		);
		$rows = '';
		foreach ($this->search_results['future'] as $label => $values) 
		{
			$totals['weekly'] += $values['weekly'];
			$totals['bi_weekly'] += $values['bi_weekly'];
			$totals['twice_monthly'] += $values['twice_monthly'];
			$totals['monthly'] += $values['monthly'];
			$totals['totals'] += array_sum($values);
			if ($label == 'active') continue;
			
			$html_label = strtoupper($label);
			$rows .= <<<END_HTML
				<tr>
					<td class="label">&nbsp;</td>
					<td colspan="2" class="label">{$html_label}</td>
					<td class="highlight b_left b_right b_bottom a_center">
						{$this->Format_Int(array_sum($values))}
					</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future'][$label]['weekly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future'][$label]['bi_weekly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future'][$label]['twice_monthly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future'][$label]['monthly'])}</td>
				</tr>
END_HTML;
		}
		
		
		/**
		 * 					<td class="b_right b_bottom a_center shade">
						{$this->Format_Percent($this->search_results['future']['active']['weekly'] / 
						array_sum($this->search_results['future']['active']))}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$this->Format_Percent($this->search_results['future']['active']['bi_weekly'] / 
						array_sum($this->search_results['future']['active']))}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$this->Format_Percent($this->search_results['future']['active']['twice_monthly'] / 
						array_sum($this->search_results['future']['active']))}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$this->Format_Percent($this->search_results['future']['active']['monthly'] / 
						array_sum($this->search_results['future']['active']))}
		 */
		$future_active = array_sum($this->search_results['future']['active']);
		
		$weekly_active = $this->Format_Percent($future_active ? $this->search_results['future']['active']['weekly'] / 
							$future_active : 0);
		
		$biweekly_active = $this->Format_Percent($future_active ? $this->search_results['future']['active']['bi_weekly'] / 
							$future_active : 0);

		$twice_monthly_active = $this->Format_Percent($future_active ? $this->search_results['future']['active']['twice_monthly'] / 
							$future_active : 0);
							
		$monthly_active = $this->Format_Percent($future_active ? $this->search_results['future']['active']['monthly'] / 
							$future_active : 0);
							
		$html  = <<<END_HTML
			<table cellspacing="0" class="daily_cash">
				<tr>
					<td style="width: 85px;">&nbsp;</td>
					<td style="width: 85px;">&nbsp;</td>
					<td style="width: 85px;">&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td colspan="3" class="label underline">FUTURE ITEMS</td>
					<td colspan="5">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="8">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="3" class="label">CUSTOMERS</td>
					<td class="column_header b_left b_right">TOTAL</td>
					<td class="column_header b_right">WEEKLY</td>
					<td class="column_header b_right">BI-WEEKLY</td>
					<td class="column_header b_right">SEMI-MONTHLY</td>
					<td class="column_header b_right">MONTHLY</td>
				</tr>
				<tr>
					<td class="label">&nbsp;</td>
					<td colspan="2" class="label">ACTIVE</td>
					<td class="highlight b_left b_right b_bottom a_center">
						{$this->Format_Int(array_sum($this->search_results['future']['active']))}
					</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future']['active']['weekly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future']['active']['bi_weekly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future']['active']['twice_monthly'])}</td>
					<td class="b_right b_bottom a_center">{$this->Format_Int($this->search_results['future']['active']['monthly'])}</td>
				</tr>
				<tr>
					<td class="label">&nbsp;</td>
					<td colspan="2" class="label">% OF ACTIVE</td>
					<td class="b_left b_right b_bottom a_center">&nbsp;</td>
					<td class="b_right b_bottom a_center shade">
						{$weekly_active}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$biweekly_active}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$twice_monthly_active}
					</td>
					<td class="b_right b_bottom a_center shade">
						{$monthly_active}
					</td>
				</tr>
				{$rows}
				<tr>
					<td class="label" colspan="2">&nbsp;</td>
					<td class="label">TOTAL</td>
					<td class="highlight b_left b_right b_bottom a_center">{$this->Format_Int($totals['totals'])}</td>
					<td class="highlight b_right b_bottom a_center">{$this->Format_Int($totals['weekly'])}</td>
					<td class="highlight b_right b_bottom a_center">{$this->Format_Int($totals['bi_weekly'])}</td>
					<td class="highlight b_right b_bottom a_center">{$this->Format_Int($totals['twice_monthly'])}</td>
					<td class="highlight b_right b_bottom a_center">{$this->Format_Int($totals['monthly'])}</td>
				</tr>
			</table>
END_HTML;
		return $html;
	}
	
	public function Get_Monthly_Data_HTML() {
		$totals = array(
			'weekly' => 0,
			'bi_weekly' => 0,
			'twice_monthly' => 0,
			'monthly' => 0,
			'totals' => 0
		);
		$rows = '';
		foreach ($this->search_results['period'] as $label => $values) 
		{
			$html_label = strtoupper($label);
			switch ($label) 
			{
				case 'nsf$':
					continue;
				case 'returns':
					$rows .= <<<END_HTML
						<tr>
							<td class="label a_right">{$html_label}</td>
							<td class="b_left b_right b_bottom a_right">{$this->Format_Float($values['span'])}</td>
				<!--			<td class="b_right b_bottom a_right">{$this->Format_Float($values['week'])}</td>
							<td class="b_right b_bottom a_right">{$this->Format_Float($values['month'])}</td>
							<td class="b_right b_bottom b_top a_right highlight">{$this->Format_Percent($values['month'] / max($this->search_results['period']['total debited']['month'], 1))}</td>-->
							<td colspan="2">&nbsp;</td>
						</tr>
END_HTML;
					break;
				case 'net cash collected':
					$rows .= <<<END_HTML
						<tr>
							<td class="label a_right">{$html_label}</td>
							<td class="b_left b_right b_bottom a_right highlight">{$this->Format_Float($this->search_results['period']['total debited']['span'] - $this->search_results['period']['nsf$']['span'])}</td>
							<!-- <td class="b_right b_bottom a_right highlight">{$this->Format_Float($this->search_results['period']['total debited']['week'] - $this->search_results['period']['nsf$']['week'])}</td>
							<td class="b_right b_bottom a_right highlight">{$this->Format_Float($this->search_results['period']['total debited']['month'] - $this->search_results['period']['nsf$']['month'])}</td>-->
							<td colspan="3">&nbsp;</td>
						</tr>
END_HTML;
					break;
				case 'new customers':
				case 'reactivated customers':
				case 'increased customers':
				case 'refunded customers':
				case 'resend customers':
				case 'cancelled customers':
				case 'paid out customers (ach)':
				case 'paid out customers (non-ach)':
					$rows .= <<<END_HTML
						<tr>
							<td class="label a_right">{$html_label}</td>
							<td class="b_left b_right b_bottom a_right">{$this->Format_Int($values['span'])}</td>
							<!-- <td class="b_right b_bottom a_right">{$this->Format_Int($values['week'])}</td>
							<td class="b_right b_bottom a_right">{$this->Format_Int($values['month'])}</td>-->
							<td colspan="3">&nbsp;</td>
						</tr>
END_HTML;
					break;
				//FORBIDDEN ROWS!!!!
				case 'moneygram deposit':
			
					$rows .= '';
					break;
				default:
					$rows .= <<<END_HTML
						<tr>
							<td class="label a_right">{$html_label}</td>
							<td class="b_left b_right b_bottom a_right">{$this->Format_Float($values['span'])}</td>
					<!--    <td class="b_right b_bottom a_right">{$this->Format_Float($values['week'])}</td>
							<td class="b_right b_bottom a_right">{$this->Format_Float($values['month'])}</td>-->
							<td colspan="3">&nbsp;</td>
						</tr>
END_HTML;
					break;
			}
		}
		
		$html  = <<<END_HTML
			<table style="width: 100%" cellspacing="0" class="daily_cash">
				<tr>
					<td style="width: 258px;">&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="width: 85px;">&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class="label">CURRENT PERIOD</td>
					<td class="column_header b_left b_right">PERIOD</td>
					<!--<td class="column_header b_right">WEEK</td>
					<td class="column_header b_right">MONTH</td>-->
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				{$rows}
				<tr>
					<td class="label a_right">ADVANCES IN COLLECTION</td>
					<td class="highlight b_left b_right b_bottom a_right">
						{$this->Format_Float($this->search_results['advances_collections'])}
					</td>
					<td colspan="2">&nbsp;</td>
					<td colspan="2" class="label a_right">ACTIVE ADVANCES OUT</td>
					<td class="highlight b_left b_right b_bottom b_top a_right">
						{$this->Format_Float($this->search_results['advances_active'])}
					</td>
				</tr>
			</table>
END_HTML;
		return $html;
	}
	
	public function Get_Balance_Table_HTML() 
		{
		$html  = <<<END_HTML
			<table style="width: 100%" cellspacing="0" class="daily_cash">
				<tr>
					<td colspan="4">&nbsp</td>
					<td class="column_header b_left b_right">OPERATING ACCOUNT</td>
					<td class="column_header b_right">RETURNS</td>
				</tr>
				<tr>
					<td class="label underline" colspan="3">BEGINNING CHECKING BALANCE</td>
					<td style="width: 200px">&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right a_right">{$this->Format_Float($this->search_results['intercept_reserve'])}</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<tr>
					<td class="label underline">DEPOSITS</td>
					<td colspan="2">DEPOSITS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['total debited']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">CREDIT CARD PAYMENTS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['credit card payments']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">CHARGEBACKS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['chargebacks']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">WESTERN UNION PAYMENTS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['western union deposit']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">MONEY ORDERS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['money order deposit']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">QUICK CHECK DEPOSIT</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['quick check deposit']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">MONEYGRAM</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['moneygram deposit']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">CRSI RECOVERY</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['crsi recovery']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">RECOVERY</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['pinion recovery']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">FINAL COLLECTIONS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['final collections']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">DEBIT RETURNS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right a_right">{$this->Format_Float($this->search_results['period']['debit returns']['span'])}</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">CREDIT RETURNS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right a_right">{$this->Format_Float($this->search_results['period']['credit returns']['span'])}</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2" class="label underline">TOTAL RECEIPTS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right highlight a_right">{$this->Format_Float($this->search_results['period']['debit returns']['span'] + $this->search_results['period']['credit returns']['span'])}</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<tr>
					<td class="label underline">DISBURSEMENTS</td>
					<td colspan="2">LOANS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['period']['loan disbursement']['span'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>
				<!--<tr>
					<td>&nbsp;</td>
					<td colspan="2">CARD LOANS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right a_right">{$this->Format_Float($this->search_results['card_loan_disbursement'])}</td>
					<td class="b_right">&nbsp;</td>
				</tr>-->
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">DEBIT RETURNS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right highlight a_right">{$this->Format_Float($this->search_results['period']['debit returns']['span'])}</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">CREDIT RETURNS</td>
					<td>&nbsp;</td>
					<td class="b_left b_right">&nbsp;</td>
					<td class="b_right highlight a_right">{$this->Format_Float($this->search_results['period']['credit returns']['span'])}</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
					<td class="b_left b_right b_bottom">&nbsp;</td>
					<td class="b_right b_bottom">&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2" class="label underline">TOTAL DISBURSED</td>
					<td>&nbsp;</td>
					<td class="b_left b_right highlight a_right">{$this->Format_Float($this->search_results['period']['loan disbursement']['span'])}</td>
					<td class="b_right highlight a_right">{$this->Format_Float($this->search_results['period']['returns']['span'])}</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
					<td class="b_left b_right b_bottom">&nbsp;</td>
					<td class="b_right b_bottom">&nbsp;</td>
				</tr>
				<tr>
					<td class="label underline" colspan="2">ENDING CHECKING BALANCE</td>
					<td colspan="2">&nbsp;</td>
					<td class="b_left b_right b_bottom">&nbsp;</td>
					<td class="b_right highlight b_bottom a_right">{$this->Format_Float($this->search_results['intercept_reserve'])}</td>
				</tr>
			</table>
END_HTML;
		return $html;
	}

	public function Download_Data()
	{
		$file = $this->data->filename;
		$dl_data = file_get_contents($file);
		// for the html headers
		$data_length = strlen($dl_data);

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: application/pdf\n\n");

		echo $dl_data;
	}
}

?>
