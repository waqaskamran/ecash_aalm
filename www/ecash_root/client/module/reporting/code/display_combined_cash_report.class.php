<?php

require_once(CLIENT_MODULE_DIR . "reporting/code/display_daily_cash_report.class.php");

/**
 * @package Reporting
 * @category Display
 */
class Combined_Cash_Report extends Daily_Cash_Report
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$this->report_title       = "Combined Cash Report";
		$this->column_names       = array();

		$this->sort_columns       = array();

		$this->link_columns       = array();

		$this->totals             = array();

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = false;
		$this->download_file_name = "combined_cash_report-".date('Y-m-d').".pdf";
		$this->company_list = FALSE;

		
		$this->data->filename = ECash::getTransport()->Get_Data()->filename;
	}
	
	public function Get_Header_HTML() {
		$html = '';
		
		$html  = '<table style="width: 100%"><tr>';
		$html .= '<td style="text-align: left" class="header">Combined Companies</td>';
		$html .= '<td style="text-align: right" class="header">'.htmlentities($this->search_results['date']).'</td>';
		$html .= '</tr></table>';
		
		return $html;
	}
		
	public function Get_Module_HTML()
	{
		//echo "<!-- Module Name: " . ECash::getTransport()->page_array[2] . " -->\n";
		$mode = ECash::getTransport()->page_array[2];
		$form = new Form ( CLIENT_MODULE_DIR . "/reporting/view/display_combined_cash_report.html");

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
				$substitutions->download_link = "[ <a href=\"http://".MASTER_DOMAIN."/?module=reporting&mode=" . urlencode($mode) . "&action=download_report\" class=\"download\">Download PDF File</a> ]<br/>";
				$substitutions->email_link = "";
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
	
}

?>
