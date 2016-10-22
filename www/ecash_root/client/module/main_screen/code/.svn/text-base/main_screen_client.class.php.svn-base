<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Main_Screen_Client extends Client_View_Parent implements Display_Module
{

   	public function __construct(ECash_Transport $transport, $module_name)
   	{
   		$this->mode = ECash::getTransport()->Get_Current_Level();
		$this->view = ECash::getTransport()->Get_Next_Level();
		$this->transport = ECash::getTransport();
		$this->module_name = $module_name;
		$this->data = ECash::getTransport()->Get_Data();
		
		if(file_exists(CUSTOMER_LIB . "display_".$this->view.".class.php"))
		{
			require_once(CUSTOMER_LIB . "display_".$this->view.".class.php");
			$this->display  = new Customer_Display_View(ECash::getTransport(), $this->module_name, $this->mode);	
		}
		else 
		{
			require_once(CLIENT_MODULE_DIR . $this->module_name."/code/display_". $this->view . ".class.php");
			$this->display  = new Display_View(ECash::getTransport(), $this->module_name, $this->mode);	
		}
   	}

	public function Get_Hotkeys()
	{
		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}
		$flux = rand(1,100000000);
        include_once(WWW_DIR . "include_js.php");
		$hotkey_js = include_js(Array('main_screen_hotkeys')) . "
	<script type=\"text/javascript\">
		//for hotkeys
		var co_abbrev = \"". ECash::getTransport()->company ."\";
		var agent_id = \"" . ECash::getTransport()->agent_id . "\";
	</script>\n";
		return $hotkey_js;
	}

	public function Get_Menu_HTML()
	{
		// Get Menu HTML and Replace Tokens
		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/main_screen_menu.html");
		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
}

?>
