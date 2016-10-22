<?php

/**
 * Loan Actions Report
 * 
 * @package Reporting
 * @category Display
 */
class Loanactions_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "Loan Actions";

		$this->column_names       = array( 
			"application_id"            => "Application ID",
			"is_react"                  => "New/React",
			"name_first"                => "First Name",
			"name_last"                 => "Last Name",
			"phone_home"                => "Home Phone",
			"phone_cell"                => "Cell Phone",
			"phone_work"                => "Work Phone",
			"email"                     => "Email",
			"application_status_name"   => "Application Status",
			"date_created"              => "Application Date",
			"date_fund_actual"          => "Fund Date",
			"loan_action_date"          => "Date of Loan Action",
			"loan_action_description"   => "Loan Action Description",
			"comment"                   => "Comment",
			"agent_name"                => "Agent Name",
			"income_monthly"            => "Monthly Income",
			"income_source"             => "income Type",
			"income_direct_deposit"     => "Direct Deposit",
			"employer"                  => "Employer Name",
			"income_frequency"          => "Income Frequency",
			"week_1"                    => "Week 1",
			"week_2"                    => "Week 2",
			"day_of_week"               => "Day of Week",
			"day_of_month_1"            => "Day of Month 1",
			"day_of_month_2"            => "Day of Month 2",
			"campaign_name"             => "Campagn",
			"promo_id"                  => "Promo ID"

		);
		$this->column_format = array( 
				'date_created' 				=> self::FORMAT_DATE,
				'loan_action_date' 			=> self::FORMAT_DATE,
				'income_monthly' 			=> self::FORMAT_CURRENCY,
		);


		$this->sort_columns = array( 
			"application_id",
			"is_react",
			"name_first",
			"name_last",
			"phone_home",
			"phone_cell",
			"phone_work",
			"email",
			"application_status_name",
			"date_created",
			"date_fund_actual",
			"loan_action_date",
			"loan_action_description",
			"comment",
			"agent_name",
			"income_monthly",
			"income_source",
			"income_direct_deposit",
			"employer",
			"income_frequency",
			"week_1",
			"week_2",
			"day_of_week",
			"day_of_month_1",
			"day_of_month_2",
			"campaign_name",
			"promo_idame",
			"promo_idame",
			"promo_id",    
		);
		
		$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );
		$this->totals             = array(
			'company' => array('rows'),
			'grand'   => array('rows')
			);
		
				   //$this->report_table_height = 276;
		
		$this->totals_conditions   	= FALSE;
		$this->date_dropdown       	= Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           	= FALSE;
		$this->download_file_name  	= FALSE;
		$this->ajax_reporting 	  	= TRUE;
		
		parent::__construct($transport, $module_name);
	}

	// Get rid of the empty totals line in the csv file report
	protected function Get_Company_Total_Line($company_name, $company_totals)
	{
		return '';
	}
}
?>
