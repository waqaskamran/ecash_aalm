<?php

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Application implements Display
{
	public function __construct()
	{
	}

	public function Do_Display(ECash_Transport $transport)
	{
		/*
		* Somwhere in here we need to figure out what modules this person
		* has access to and generate the correct module menu to display,
		* then pass it on to the module we create here.
		*/

		//set login cookie if need be
		if(!empty($_REQUEST['login']))
		{
			//this guy passed authentication, so let's save his company in a cookie
			$cookie_exp = time()+60*60*24*30; //thirty days
			$domain = ECash::getConfig()->COOKIE_DOMAIN;
			
			setcookie('default_company', $_REQUEST['abbrev'], $cookie_exp, '/', $domain);
		}

		// Gets the module name, build it using the directory-specific Client_Module,
		// and retrieve all content.
		
		$module_name = ECash::getTransport()->Get_Next_Level();

		if (isset($module_name))
		{
			require_once(CLIENT_MODULE_DIR . "${module_name}/code/client_module.class.php");
			$mod = Client_Module::Get_Display_Module($transport, $module_name);			
			// Make sure the module actually wants to keep processing
			if (!$mod->send_display_data) return;

			$header           = $mod->Get_Header();
			$body_tags        = $mod->Get_Body_Tags();
			$hotkeys          = $mod->Get_Hotkeys();
			$module_menu_html = $mod->Get_Menu_HTML();
			$module_html      = $mod->Get_Module_HTML();

			$yarra_egap = array_reverse(ECash::getTransport()->page_array);
			
			if (ECash::getTransport()->company_id > 100 && $yarra_egap[0] != 'search')
			{
				$module_menu_html = preg_replace("/<a href=[^>]+>([^<>]*(get next)[^<>]*(<span [^>]+>[^<>]*<\/span>)?[^<>]*)<\/a>/isU", "\\1", $module_menu_html);
				$module_html = preg_replace("/<a href=[^>]+>([^<>]*(edit|wizard)[^<>]*)<\/a>/isU", "", $module_html);
				$module_html = preg_replace("/<a href=[^>]+>([^<>]*(email|address|phone)[^<>]*)<\/a>/isU", "\\1", $module_html);
				if($module_name != 'reporting')
					$module_html = preg_replace("/(<input type=\"?(button|submit|text|checkbox)\"?[^>]+)>/isU", "\\1 disabled>", $module_html);
			}

		}
		if (!isset($mod) || $mod->Include_Template())
		{
			file_exists(CUSTOMER_LIB . "/view/template.php")?include(CUSTOMER_LIB."/view/template.php"):include(CLIENT_VIEW_DIR."template.php");
		}

	}
}
?>
