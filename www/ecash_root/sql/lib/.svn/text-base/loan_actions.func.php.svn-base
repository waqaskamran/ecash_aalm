<?php
/* unused?
/* unused?
function Get_Status_ID($status_history_id) 
{
	$db = ECash::getMasterDb();
	
	$query = "
		-- eCash 3.5, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT application_status_id
		FROM status_history
		WHERE status_history_id = ?
	";
	$st = $db->queryPrepared($query, array($status_history_id));
	
	$row = $st->fetch(PDO::FETCH_OBJ);
	return $row->application_status_id;
}
*/

function Insert_Loan_Action($application_id, $loan_action_id, $status, $agent_id)
{
	$db = ECash::getMasterDb();

	$status_utility = new Status_Utility($db);
	$status_id = $status_utility->Get_Status_ID_By_Chain($status);

	$factory = ECash::getFactory();
	$LoanAction = $factory->getModel('LoanActions');
	$LoanAction->loadBy(array('loan_action_id' => $loan_action_id));

	/* $loan_action_section_id) not set
	$LoanActionSection = $factory->getModel('LoanActionSection');
	$LoanActionSection->loadBy(array('loan_action_section_id' => $loan_action_section_id));
	$loan_action_section_name = $LoanActionSection->name_short;
	*/
	$loan_action_section_name = '';

	$args = array();
	$args['application_id'] = $application_id;
	$args['loan_action'] = $LoanAction->name_short;
	$args['loan_action_section'] = $loan_action_section_name;
	$args['application_status'] = $status;
	$args['agent_id'] = $agent_id;

	$la_client = $factory->getLoanActionClient();
	$lah_id = $la_client->insert($args);


	/*$query = "
		INSERT INTO loan_action_history
		(loan_action_history_id, loan_action_id, application_id, date_created, application_status_id, agent_id)
		VALUES (?, ?, ?, now(), ?, ?)
	";
	
	$db->queryPrepared(
		$query,
		array(
			$lah_id,
			$loan_action_id,
			$application_id,
			$status_id,
			$agent_id,
		)
	);
	*/

	try
	{
		// put stat call here... or where this is called.
		$stats = new Stat();
		$stats->LoanAction_Hit_Stat($application_id, $loan_action_id);
	}
	catch (Exception $e)
	{
//		$log->Write("Caught exception trying to hit stat: ".$e->getTraceAsString());
//		$stats->Reconnect();
	}

	return $lah_id;
}

function Get_Loan_Action_Types($type = "VERIFY")
{
	$db = ECash::getMasterDb();
	
	$query = "
		SELECT
			loan_action_id,
			name_short,
			description,
			status,
			type
		 FROM loan_actions
		 WHERE status = 'ACTIVE'
		  AND	type LIKE '%{$type}%'
		 GROUP BY description
		 ORDER BY description, name_short
	";
	$st = $db->query($query);
	
	return $st->fetchAll(PDO::FETCH_OBJ);
}

function Get_Company_Loan_Action_Types($type = "VERIFY", $company_id)
{
	$db = ECash::getMasterDb();
	
	$query = "
		SELECT
			la.loan_action_id,
			la.name_short,
			la.description,
			la.status,
			la.type
		 FROM
			loan_actions as la
			join loan_action_company as lac using (loan_action_id)
		 WHERE
			la.status = 'ACTIVE'
		  AND la.type LIKE '%{$type}%'
		  AND lac.company_id = ?
		 ORDER BY la.description
	";
	$st = $db->queryPrepared($query, array($company_id));
	
	return $st->fetchAll(PDO::FETCH_OBJ);
}

function Get_Loan_Action_Name_Short($loan_action_id) 
{
	$db = ECash::getMasterDb();
	
	$query = "
		SELECT name_short
		FROM loan_actions
		WHERE loan_action_id = ?
	";
	$st = $db->queryPrepared($query, array($loan_action_id));
	
	$row = $st->fetch(PDO::FETCH_OBJ);
	return $row->name_short;
}

//mantis:6106
function Get_Loan_Action_ID($name_short) 
{
	$db = ECash::getMasterDb();
	
	$query = "
		SELECT loan_action_id
		FROM loan_actions
		WHERE name_short = ?
	";
	$st = $db->queryPrepared($query, array($name_short));
	
	$row = $st->fetch(PDO::FETCH_OBJ);
	return $row->loan_action_id;
}

//this is retarded
function Get_Loan_Action_Description_By_Name($name_short)
{
	$db = ECash::getMasterDb();
	
	$query = "
		SELECT description
		FROM loan_actions
		WHERE name_short = ?
	";
	$st = $db->queryPrepared($query, array($name_short));
	
	$row = $st->fetch(PDO::FETCH_OBJ);
	return $row->description;
}

function Get_Loan_Actions($application_id)
{
    $loan_action_array = array();

    $la = ECash::getFactory()->getQueryClient()->getLoanActions(array(
		"applicationId" => $application_id,
		"actionName" => null
		));
    //if (is_object($la) && (isset($la->item))) $la = $la->item;

    if (is_array($la) && is_array($la[0]) && is_object($la[0][0]) && isset($la[0][0]->key)) {
    	$tmp = array();
	foreach($la as $a){
		$tmp2 = array();
		foreach ($a as $elem) {
			$key = $elem->key;
			$tmp2[$key] = $elem->value;
		}
		$tmp[] = $tmp2;
	}
	$la = $tmp;
    }

    if(is_array($la) && count($la) > 0) {

		// TODO: use tmp table
        $action = lookupByColumnValue("select la.name_short, la.description, la.type from loan_actions la", "name_short", $la, "loan_action_name_short");
	$section = lookupByColumnValue("select name_short, description from loan_action_section", "name_short", $la, "loan_action_section_name_short");
        $comment = lookupByColumnValue("select comment_id, comment from comment", "comment_id", $la);
        $agent = lookupByColumnValue("select agent_id, if(a.name_first is null, 'unknown', lower(a.name_first)) AS agent_name_first, if(a.name_last is null, 'unknown', lower(a.name_last)) AS agent_name_last, concat(a.name_first, ' ', a.name_last) AS agent_name from agent a", "agent_id", $la);
        foreach($la as $a) {

            $a['description'] = $action[$a['loan_action_name_short']]->description;
			$a['loan_action_type'] = $action[$a['loan_action_name_short']]->type;
			$a['comment'] = empty($comment[$a['comment_id']]->comment) ? '' : $comment[$a['comment_id']]->comment;

            $a['agent_name'] = $agent[$a['agent_id']]->agent_name;
			$a['agent_name_first'] = $agent[$a['agent_id']]->agent_name_first;
			$a['agent_name_last'] = $agent[$a['agent_id']]->agent_name_last;

			$a['agent_name_short'] = substr(ucfirst($a['agent_name_first']), 0, 1).". ".ucfirst($a['agent_name_last']);
			$a['loan_action_section_description'] = empty($section[$a['loan_action_section_name_short']]->description) ? '' : $section[$a['loan_action_section_name_short']]->description;
			$a['status_name_short'] = $a['status'];

		$lah_model = ECash::getFactory()->getModel('LoanActionHistory');
		$lah_model->loadBy(array('loan_action_history_id'=>$a['loan_action_history_id'],));
		$a['is_resolved'] = $lah_model->is_resolved;

            $loan_action_array[$a['loan_action_history_id']] = (object)$a;
        }
    }

	return $loan_action_array;
}

function lookupByColumnValue($sql, $column, $rowset, $name = null)
{
	if ($name === null) $name = $column;
	$db = ECash::getMasterDb();
    $result = array();
	if (count($rowset))
	{
		$i = 0;
		$sql .= " where {$column} in (";
		foreach($rowset as $row)
		{
			if ($row[$name] !== null)
			{
				$sql .= $db->quote($row[$name]).",";
				$i++;
			}
		}
		if ($i > 0)
		{
			$res = $db->query(substr($sql, 0, -1).')');
			while($row = $res->fetch(DB_IStatement_1::FETCH_OBJ)) {
				$result[$row->$column] = $row;
			}
		}
	}
    return $result;
}


?>
