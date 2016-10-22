<?php
require_once('libolution/Object.1.php');
require_once(SQL_LIB_DIR.'fetch_status_map.func.php');
require_once(SERVER_CODE_DIR . "stat.class.php");
require_once(LIB_DIR . "business_rules.class.php");

class eCash_Application extends Object_1
{
	private $application_id;
	
	private $application_status;
	
	private $date_next_contact;
	
	private $modifying_agent_id;
	
	private $updateFields = array();
	
	private $stat;
	
	public function __construct($application_id = null)
	{
		$this->application_id = $application_id;
	}
	
	protected function getStat()
	{
		if (empty($stat))
		{
			$this->stat = new Stat();
		}
		
		return $this->stat;
	}
	
	public function failFunding($agent_id)
	{
		$this->setApplicationStatus(new eCash_ApplicationStatus('funding_failed::active::customer::*root'), $agent_id);
		$this->updateFields['date_fund_actual'] = 'NULL';
	}
	
	public function fundApplication($agent_id)
	{
		$this->setApplicationStatus(new eCash_ApplicationStatus('approved::servicing::customer::*root'), $agent_id);
		$this->updateFields['date_fund_actual'] = 'CURDATE()';
		$this->updateFields['fund_actual'] = 'IFNULL(fund_actual, fund_qualified)';
	}
	
	public function update($date_modified = null)
	{
		$db = ECash::getMasterDb();
		if (empty($date_modified))
		{
			$date_modified_check = '';
		}
		else 
		{
			$date_modified_check = ' AND date_modified = '.
				$db->quote($date_modified);
		}
		
		$fields = array();
		foreach ($this->updateFields as $name => $value)
		{
			$fields[] = $name.' = '.$value;
		}
		
		$fields = implode(', ', $fields);
		
		$query = "
			UPDATE application
			SET
				{$fields}
			WHERE
				application_id = {$this->application_id}
				{$date_modified_check}
		";
		
		return $db->exec($query);
	}
	
	public function checkStatusHistory()
	{
		return $this->getStat()->Check_Status_Hist($this->application_id, $this->application_status->ApplicationStatusArray);
	}
	
	public function hitStat()
	{
		
		$company = ECash::getCompany()->name_short;
		$stats  = $this->getStat();
		
		
		//Make the DataX Fund Update call if applicable (only for Pre-Fund, without a fundupd type of 'NONE') 
		if($this->application_status->ApplicationStatusString == 'approved::servicing::customer::*root')
		{
			$business_rules = new ECash_Business_Rules(get_mysqli());
			$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($this->application_id);
			$rule_sets = $business_rules->Get_Rule_Set_Tree($rule_set_id);
			
			//get the fund update type to determine if the DataX call should even be made.
			//$datax_fundupd_type = ECash::getConfig()->DataX_Fund_Update_Call;
			$datax_fundupd_type = isset($rule_sets['FUNDUPD_CALL']) ? $rule_sets['FUNDUPD_CALL'] : NULL;
			
			if(!empty($datax_fundupd_type) && $datax_fundupd_type != 'NONE')
			{			
				$fund_call = CLI_EXE_PATH . "php -f " . CLI_SCRIPT_PATH . "idv_cli_wrapper.php " . EXECUTION_MODE .
					" {$company} {$this->application_id} >> /virtualhosts/log/applog/" .
					APPLOG_SUBDIRECTORY . "/fork_cli.log &";
	
				get_log()->Write("Forking DataX Fund Update: {$fund_call}");
				// fork the DataX fund event wrapper script
				exec($fund_call);
			}
		}

		$timer_name = "Statistics App ID - " . $this->application_id;
		$timer = new Timer(get_log());
		$timer->Timer_Start($timer_name);
		if((isset($_SESSION['current_app'])) && (isset($_SESSION['current_app']->is_react))) 
		{
			$is_react = ($_SESSION['current_app']->is_react == 'no') ? '' : 'is_react';
		} 
		else 
		{
			$is_react = '';
		}

		try 
		{
			$stats->Ecash_Hit_Stat(
				$this->application_id, 
				$this->application_status->ApplicationStatusString, 
				$is_react
			);
		} 
		catch (Exception $e) 
		{
			get_log()->Write("Caught exception trying to hit stat: ".$e->getMessage());
			get_log()->Write($e->getTraceAsString());
			$stats->Reconnect();
		}
		$timer->Timer_Stop($timer_name);
	}
	
	public function setApplicationStatus(eCash_ApplicationStatus $applicationStatus, $agent_id)
	{
		$this->application_status = $applicationStatus;
		$this->modifying_agent_id = $agent_id;
		$this->updateFields['application_status_id'] = 
			$db->quote($applicationStatus->ApplicationStatusId);
		$this->updateFields['modifying_agent_id'] = 
			$db->quote($agent_id);
		$this->updateFields['date_application_status_set'] = 'CURRENT_TIMESTAMP()';
	}
	
	public function setFollowUpTime($follow_up_time)
	{
		$this->date_next_contact = $follow_up_time;
		$this->updateFields['date_next_contact'] = 
			$db->quote($follow_up_time);
	}
	
	public function getApplicationStatus()
	{
		return $this->application_status;
	}
	
	public function getApplicationId()
	{
		return $this->application_id;
	}
	
	public function getFollowUpTime()
	{
		return $this->date_next_contact;
	}
}

class eCash_ApplicationStatus extends Object_1 
{
	private $name;
	private $short_name;
	private $application_status_id;
	private $application_status_array;
	private $application_status_string;
	
	public function __construct($status)
	{
		if (ctype_digit((string) $status))
		{
			$this->createFromId($status);
		}
		
		elseif (is_array($status))
		{
			$this->createFromArray($status);
		}
		
		elseif (is_string($status))
		{
			$this->createFromString($status);
		}
		
		else
		{
			throw new Exception('Unknown status type passed to '.__CLASS__.'. Please pass an array, chain, or id.');
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getShortName()
	{
		return $this->short_name;
	}
	
	public function getApplicationStatusId()
	{
		return $this->application_status_id;
	}
	
	public function getApplicationStatusArray()
	{
		return $this->application_status_array;
	}
	
	public function getApplicationStatusString()
	{
		return $this->application_status_string;
	}
	
	protected function createFromId($application_status_id)
	{
		$status_map = Fetch_Status_Map();
		
		$this->application_status_id = $application_status_id;
		$this->application_status_string = $status_map[$application_status_id]['chain'];
		$this->application_status_array = explode('::', $this->application_status_string);
	}
	
	protected function createFromArray(Array $application_status_array)
	{
		$status_map = Fetch_Status_Map();
		
		$this->application_status_array = $application_status_array;
		$this->application_status_string = implode('::', $this->application_status_array);
		$this->application_status_id = Search_Status_Map($this->application_status_string, $status_map);
	}
	
	protected function createFromString($application_status_string)
	{
		$status_map = Fetch_Status_Map();
		
		$this->application_status_string = $application_status_string;
		$this->application_status_array = explode('::', $this->application_status_string);
		$this->application_status_id = Search_Status_Map($this->application_status_string, $status_map);
	}
	
	static public function create($status)
	{
		return new self($status);
	}
}
?>
