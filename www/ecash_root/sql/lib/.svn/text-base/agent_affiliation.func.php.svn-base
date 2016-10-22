<?php


function Remove_Arrangement_Affiliation($agent_id, $evid)
{
	$db = ECash::getMasterDb();
	$args = array();
	
	$query = "DELETE FROM agent_affiliation_event_schedule WHERE ";
	
	if ($agent_id == null)
	{
		$query .= "event_schedule_id = :evid";
		$args['evid'] = $evid;
	}
	else if ($evid == null)
	{
		$query .= "agent_id = :agent_id";
		$args['agent_id'] = $agent_id;
	}
	else
	{
		$query .= "agent_id = :agent_id AND event_schedule_id = :evid";
		$args['agent_id'] = $agent_id;
		$args['evid'] = $evid;
	}
	
	$db->queryPrepared($query, $args);
	
	// Removed any Debt Company Event Schedule assoc if they exisit [RayL]
	if ($evid != null)
	{
		$query = "DELETE FROM debt_company_event_schedule where event_schedule_id = ?";
		$db->queryPrepared($query, array($evid));
	}
}

?>
