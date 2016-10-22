<?php

require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CUSTOMER_LIB . "list_available_admin_menu.php");

abstract class Admin_Parent implements Display_Module
{
	protected $module_name;
	protected $transport;
	protected $mode;
	protected $view;
	protected $display_level;
	protected $display_sequence;
	protected $data;


	// Used by Display_Application to call the display methods (i.e Get_Module_HTML)
	// Set to false to not send a new HTML content page, but do alternative processing.
	public $send_display_data = true;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->transport = ECash::getTransport();
		$this->module_name = $module_name;
		$this->display_level = 2;
		$this->display_sequence = 5;
	}

	public function Set_Mode($mode)
	{
		$this->mode = $mode;
	}

	public function Set_View($view)
	{
		$this->view = $view;
	}

	public function Get_Hotkeys()
	{
		include_once(WWW_DIR . "include_js.php");
		return include_js(Array('admin_hotkeys'));
	}

	public function Get_Header()
	{
		return '<script language=javascript src="js/overlib.js"></script><link rel="stylesheet" type="text/css" href="css/admin.css" />';
	}

	public function Include_Template() { return true; }

	public function Get_Body_Tags()
	{
		//  Disabling because the method isn't defined when this module is loaded
		//  and obviously isn't needed. - BR
		//	$onload = "onLoad=\"javascript:get_last_settings();\"";
		//	return $onload;
		return '';
	}

	public function Get_Menu_HTML()
	{
		$button_size = 130;
		$button_count = 0;

		$menu = "";

		$available_menu_items = list_available_admin_menu(ECash::getTransport()->user_acl_sub_names);

		/**
		 * If the Online Help is set up, we'll show URL in the Admin section
		 */
		if (isset(ECash::getTransport()->user_acl_sub_names['admin_help']) && isset(ECash::getConfig()->ONLINE_HELP_ADMIN_URL_ROOT) && isset(ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX))
		{
			$help_url = ECash::getConfig()->ONLINE_HELP_ADMIN_URL_ROOT . ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX;
			$help_html = '<div class="AppMenuOnlineHelp" style="vertical-align: middle;" onClick="javascript:openOnlineHelpWindow(\'' . $help_url . '\', \'Admin Help\');">Help</div>';
			$available_menu_items[] = array('inline' => $help_html);
		}

		foreach ($available_menu_items as $menu_title => $menu_info) 
		{
			$button_start = ($button_size * $button_count);

			$Sub_Menu_ID = '';
			if(!empty($menu_info['class']))
				$Sub_Menu_ID = str_replace(" ","",ucwords(str_replace("_"," ",$menu_info['class'])));
			
			if (!empty($menu_info['inline'])) 
			{
				$menu .= $menu_info['inline'];
			} 
			else 
			{
				$menu .= "<div id=\"AppSubMenuDiv{$Sub_Menu_ID}\" class=\"level2nav\" onClick=\"Toggle_Menu('{$menu_info['class']}', this.id);\">{$menu_title}</div>\n";
			}

			if (!empty($menu_info['submenu']) && count($menu_info['submenu'])) 
			{
				$menu .= "<div class=\"admin_menu_layer\" id=\"{$menu_info['class']}\">\n";

				foreach ($menu_info['submenu'] as $sub_title => $sub_url) 
				{
					$Sub_Item_ID = str_replace(" ","",$sub_title);
					
					if ($sub_url === NULL)
					{
						$menu .= '<hr height="1" />';
					}
					else {
						$menu .= "<a href=\"{$sub_url}&module=admin\" id=\"AppSubMenuLink{$Sub_Menu_ID}SubItem{$Sub_Item_ID}\"><div class=\"admin_submenu_item\" id=\"AppSubMenuDiv{$Sub_Menu_ID}SubItem{$Sub_Item_ID}\">{$sub_title}</div></a>\n";
					}

				}
				$menu .= '</div>';
			}
			$button_count++;
		}

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/admin_menu.html");
		$html = str_replace('%%%ADMIN_HTML%%%', $menu, $html);

		return $html;
	}

	protected function Replace($matches)
	{
		$return_value = NULL;

		if(isset($this->data->{$matches[1]}))
		{
			$return_value = $this->data->{$matches[1]};
		}

		return $return_value;
	}
}
