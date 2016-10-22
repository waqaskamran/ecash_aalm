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
			<link rel=\"stylesheet\" type=\"text/css\" href=\"http://yui.yahooapis.com/2.7.0/build/fonts/fonts-min.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"http://yui.yahooapis.com/2.7.0/build/datatable/assets/skins/sam/datatable.css\" />
			<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.7.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>
			<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.7.0/build/dragdrop/dragdrop-min.js\"></script>
			<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.7.0/build/element/element-min.js\"></script>
			<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.7.0/build/datasource/datasource-min.js\"></script>
			<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.7.0/build/datatable/datatable-min.js\"></script>
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
		case 'display_return_file_history':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/return_history.html");
			$this->Format_Return_List();
			break;
		case 'display_return_file_history_detail':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/return_history_detail.html");
			$this->Format_Return_Detail();
			
			break;
		case 'display_upload_return_file':
			$html = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/upload_return_file.html");
			$this->Format_Return_Detail();
			break;

		}

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

	protected function Format_Return_Detail()
	{
		$columndefs = array();
		$data_array = array();
		$ix = 0;
		$keys = NULL;
		if(!empty($this->data->report_data))
		{
			foreach ($this->data->report_data as $item)
			{
				$keys = array_keys($item);
				foreach($keys as $column)
				{
					$columndefs[] = array('key' => $column, 'sortable' => true, 'resizeable' => true);
				}
				break;
			}
			//this is a hack because us bank rows are associative and yui datatable dies for some reason
			foreach($this->data->report_data as $row)
			{
				$data_array[] = $row;
			}	
		}	
		$this->data->columndefs = json_encode($columndefs);	
		$this->data->columns = json_encode($keys);

		if(!empty($this->data->file_types))
		{
			foreach($this->data->file_types as $value => $name)
			{
				if($this->data->file_type == $value)
					$selected = 'SELECTED';
				else
					$selected ='';
				$this->data->file_type_options .= "<option value= '{$value}' {$selected}>{$name}</option>";	
			}
		}

		//asm 104
		if(!empty($this->data->file_formats))
		{
			foreach($this->data->file_formats as $value => $name)
			{
				if($this->data->file_format == $value)
					$selected = 'SELECTED';
				else
					$selected ='';
				$this->data->file_format_options .= "<option value= '{$value}' {$selected}>{$name}</option>";
			}
		}
		/////////

		$this->data->return_data = json_encode($data_array);	
	}

	private function Format_Return_List()
	{
		//asm 114
		$providers = array();
		$providers[0] = "";
		$pr_model = ECash::getFactory()->getModel('AchProvider');
		$pr_array = $pr_model->loadAllBy(array('active_status' => 'active',));
		foreach ($pr_array as $pr)
		{
			$providers[$pr->ach_provider_id] = $pr->name;
		}
		////////
		$output = "";

		$ix = 0;
		foreach ($this->data->report_list as $item)
		{
			$ix++;
			$row_style = ($ix % 2) ? "odd" : "even";

			$url = "<a href='?module=loan_servicing&mode=batch_mgmt&action=return_history_detail&report_id={$item->ach_report_id}'>{$item->ach_report_id}</a>";
			$output .= "<tr class=\"batch_mgmt_{$row_style}\">\n";

			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">{$url}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . date('Y-m-d', strtotime($item->date_created)) . "</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">{$item->date_request}</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . ucfirst($item->report_type) . "</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . ucfirst($item->report_status) . "</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . ucfirst($item->delivery_method) . "</td>\n";
			$output .= "  <td class=\"batch_mgmt\" style=\"text-align: center;\">" . $providers[$item->ach_provider_id] . "</td>\n"; //asm 114

			$output .= "</tr>\n";
			unset($item);
		}

		$this->data->returnlist = $output;
	}

}

?>
