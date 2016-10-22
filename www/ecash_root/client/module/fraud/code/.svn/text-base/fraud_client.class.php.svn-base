<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Fraud_Client extends Client_View_Parent implements Display_Module
{

	private static $submenu_list = array("fraud_queue","high_risk_queue","watch", "rules");
	private static $submenu_item_list = array(	"fraud_rules" 		=> "Fraud Rules",
												"high_risk_rules"	=> "High Risk Rules");

	public function Get_Hotkeys()
	{

		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		$allow_cashline = in_array('cashline', $this->data->allowed_submenus) ? 'true' : 'false';

        include_once(WWW_DIR . "include_js.php");
		return include_js(Array('fraud_hotkeys')) . "
				<script type=\"text/javascript\">
				//for hotkeys
				var allow_cashline = {$allow_cashline};
				var co_abbrev = \"". ECash::getTransport()->company ."\";
				var agent_id = \"" . ECash::getTransport()->agent_id . "\";
				</script>";
	}


	public function Get_Menu_HTML()
	{

		/* I don't know why this is here or commented out -- JRF
		// TODO: figure out if this is supposed to be here
		if (method_exists($this->display, "Get_Menu_HTML"))
		{
			return $this->display->Get_Menu_HTML();
		}
		*/
		$button_count = 0;

		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;
		$this->data->fraud_menu = "";
		$this->data->queue_buttons = "";

		foreach( self::$submenu_list as $menu_item)
		{
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";
			
			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{
				$this->data->{$menu_item_name} = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/" . $file);
				$button_count++;
				if($menu_item == "rules")
				{
					foreach (self::$submenu_item_list as $subkey => $subitem)
					{
						if(in_array($subkey, $this->data->allowed_submenus))
						{
							$this->data->fraud_menu .= "<a href=\"/?mode=$subkey&action=show_fraud_rules\"><div class=\"submenu_item\">$subitem</div></a>";
						}
					}
					
					
				}
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array) $this->data);
			}
			else 
			{ 
				$this->data->{$menu_item_name} = '';
			}

		}
		
		if($this->mode != 'fraud_rules' && $this->mode != 'high_risk_rules')
			$this->data->search_box_form = file_get_contents(CLIENT_VIEW_DIR . "search_box.html");
		else 
			$this->data->search_box_form = '';	
		
			// Create the Queue Buttons
		self::Create_Queue_Buttons();

		if(file_exists(CUSTOMER_LIB . $this->module_name . "/view/fraud_menu.html"))
		{
			$html = file_get_contents(CUSTOMER_LIB . $this->module_name . "/view/fraud_menu.html");
		}
		else
		{
		
			$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/fraud_menu.html");
		}

        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
}
?>
