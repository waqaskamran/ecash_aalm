<?php

require_once(LIB_DIR.'AgentAffiliation.php');

class dda_controlling_agent extends dda
{
    public function get_resource_name()
    {
        $return = "Change Controlling Collections Agent";

        return($return);
    }

    private function get_agents()
    {
        $return = array();
        $return[0] = '-- REMOVE CONTROLLING AGENT --';

        $db = ECash::getMasterDb();

        $query = "
            SELECT      `agent_id`
                ,       `login`
                ,       `name_last`
                ,       `name_first`
            FROM        `agent`
            WHERE       `system_id` = 3 -- Magic Number: eCash 3.0
					AND   active_status = 'active'
		            ORDER BY    `name_last` ASC
                ,       `name_first` ASC
                ,       `login` ASC
            ";
        
        $st = $db->query($query);
        
        while ($row = $st->fetch(PDO::FETCH_ASSOC))
        {
            $return[$row['agent_id']] = ''
                .   strtolower($row['name_last'])
                .   ', '
                .   strtolower($row['name_first'])
                .   ' ['
                .   $row['login']
                .   ']'
                ;
        }

        return($return);
    }

    private function save_change()
    {
        $return = "";

        $server = $this->server;
        $request = $this->request;

        $application_id = $request->application_id;
        $agent_id = $request->agent_id;

        $history = array();
        $history['action'] = 'change_controlling_agent';
        $history['request'] = $request;
        $history['agent_id'] = $server->agent_id;
        $history['application_id'] = $application_id;
        $history['new_agent_id'] = $agent_id;

		try 
		{
			$app = ECash::getApplicationById($application_id);
			$affiliations = $app->getAffiliations();
			if($agent_id)
			{
				$affiliations->add(ECash::getAgentById($agent_id), 'collections', 'owner', null);
				// date_expiration is not explicity set because it
				// defaults to NULL
			}
			else 
			{
				$affiliations->expire('collections', 'owner');
			}
			$this->save_history($history);

			$return = "";
			$return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
			$return .=      "Changes saved";
			$return .=      "</div>";
		} 
		catch (No_Such_Application_Affiliation_Exception $e) 
		{
			$return = "";
			$return .=      "<div style='text-align: center; background-color: #FF8888; font-weight: bold; padding: 15px;'>";
			$return .=      "Application specified does not exist";
			$return .=      "</div>";
		}

        return($return);
    }

    private function show_form()
    {
        $return = "";

        $agents = $this->get_agents();

        $return .=  "<form id='edit_form' style='border: 1px solid #000000;'>";
        $return .=      "<input type='hidden' name='dda_resource' value='controlling_agent'>";
        $return .=      "<fieldset>";
        $return .=          "<dt>";
        $return .=              "Application Id";
        $return .=          "</dt>";
        $return .=          "<dd>";
        $return .=              $this->build_html_form_input('application_id',isset($this->request->application_id) ? $this->request->application_id : null);
        $return .=          "</dd>";
        $return .=          "<dt>";
        $return .=              "New Controlling Collections Agent";
        $return .=          "</dt>";
        $return .=          "<dd>";
        $return .=              $this->build_html_form_select('agent_id',$agents,isset($this->request->agent_id) ? $this->request->agent_id : null);
        $return .=          "</dd>";
        $return .=          "<dt>";
        $return .=              "<input type='submit' value='Save'>";
        $return .=          "</dt>";
        $return .=      "</fieldset>";
        $return .=  "</form>";

        return($return);
    }

    private function entry_point()
    {
        if(!isset($this->request->undo))
        {
            unset($_SESSION['dda_controlling_agent']);
        }

        $return = "";

        if(isset($this->request->application_id) && isset($this->request->agent_id))
        {
            $return .= $this->save_change();
        }

        $return .= $this->show_form();

        return($return);
    }

    public function main()
    {
        $result = $this->entry_point();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
