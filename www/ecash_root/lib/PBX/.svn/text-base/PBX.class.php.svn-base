<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Feb 6, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

if(!defined("eCash_PBX_DIR")) define("eCash_PBX_DIR", realpath(dirname(__FILE__)));
 
require_once LIB_DIR . "/company_rules.class.php";

require_once eCash_PBX_DIR . "/History.class.php";

class eCash_PBX 
{

	protected $server;
	
	protected $pbx_mode;
	
	private static $log_context;
	
	static public function isEnabled(Server $server, $company_id)
	{
		return (bool) (ECash::getConfig()->PBX_ENABLED === TRUE);
	}
	
	static public function QuickDial(Server $server, $phone, $contact_id = NULL, $timeout = NULL)
	{
		$pbx = new eCash_PBX($server);
		return $pbx->Dial($phone, $contact_id, $timeout);
	}
	
	static public function LoggedDial(Server $server, $phone, $contact_id, $timeout = NULL)
	{
		$phone = preg_replace("/\D/","",$phone);
		return eCash_PBX_History::Factory(new eCash_PBX($server), $contact_id)->Dial($phone, $contact_id, $timeout);
	}
	
	public function Dial($phone, $contact_id = NULL, $timeout = NULL)
	{
		$allowed = $this->getConfig("PBX Enabled");
		
		if ($allowed !== 'true') {
			throw new Exception (__METHOD__ . " Error: This Capability is not enabled. Please contact your eCash Partner Representative");
		}
		
		self::registerLogin($this->server);
		
		$timeout = (!$timeout) ? $this->getConfig("PBX Agent Answer Timeout") : $timeout;
		
		switch (strtolower($this->getPBXType())) {
			case "asterisk":
				$context = $this->getConfig("PBX Asterisk Context");
				$context = ($context) ? $context : "default";
				
				$priority = $this->getConfig("PBX Asterisk Priority");
				$priority = ($priority) ? $priority : 1;
								
				$caller_id = $this->getConfig("Company Phone");
				
				return $dpb->Dial($phone, $this->server->agent_phone, $caller_id, $timeout, $context, $priority, $contact_id);
				
			case "custom":
			default:
				return $this->getPBX()->Dial($phone, $contact_id, $timeout);
				
		}
		
	}
	
	
	public function __construct(Server $server)
	{
		$this->server = $server;
	}

	static public function registerContact(Server $server, $contact_id, $dialed, $agent_id = NULL)
	{
		$db = ECash::getMasterDb();
		
		$dialed = $db->quote($dialed);
		
		$agent_id = ($agent_id) ?  $agent_id : $server->agent_id;
		
		$query = "
			INSERT INTO active_pbx_contacts ( application_contact_id, pbx_dialed, agent_id) VALUES ( {$contact_id}, {$dialed}, {$agent_id} )
			ON DUPLICATE KEY UPDATE pbx_dialed = {$dialed}, date_modified = CURRENT_TIMESTAMP
			";

		$db->exec($query);
		
	}
	
	static public function deregisterContact(Server $server, $contact_id, $agent_id = NULL)
	{
		$agent_id = ($agent_id) ?  $agent_id : $server->agent_id;
		$agent_id = ($agent_id) ? $agent_id : 0 ;
		
		$query = "
			DELETE FROM active_pbx_contacts WHERE application_contact_id = {$contact_id} AND agent_id = {$agent_id}
			";
		$db = ECash::getMasterDb();
		
		$db->exec($query);
		
	}
	
	static public function registerLogin(Server $server)
	{
		$query = "
			INSERT INTO agent_pbx_map ( agent_id, pbx_extension, company_id ) VALUES ( {$server->agent_id}, {$server->agent_phone}, {$server->company_id} )
			ON DUPLICATE KEY UPDATE agent_id = {$server->agent_id}
		";
		$db = ECash::getMasterDb();
		
		$db->exec($query);		
	}
		
	public function getRegisteredContact($extension)
	{
		$db = ECash::getMasterDb();

		$extension = $db->quote($extension);
		
		$query = "
			SELECT application_contact_id FROM active_pbx_contacts WHERE pbx_dialed = {$extension}
			";

		$res = $db->query($query);
		if ($row = $res->fetch(PDO::FETCH_OBJ))
		{
			return $row->application_contact_id;
		} 
		
		return 0; // should be default agent id.. won't bother with it now
		
	}
	
	public function setRegisteredAgent()
	{
		return self::registerLogin($this->server);
	}
	
	public function getRegisteredAgent($extension)
	{
		$query = "
			SELECT agent_id FROM agent_pbx_map WHERE pbx_extension  = {$extension} AND company_id = {$this->server->company_id}
			";
		
		$db = ECash::getMasterDb();
		$res = $db->query($query);
		if ($row = $res->fetch(PDO::FETCH_OBJ))
		{
			return $row->agent_id;
		} 
		
		return 0; // should be default agent id.. won't bother with it now
		
	}
	
	public function getSQL()
	{
		return ECash::getMasterDb();
	}
	
	public function getCompanyId($company = NULL)
	{
		return $this->server->company_id;
	}
	
	public function getServer()
	{
		return $this->server;
	}
	
	protected function getPBXType()
	{
		$this->pbx_mode = $this->getConfig("PBX Server Type");
	}
	
	public function getPBX()
	{
		switch (strtolower($this->getPBXType())) {
			case "asterisk":
				require_once eCash_PBX_DIR . "/Asterisk.class.php";
				return new eCash_PBX_Asterisk($this);
				
			case "custom":
			default:
				if(!file_exists(CUSTOMER_LIB . "/PBX.class.php")) 
				{
					throw new Exception(__METHOD__ . " Error: PBX Mode Type has not been selected in eCash configuration.");					
				}
				
				// pull from customer_lib
				require_once CUSTOMER_LIB . "/PBX.class.php";
				return new eCash_Custom_PBX($this);
		}
	}
	
	public function getConfig($rule_name) 
	{
		return Company_Rules::Singleton($this->getCompanyId(), $this->getSQL())->Get_Config_Value($rule_name);
	}	
	
	static public function Log()
	{
		if (!class_exists('Applog_Singleton')) require_once COMMON_LIB_DIR . '/applog.singleton.class.php';
		
		if(!self::$log_context) self::$log_context = ( isset($_SESSION["Server_state"]["company"]) ) ? strtoupper($_SESSION["Server_state"]["company"]) : "";
		
		return Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY."/pbx", APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, self::$log_context, 'TRUE');
		
	}
}