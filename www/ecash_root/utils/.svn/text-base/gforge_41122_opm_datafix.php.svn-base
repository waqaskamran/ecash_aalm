<?php

require_once('sql/lib/scheduling.func.php');

function main()
{
	$application_list = array();
	$application_list[] = 225914317;
	$application_list[] = 230198489;
	$application_list[] = 238626261;
	$application_list[] = 231577887;
	$application_list[] = 235660943;
	$application_list[] = 238176479;
	$application_list[] = 240760567;
	$application_list[] = 240906953;
	$application_list[] = 241546987;
	$application_list[] = 241651445;
	$application_list[] = 241798277;
	$application_list[] = 242346189;
	$application_list[] = 242491551;
	$application_list[] = 234226293;
	$application_list[] = 241069921;
	$application_list[] = 241372353;
	$application_list[] = 238406755;
	
	// Grab our Collections Contact status ID for reference
	$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
	$contact_status = $asf->toId('queued::contact::collections::customer::*root');
	
	$agent_id = ECash::getAgent()->getAgentId();
	
	foreach($application_list as $application_id)
	{
		echo "Updating $application_id\n";
		$application = ECash::getApplicationById($application_id);
		$application->application_status_id = $contact_status;
		$application->modifying_agent_id = $agent_id;
		$application->save();
	
		Remove_Unregistered_Events_From_Schedule($application_id);
	}
}