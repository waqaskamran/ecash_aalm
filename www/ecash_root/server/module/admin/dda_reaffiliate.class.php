<?php

require_once(LIB_DIR.'AgentAffiliation.php');

/*
ob_start();
var_dump($found);
$return = ob_get_contents();
ob_end_clean();
*/

class dda_reaffiliate extends dda
{
    public function get_resource_name()
    {
        $return = "Reassign all of an agent's applications";
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


    private function preview_applications($application_ids, $message)
    {
        $return = "<div style='text-align: center; background-color: #FFFF88; padding: 20px;'>{$message}<br>\n";
		foreach ($application_ids as $app_id) 
		{
			$return .= "(<a id='pullapp{$app_id}' href=\"?module=collections&action=show_applicant&application_id={$app_id}\">{$app_id}</a>)\n";
		}
        $return .= "</div>\n";
		
        return($return);
    }

 
    private function get_agent_options($get_inactive, $default_selection = 0)
    {
        $db = ECash::getMasterDb();
        $query = "
                SELECT          `a1`.`agent_id`             AS  `agent_id`
                    ,           CONCAT( `a1`.`login`
                                    ,   ' ('
                                    ,   `a1`.`name_first`
                                    ,   ' '
                                    ,   `a1`.`name_last`
                                    ,   ') ['
                                    ,   COUNT(`aa1`.`agent_id`)
                                    ,   ']'
                                    )                       AS  `name`
                FROM            `agent`                     AS  `a1`
                LEFT JOIN       `agent_affiliation`         AS  `aa1`   ON
                    (           `aa1`.`agent_id`            =   `a1`.`agent_id`
                        AND (   `aa1`.`date_expiration`     IS  NULL
                            OR  `aa1`.`date_expiration`     >   CURRENT_TIMESTAMP
                            )
                        AND     `aa1`.`affiliation_type`    =   'owner'
                    )
                WHERE           `a1`.`system_id`            =   ".$db->quote($_SESSION["Server_Web_state"]["system_id"])."
                    AND         `aa1`.`affiliation_status`  =   'active'
            ";
        if($get_inactive)
        {
            $query .= "
                    AND         `a1`.`active_status`        =   'inactive'
                ";
        }
        else
        {
            $query .= "
                    AND         `a1`.`active_status`        =   'active'
                ";
        }
        $query .= "
                GROUP BY        `a1`.`agent_id`
            ";
        if($get_inactive)
        {
            $query .= "
                HAVING          COUNT(`aa1`.`agent_id`)     <>  0
                ";
        }
        $query .= "
                ORDER BY        `a1`.`login`            ASC
            ";
        
        $result = $db->query($query);
        $found = "<option value=\"0\">-- Please choose an agent --</option>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            $found .= "<option value=\"".htmlentities($row["agent_id"])."\"";
			if ($default_selection == $row["agent_id"]) $found .= " selected ";
			$found .= ">".htmlentities($row["name"])."</option>";
        }

        return($found);
    }

    private function move_records()
    {
        if(!$this->request->confirmed)
        {
            return($this->error_message("Record not moved because you weren't presented with a confirmation box.  Please use the button, not the return key."));
        }
        if(!$this->request->from_agent_id || !$this->request->to_agent_id)
        {
            return($this->error_message("You must pick a valid agent from BOTH lists"));
        }

        if($this->request->from_agent_id === $this->request->to_agent_id)
        {
            return($this->success_message("Moving records from an agent to himself/herself doesn't really do anything..."));
        }

        try
        {
        	$this->affected_ids = ECash_AgentAffiliation::reassignApplications($this->request->from_agent_id, $this->request->to_agent_id);
        	//moving agent queue apps to other agent
        	$fromAgent = ECash::getAgentById($this->request->from_agent_id);
        	$fromAgent->getQueue()->reassign(ECash::getAgentById($this->request->to_agent_id));
        }
        catch(Exception $e)
        {
            return($this->error_message($e->GetMessage()));
        }

        return($this->success_message("Successfully moved " . $this->affected_ids . " records"));
    }

    private function view_form()
    {
        $return =   "";


        $agent_options = $this->get_agent_options(TRUE, isset($this->request->from_agent_id) ? $this->request->from_agent_id : null);
        $active_agent_options = $this->get_agent_options(FALSE, isset($this->request->to_agent_id) ? $this->request->to_agent_id : null);

        $return .=  "<script>";
        $return .=      "function confirm_and_submit()";
        $return .=      "{";
        $return .=          "if(confirm(\"Are you absolutely certain? This CANNOT be undone!\"))";
        $return .=          "{";
        $return .=              "document.getElementById(\"confirmation\").value = 1;";
        $return .=              "document.getElementById(\"dangerous_form\").submit();";
        $return .=          "}";
        $return .=      "}";
        $return .=  "</script>";
        $return .=  "<form id=\"dangerous_form\" style=\"margin: 0px; padding: 0px;\" method=\"post\" action=\"?module=admin&mode=dda&dda_resource=reaffiliate\">";
        $return .=      "<input id=\"confirmation\" type=\"hidden\" name=\"confirmed\" value=\"0\">";
        $return .=      "<fieldset>";
        $return .=          "<legend>";
        $return .=              "Move Affiliated Applications From Inactive Agent";
        $return .=          "</legend>";
        $return .=          "<dt>";
        $return .=              "Move from inactive agent:";
        $return .=          "</dt>";
        $return .=          "<dd>";
        $return .=              "<select name=\"from_agent_id\" onChange=\"this.form.preview_button.disabled = (parseInt(this.value) == 0);\">";
        $return .=                  $agent_options;
        $return .=              "</select>";
        $return .=          "</dd>";
        $return .=          "<dt>";
        $return .=              "Move to active agent:";
        $return .=          "</dt>";
        $return .=          "<dd>";
        $return .=              "<select name=\"to_agent_id\">";
        $return .=                  $active_agent_options;
        $return .=              "</select>";
        $return .=          "</dd>";
        $return .=          "<dt>";
        $return .=              "<input type=\"button\" value=\"Re-assign applications now\" onClick=\"confirm_and_submit();\">";
        $return .=              "<input type=\"submit\" name=\"preview_button\" value=\"Preview inactive agent applications\" ";
		if (empty($this->request->from_agent_id)) $return .= " disabled "; 
		$return .= ">";
        $return .=          "</dt>";
        $return .=      "</fieldset>";
        $return .=  "</form>";

        return($return);
    }

    private function entry_point()
    {
        $return = "";

		if (isset($this->request->preview_button)) {
            $return .= $this->view_form();
            $return .= $this->preview_applications(ECash_AgentAffiliation::getAgentActiveAffiliations($this->request->from_agent_id), 'Applications affiliated with selected inactive agent:');
		}
        elseif(isset($this->request->confirmed))
        {
            $prereturn = $this->preview_applications(ECash_AgentAffiliation::getAgentActiveAffiliations($this->request->from_agent_id), 'Applications moved to new agent:');
            $return .= $this->move_records();
			$return .= $prereturn;
        }
        else
        {
            $return .= $this->view_form();
        }

        return($return);
    }

    public function main()
    {
        $result = "<font color=\"red\" style=\"float: right;\"><b>Note: This interface does not have any \"undo\" options</b></font><br>";
        $result .= $this->entry_point();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
