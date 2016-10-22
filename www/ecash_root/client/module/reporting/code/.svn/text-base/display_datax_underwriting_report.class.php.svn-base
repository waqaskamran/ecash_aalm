<?php

/**
 * @package Reporting
 * @category Display
 */
class Datax_Underwriting_Report extends Report_Parent
{
	protected $dl_ready = FALSE;
	
	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Datax Underwriting Report";

		$this->column_names = array();
		/* No real column names as this report is *download only*
		$this->column_names = array( 	
						'application_id' => 'Application ID',
						'campaign_name' => 'Campaign Name',
						'promo_id' => 'Promo ID',
						'promo_sub_code' => 'Promo Sub-Code',
						'number_interactive' => 'Number Inactive',
						'firstname_lastname' => 'firstname lastname',
						'email' => 'E-mail',
						'zip_code' => 'Zip Code',
						'company'   => 'Company',
						'status' => 'Current Status',
						'date_created' => 'Date Created',
						'fund_date' => 'Funding Date',
						'payoff_date' => 'Payoff Date (Date of last payment in schedule)',
						'income_frequency' => 'Debit Frequency',
						'last_complete' => 'Days since last completed payment',
						'num_failed' => 'Number of failed payments',
						'last_return_fatal' => 'Was last return fatal',
						'fund_actual' => 'Initial loan amount',
						'posted_balance' => 'Loan balance',
						'posted_principal' => 'Principal balance',
						'posted_fee' => 'Fees balance',
						'posted_interest' => 'Interest balance',
						'pending_balance' => 'Pending balance',
						'pending_principal' => 'Pending principal',
						'pending_fee' => 'Pending Fees',
						'pending_interest' => 'Pending Interest',
						'paid_balance' => 'Paid balance',
						'paid_principal' => 'Paid principal',
						'paid_fee' => 'Paid fees',
						'paid_interest' => 'Paid interest',
						'adjustment_total' => 'Manual Adjustments',
						'cc_total' =>  'Credit Cards',
						'second_tier_total' =>  'Second Tier Payments',
						'wire_total' =>  'Wire Transfers',
						'money_order_total' =>  'Money Orders',
						'ach_total' => 'ACH Payments',
						'reattempt_total' => 'Successful Reattempts',
						'payment1' =>  'Payment1',
						'payment2' =>  'Payment2',
						'payment3' =>  'Payment3',
						'payment4' =>  'Payment4',
						'payment5' =>  'Payment5',
						'payment6' =>  'Payment6',
						'payment7' =>  'Payment7',
						'payment8' =>  'Payment8',
						'payment9' =>  'Payment9',
						'payment10' =>  'Payment10',
						'cs_total' =>  'Customer Service Payments',
						'collections_total' =>  'Collection Payments',
					);

		$this->sort_columns = array(	
						'application_id',
						'company',
						'firstname_lastname',
						'status',
						'date_created',
					);

		$this->column_format       = array(						
						'date_created' => self::FORMAT_DATETIME,
                                   		);

		$this->link_columns = array('application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%');

		*/
		
		
		$this->totals_conditions  = NULL;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->company_list		  = FALSE;
		$this->ajax_reporting 	  = FALSE;

		
		$this->dl_ready = ECash::getTransport()->Get_Data()->dl_ready;
		
		parent::__construct($transport, $module_name);

		//do this after to insure $this->search_criteria has been set
		$this->download_file_name = preg_replace("/\s/", "_", $this->report_title) .
			date('Ymd', strtotime($this->search_criteria['specific_date'])) . '.txt';
	}

	/**
	 * Overridden to just show the download link
	 */
	public function Get_Module_HTML_Data()
	{
		$mode = ECash::getTransport()->page_array[2];


		$substitutions = new stdClass();

		$substitutions->report_title = $this->report_title;

		// Get the date dropdown & loan type html stuff
		$this->Get_Form_Options_HTML( $substitutions );

		$substitutions->search_message    = "<tr><td>&nbsp;</td></tr>";
		$substitutions->search_result_set = "<tr><td><div id=\"report_result\" class=\"reporting\"></div></td></tr>";

		while (!is_null($next_level = ECash::getTransport()->Get_Next_Level()))
		{
			if($this->dl_ready && $next_level == 'message')
			{
				//turn on the download link
				$substitutions->download_link = "[ <a href=\"?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download Data to CSV File</a> ]";
				//add message
				$substitutions->search_message = "<tr><td class='align_left' style='color: red'>{$this->search_message}</td></tr>\n";
			}
			//standard message
			else if ($next_level == 'message')
			{
				$substitutions->search_message = "<tr><td class='align_left' style='color: red'>{$this->search_message}</td></tr>\n";
			}
			else if ($next_level == 'report_results')
			{
				$message = "No application data was found that meets the specified report criteria.";
				$substitutions->search_message = "<span style=\"color: darkblue\">$message</span>\n";
			}
		}

		return $substitutions;
	}

	public function Download_Data()
	{
		$document = $this->search_results['dl_data'];
		
		$generic_data = ECash::getTransport()->Get_Data();

		if($generic_data->is_upper_case)
			$document = strtoupper($document);
		
		unset($generic_data);

		// html headers
		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: " . strlen($document) . "\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: text/csv\n\n");
		die($document); //insure no further output
	}	
}

?>
