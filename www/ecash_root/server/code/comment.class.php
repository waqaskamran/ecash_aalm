<?php

class Comment
{
	public static function Add_Comment($company_id, $application_id, $agent_id, $comment, $comment_type = 'standard', $reference_id = NULL)
	{
		if(empty($company_id))
			$company_id = ECash::getCompany()->company_id;
			
		if(empty($application_id) || ! ctype_digit((string) $application_id))
			throw new Exception ("Invalid Application_Id '$application_id'");

		if(!get_magic_quotes_gpc())
			$comment = mysql_escape_string($comment);
			
		if(empty($agent_id))
			$agent_id = Fetch_Current_Agent();
			
		if(! is_string($comment))
			throw new Exception ("Can't insert an empty comment value!");
		
		$reference_id = is_null($reference_id) ? "NULL" : $reference_id;

		$query = "
			INSERT INTO comment
			(date_created, date_modified, application_id, company_id, agent_id, source, type, related_key, comment)
			VALUES
			(NOW(), NOW(), {$application_id}, '{$company_id}', '{$agent_id}', 'loan agent','$comment_type', $reference_id, '{$comment}')";

		$db = ECash::getMasterDb();
		$db->query($query);
		return $db->lastInsertId();
	}

	public static function Fetch_Comments($application_id)
	{
		$comments = array();

		$query = "
			SELECT
				date_format(comment.date_created, '%m-%d-%Y %H:%i') as date_created,
				comment.comment,
				comment.type as type,
				comment.source as comment_source,
				comment.comment_id,
				comment.agent_id,
				agent.login
			FROM
				comment,
				agent
			WHERE
				comment.application_id = {$application_id}
			and comment.agent_id = agent.agent_id
			ORDER BY
				comment.date_modified desc";

		$db = ECash::getMasterDb();
		$st = $db->query($query);

		$comment_prefix = array('withdraw' => 'WITHDRAWN', 'deny'     => 'DENIED' );

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			if (isset($comment_prefix[$row->type]))
			$row->comment = $comment_prefix[$row->type] . " - " . $row->comment;
			$comments[] = $row;
		}

		return $comments;
	}


}
