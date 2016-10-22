<?php
require_once(SQL_LIB_DIR . "scheduling.func.php"); //mantis:4454
require_once(SQL_LIB_DIR.'fetch_status_map.func.php'); //mantis:4454

class dda_application extends dda
{
    public function get_resource_name()
    {
        $return = "Edit Applications";

        if(isset($_SESSION['dda_application']) && isset($_SESSION['dda_application']['id']))
        {
            $return .= ": #".$_SESSION['dda_application']['id'];
        }

        return($return);
    }

    private function html_search_page()
    {
        $return =   "<form>";
		$return .=		"<input type='hidden' name='dda_resource' value='application'>";

        $return .=      $this->build_html_form_select(
                            "field", array(
                             "1" => "Find Application Id",
			),
		    (isset($_SESSION['dda_application']['field'])) ? $_SESSION['dda_application']['field'] : NULL
		    );

        $return .=      $this->build_html_form_input("value", (isset($_SESSION['dda_application']['value'])) ? $_SESSION['dda_application']['value'] : null);
        $return .=      "<input type='submit' value='Search'>";
        $return .=  "</form>";

        return($return);
    }

    private function html_save_changes()
    {
        if  (   !isset($this->request->save)
            ||  !$this->request->save
            ||  !isset($_SESSION['dda_application']['id'])
            )
        {
            return("");
        }

		$agent_id = ECash::getAgent()->getAgentId();

        $history = array();
        $history['action'] = 'edit';
        $history['request'] = $this->request;
        $history['application_id'] = $_SESSION['dda_application']['id'];
        $history['agent_id'] = $agent_id;
        $before = array();
		
        try
        {
        	$application_id = $_SESSION['dda_application']['id'];
        	if($application = ECash::getApplicationById($application_id))
        	{
	        	$before['application_status_id'] = $application->application_status_id;
	        	$before['is_react'] = $application->is_react;
	        	
	        	$react_flag = $application->is_react;
				$this->log->Write("[Agent:{$agent_id}][AppID:{$application_id}] DDA Adjusting application: Status {$before['application_status_id']} -> {$this->request->application_status_id}; Is React {$before['is_react']} -> {$this->request->is_react};");
				$application->application_status_id = intval($this->request->application_status_id);
				$application->is_react = $this->request->is_react;
				$application->modifying_agent_id = $agent_id;

				$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
				if($this->request->application_status_id == $status_list->toId('funding_failed::servicing::customer::*root'))
				{
					$application->date_fund_actual = NULL;

					$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
					$args = array('date_fund_actual' => NULL);
					$app_client->updateApplication($application_id, $args);
				}
				elseif ($this->request->application_status_id == $status_list->toId('withdrawn::applicant::*root'))
				{
					$qm = ECash::getFactory()->getQueueManager();
					$queue_item = new ECash_Queues_BasicQueueItem($application_id);
					$qm->removeFromAllQueues($queue_item);

					if (isset($application->fund_actual)
					    && ($application->fund_actual > 0)
					)
					{
					    $fund_qualified = $application->fund_qualified;
					    $application->fund_actual = $fund_qualified;
    
					    $app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
					    $args = array('fund_actual' => $fund_qualified);
					    $app_client->updateApplication($application_id, $args);
					}
				}
				elseif (
					$this->request->application_status_id == $status_list->toId('internal_recovered::external_collections::*root')
					|| $this->request->application_status_id == $status_list->toId('hold::arrangements::collections::customer::*root')
				)
				{
					$queue_name_array = array('collections_new','collections_general','collections_rework','st_pending');
					foreach ($queue_name_array as $queue_name)
					{	
						$qm = ECash::getFactory()->getQueueManager();
						$queue = $qm->getQueue($queue_name);
						$queue_item = new ECash_Queues_BasicQueueItem($application_id);
						$queue->remove($queue_item);
					}
				}
				elseif (
					$this->request->application_status_id == $status_list->toId('denied::applicant::*root')
					|| $this->request->application_status_id == $status_list->toId('recovered::external_collections::*root')
					|| $this->request->application_status_id == $status_list->toId('verified::deceased::collections::customer::*root')
				)
				{
					$qm = ECash::getFactory()->getQueueManager();
					$queue_item = new ECash_Queues_BasicQueueItem($application_id);
					$qm->removeFromAllQueues($queue_item);
				}

				$application->save();
        	}
        	else
        	{
        		return("<div style='text-align: center; background-color: #FF8888;'>Unable to locate application!</span></div>");
        	}

		}
        catch(Exception $e)
        {
            return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Please tell an administrator:<br><span style='text-align: left;'><pre>".$e->getMessage()."</pre></span></div>");
        }

		//mantis:4454
		if($this->request->recreate_schedule == 'yes')
			Restore_Suspended_Events($history['application_id']);

        $return = "";
        if(!isset($this->request->undo) || !$this->request->undo)
        {
            $return .=  "<form>";
			$return .=		"<input type='hidden' name='dda_resource' value='application'>";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved<br>";
            $return .=          "<input type='hidden' name='save' value='1'>";
            $return .=          "<input type='hidden' name='undo' value='1'>";
            $return .=          "<input type='hidden' name='application_status_id' value='".htmlentities($before['application_status_id'])."'>";
            $return .=          "<input type='hidden' name='is_react' value='".htmlentities($before['is_react'])."'>";
            $return .=          "<input type='submit' value='Undo'>";
            $return .=      "</div>";
            $return .=  "</form>";
        }
        else
        {
			//mantis:4454
			$status_map = Fetch_Status_Map();
			$bankruptcy_array = array(
				Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map), 
				Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map)
				);

			if(in_array($history['request']->application_status_id, $bankruptcy_array))
				Remove_And_Suspend_Events_From_Schedule($history['application_id']);
				//Remove_Unregistered_Events_From_Schedule($history['application_id']);
			//end mantis:4454

            $return .=  "<div style='text-align: center; background-color: #FFFF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes reversed";
            $return .=  "</div>";
        }

        return($return);
    }

    private function get_application_status_tree()
    {
		$query = "
			select ass.*
			from
			application_status asp 
			left join application_status ass on (asp.application_status_id = ass.application_status_parent_id)
			where ass.application_status_id is not null
			and asp.application_status_parent_id is not null
			and ass.active_status = 'active'
			AND ass.name NOT LIKE '%preact%' 
			AND ass.name NOT LIKE '%fraud%' 
			AND ass.name NOT LIKE '%watch%' 
			AND ass.name NOT LIKE '%duplicate%'
		";

        $result = ECash::getMasterDb()->query($query);
		$leaf_ids = array();
        while ($row = $result->fetch(PDO::FETCH_OBJ))
        {
        	$leaf_ids[] = $row->application_status_id;
        }
		
		$factory = ECash::getFactory();
		$asf = $factory->getReferenceList('ApplicationStatusFlat');
		$statuses = array();
		foreach($asf as $application_status)
		{
			if(in_array($application_status->application_status_id, $leaf_ids))
			{
				$statuses[$application_status->application_status_id] =
					//$application_status->toName() . ' (' . $application_status->level0_name  . ')';
					$application_status->level0_name . ' (' . $application_status->toName() . ')';
			}
		}
		asort($statuses);
		return $statuses;
	}

    private function html_search_results()
    {
    	/**
    	 * Note: I've rewritten this to only use the Application ID since we don't support cashline id
    	 * 
    	 * This needs to be cleaned up more at some point.
    	 */
        if  (   !isset($_SESSION['dda_application']['field'])
            ||  !isset($_SESSION['dda_application']['value'])
            )
        {
            return("");
        }

        $application_id = $_SESSION['dda_application']['value'];
		if(! $application = ECash::getApplicationById($application_id))
		{
            $return =   "<div style='background-color: #FFFF00; padding: 5px; text-align: center;'>No records found</span>";
        }
        else
        {
            $_SESSION['dda_application']['id'] = $application_id;

            $return  =  "<form>";
			$return .=		"<input type='hidden' name='dda_resource' value='application'>";
            $return .=      "<input type='hidden' name='save' value='1'>";
            $return .=      "<fieldset style='border: 1px solid #000000;'>";
            $return .=          "<dt>";
            $return .=              "Status";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('application_status_id',
                                        $this->get_application_status_tree(1),
                                        $application->application_status_id
                                        );
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Is React";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('is_react',
                                        array(
                                            'no'    => 'No' ,
                                            'yes'   => 'Yes',
                                            ),
                                        $application->is_react
                                        );
            $return .=          "</dd>";
/**
 * There is no way to detatch the CFE observer at this point and we need to use the models to easily update the App Service.
 * For now, all updates will run CFE.
 */
//            $return .=          "<dt>";
//            $return .=              "Run CFE Rules";
//            $return .=          "</dt>";
//            $return .=          "<dd>";
//            $return .=              "<input type='checkbox' name='run_cfe' id='run_cfe' /><label for='run_cfe'>Run CFE Rules</label> (Caution: CFE actions cannot be undone)";
//            $return .=          "</dd>";
		//mantis:4454
		$status_map = Fetch_Status_Map();
		$bankruptcy_array = array(
					Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map), 
					Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map)
					);

		if(in_array($application->application_status_id, $bankruptcy_array))
		{	
			$return .=          "<dt>";
            		$return .=              "Recreate Schedule";
            		$return .=          "</dt>";

			$return .=          "<dd>";
            		$return .=              $this->build_html_form_select('recreate_schedule',
                                        					array(
                                            					'no'    => 'No' ,
                                            					'yes'   => 'Yes',
                                            				     	     )
                                        			      	      );
            		$return .=          "</dd>";
		}
		//end mantis:4454
            $return .=          "<dt>";
            $return .=              "<input type='submit' value='Save Changes'>";
            $return .=          "</dt>";
            $return .=      "</fieldset>";
            $return .=  "</form>";
        }

        return($return);
    }

    private function search()
    {
        if  (   isset($this->request->field)
            &&  isset($this->request->value)
            )
        {
            $_SESSION['dda_application']['field'] = $this->request->field;
            $_SESSION['dda_application']['value'] = $this->request->value;
        }

        $return  = "";
        $return .= $this->html_search_page();
        $return .= $this->html_save_changes();
        $return .= $this->html_search_results();

        return($return);
    }

    public function main()
    {
        $result = $this->search();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
