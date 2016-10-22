<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");

class Conversion_Client extends Client_View_Parent implements Display_Module
{
	public static $submenu_list = array("conversion_manager");

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
	}

	public function Get_Hotkeys()
	{
		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		include_once(WWW_DIR . "include_js.php");
		$str = include_js();
	}

	public function Get_Menu_HTML()
	{
		if (method_exists($this->display, "Get_Menu_HTML"))
		{
			return $this->display->Get_Menu_HTML();
		}

		$button_size = 120;
		$button_count = 0;

		$this->data->company = ECash::getTransport()->company;
		$this->data->agent_id = ECash::getTransport()->agent_id;

		$queue_count = "";

		if ( isset($this->data->queue_count) )
		{
			$this->data->queue_count->collection = number_format($this->data->queue_count->collection);
			$this->data->queue_count->hold = number_format($this->data->queue_count->hold);
			$this->data->queue_count->active = number_format($this->data->queue_count->active);
			$this->data->queue_count->other = number_format($this->data->queue_count->other);			
			$this->data->queue_count->conversion_manager = number_format($this->data->queue_count->conversion_manager);
			
			$this->data->queue_count->returns_past_due = number_format($this->data->queue_count->returns_past_due);
			$this->data->queue_count->returns_collections_new = number_format($this->data->queue_count->returns_collections_new);
			$this->data->queue_count->returns_collections_contact = number_format($this->data->queue_count->returns_collections_contact);
			$this->data->queue_count->returns = number_format($this->data->queue_count->returns);
		}
				
		$this->data->button_pos_returns = "129px";
		
		$this->data->next_app_dest_returns = "/?mode=conversion&action=get_next_app_returns&flux_capacitor=" . rand(1,10000000);
		$this->data->queue_count_returns = $this->data->queue_count->returns;
				
		$this->data->next_app_dest_returns_collections_contact = "/?mode=conversion&action=get_next_app_returns_collections_contact&flux_capacitor=" . rand(1,10000000);
		$this->data->queue_count_returns_collections_contact = $this->data->queue_count->returns_collections_contact;
				
		$this->data->next_app_dest_returns_collections_new = "/?mode=conversion&action=get_next_app_returns_collections_new&flux_capacitor=" . rand(1,10000000);
		$this->data->queue_count_returns_collections_new = $this->data->queue_count->returns_collections_new;
				
		$this->data->next_app_dest_returns_past_due = "/?mode=conversion&action=get_next_app_returns_past_due&flux_capacitor=" . rand(1,10000000);
		$this->data->queue_count_returns_past_due = $this->data->queue_count->returns_past_due;
		
		$display_search = true;
		foreach( self::$submenu_list as $menu_item)
		{
			$display_search = false;
			$menu_item_name = $menu_item."_button";
			$file = $menu_item . "_block.html";

			if( is_array($this->data->allowed_submenus) && in_array($menu_item, $this->data->allowed_submenus) )
			{

				$button_str = ($button_count * $button_size) - 20  . "px";
				$this->data->{$menu_item_name} = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/" . $file);
				$this->data->size = $button_str;
				$next_app_dest = "next_app_dest_{$menu_item}";
				$queue_count = "queue_count_{$menu_item}";
				$this->data->{$next_app_dest} = "/?mode=conversion&action=get_next_app_{$menu_item}&flux_capacitor=" . rand(1,10000000);
				if ($menu_item != 'search') $this->data->{$queue_count} = $this->data->queue_count->{$menu_item};
				$button_count++;
				$this->data->{$menu_item_name} = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);

			}
			else
			{
				$this->data->{$menu_item_name} = "";
			}
		}

		if ($display_search)
		{
			$button_str = ($button_count * $button_size) - 20  . "px";
			$this->data->search_button = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/search_block.html");		
			$this->data->size = $button_str;
			$button_count++;
			$this->data->search_button = Display_Utility::Token_Replace($this->data->{$menu_item_name}, (array)$this->data);
		}
		else
		{
			$this->data->search_button = "";
		}
		
/*		
        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));

		$this->data->next_app_button = file_get_contents(CLIENT_VIEW_DIR . "next_app_block.html");

*/
		
		$this->data->next_app_dest_hold = "/?mode=conversion&action=get_next_app_hold&flux_capacitor=" . rand(1,10000000);
		$this->data->button_pos_hold = "253px";
		$this->data->queue_count_hold = $this->data->queue_count->hold;

		$this->data->next_app_dest_act = "/?mode=conversion&action=get_next_app_act&flux_capacitor=" . rand(1,10000000);
		$this->data->button_pos_act = "377px";
		$this->data->queue_count_act = $this->data->queue_count->active;
		
		$this->data->next_app_dest_coll = "/?mode=conversion&action=get_next_app_coll&flux_capacitor=" . rand(1,10000000);
		$this->data->button_pos_coll = "501px";
		$this->data->queue_count_coll = $this->data->queue_count->collection;
		
		$this->data->next_app_dest_other = "/?mode=conversion&action=get_next_app_other&flux_capacitor=" . rand(1,10000000);
		$this->data->button_pos_other = "625px";
		$this->data->queue_count_other = $this->data->queue_count->other;

		//Conditionally display the Cashline ID
		if($this->data->archive_cashline_id)
		{
			$this->data->archive_cashline_id_tag = "Cashline ID: " . $this->data->archive_cashline_id;
		}
		else
		{
			$this->data->archive_cashline_id_tag = NULL;
		}

		$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/conversion_menu.html");

        include_once(WWW_DIR . "include_js.php");
        $this->data->JAVASCRIPT_disable_link = include_js(Array('disable_link'));


		return Display_Utility::Token_Replace($html, (array)$this->data);
	}
}

?>
