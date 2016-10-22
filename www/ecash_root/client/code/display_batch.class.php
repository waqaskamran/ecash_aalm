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
		case 'review_batch':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/review_batch.html");
			$this->Format_Batch_List();
			$this->Format_Batch_Messages();
			$this->data->master_domain = ECash::getConfig()->MASTER_DOMAIN;
			break;
		case 'review_cards':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/review_cards.html");
			$this->Format_Batch_List();
			$this->Format_Batch_Messages();
			$this->data->master_domain = ECash::getConfig()->MASTER_DOMAIN;
			break;
		}

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
	private function Format_Batch_Messages()
	{
		$output = '';
		if(!empty($this->data->batchmessages))
		{
			$output = '<table class="batchMessages" width=94% align=center><tbody style = "height:35px;"><tr><th colspan = 4 >Batch Warnings<tr><th>Application<th>Event ID <th>Amount<th style="text-align: left;">Message</th></tbody><tbody>';
		}

		foreach ($this->data->batchmessages as $item)
		{
			$url = "/?module=loan_servicing&mode=account_mgmt&action=show_applicant&application_id={$item['application_id']}";
			$output .= "\n\t<tr ><td><a href=\"{$url}\">" . $item['application_id'] . '</a><td>' . $item['event_schedule_id'] . '<td>' . $item['amount'] . '<td style="text-align: left;" >' . $item['message'];
		}
		if(!empty($this->data->batchmessages))
		{
			$output .= '</tbody></table>';
		}	
		$this->data->batchmessages = $output;
	}
	private function Format_Batch_List()
	{
		//dvar_dump($this->data->batchlist, "Batch list");
		$output = "";

		$totals = array(); // [ach_type]["count"] -or- [ach_type]["total"]

		foreach ($this->data->batchlist as $item)
		{
			$url = "/?module=loan_servicing&mode=account_mgmt&action=show_applicant&application_id={$item['application_id']}";

			$output .= "<tr>\n";
			$output .= "  <td><a href=\"{$url}\">{$item['application_id']}</a></td>\n";
			$output .= "  <td>{$item['name']}</td>\n";
			$output .= "  <td>{$item['ach_type']}</td>\n";
			$output .= "  <td style='text-align:right'>{$item['amount']}</td>\n";
			if (isset($item['ach_provider_name']))
			{
				$output .= "  <td>{$item['ach_provider_name']}</td>\n"; //asm 80
			}
			$output .= "</tr>\n";

			if(!isset($totals[$item['ach_type']]))
			{
				$totals[$item['ach_type']]["count"] = 0;
				$totals[$item['ach_type']]["total"] = 0;
			}

			$totals[$item["ach_type"]]["count"] += 1;
			$totals[$item["ach_type"]]["total"] += $item["amount"];
		}

		if($totals)
		{
			$output
				.=	"<tr>"
				.	"<td colspan=\"5\"><hr></td>"
				.	"</tr>"
				;
		}

		foreach($totals as $ach_type => $detail)
		{
			$output
				.=	"<tr>"
				.	"<td><b>"
				.	$detail["count"]
				.	"</b></td>"
				.	"<td><b>Total</b></td>"
				.	"<td><b>$ach_type</b></td>"
				.	"<td style='text-align:right;'><b>"
				.	number_format($detail["total"],2)
				.	"</b></td>"
				.	"</tr>"
				;
		}


		$this->data->batchlist = $output;
	}

}

?>
