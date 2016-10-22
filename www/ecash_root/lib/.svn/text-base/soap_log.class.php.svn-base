<?php

class Soap_Log
{
	public $soap_log_id;
	private $db;
	
	public function __construct() 
	{		
		$this->db = ECash::getMasterDb();
	}
	
	public function Insert_Request($company_id, $application_id, $agent_id, $soap_data, $type = "soap_call")
	{
		$query = 'INSERT INTO soap_log
				  (
					date_created,
					company_id,
					application_id,
					agent_id,	
					soap_data,
					type,			  
					status
				  )
				  VALUES
				  (
				  	now(),
					'. $company_id .',
					'.$application_id.',
					'.$agent_id.',
					compress("'.addslashes($soap_data).'"),
					"'.$this->db->quote($type).'",
					"created"					
				  )
				  ';
		$this->db->exec($query);	
		$this->soap_log_id =  $this->db->lastInsertId();
		return $this->soap_log_id;

	}
	
	
	public function Set_Sent($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->Set_Response($soap_response,"sent");
	}
	
	public function Set_Failed($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->Set_Response($soap_response,"failed");
	}
	
	public function Set_Success($soap_response = null,$soap_log_id = null)
	{
		$this->soap_log_id = is_null($soap_log_id) ? $this->soap_log_id : $soap_log_id;
		$this->Set_Response($soap_response,"success");
	}

	
	private function Set_Response($soap_response = null,$action)
	{
		$soap_response = is_null($soap_response) ? "" : "soap_response = compress(\"".$this->db->quote($soap_response)."\"),";
		
		$query = '
		 UPDATE 
		 	soap_log
		 SET
		 	'.$soap_response.'
		 	status = "'.$this->db->quote($action).'"
		 WHERE
		 	soap_log_id = '.$this->soap_log_id;		
		$this->db->exec($query);
	}
}