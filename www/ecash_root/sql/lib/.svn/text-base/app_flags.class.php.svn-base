<?php

require_once(SQL_LIB_DIR ."application.func.php");


class Application_Flags
{
	private $db;
	private $acl;
	private $application_id;
	static $flag_types;

	/**
	 * @param Server $server - We don't need this any more. [W!-12-05-2008]
	 * @param int $application_id
	 */
	public function __construct($server = null, $application_id)
	{
		$this->db = ECash::getMasterDb();

		/**
		 * This is cheese.  If we're running as a cron-job we're not going to check for permissions
		 * but if we running a web-interactive session then we do. [BR]
		 */
		if(! defined('IS_CRONJOB') || IS_CRONJOB !== TRUE)
		{
			$this->acl = ECash::getACL();
		}
		else 
		{
			$this->acl = FALSE;	
		}
		$this->application_id = $application_id;
		$this->agent_id = Fetch_Current_Agent();
		$this->company_id = ECash::getCompany()->company_id;
		
	}

	public function Set_Flag($flag, $operation_location = Array())
	{
		$this->retrieve_flag_types();
		if (!array_key_exists($flag, self::$flag_types))
		{
			throw new Exception("Attempt to set flag that does not exist ({$flag})");
		}

		if ($this->acl !== FALSE && $this->acl->Acl_Check_For_Access($operation_location) == false)
		{
			throw new Exception("Permission denied to set flag ({$flag}) at permission location (" . $this->sprint_r($operation_location) . ")");
		}
		if ($this->Get_Flag_State($flag)) {
			throw new Exception("Attempt to set flag that is already set! ({$flag} on {$this->application_id})");
		}

		// columns modifying_agent_id and company_id and ON DUPLICATE because triggers manage the application audit.
		$query = "
						INSERT INTO application_flag SET 
							modifying_agent_id = {$this->agent_id},
							flag_type_id = " . self::$flag_types[$flag]['id'] . ",
							application_id = {$this->application_id},
							company_id = {$this->company_id},
							active_status = 'active'
						ON DUPLICATE KEY UPDATE active_status = 'active', modifying_agent_id = {$this->agent_id}, company_id = {$this->company_id}";
	
		$retval = $this->db->exec($query);
		if($retval)
		{
			ECash::getApplicationById($this->application_id);
	        $engine = ECash::getEngine();	
	        $engine->executeEvent('FLAG_ADD');
		}
		return $retval;
	}
	public function Clear_Flag($flag, $operation_location = Array())
	{
		$this->retrieve_flag_types();
		if (!array_key_exists($flag, self::$flag_types))
		{
			throw new Exception("Attempt to set flag that does not exist ({$flag})");
		}

		if ($this->acl->Acl_Check_For_Access($operation_location) == false)
		{
			throw new Exception("Permission denied to clear flag ({$flag}) at location (" . $this->sprint_r($operation_location) . ")");
		}
		if (!$this->Get_Flag_State($flag)) 
		{
			throw new Exception("Attempt to clear flag that is already clear! ({$flag} on {$this->application_id})");
		}

		// columns modifying_agent_id and company_id and action update instead of delete because triggers manage the application audit.
		$query = "
						UPDATE application_flag 
						SET active_status = 'inactive', modifying_agent_id = {$this->agent_id}, company_id = {$this->company_id}
						WHERE application_id = {$this->application_id}
						AND flag_type_id = " . self::$flag_types[$flag]['id'];
	
		$retval = $this->db->exec($query);
		if($retval)
		{
			ECash::getApplicationById($this->application_id);
	        $engine = ECash::getEngine();	
	        $engine->executeEvent('FLAG_REMOVE');
		}
		return $retval;
	}

	public function Get_Active_Flag_Array()
	{
		$this->retrieve_flag_types();
		$query = "
					SELECT name_short, name
					FROM application_flag af
					JOIN flag_type USING (flag_type_id)
					WHERE application_id = {$this->application_id}
					  AND af.active_status = 'active'";
	
		$result = $this->db->query($query);
		$flags = Array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$flags[$row->name_short] = $row->name;
		}
		return $flags;
	}
	
	public function Get_Flag_State($flag)
	{

		$this->retrieve_flag_types();
		$query = "
					SELECT count(*) as 'count'
					FROM application_flag
					WHERE application_id = {$this->application_id}
					  AND flag_type_id = '" . self::$flag_types[$flag]['id'] . "'
					  AND active_status = 'active'";
		$result = $this->db->query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		if ($row->count > 0) return true;
		return false;
	}
	
	//CFE/CCRT FLAG STUFF

	public function Get_Flag_Description($flag) 
	{
		$this->retrieve_flag_types();
		return self::$flag_types[$flag]['name'];
	}

	private function retrieve_flag_types ()
	{
		if (is_array(self::$flag_types)) return false;
		$flag_types = Array();
		$query = "
					SELECT flag_type_id, name_short, name
					FROM flag_type
					WHERE active_status = 'active'";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$flag_types[$row->name_short] = Array('id' => $row->flag_type_id, 'name' => $row->name, 'name_short' => $row->name_short);
		}
		self::$flag_types = $flag_types;
	}


	//functions added for CCRT's needs.


	public function Get_Flag_Types()
	{
		$this->retrieve_flag_types();
		return $this->flag_types;
	}


	public function Get_Application_Flags()
	{
		$query = "
					SELECT 
						ft.name_short, 
						ft.name,
						af.modifying_agent_id,
						af.application_flag_id,
						a.name_first  as agent_name_first,
						a.name_last  as agent_name_last,
						af.date_created
					FROM application_flag af
					JOIN flag_type ft USING (flag_type_id)
					JOIN agent a ON (af.modifying_agent_id = a.agent_id)
					WHERE application_id = {$this->application_id}
					  AND af.active_status = 'active'";
	
		$result = $this->db->query($query);
		$flags = Array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$flags[$row['name_short']] = $row;
		}

		return $flags;

	}
		

	public function Get_Application_Flag_History()
	{
	$query = "
					SELECT 
						ft.name_short, 
						ft.name,
						afh.action,
						afh.date_created,
						agent.name_first,
						agent.name_last

					FROM application_flag_history afh
					JOIN flag_type ft USING (flag_type_id)
					JOIN agent  ON (afh.modifying_agent_id = agent.agent_id)
					WHERE application_id = {$this->application_id}";
		$result = $this->db->query($query);
		$history = Array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$history[] = $row;
		}

		return $history;
	}

	public function Add_Application_Flag_History($flag,$action)
	{
		$this->retrieve_flag_types();
		// columns modifying_agent_id and company_id and ON DUPLICATE because triggers manage the application audit.
		$query = "
						INSERT INTO application_flag_history SET 
							date_created = now(),
							modifying_agent_id = {$this->agent_id},
							flag_type_id = " . self::$flag_types[$flag]['id'] . ",
							application_id = {$this->application_id},
							company_id = {$this->company_id},
							action = '{$action}'
						";
	
		return $this->db->exec($query);
	}

	public function Remove_Flag($flag)
	{
		$this->retrieve_flag_types();

		if (!array_key_exists($flag, $this->flag_types))
		{
			throw new Exception("Attempt to set flag that does not exist ({$flag})");
		}

		if (!$this->Get_Flag_State($flag)) {
			throw new Exception("Attempt to clear flag that is already clear! ({$flag} on {$this->application_id})");
		}

		// columns modifying_agent_id and company_id and action update instead of delete because triggers manage the application audit.
		$query = "
						UPDATE application_flag 
						SET active_status = 'inactive', modifying_agent_id = {$this->agent_id}, company_id = {$this->company_id}
						WHERE application_id = {$this->application_id}
						AND flag_type_id = " . self::$flag_types[$flag]['id'];
	
		return $this->db->exec($query);
	}

	//add a flag to the application
	public function Add_Flag($flag)
	{
		$this->retrieve_flag_types();
		if (!array_key_exists($flag, $this->flag_types))
		{
			throw new Exception("Attempt to set flag that does not exist ({$flag})");
		}

		// columns modifying_agent_id and company_id and ON DUPLICATE because triggers manage the application audit.
		$query = "
						INSERT INTO application_flag SET 
							modifying_agent_id = {$this->agent_id},
							flag_type_id = " . self::$flag_types[$flag]['id'] . ",
							application_id = {$this->application_id},
							company_id = {$this->company_id},
							active_status = 'active'
						ON DUPLICATE KEY UPDATE active_status = 'active', modifying_agent_id = {$this->agent_id}, company_id = {$this->company_id}";
	
		return $this->db->exec($query);
	}

	public function Add_Flag_Type($name, $description)
	{
		// columns modifying_agent_id and company_id and ON DUPLICATE because triggers manage the application audit.
		$query = "
						INSERT INTO flag_type SET 
							name = '{$description}',
							name_short = '{$name}',
							date_created = now(),
							date_modified = now(),
							active_status = 'active'
						ON DUPLICATE KEY UPDATE active_status = 'active',date_modified = now()";
	
		return $this->db->exec($query);
	}



	 function sprint_r($var) 
	 {
		ob_start();
		print_r($var);
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}
}

?>
