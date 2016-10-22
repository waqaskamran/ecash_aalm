<?php

class Request_Log
{
	public $last_error;
	
	public function Retrieve_Requests($start_boundary, $end_boundary, $company_id, $agent_id = null)
	{
		settype($start_boundary, 'int');
		settype($end_boundary, 'int');
		settype($company_id, 'int');
		
		if (isset($agent_id) && is_array($agent_id) && count($agent_id))
		{
			$agent_query = "AND agent_id IN ('" . implode("','", $agent_id) . "')";
		}
		else if (isset($agent_id) && !is_array($agent_id))
		{
			settype($agent_id, 'int');
			
			$agent_query = "AND agent_id = $agent_id";
		} 
		else 
		{
			$agent_id = "";
		}
		
		$query = "
			SELECT *
			FROM request_log 
			WHERE
				company_id = $company_id
				$agent_query
				AND	start_time BETWEEN $start_boundary AND $end_boundary
		";
		
		$db = ECash::getMasterDb();
		$result = $db->query($query);
		
		$requests = array();
		try 
		{
			while (($row = $result->fetch(PDO::FETCH_ASSOC)) !== FALSE)
			{
				$requests[] = $row;
			}

			
			return $requests;
		} 
		catch (Exception $e)
		{
			$this->last_error = $e->getMessage();		
		}
	}
	
	/**
	 * Inserts row into the request_log table
	 */
	public function Insert_Request($values)
	{
		try
		{
			$acceptable_columns = array(
				'company_id', 'agent_id', 'module',
				'mode', 'action', 'levels', 'start_time',
				'stop_time', 'elapsed_time', 'memory_usage',
				'user_time', 'system_time'
			);
			$accepted_columns = array();
			foreach($acceptable_columns as $key_name)
			{
				if(isset($values[$key_name]))
				{
					$accepted_columns[$key_name] = $values[$key_name];
				}
			}
			
			$db = ECash::getMasterDb();
			$st = $db->queryPrepared(
				"insert into request_log
				(".implode(', ', array_keys($accepted_columns)) . ")
				VALUES (?".str_repeat(",?", sizeof($accepted_columns)-1).")
				",
				array_values($accepted_columns)
			);
			
			return $db->lastInsertId();
		}
		catch (Exception $e)
		{
			$this->last_error = "{$e->getFile()}:{$e->getLine()} - {$e->getMessage()}";
			return false;
		}
	}
}
