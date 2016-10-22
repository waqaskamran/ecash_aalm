<?php
require_once(LIB_DIR.'AgentAffiliation.php');


/*
ob_start();
var_dump($found);
$return = ob_get_contents();
ob_end_clean();
*/

class dda_queues extends dda
{	
    public function get_resource_name()
    {
        $return = "Edit queue contents";
        if(isset($this->request->subsection_title) && $this->request->subsection_title)
        {
            $return .= ": ".$this->request->subsection_title;
        }

        return($return);
    }

    private function error_message($string)
    {
        $return = "
            <div style='text-align: center; background-color: #FF8888; padding: 35px;'>
                $string
            </div>
            ";
        return($return);
    }

    private function success_message($string)
    {
        $return = "
            <div style='text-align: center; background-color: #88FF88; padding: 35px;'>
                $string
            </div>
            ";
        return($return);
    }

    private static $agents = NULL;
    private static function get_queue_agents()
    {
    	/**
    	 * @TODO: Make this not retarded.
    	 */
    	if (self::$agents === NULL)
    	{
    		self::$agents = array();
    		$agents = ECash::getFactory()->getReferenceList('Agent');
    		foreach ($agents as $agent)
    		{
    			self::$agents[$agent->agent_id] = $agent->login;
    		}
    	}

    	return self::$agents;
    }

    private static $queue_names = NULL;
    private static $queue_objs = NULL;
    private static function get_queue_names()
    {
    	if (self::$queue_names === NULL)
    	{
    		$queue_manager = ECash::getFactory()->getQueueManager();
    		$queues = $queue_manager->getQueues();

    		self::$queue_names = array();

    		foreach ($queues as $queue)
    		{
    			self::$queue_names[] = $queue->Model->name;
    			self::$queue_objs[$queue->Model->name] = $queue;
    		}
    	}
    	return self::$queue_names;
    }

    private function new_entry_form()
    {
        $automated_queue_options = "";
        foreach(self::get_queue_names() as $queue)
        {
            $automated_queue_options .= "<option value=\"".htmlentities($queue)."\">".htmlentities($queue)."</option>";
        }


        $manual_queue_options = "";
/**
        foreach($this->get_queue_names(TRUE,FALSE) as $queue)
        {
            $manual_queue_options .= "<option value=\"".htmlentities($queue)."\">".htmlentities($queue)."</option>";
        }**/

        $return = "
            <fieldset style=\"text-align: left; margin: 10px; z-index: 0;\">

                <legend>
                    Add Application To Queue
                </legend>
                <form method=\"post\" style=\"margin: 0px; padding: 0px;\">
                    <input type=\"hidden\" name=\"subsection\" value=\"insert\">
                    <dl>
                        <dt>Please choose a queue:</dt>
                            <dd>
                                Automated Queue:
                                <select name=\"automated_queue\">
                                    <option value=\"\">
                                        -- Choose one --
                                    </option>
                                    $automated_queue_options
                                </select>
                            </dd>
                       <!-- <dd>
                                Create Queue:
                                <input type=\"text\" name=\"new_queue\" value=\"\">
                            </dd> -->
                        <dt>Application Id:</dt>
                            <dd>
                                <input type=\"text\" name=\"application_id\" value=\"\">
                            </dd>
                        <dt>Availability: (Queue entries can be made available within a given time range)</dt>
                            <dd>
                                Becomes available: (date)
                                <input type=\"text\" name=\"date_available\" value=\"".date('m/d/Y g:i:s a')."\">
                            </dd>
                            <dd>
                                Becomes unavailable: ('never' or date)
                                <input type=\"text\" name=\"date_unavailable\" value=\"never\">
                            </dd>
                        <dt>Cleanup Options:</dt>
                            <dd>
                                Remove from automated queues?
                                <input type=\"checkbox\" name=\"delete_automated\" checked=\"checked\">
                            </dd>
                            <dd>
                                Unaffiliate from all agents?
                                <input type=\"checkbox\" name=\"unaffiliate\" checked=\"checked\">
                            </dd>
                        <dt><input type=\"submit\" value=\"Create/Move Queue Entry\"></dt>
                    </dl>
                </form>
            </fieldset>
            ";
        return($return);
    }

    private function filter_form()
    {
        $joined_queue_options = "";
        foreach($this->get_queue_names(TRUE,TRUE,TRUE) as $queue)
        {
            $joined_queue_options .= "<option value=\"".htmlentities($queue)."\" selected=\"selected\">".htmlentities($queue)."</option>";
        }

        $agent_id_options = "";
        foreach($this->get_queue_agents() as $agent_id => $agent_login)
        {
            $agent_id_options .= "<option value=\"".htmlentities($agent_id)."\" selected=\"selected\">".htmlentities($agent_login)."</option>";
        }

        $return = "
            <fieldset style=\"text-align: left; margin: 10px; z-index: 0;\">

                <legend>
                   Show Queue Contents
                </legend>
                <form method=\"post\" style=\"margin: 0px; padding: 0px;\">
                    <input type=\"hidden\" name=\"subsection\" value=\"perform_search\">
                    <dl>
                        <dt>Please choose queues (hold \"Control\" key while making selection):</dt>
                            <dd>
                                <select name=\"queue_names[]\" multiple=\"1\" size=\"5\">
                                    $joined_queue_options
                                </select>
                            </dd>
                        <dt>Application Ids (separate with spaces, leave blank to show ANY/ALL):</dt>
                            <dd>
                                <input type=\"text\" name=\"application_ids\" value=\"\">
                            </dd>
                        <dt>Creating Agents (hold \"Control\" key while making selection):</dt>
                            <dd>
                                <select name=\"agent_ids[]\" multiple=\"1\" size=\"5\">
                                    $agent_id_options
                                </select>
                            </dd>
                        <dt>Show the first X records that match from each queue:</dt>
                            <dd>
                                <input type=\"text\" name=\"limit\" value=\"100\">
                            </dd>
                        <dt><input type=\"submit\" value=\"Show Queue Contents\"></dt>
                    </dl>
                </form>
            </fieldset>
            ";
        return($return);
    }

    private function sort_found_asc($a, $b)
    {
        if($a["sort_string"] == $b["sort_string"])
        {
            if($a["application_id"] == $b["application_id"])
            {
                return(0);
            }
            if($a["application_id"] < $b["application_id"])
            {
                return(-1);
            }
            return(1);
        }
        if($a["sort_string"] < $b["sort_string"])
        {
            return(-1);
        }
        return(1);
    }

    private function sort_found_desc($a, $b)
    {
        if($a["sort_string"] == $b["sort_string"])
        {
            if($a["application_id"] == $b["application_id"])
            {
                return(0);
            }
            if($a["application_id"] > $b["application_id"])
            {
                return(-1);
            }
            return(1);
        }
        if($a["sort_string"] > $b["sort_string"])
        {
            return(-1);
        }
        return(1);
    }

    private function view_contents()
    {
		$no_pull = FALSE;
        $queue_names = $this->request->queue_names;
        {
            if(!is_array($queue_names))
            {
                return($this->error_message("Invalid queue names"));
            }
            $possible_queue_names = $this->get_queue_names(TRUE,TRUE);
            foreach($queue_names as $array_id => $queue_name)
            {
                if(!is_string($queue_name) || !in_array($queue_name,$possible_queue_names))
                {
                    return($this->error_message("Invalid queue name: $queue_name"));
                }
            }
            sort($queue_names);
        }

        $application_ids = $this->request->application_ids;
        {
            if(!is_string($application_ids) || !preg_match('/^[0-9 ]*$/',$application_ids))
            {
                return($this->error_message("Invalid application ids, please use only numbers and spaces"));
            }
            $application_ids = preg_split('/[ ]+/',$application_ids,-1,PREG_SPLIT_NO_EMPTY);
            $application_ids = join(",",$application_ids);
        }

        $agent_ids = $this->request->agent_ids;
        {
            if(!is_array($agent_ids))
            {
                return($this->error_message("Invalid agent ids"));
            }

            $possible_agent_ids = $this->get_queue_agents();
            foreach($agent_ids as $agent_id)
            {
                if(!isset($possible_agent_ids[$agent_id]))
                {
                    return($this->error_message("Invalid agent id: $agent_id"));
                }
            }

            $agent_ids = join(",",$agent_ids);
        }

        $limit = $this->request->limit;
        {
            if(!preg_match('/^[0-9]+$/',$limit))
            {
                return($this->error_message("Invalid record limit, please use only positive integers"));
            }
        }

        $counter = 0;
        $return = "
            <script>
                function toggle_visible(id)
                {
                    var obj = document.getElementById(id);
                    if(\"none\" == obj.style.display)
                    {
                        obj.style.display = \"\";
                    }
                    else
                    {
                        obj.style.display = \"none\";
                    }
                }
            </script>
			<div style=\"overflow: hidden; overflow-y: auto; height: 100px; text-align: left;\">
            ";

//        var_dump(ECash::getConfig());
//        $sorts = ECash::getConfig()->QUEUE_CONFIG->getSortOrders();

        foreach($queue_names as $queue_name)
        {
        	$queue_obj = self::$queue_objs[$queue_name];

        	$query = "
        		SELECT
        			q.date_queued date_created,
        			q.date_available date_available,
        			a.login creating_agent_login,
        			q.related_id application_id,
        			q.priority sort_string
        		FROM " . $queue_obj->getQueueEntryTableName() . " q
        		JOIN agent a ON (a.agent_id = q.agent_id)
        		JOIN application ap ON (ap.application_id = q.related_id)
        		JOIN application_status aps ON (aps.application_status_id = ap.application_status_id)
        		WHERE
        			aps.name_short != 'skip_trace'
        			AND q.queue_id = :queue_id
        			AND q.agent_id IN ($agent_ids)

        	";



            if($application_ids)
            {

                $query .= "
                            AND q.related_id in ($application_ids)
                    ";

            }

			$db	= ECash::getMasterDb();
            $result = $db->queryPrepared($query, array('queue_id' => $queue_obj->Model->queue_id));

            $found = array();

            while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== FALSE)
            {
                $found[] = $row;
            }
            $return .= ""
                .   "<fieldset style='z-index:0;'>"
                .       "<legend>"
                .           htmlentities($queue_name)
                .       "</legend>"
                .       "<dd>"
                ;
            foreach($found as $array_id => $row)
            {
                switch($queue_obj->Model->name_short)
                {
                    case "verification_react"      :
                    case "verification_non_react"  :
                        $queue_routing = "module=funding&mode=verification";
                        $no_pull = true;
                        break;

                    case "underwriting_react"      :
                    case "underwriting_non_react"  :
                    case "Underwriting (react)"      :
                    case "Underwriting (non-react)"  :
                    case "Underwriting"  :
                        $queue_routing = "module=funding&mode=underwriting";
                        break;


                    case "watch"                     :
      //                  $queue_routing = "module=watch";
                        $queue_routing = "module=fraud&mode=watch";
                        break;

                    case "collections_new"           :
                    case "collections_returned_qc"   :
                    case "collections_general"       :
                        $queue_routing = "module=collections&mode=internal";
//                        $queue_routing = "module=collections";
                        break;

                    default:
                        $queue_routing = "module=loan_servicing&mode=account_mgmt";
                        break;
                }

                $found[$array_id] = ""
                    .       "<a id=\"link_$counter\" href=\"javascript: toggle_visible('link_$counter'); toggle_visible('record_$counter');\">"
					.		$row["application_id"]
					.		((isset($row["waiting_for_recycle"]) && $row["waiting_for_recycle"] > 1) ? '(Waiting For Recycle)' : '')
					.		(!empty($row["final_queuing"]) ? '(No More Recycles)' : '')
					.		(!empty($row["not_yet_available"]) ? '(Not Yet Available)' : '')
					.		"</a>"
                    .       "<div id=\"record_$counter\" style=\"border: 1px solid #000000; display: none;\">"
                    .           "<a id=\"link_$counter\" href=\"javascript: toggle_visible('link_$counter'); toggle_visible('record_$counter');\">".$row["application_id"]."</a>"
                    .           "&nbsp;&nbsp;&nbsp;"
                    .           "<a href=\"?dda_resource=queues&subsection=remove&queue_name=".urlencode($queue_name)."&application_id=".urlencode($row["application_id"])."\">[remove]</a>"
                    .           "&nbsp;&nbsp;&nbsp;"
                    .           "<a id='pullapp".urlencode($row["application_id"])."' href=\"?$queue_routing&action=show_applicant&application_id=" . $row["application_id"] . "\">[view application]</a>"
                    .			($no_pull ? "<input onclick=\"if(!this.checked){document.getElementById('pullapp".urlencode($row["application_id"])."').href='?".$queue_routing.'&action=show_applicant&application_id=' . $row["application_id"] . "';}else{document.getElementById('pullapp".urlencode($row["application_id"])."').href='?$queue_routing&criteria_type_1=application_id&search_deliminator_1=is&search_criteria_1=".urlencode($row["application_id"])."&criteria_type_2=&search_deliminator_2=is&search_criteria_2=&search=Search&action=search';}\" type=checkbox> (Check to pull from Queue on view)" : "")
                    .           "<br>"
                    .           "<ul>"
                    .           "<li>"
                    .           "Date queued: "
                    .           date('g:i:s A D M. jS, Y',strtotime($row["date_created"]))
                    .           "<li>"
                    .           "Queued by: "
                    .           $row["creating_agent_login"]
                    .           "<li>"
                    .           "Available from "
                    .           date('g:i:s A D M. jS, Y',strtotime($row["date_available"]))
                    .           "<li>"
                    .           "Expires: "
                    .           ( empty($row["date_unavailable"]) ? "Never" : date('g:i:s A D M. jS, Y',$row["date_unavailable"]) )
                    .           "</ul>"
                    .       "</div>"
                    ;

                $counter ++;
            }
            $return .= join(" , ",$found);

            $return .= ""
                .       "</dd>"
                .   "</fieldset>"
                ;
        }
		$return .= "</div>";
		if(!$counter)
		{
			$return = "NO MATCHING APPLICATIONS FOUND";
		}
        return($return);
    }

    private function remove_queue_entry()
    {
        $queue_name     = $this->request->queue_name;
        $application_id = $this->request->application_id;

        if(is_null($application_id) || !$application_id)
        {
            return($this->error_message("Error: Application Id not found"));
        }

        if(is_null($queue_name) || !$queue_name)
        {
            return($this->error_message("Error: Queue name not found"));
        }

        self::get_queue_names();
        if (isset(self::$queue_objs[$queue_name]))
        {
        	$queue = self::$queue_objs[$queue_name];
        	$queue->remove(new ECash_Queues_BasicQueueItem($application_id));
			$agent_id = ECash::getAgent()->getAgentId();
			$this->log->Write("[Agent:{$agent_id}][AppID:{$application_id}] DDA Removing from queue: {$queue_name}");
        }		
		
        return($this->success_message("Queue record removed"));
    }

    private function insert_queue_entry()
    {
		require_once(SQL_LIB_DIR."util.func.php");
		
		self::get_queue_names();
		
		
		$automated_queue_name = $this->request->automated_queue;
		if(!is_null($automated_queue_name) && !is_string($automated_queue_name))
		{
			return($this->error_message("Invalid automated queue selection"));
		}
		
		$manual_queue_name = $this->request->manual_queue;
		if(!is_null($manual_queue_name) && !is_string($manual_queue_name))
		{
			return($this->error_message("Invalid manual queue selection"));
		}
		
		$new_queue_name = $this->request->new_queue;
		if(!is_null($new_queue_name) && !is_string($new_queue_name))
		{
			return($this->error_message("Invalid new queue selection"));
		}
		
		if  (   (   $automated_queue_name   &&  $manual_queue_name  )
			||  (   $automated_queue_name   &&  $new_queue_name     )
			||  (   $manual_queue_name      &&  $new_queue_name     )
		)
		{
			return($this->error_message("Invalid selection, please only choose a single queue"));
		}
		
		if($automated_queue_name)
		{
			$queue_name = $automated_queue_name;
		}
		elseif($manual_queue_name)
		{
			$queue_name = $manual_queue_name;
		}
		elseif($new_queue_name)
		{
			$queue_name = $new_queue_name;
		}
		else
		{
			return($this->error_message("Invalid selection, no queue selected"));
		}
		
		
		$application_id = $this->request->application_id;
		
		
		if(!is_string($application_id) || !preg_match('/^[0-9]+$/',$application_id))
		{
			return($this->error_message("Invalid application id: $application_id"));
		}
		
		if(!Application_Exists($application_id))
		{
			return ($this->error_message("Application ID Does not exist!"));
		}
		
		$date_available = $this->request->date_available;
		if(!is_string($date_available))
		{
			return($this->error_message("Invalid date available: $date_available"));
		}
		if(!($date_available_nixtime = strtotime($date_available)))
		{
			return($this->error_message("Invalid date format: $date_available"));
		}
		
		$date_unavailable = $this->request->date_unavailable;
		if(!is_string($date_unavailable))
		{
			return($this->error_message("Invalid date unavailable: $date_unavailable"));
		}
		if("never" === $date_unavailable)
		{
			$date_unavailable_nixtime = NULL;
		}
		elseif(!($date_unavailable_nixtime = strtotime($date_unavailable)))
		{
			return($this->error_message("Invalid date format: $date_unavailable"));
		}
		
		$delete_automated = $this->request->delete_automated ? TRUE : FALSE;
		$delete_manual = $this->request->delete_manual ? TRUE : FALSE;
		$unaffiliate = $this->request->unaffiliate ? TRUE : FALSE;
		
		$qm = ECash::getFactory()->getQueueManager();
		
		$remove_item = new ECash_Queues_BasicQueueItem($application_id);
		if ($delete_automated)
		{
			$qm->getQueueGroup('automated')->remove($remove_item);
		}
		else
		{
			self::$queue_objs[$queue_name]->remove($remove_item);
		}
		
		if($unaffiliate)
		{
			$application = ECash::getApplicationById($application_id);
			$affiliations = $application->getAffiliations();
			$affiliations->expireAll();
		}
		
		
		$item = self::$queue_objs[$queue_name]->getNewQueueItem($application_id);
		$item->DateAvailable = $date_available_nixtime;

		//[#36339] Agent Queues require an owning agent id and reason (default to 'other') to be set
		$agent_id = ECash::getAgent()->getAgentId();
		if($item instanceof ECash_AgentQueue_QueueItem)
		{
			$item->AgentId = $agent_id;
			$item->QueueReason = 'other';
		}
		
		$this->log->Write("[Agent:{$agent_id}][AppID:{$application_id}] DDA inserting into queue: {$queue_name}");
		self::$queue_objs[$queue_name]->insert($item);
		
		return($this->success_message("Record created"));
    }

    private function entry_point()
    {
        $return = "";

        switch(isset($this->request->subsection) ? $this->request->subsection : NULL)
        {
            case "perform_search":
				$return .= '<table border="0" cellpadding="0" cellspacing="20"><tr><td style="border: 1px solid #666666;">';
                $return .= $this->filter_form();
				$return .= '</td><td valign="top" style="border: 1px solid #666666;">';
                $return .= $this->new_entry_form();
				$return .= '</td></tr><tr><td colspan="2" style="border: 1px solid #666666;">';
                $this->request->subsection_title = "Show Queue Contents";
                $return .= $this->view_contents();
				$return .= '</td></tr></table>';
                break;

            case "remove":
                $this->request->subsection_title = "Removing queue entry";
                $return .= $this->remove_queue_entry();
                break;

            case "insert":
                $this->request->subsection_title = "Inserting new entry";
                $return .= $this->insert_queue_entry();
                break;

            default:
				$return .= '<table border="0" cellpadding="0" cellspacing="20"><tr><td style="border: 1px solid #666666;">';
                $return .= $this->filter_form();
				$return .= '</td><td valign="top" style="border: 1px solid #666666;">';
                $return .= $this->new_entry_form();
				$return .= '</td></tr></table>';
                break;
        }

        return($return);
    }

    public function main()
    {
        $result = "<font color=\"red\" ><b>Note: This interface does not have any \"undo\" options</b></font><br>";
        $result .= $this->entry_point();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
