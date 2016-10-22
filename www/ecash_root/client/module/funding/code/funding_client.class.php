<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Funding_Client extends Client_View_Parent implements Display_Module
{

	private static $submenu_list = array(
		"verification",
		"underwriting",
		"tiffing",
		"funding_help"
		);

	public function Get_Hotkeys()
	{
		//skip hotkeys for those who don't want them
		if(ECash::getConfig()->USE_HOTKEYS === FALSE)
			return '';

		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		$allow_cashline = in_array('cashline', $this->data->allowed_submenus) ? 'true' : 'false';
		$flux = rand(1,100000000);
        include_once(WWW_DIR . "include_js.php");
		$hotkey_js = include_js(Array('funding_hotkeys')) . "
	<script type=\"text/javascript\">
		//for hotkeys
		var allow_cashline = {$allow_cashline};
		var co_abbrev = \"". ECash::getTransport()->company ."\";
		var agent_id = \"" . ECash::getTransport()->agent_id . "\";
	</script>\n";
		return $hotkey_js;

	}

	public function Get_Menu_HTML()
	{
		if (method_exists($this->display, "Get_Menu_HTML"))
		{
			return $this->display->Get_Menu_HTML();
		}

		$button_count = 0;

		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;

		/**
		 * This will show the HELP link in the funding section
		 */
		if (isset(ECash::getConfig()->ONLINE_HELP_FUNDING_URL_ROOT) && isset(ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX))
		{
			$help_url = ECash::getConfig()->ONLINE_HELP_FUNDING_URL_ROOT . ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX;
			$help_html = '<div class="AppMenuOnlineHelp" style="vertical-align: middle;" onClick="javascript:openOnlineHelpWindow(\'' . $help_url . '\', \'Funding Help\');">Help</div>';

			$this->data->funding_help_link = $help_html;
		}

		// Create Submenu Buttons
		foreach( self::$submenu_list as $menu_item)
		{
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";
			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{
				// if we can access customized funding buttons for this user, use them!
				if(file_exists(CUSTOMER_LIB . $this->module_name . "/view/" .$file))
				{
					$this->data->{$menu_item_name} = file_get_contents(CUSTOMER_LIB . $this->module_name . "/view/" .$file);
				}
				else
				{
					$this->data->{$menu_item_name} = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/" . $file);
				}
				$button_count++;
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);
			}
			else
			{
				$this->data->{$menu_item_name} = "";
			}
		}

		$this->data->search_box_form = file_get_contents(CLIENT_VIEW_DIR . "search_box.html");

		$this->Create_Queue_Buttons();

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/funding_menu.html");

        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));

		return Display_Utility::Token_Replace($html, (array)$this->data);
	}

}

?>
