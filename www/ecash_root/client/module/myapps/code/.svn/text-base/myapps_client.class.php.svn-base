<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class MyApps_Client extends Client_View_Parent implements Display_Module
{



	public function Get_Header()
	{
        include_once(WWW_DIR . "include_js.php");
		return include_js();
	}

		public function Get_Module_HTML()
	{
	 
     	$this->data->display_my_apps_menu = $this->display->Get_Menu_HTML();
		$this->data->display_my_apps_rows = $this->display->Get_Rows_HTML();
    	$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/my_apps.html");
		return Display_Utility::Token_Replace($html, (array)$this->data);
    		   	
	}
	
	
	public function Get_Body_Tags()
	{
	}
   
    public function Get_Hotkeys()
    {
        if (method_exists($this->display, "Get_Hotkeys"))
        {
            return $this->display->Get_Hotkeys();
        }

        return("");
    }

    public function Get_Menu_HTML()
    {
        return("");
    }
}

?>
