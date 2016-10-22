<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

class Display_View extends Display_Parent
{


	
	public function Get_Header()
	{
        include_once(WWW_DIR . "include_js.php");
		return include_js();
	}

	public function Get_Module_HTML()
	{
	 
		$this->Get_Queues();
    	$html = file_get_contents(CLIENT_MODULE_DIR . $this->module_name . "/view/main_screen.html");
		return Display_Utility::Token_Replace($html, (array)$this->data);
    		   	
	}
	
	public function Get_Queues()
	{
		$queues = $this->data->queues;
		
		$main_queues = "<div id='main_queues'>";
		foreach ($queues as $queue_name => $queue)
		{
			$queue_count = count($queue['apps']);
			
			if ($queue_count)
			{
				$main_queues.= "<a onclick=\"LoadHelpScreen('{$queue['name']}',document.getElementById('{$queue_name}_div').innerHTML);\">";
				$main_queues.= "<span id='{$queue_name}'> + </span>";
				$main_queues.= "{$queue['name']}</a>";
			}
			else 
			{
				$main_queues.= "{$queue['name']}";
			}
			$main_queues.= " <a href='/?module={$queue['module']}&mode={$queue['mode']}&action=get_next_application&queue={$queue_name}'>[{$queue_count}]</a><br>\n";
			$main_queues.= "<div id='{$queue_name}_div'  style='overflow:auto;display:none;' align='center'>\n";
			$main_queues.= $this->Get_Queue_Applications($queue['apps']);
			$main_queues.= "<br></div>";
		}
		$main_queues.="</div>";
		
		$this->data->main_queues =$main_queues;
	}
	public function Get_Body_Tags()
	{
	}
   
	protected function Get_Queue_Applications($applications)
	{
		if (!is_array($applications))
		{
			return;
		}
		//$queue_details = "<br/><br/>";
		$queue_details .="<table style='background:white;'>
						    <tr>
						<th>Application ID</th><th>Name</th><th>Status</th><th>Time Entered in Queue</th>
							</tr>";
		foreach ($applications as $application)
		{
			$queue_details.= "<tr>";
			$queue_details.= "
								<td>
								<a onclick=\"window.location.href='?module={$application['module']}&action=show_applicant&company_id=1&application_id={$application['application_id']}'\">{$application['application_id']} </td> 
								<td>{$application['name']}</td> <td>{$application['status']}</td> <td> {$application['queue_time']}</td>
							  </tr>";
			$queue_details.= "</a>\n";
			
		}
		$queue_details.= "</table>\n";
		return $queue_details;
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
