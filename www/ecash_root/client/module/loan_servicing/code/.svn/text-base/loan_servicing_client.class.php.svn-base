<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Loan_Servicing_Client extends Client_View_Parent implements Display_Module
{
	private static $submenu_list = array(
		"customer_service",
		"account_mgmt",
		"batch_mgmt",
		'it_settlement',
		'servicing_help'
		);

   	public function __construct(ECash_Transport $transport, $module_name)
   	{
		parent::__construct($transport, $module_name);
   	}

	public function Get_Hotkeys()
	{
		$hotkey_js = '';

		if(ECash::getConfig()->USE_HOTKEYS === TRUE)
		{
			if (method_exists($this->display, "Get_Hotkeys"))
			{
				return $this->display->Get_Hotkeys();
			}
			else
			{
				$flux = rand(1,100000000);
				include_once(WWW_DIR . "include_js.php");
				$hotkey_js = include_js(Array('loan_servicing_hotkeys'));
			}
		}

		$hotkey_js .= "
		<script type=\"text/javascript\">
			var co_abbrev = \"". ECash::getTransport()->company ."\";
			var agent_id = \"" . ECash::getTransport()->agent_id . "\";
			var nextSafeACHActionDate = \"" . ECash::getTransport()->nextSafeACHActionDate . "\";
			var nextSafeACHDueDate = \"" . ECash::getTransport()->nextSafeACHDueDate . "\";
		</script>\n";
		return $hotkey_js;
	}

	public function Get_Menu_HTML()
	{
		$button_count = 0;

		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;

		/**
		 * This will show the HELP link in the servicing section
		 */
		if (isset(ECash::getConfig()->ONLINE_HELP_SERVICING_URL_ROOT) && isset(ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX))
		{
			$help_url = ECash::getConfig()->ONLINE_HELP_SERVICING_URL_ROOT . ECash::getConfig()->ONLINE_HELP_DEFAULT_INDEX;
			$help_html = '<div class="AppMenuOnlineHelp" style="vertical-align: middle;" onClick="javascript:openOnlineHelpWindow(\'' . $help_url . '\', \'Servicing Help\');">Help</div>';

			$this->data->servicing_help_link = $help_html;
		}

		// Create Submenu Buttons
		foreach( self::$submenu_list as $menu_item)
		{
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";
			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{
				$this->data->{$menu_item_name} = $this->getHTMLTemplate($this->module_name . "/view/" . $file);
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);
			}
			else
			{
				$this->data->{$menu_item_name} = "";
			}
		}

		$this->Create_Queue_Buttons();

		/**
		 * HACK: This code needs to go away.
		 */
		if (($this->mode == 'customer_service' || $this->mode == 'account_mgmt') && in_array('servicing_email_queue', $this->data->allowed_submenus)) 
		{
			global $server;
			$eq = new Incoming_Email_Queue($server, $this->data);
			$count = $eq->Fetch_Queue_Count('servicing_email_queue');
			
			$new_button =
					<<<END_HTML
						<a id="AppQueueBarEmail" class="menu" href="/?module=loan_servicing&mode={$this->mode}&action=get_next_email">
							<div class="menu_label_nextapp {$this->mode}">
								Email: &nbsp;<span class="queue_count">{$count}</span>
							</div>
						</a>
END_HTML;
			if (!ECash::getACL()->Acl_Check_For_Access(Array('loan_servicing', 'restriced_access')))
			{
				//THIS IS A SLOPPY HACK TO INJECT THE EMAIL QUEUE BUTTON IN THE 'RIGHT' PLACE... REMOVE AS SOON AS POSSIBLE!
				if(stristr($this->data->queue_buttons, '%%%email_queue%%%'))
				{
					$this->data->queue_buttons = str_replace('%%%email_queue%%%', $new_button, $this->data->queue_buttons);	
				}
				else 
				{
					$this->data->queue_buttons .= $new_button;
				}
			}
		}
		/**
		 * ENDHACK
		 */

		// Conditionally display the Search Box
		if (in_array($this->mode, array('customer_service', 'account_mgmt')))
		{
			$this->data->search_box_form = file_get_contents(CLIENT_VIEW_DIR . "search_box.html");
		}
		else
		{
			$this->data->search_box_form = '';
		}

		$this->data->next_app_button = '';

		// Get Menu HTML and Replace Tokens
		$html = $this->getHTMLTemplate($this->module_name . "/view/loan_servicing_menu.html");
		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
}

?>
