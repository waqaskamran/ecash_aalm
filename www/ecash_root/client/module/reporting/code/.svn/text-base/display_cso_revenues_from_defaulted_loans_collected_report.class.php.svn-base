<?php

/**
 * @package Reporting
 * @category Display
 */
class CSO_Revenues_From_Defaulted_Loans_Collected_Report extends Report_Parent
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

		$this->report_title       = "CSO Revenues From Defaulted Loans Collected Report";

		$this->column_names       = array( 'company_name'               => 'Company',
										   'payment_date'               => 'Payment Date',
										   'application_id'             => 'Application ID',
										   'agent'                      => 'Agent',
										   'payment_type'               => 'Payment Type',
										   'payment_amount'             => 'Payment Amount' );
										
		$this->column_format       = array( 'payment_date'              => self::FORMAT_DATE,
											'application_id'            => self::FORMAT_ID,
											'payment_amount'            => self::FORMAT_CURRENCY );

		$this->sort_columns       = array(	'company_name',	
											'payment_date', 
											'application_id',
											'agent', 
											'payment_type',
											'payment_amount'
										);

        $this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 			   = null;
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = true;
		parent::__construct($transport, $module_name);
	}

	// I am overriding this because of the special summaries
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		$html = "";

        foreach ($this->search_results as $company_name => $company_data)
		{
			// Sane starting values
			$ga           = 0;
			$gt           = 0;
			$agents       = array();
			$agent_totals = array();
			$types        = array();
			$type_totals  = array();

			// Add up all the totals
			foreach ($company_data as $key => $value)
			{
				$agent  = $value['agent'];
				$amount = $value['payment_amount'];
				$type   = $value['payment_type'];

				if (!isset($agents[$agent]))
					$agents[$agent] = 0;

				$agents[$agent]++;
				
				if (!isset($agent_totals[$agent]))
					$agent_totals[$agent] = 0;
	
				$agent_totals[$agent] += $amount;
			
				if (!isset($types[$type]))
					$types[$type] = 0;

				$types[$type]++;

				if (!isset($type_totals[$type]))
					$type_totals[$type] = 0;

				$type_totals[$type] += $amount;
		
				$ga++;
				$gt += $amount;
				
			}

			// HEADERS
			$tsv .= "\n";
			$tsv .= "Company\t";
			$tsv .= "Rows\t";
			$tsv .= "Agent\t";
			$tsv .= "Total\n";


			// Make the agents summary
			foreach($agent_totals as $agent => $total)
			{
				$tsv .= "{$company_name}\t";
				$tsv .= "{$agents[$agent]}\t";
				$tsv .= "{$agent}\t";
				$tsv .= '$' . money_format('%#6n', $agent_totals[$agent]) . "\n";
			}

			// HEADERS
			$tsv .= "Company\t";
			$tsv .= "Rows\t";
			$tsv .= "Type of Payment\t";
			$tsv .= "Total\n";

			// Make the types summary
			foreach($type_totals as $type => $total)
			{
				$tsv .= "{$company_name}\t";
				$tsv .= "{$types[$type]}\t";
				$tsv .= "{$type}\t";
				$tsv .= '$' . money_format('%#6n', $type_totals[$type]) . "\n";
			}

			// HEADERS
			$tsv .= "Company\t";
			$tsv .= "Rows\t\t";
			$tsv .= "Total\n";

			$tsv .= "{$company_name}\t";
			$tsv .= "{$ga}\t\t";
			$tsv .= '$' . money_format('%#6n', $gt) . "\t";
		}

		return $tsv;
	}


	// I am overriding this because of the special summaries
	protected function Get_Total_HTML($company_name, &$company_totals)
	{
		$html = "";

        foreach ($this->search_results as $company_name => $company_data)
		{
			// Sane starting values
			$ga           = 0;
			$gt           = 0;
			$agents       = array();
			$agent_totals = array();
			$types        = array();
			$type_totals  = array();

			// Add up all the totals
			foreach ($company_data as $key => $value)
			{
				$agent  = $value['agent'];
				$amount = $value['payment_amount'];
				$type   = $value['payment_type'];

				if (!isset($agents[$agent]))
					$agents[$agent] = 0;

				$agents[$agent]++;
				
				if (!isset($agent_totals[$agent]))
					$agent_totals[$agent] = 0;
	
				$agent_totals[$agent] += $amount;
			
				if (!isset($types[$type]))
					$types[$type] = 0;

				$types[$type]++;

				if (!isset($type_totals[$type]))
					$type_totals[$type] = 0;

				$type_totals[$type] += $amount;
		
				$ga++;
				$gt += $amount;
				
			}

			// HEADERS
			$html .= "<tr>\n";
			$html .= "  <th class='report_head'>Company</th>\n";
			$html .= "  <th class='report_head'>Rows</th>\n";
			$html .= "  <th class='report_head'>Agent</th>\n";
			$html .= "  <th class='report_head'>Total</th>\n";
			$html .= "  <th class='report_head' colspan=2>&nbsp;</th>\n";
			$html .= "</tr>\n";


			// Make the agents summary
			foreach($agent_totals as $agent => $total)
			{
				$html .= "<tr>\n";
				$html .= "  <th class='report_foot'>$company_name</th>\n";
				$html .= "  <th class='report_foot'>" . $agents[$agent] . "</th>\n";
				$html .= "  <th class='report_foot'>{$agent}</th>\n";
				$html .= "  <th class='report_foot'>\$" . money_format('%#6n', $agent_totals[$agent]) . "</th>\n";
				$html .= "  <th class='report_foot' colspan=2>&nbsp;</th>\n";
				$html .= "</tr>\n";
			}

			// HEADERS
			$html .= "<tr>\n";
			$html .= "  <th class='report_head'>Company</th>\n";
			$html .= "  <th class='report_head'>Rows</th>\n";
			$html .= "  <th class='report_head'>Type of Payment</th>\n";
			$html .= "  <th class='report_head'>Total</th>\n";
			$html .= "  <th class='report_head' colspan=2>&nbsp;</th>\n";
			$html .= "</tr>\n";

			// Make the types summary
			foreach($type_totals as $type => $total)
			{
				$html .= "<tr>\n";
				$html .= "  <th class='report_foot'>$company_name</th>\n";
				$html .= "  <th class='report_foot'>" . $types[$type] . "</th>\n";
				$html .= "  <th class='report_foot'>{$type}</th>\n";
				$html .= "  <th class='report_foot'>\$" . money_format('%#6n', $type_totals[$type]) . "</th>\n";
				$html .= "  <th class='report_foot' colspan=2>&nbsp;</th>\n";
				$html .= "</tr>\n";
			}

			// HEADERS
			$html .= "<tr>\n";
			$html .= "  <th class='report_head'>Company</th>\n";
			$html .= "  <th class='report_head' colspan=2>Rows</th>\n";
			$html .= "  <th class='report_head'>Total</th>\n";
			$html .= "  <th class='report_head' colspan=2>&nbsp;</th>\n";
			$html .= "</tr>\n";

			$html .= "<tr>\n";
			$html .= "  <th class='report_foot'>$company_name</th>\n";
			$html .= "  <th class='report_foot' colspan=2>{$ga}</th>\n";
			$html .= "  <th class='report_foot'>\$" . money_format('%#6n', $gt) . "</th>\n";
			$html .= "  <th class='report_foot' colspan=2>&nbsp;</th>\n";
			$html .= "</tr>\n";
		}

		return $html;
	}
}

?>
