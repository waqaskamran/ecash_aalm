<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Display_View extends Display_Parent
{
	public function __construct($transport, $module_name, $mode)
	{
		parent::__construct($transport, $module_name, $mode);
	}

	public function Get_Header()
	{
		include_once(WWW_DIR . "include_js.php");
		return "<link rel=\"stylesheet\" href=\"css/transactions.css\">
			<link rel=\"stylesheet\" href=\"js/calendar/calendar-dp.css\">
			<script type='text/javascript' src='js/it_settlement.js'></script>
			" . include_js();
	}

	public function Get_Body_Tags()
	{
		return "";
	}

	protected function Date_Calendar( $select_prefix, $option_prefix, $extra_attributes='', $month = NULL, $day = NULL, $year = NULL)
	{
		$date_day_selx   = ($day   != NULL) ? ($day  ) : date('d');
		$date_month_selx = ($month != NULL) ? ($month) : date('m');
		$date_year_selx  = ($year  != NULL) ? ($year ) : date('Y');

		$date_code = "<input type=text {$extra_attributes} id='{$select_prefix}display' name='{$select_prefix}display' value='{$date_month_selx}/{$date_day_selx}/{$date_year_selx}' class=\"disabled\" style=\"cursor: pointer; font-family: monospace;\" size=\"10\" readonly=\"readonly\" \>\n";
		$date_code .= "<input type=hidden id='{$select_prefix}month' name='{$select_prefix}month' value='{$date_month_selx}'>\n";
		$date_code .= "<input type=hidden id='{$select_prefix}day' name='{$select_prefix}day' value='{$date_day_selx}'>\n";
		$date_code .= "<input type=hidden id='{$select_prefix}year' name='{$select_prefix}year' value='{$date_year_selx}'>\n";
		return $date_code;
	}

	public function Get_Calendar_JS()
	{
		$js = "
			<script>
			function selectHand(cal, date)
			{
				cal.sel.value = date;
				var arrdate = cal.sel.value.split('/');
				var el = document.getElementById(cal.frmTarget + 'month');
				el.value = arrdate[0];
				var el = document.getElementById(cal.frmTarget + 'day');
				el.value = arrdate[1];
				var el = document.getElementById(cal.frmTarget + 'year');
				el.value = arrdate[2];
				cal.callCloseHandler();
			}

		function ReportCalendar(target, x, y)
		{
			var el = document.getElementById(target + 'display');
			if (calendar != null)
			{
				calendar.onSelected = selectHand;
				calendar.hide();
				calendar.parseDate(el.value);
			}
			else
			{
				var cal = new Calendar(true, serverdate, selectHand, closeHandler);
				calendar = cal;
				cal.setRange(1900, 2070);
				calendar.create();
				calendar.parseDate(el.value);
			}

			calendar.frmTarget = target;
			calendar.sel = el;
			//calendar.pt_dropdown = pt_dropdown;

			// Don't show *at* the element, b/c the position might be jacked.
			// Show at the cursor location
			calendar.showAt(x, y);

			// Need this to hide the calendar
			Calendar.addEvent(document, 'mousedown', checkCalendar);

			return false;
		}

		</script>
			";

		return $js;
	}


	public function Get_To_Date_HTML()
	{
		$html = "";
	
		$html .= $this->Get_Calendar_JS();
		$date_day_selx   = ($_REQUEST['end_date_day']   != NULL) ? ($_REQUEST['end_date_day']  ) : date('d');
		$date_month_selx = ($_REQUEST['end_date_month'] != NULL) ? ($_REQUEST['end_date_month']) : date('m');
		$date_year_selx  = ($_REQUEST['end_date_year']  != NULL) ? ($_REQUEST['end_date_year'] ) : date('Y');


		$extra_attribute = 'onClick="ReportCalendar(\'end_date_\', event.clientX, event.clientY)"';
		$html .= '<span>End Date :</span>';
		$html .= '<span style="white-space: nowrap;">' . $this->Date_Calendar( "end_date_", "end", $extra_attribute, $date_month_selx, $date_day_selx, $date_year_selx )
			. ' (<a style="text-decoration: underline;" href="#" ' . $extra_attribute . '>select</a>)</span>';

	
		return $html;
	}


	public function Get_From_Date_HTML()
	{
		$html = "";
	
		$html .= $this->Get_Calendar_JS();

		// We want the from to encompass all dates the reports have been running at first
		$date_day_selx   = ($_REQUEST['start_date_day']   != NULL) ? ($_REQUEST['start_date_day']  ) : date('d');
		$date_month_selx = ($_REQUEST['start_date_month'] != NULL) ? ($_REQUEST['start_date_month']) : date('m');
		$date_year_selx  = ($_REQUEST['start_date_year']  != NULL) ? ($_REQUEST['start_date_year'] ) : date('Y')-1;


		$extra_attribute = 'onClick="ReportCalendar(\'start_date_\', event.clientX, event.clientY)"';
		$html .= '<span>Start Date :</span>';
		$html .= '<span style="white-space: nowrap;">' . $this->Date_Calendar( "start_date_", "start", $extra_attribute, $date_month_selx, $date_day_selx, $date_year_selx )
			. ' (<a style="text-decoration: underline;" href="#" ' . $extra_attribute . '>select</a>)</span>';

	
		return $html;
	}

	public function getBatchReportList()
	{
		$companies = $this->data->batch_reports;
		$batch_report_select = "<select name='batch_report'>\n";
		foreach ($companies  as $company) 
		{
			$batch_report_select .= "<option value={$company->name_short}>{$company->name}</option>\n";
		}
		$batch_report_select .= "</select>\n";
		$this->data->batch_report_select = $batch_report_select;
	}
	public function Get_Module_HTML()
	{
		$action = ECash::getTransport()->Get_Next_Level();
		$this->data->mode_class = $this->mode;
		switch($action)
		{
			case  'quick_checks':
				$this->data->quick_checks_batches  = $this->Format_Batches();
				$this->data->from_date = $this->Get_From_Date_HTML();
				$this->data->to_date   = $this->Get_To_Date_HTML();

				$this->getBatchReportList();
				$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/quick_checks.html");
				break;
		}

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

	private function Format_Batches()
	{
		$output = "";

		$json_reports = json_encode($this->data->batches);

		// Put in some JSON vars
		$output = "
			<script type='text/javascript'>
				var json_reports = {$json_reports};
			</script>
		";


		$row_number = 0;

		if (is_array($this->data->batches))
		{
			foreach ($this->data->batches as $batch)
			{
				// Staggering row shading
				$class = $row_number % 2 == 0 ? 'align_left' : 'align_left_alt';

				// Report Download Links
				$download_summary = "<a href ='/?module=collections&mode=quick_checks&action=download_external_batch&report_id={$batch['batch_id']}&type=quickcheck'>download</a>";

				$start_date_day   = ($_REQUEST['start_date_day']   != NULL) ? ($_REQUEST['start_date_day']  ) : date('d');
				$start_date_month = ($_REQUEST['start_date_month'] != NULL) ? ($_REQUEST['start_date_month']) : date('m');
				$start_date_year  = ($_REQUEST['start_date_year']  != NULL) ? ($_REQUEST['start_date_year'] ) : date('Y')-1;

				$end_date_day   = ($_REQUEST['end_date_day']   != NULL) ? ($_REQUEST['end_date_day']  ) : date('d');
				$end_date_month = ($_REQUEST['end_date_month'] != NULL) ? ($_REQUEST['end_date_month']) : date('m');
				$end_date_year  = ($_REQUEST['end_date_year']  != NULL) ? ($_REQUEST['end_date_year'] ) : date('Y');

				$total = '$'.number_format($batch['batch_total'],2);
				$output .= "<tr class=\"{$class}\">\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$batch['batch_id']}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$batch['batch_date']}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$batch['batch_type']}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$batch['batch_count']}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$total}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$batch['batch_status']}</td>\n";
				$output .= "  <td class=\"it_settlement\" style=\"text-align: center;\">{$download_summary}</td>\n";
				$output .= "</tr>\n";

				$row_number++;

			}	
		}

		return $output;	
	}
}

?>
