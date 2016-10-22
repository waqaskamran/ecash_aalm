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
                       " . include_js();
	}

	public function Get_Body_Tags()
	{
		return "";
	}

	public function Get_Module_HTML()
	{
		$action = ECash::getTransport()->Get_Next_Level();
		$this->data->mode_class = $this->mode;
		switch($action)
		{
		case 'message':
			$html = file_get_contents(CLIENT_VIEW_DIR . "message.html");			
			break;
		case 'display_batch_history':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/batch_history.html");
			$this->Format_Batch_List();
			break;
		}

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

	private function Format_Batch_List()
	{
		dvar_dump($this->data->batchlist, "Batch list");
		$output = "";
		
		$ix = 0;
		foreach ($this->data->batchlist as $item)
		{
			$ix++;
			$row_style = ($ix % 2) ? "odd" : "even";
			
			
			$restart_batch = "/?module=loan_servicing&mode=batch_mgmt&action=send_batch";
			$resend_batch  = "/?module=loan_servicing&mode=batch_mgmt&action=resend_batch&batch_id={$item['batch_id']}";
			$download_batch = "/?module=loan_servicing&mode=batch_mgmt&action=download_batch&batch_id={$item['batch_id']}";
			$url = (($item['batch_status'] == 'created') ? $restart_batch : $resend_batch);
			
			$output .= "<tr class=\"batch_mgmt_{$row_style}\">\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center; text-decoration: underline\">" . 
															"<a href=\"{$url}\" onClick=\"return window.confirm('Are you sure you want to resend batch {$item['batch_id']}?');\" onMouseOver=\"tooltip(event, 'Click to re-send batch #{$item['batch_id']}.', 150, -30);\" onMouseOut=\"tooltip(null);\">{$item['batch_id']}</a>

				<br><a href=\"{$download_batch}\" >[Download]</a></td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\" >{$item['batch_created']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\" >{$item['batch_sent']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\">{$item['batch_count']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\">{$item['credit_count']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\">{$item['credit_amount']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\">{$item['debit_count']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: right;\">{$item['debit_amount']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">{$item['error_code']}</td>\n";
			//$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">{$item['intercept_refno']}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\" >{$item['ach_provider_name']}</td>\n"; //asm 80
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . ucwords($item['batch_status']) . "</td>\n";
			$output .= "</tr>\n";
		}			
		$this->data->batchlist = $output;
	}
	
}

?>
