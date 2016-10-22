<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

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
		$this->data->master_domain = ECash::getConfig()->MASTER_DOMAIN;
		switch($action)
		{
		case 'refresh_view':
			$html = file_get_contents(CLIENT_VIEW_DIR . "batch_progress.html");
			break;
		}
		//$html = "Mode: {$this->mode}, Action: '{$action}'\n";

		
		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
	
}

?>
