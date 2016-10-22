<?php

class ECash_Security
{
	/**
	 * @var int
	 */
	private $timeout;
	/**
	 * @var ECash_Agent
	 */
	private $agent;

	/**
	 * @var DB_IConnection_1
	 */
	private $db;

	public function __construct($timeout = 10, DB_IConnection_1 $db = NULL)
	{
		$this->timeout = $timeout;
		$this->db = $db;
	}

	public function loginUser($system_name_short, $login, $password, &$session_storage_location = NULL)
	{
		$this->agent = ECash::getAgentBySystemLogin($system_name_short, $login, $this->db);

		if ($this->agent->authenticate($login, $password))
		{
			$this->agent_model = $this->agent->getModel();
			$session_storage_location = strtotime("now");

			return TRUE;
		}
		return FALSE;
	}

	public function getAgent()
	{
		return $this->agent;
	}

	public function checkTimeout($session_storage_location)
	{
		if( strtotime("+{$this->timeout} hours", $session_storage_location) < strtotime("now") )
		{
			return FALSE;
		}
		return TRUE;
	}

	public static function manglePassword($clear_password)
	{
		return md5($clear_password);
	}

}

?>
