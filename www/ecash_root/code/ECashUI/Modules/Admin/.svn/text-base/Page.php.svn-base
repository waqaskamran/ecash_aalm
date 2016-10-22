<?php

/**
 * The base page used for the admin module.
 * 
 * @package ECashUI.Modules.Admin
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
abstract class ECashUI_Modules_Admin_Page extends ECashUI_AbstractPage 
{
	/**
	 * Returns the HTML that will be placed in the header.
	 */
	public function getHeaderHtml()
	{
		return '<script language=javascript src="js/overlib.js"></script><link rel="stylesheet" type="text/css" href="css/admin.css" />';
	}
	
	/**
	 * Returns the HTML that will enable hot keys
	 */
	public function getHotKeys()
	{
		return '<script type="text/javascript" src="get_js.php?override=admin_hotkeys&version=' . ECASH_VERSION_FULL . '"></script>';
	}

	/**
	 * Returns menu html
	 *
	 * @todo a majority of this code should probably be somewhere
	 * 		 common (for other modules)
	 * @return string
	 */
	public function getModuleMenu()
	{
		require_once(CUSTOMER_LIB . 'list_available_admin_menu.php');
		
		$acl = ECash::getAcl();
		$acl_sub_access = $acl->Get_Acl_Access('admin');
		$user_acl_sub_names = $acl->Get_Acl_Names($acl_sub_access);
		
		$button_count = 0;
		$available_menu_items = list_available_admin_menu($user_acl_sub_names);

		$menu = '<div id="module_menu">';
		$menu .= '<script type="text/javascript" src="js/menu.js"></script>' . "\n";
		$menu .= '<div id="section_nav">' . "\n";

		/**
		 * If the Online Help is set up, we'll show URL in the Admin section
		 */
		if (isset($user_acl_sub_names['admin_help']) && isset(ECash::getConfig()->ONLINE_HELP_ADMIN_URL_ROOT) && isset(ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX))
		{
			$help_url = ECash::getConfig()->ONLINE_HELP_ADMIN_URL_ROOT . ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX;
			$help_html = '<div class="AppMenuOnlineHelp" style="vertical-align: middle;" onClick="javascript:openOnlineHelpWindow(\'' . $help_url . '\', \'Admin Help\');">Help</div>';
			$available_menu_items[] = array('inline' => $help_html);
		}

		foreach ($available_menu_items as $menu_title => $menu_info) 
		{
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
		
		$menu .= "\n</div>\n";
		$menu .= '</div>';
		
		$html = file_get_contents(CLIENT_MODULE_DIR . "admin/view/admin_menu.html");
		$html = str_replace('%%%ADMIN_HTML%%%', $menu, $html);

		return $menu;
	}
	
}

?>