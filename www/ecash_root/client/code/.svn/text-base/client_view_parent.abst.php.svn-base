<?php


abstract class Client_View_Parent
{
	protected $transport;
	protected $module_name;
	protected $mode;
	protected $view;
	protected $data;
	protected $display;

	// Used by Display_Application to call stuff like Get_Header, Get_Body_Tags, etc.
	// If ever need client-side processing but don't want to send new HTML content,
	// set this to false (example, downloading a file)
	public $send_display_data = true;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->mode = ECash::getTransport()->Get_Next_Level();
		$this->view = ECash::getTransport()->Get_Next_Level();
		$this->transport = ECash::getTransport();
		$this->module_name = $module_name;
		$this->data = ECash::getTransport()->Get_Data();

		if(file_exists(CUSTOMER_LIB . "display_".$this->view.".class.php"))
		{
			require_once(CUSTOMER_LIB . "display_".$this->view.".class.php");
			$this->display  = new Customer_Display_View(ECash::getTransport(), $this->module_name, $this->mode);	
		}
		else if(file_exists(CLIENT_CODE_DIR . "display_". $this->view . ".class.php"))
		{
			require_once(CLIENT_CODE_DIR . "display_". $this->view . ".class.php");
			$this->display  = new Display_View(ECash::getTransport(), $this->module_name, $this->mode);	
		}
		else
		{
			throw new Exception("Unknown view {$this->view} for module {$this->module_name}");
		}
	}

	// Variable replacement callback function.  This got a little complicated.  Fix later.
	protected function Replace($matches)
	{
		// Is it an edit layer?
		if( strpos($matches[0], "_edit%%%") )
		{
			$matches[1] = substr($matches[1], 0, -5);

			if(!empty($this->data->saved_error_data->$matches[1]))
			{
				$return_value = $this->data->saved_error_data->{$matches[1]};
			}
			elseif(isset($this->data->$matches[1]))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		else // Non edit replacement.
		{
			if (isset($this->data->$matches[1]))
			{
				$return_value = $this->data->$matches[1];
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		return $return_value;
	}

	public function Get_Header()
	{
		return $this->display->Get_Header();
	}

	public function Get_Body_Tags()
	{
		return $this->display->Get_Body_Tags();
	}

	public function Get_Module_HTML()
	{
		return $this->display->Get_Module_HTML();
	}

	public function Include_Template()
	{
		if (method_exists($this->display, "Include_Template"))
		{
			return $this->display->Include_Template();
		}

		return true;
	}


	public function Create_Queue_Buttons()
	{
		// Create Queue Buttons
		$this->data->queue_buttons = "";
		$button_count = 0;
		if (!ECash::getACL()->Acl_Check_For_Access(Array('loan_servicing', 'restriced_access')))
		{
			
			if(ECash::getTransport()->available_queues)
			{			
				foreach(ECash::getTransport()->available_queues as $queue_params)
				{
					$name = $queue_params['display_name'];
					$name_short = $queue_params['name_short'];
					$count = $queue_params['count'];
					$button_count++;
	
					$module = (isset($queue_params['link_module'])) ? $queue_params['link_module'] : $this->module_name;
					$mode   = (isset($queue_params['link_mode']))   ? $queue_params['link_mode']   : $this->mode;
					$action = (isset($queue_params['action'])) ? $queue_params['action'] : 'get_next_application';
	
					$flux = rand(1, 10000000);
					
					$id = str_replace(" ","",str_replace("/","",$name));
					
					$new_button =
					<<<END_HTML
						<a id="AppQueueBar{$id}" class="menu" href="/?module={$module}&mode={$mode}&action={$action}&flux_capacitor={$flux}&queue={$name_short}" onClick="window.location=this.href; this.href='javascript:void(0)';">
							<div class="menu_label_nextapp {$this->mode}">
								{$name}: &nbsp;<span class="queue_count">{$count}</span>
							</div>
						</a>
END_HTML;
					//THIS IS A SLOPPY HACK TO INJECT THE EMAIL QUEUE BUTTON IN THE 'RIGHT' PLACE... REMOVE AS SOON AS POSSIBLE!
					if($name_short == "at_reminder_queue")
					{
						$new_button .= '%%%email_queue%%%';
					}
					
					$this->data->queue_buttons .= $new_button;
				}
			}
	
			// My Apps Queue
			$count = 0;
			$button_count++;
			
			if(is_numeric(ECash::getTransport()->my_queue_count)) $count = ECash::getTransport()->my_queue_count;
			$class = ($count > 0) ? 'menu_label_myqueue_red': 'menu_label_myqueue';
			
			$new_button =
					<<<END_HTML
						<a id="AppQueueBarMyQueue" class="menu" href="/?action=personal_queue_pull">
							<div class="{$class} {$this->mode}">
								My Queue: &nbsp;<span class="queue_count">{$count}</span>
							</div>
						</a>
END_HTML;
			$this->data->queue_buttons .= $new_button;
			//
	
			//Follow Up Queue
			$count = 0;
			$button_count++;
	
			if(is_numeric(ECash::getTransport()->followup_queue_count)) $count = ECash::getTransport()->followup_queue_count;
			$class = ($count > 0) ? 'menu_label_followup_queue_red': 'menu_label_followup_queue';
	
			$new_button =
					<<<END_HTML
						<a id="AppQueueBarFollowUp" class="menu" href="/?queue=follow_up&action=get_next_application">
							<div class="{$class} {$this->mode}">
								Follow Up: &nbsp;<span class="queue_count">{$count}</span>
							</div>
						</a>
END_HTML;
			$this->data->queue_buttons .= $new_button;
		}
	}

	/**
	 * Simple method for retrieving a template
	 * 
	 * Will check the customer_lib first, then the default client
	 * module dir.  This will eventually get junked by Refactor.
	 *
	 * @param string $path
	 * @return string
	 */
	static public function getHTMLTemplate($path)
	{
		if(file_exists(CUSTOMER_LIB . $path))
		{
			return file_get_contents(CUSTOMER_LIB . $path);
		}
		else
		{
			return file_get_contents(CLIENT_MODULE_DIR . $path);
		}
	}
}

?>
