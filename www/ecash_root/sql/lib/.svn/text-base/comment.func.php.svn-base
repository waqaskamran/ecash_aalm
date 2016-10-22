<?php
// From Comment_Query_Class

function Add_Comment($company_id, $application_id, $agent_id, $comment, $comment_type = 'standard', $reference_id = NULL)
{
	// @todo remove session dependancy
	if(empty($company_id))
		$company_id = ECash::getCompany()->company_id;
		
	$db = ECash::getMasterDb();
	
	$query = "
		INSERT INTO comment
			(date_created, date_modified, application_id, company_id, agent_id, source, type, related_key, comment)
		VALUES
			(NOW(), NOW(), ?, ?, ?, 'loan agent', ?, ?, ?)
	";
	$db->queryPrepared($query, array($application_id, $company_id, $agent_id, $comment_type, $reference_id, $comment));
}

function Fetch_Comments($application_id)
{
	$db = ECash::getMasterDb();

	$query = "
		SELECT
			date_format(comment.date_created, '%m-%d-%Y %H:%i') as date_created,
			comment.comment,
			comment.type as type,
			comment.source as comment_source,
			comment.comment_id,
			comment.agent_id,
			agent.login,
			concat(lower(agent.name_last), ' ', lower(agent.name_first)) 	AS agent_name,
			lower(agent.name_first) as agent_first_name,
			lower(agent.name_last) as agent_last_name
		FROM
			comment,
			agent
		WHERE
			comment.application_id = ?
		and comment.agent_id = agent.agent_id
		ORDER BY
			comment.date_modified desc
	";
	$st = $db->queryPrepared($query, array($application_id));

	$comment_prefix = array(
		'withdraw' => 'WITHDRAWN',
		'deny' => 'DENIED',
		'dnl'	=> 'DNL',
	);
	
	$comments = array();
	
	while($row = $st->fetch(PDO::FETCH_OBJ))
	{
		if (isset($comment_prefix[$row->type]))
		{
			$row->comment = $comment_prefix[$row->type] . " - " . $row->comment;
		}
		
		$row->agent_name_formatted = ucfirst($row->agent_first_name) . " ". ucfirst($row->agent_last_name);
		$row->agent_name_short = substr(ucfirst($row->agent_first_name), 0, 1) . ". ". ucfirst($row->agent_last_name);
		$comments[] = $row;
	}

	return $comments;
}


?>
