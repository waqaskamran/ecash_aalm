<?php

function lookup_action_id($action_name)
{
	$db = ECash::getMasterDb();

    $query = "
        SELECT  `action_id`
        FROM    `action`
        WHERE   `name_short`    =   ".$db->quote($action_name)."
        ";
    $result = $db->query($query);
    $row = $result->fetch(PDO::FETCH_OBJ);

    if ($row === FALSE)
    {
    	$query = "insert into action (name,name_short) 
    	VALUE  
    	(".$db->quote($action_name).",".$db->quote($action_name).")";
    	
    	$db->exec($query);
    	
    	$row->action_id = $db->lastInsertId();
    }

    return($row->action_id);
}

function track_agent_action($columns_object_or_array)
{

	$db = ECash::getMasterDb();

    if(is_object($columns_object_or_array))
    {
        $columns_object_or_array = get_class_vars($columns_object_or_array);
    }

    if(!isset($columns_object_or_array["action_id"]) || !isset($columns_object_or_array["agent_id"]))
    {
        throw(new Exception("Egg on face: Programmer forgot action_id or agent_id parameter, passed arguments: " . var_export($columns_object_or_array,true)));
    }

    foreach($columns_object_or_array as $column_name => $column_value)
    {
        if(FALSE !== strpos($column_name,"`"))
        {
            throw(new Exception("SQL Injection Attack"));
        }
        if(in_array($column_name,array('date_created')))
        {
            throw(new Exception("Column not allowed: $column_name"));
        }

        $columns_object_or_array[$column_name] = "`$column_name` = ".$db->quote($column_value);
    }
    $columns_object_or_array = join(" , ", $columns_object_or_array);

    $query = "
        INSERT INTO `agent_action`
        SET         $columns_object_or_array
        ";
    $db->exec($query);

    return(NULL);
}

?>
