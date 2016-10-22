<?php

require_once(SERVER_CODE_DIR.'module_interface.iface.php');
require_once(LIB_DIR.'AgentAffiliation.php');
require_once(SQL_LIB_DIR."application.func.php");

class Module implements Module_Interface
{
    private $search;

    public function __construct(Server $server, $request, $module_name)
    {
        parent::__construct($server, $request, $module_name); 

        ECash::getTransport()->Add_Levels('collections','internal');
        
		$all_sections = ECash::getACL()->Get_Company_Agent_Allowed_Sections($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('all_sections' => $all_sections));
		
		$read_only_fields = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('read_only_fields' => $read_only_fields));
    }

    public function Main()
    {
    }
}

?>
