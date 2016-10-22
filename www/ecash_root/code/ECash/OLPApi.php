<?php

/**
 * @package cards
 */

class ECash_OLPApi
{
	/**
	 * @var MySQLi_1e
	 */
	private $mysqli;
	
	/**
	 * @var int
	 */
	private $agent_id;
	private $company_id;
	
	protected $calltimes = array('Right Now' => '0 MINUTE',
							'In One Hour' => '1 hour', 
							'Tomorrow' => '1 day');
	
	/**
	 * Create a new ecash api object for card providers.
	 *
	 * @param MySQLi_1e $mysqli
	 */
	public function __construct(MySQLi_1e $mysqli)
	{
		$this->mysqli = $mysqli;
	}
	
	public function setAgent($agent_id)
	{
		$this->agent_id = $agent_id;
	}
	
	public function setCompany($company_id)
	{
		$this->company_id = $company_id;
	}

	public function addCallMe($application_id, $call_time, $phone_number)
	{
		$application = new ECash_Application($application_id, $this->company_id);
		if (!$application->exists())
		{
			throw new Exception("Application $application_id not found.");
		}
		
		if (!array_key_exists($call_time, $this->calltimes))
		{
			throw new InvalidArgumentException('Invalid call time.');
		}
		
		$comment = new Comment();
		$comment->Add_Comment($this->company_id, $application_id, $this->agent_id,
					"\"Call Me\" $call_time at $phone_number");
		
		$ts = strtotime($this->calltimes[$call_time]);
		if ($call_time=='Tomorrow')
		{
			$next_business_day = Company_Time::Singleton()->Get_Days_Forward(1);
			$ts = strtotime($next_business_day);
		}
		
		Set_Standby($application_id, $this->company_id, 'call_me', $ts);
		
		return 'complete';
	}
	
}

?>
