<?php
/**
 * @package rpc
 */

require_once COMMON_LIB_DIR .'/security.6.php';
require_once COMMON_LIB_DIR .'/crypt.3.php';
require_once COMMON_LIB_DIR .'/session.9.php';

/**
 * ECash_RPC_Auth
 * Authentication library for ECash_RPC
 *
 * Created on Feb 2, 2007
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */
class ECash_RPC_Auth 
{
	static private $sql_class = ECash_RPC::SqlClass;
	
	static public $max_acl_age = "300"; // in seconds
	
	protected $security;
	protected $sql;
	protected $acl;
	
	protected $session;
	
	protected $restore_acl;
	
	protected $relogin = false;
	
	static public function HTTP($username, $password, $obj = NULL)
	{
//		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		$auth = self::Factory($obj);
		
		if($auth->login($username, $password) !== TRUE) 
		{
			ECash_RPC::Log()->write("Login Failed ($username,$password)");
			ECash_RPC_HTTP::Unauthorized();
		}
		ECash_RPC::Log()->write("Login Success ($username,$password)");
		
		return $auth;
		
	}
	
	static public function check($module, $company = NULL, $obj = NULL)
	{
//		ECash_RPC::Log()->write(__METHOD__ . "() Called");

		$company_object = ECash::getFactory()->getCompanyByNameShort($company);
		ECash::setCompany($company_object);
		$server = Server_Factory::get_server_class('skeletal',null);
		$server->Set_Company($company);
		$_SESSION['server'] = $server;				
		return true;
//		try {
//			return self::Factory($obj)->checkRights($module, $company);
//			
//		} catch (RuntimeException $e) {
//			ECash_RPC::Log()->write("Exception: " . var_export($e, true));
//			ECash_RPC_HTTP::Code($e->getCode());
//		}
	}
	
	static public function allowed($module, $company = NULL, $obj = NULL)
	{
//		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		if(self::check($module, $company, $obj) !==  TRUE) 
		{
			ECash_RPC::Log()->write("Insufficient Rights to {$company}/{$module}");
			ECash_RPC_HTTP::Unauthorized();
		}
		
		ECash_RPC::Log()->write("Sufficient Rights to {$company}/{$module}");
		
	}
	
	static public function Factory($obj = NULL)
	{
//		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		switch (TRUE) {
			case ($obj != NULL && $obj instanceof ECash_RPC_Auth):
				$auth = $obj;
				$_SESSION['ECash_RPC_Auth'] = $auth;
				break;
			
			case ($obj == NULL && isset($_SESSION['ECash_RPC_Auth']) && $_SESSION['ECash_RPC_Auth'] instanceof ECash_RPC_Auth):
				$auth = $_SESSION['ECash_RPC_Auth'];
				break;
				
			case ($obj != NULL && !($obj instanceof self::$sql_class)):
				$obj = NULL;
				
			default:
				$auth = new ECash_RPC_Auth($obj);
				$_SESSION['ECash_RPC_Auth'] = $auth;
		}
		
		return $auth;
		
	}
	
	public function __construct($sqlo = NULL)
	{
//		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		if ( $sqlo instanceof self::$sql_class )
		{
			$this->sql = $sqlo;
		} 
		else
		{
			$this->sql = ECash_RPC::getSQL();			
		}
		
		$extra_insert_fields = array( 'date_created' => 'now()', 'session_open' => "'1'" );
		$extra_update_fields = array( 'date_modified' => 'now()' );

		//$this->session = new ECash_Session('ssid', $session_id, ECash_Session::GZIP);
		//$this->session = new Session_9($this->sql, 'db_not_used', 'session', ($_REQUEST['ssid']) ? $_REQUEST['ssid'] : md5(uniqid(mktime())) , $extra_insert_fields, $extra_update_fields);
		
	}
	
	public function __wakeup()
	{
		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		try {
			$this->crypt = new Crypt_3;
		
			$a = $this->crypt->Decrypt($this->auth_token, session_id());
			if ($a instanceof Error_2) 
			{
				throw new Exception($a->message);
			}
		
			list($username, $password) = unserialize($a);
		
			if ($this->auth_hash !== $this->crypt->Hash(serialize(array($username,$password,session_id())), session_id())) 
			{
				throw new Exception(__METHOD__ . " Error: Invalid authentication hash");
			}
		
			/**
			 * re-login for additional security
			 */
			if($this->relogin == TRUE && $this->login($username, $password) !== TRUE) 
			{
				throw new Exception(__METHOD__ . " Error: Unable to re-authenticate");
			}
			
		} catch (Exception $e) {
			unset($this->auth_token);
			unset($this->auth_hash);
			unset($this->sql);
			unset($this->acl);
			unset($this->security);
			unset($this->restore_acl);
		}
		
	}
	
	public function login($username, $password)
	{
		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		if(!($this->security instanceof Ecash_Security)) 
		{
			$this->security = new  ECash_Security(SESSION_EXPIRATION_HOURS);
		}
		
		if(!($this->crypt instanceof Crypt_3))
		{
			$this->crypt = new Crypt_3;
		}
		
		$ok = $this->security->loginUser(ECash::getConfig()->SYSTEM_NAME,$username, $password, $_SESSION['security_6']['login_time']);	

//		
		if($ok === TRUE) 
		{			
//			$this->auth_token = $this->crypt->Encrypt(serialize(array($username,$password)), session_id());		
//			$this->auth_hash = $this->crypt->Hash(serialize(array($username,$password,session_id())), session_id());
				//ECash::getTransport()->login = $this->login;
				$agent = $this->security->getAgent();
				ECash::setAgent($agent);
				ECash::getTransport()->agent_id = $agent->getModel()->agent_id;
				//ECash::getCompanyId();

		} 
		else 
		{
			ECash_RPC::Log()->write(__LINE__ . ": " . var_export($this,true));
			var_dump($ok);
		}
		return $ok;
		
	}	
	
	public function logout()
	{
		ECash_RPC::Log()->write(__METHOD__ . "() Called");
		
		@session_destroy();		
	}
	
	public function checkRights($section, $company = NULL)
	{
		ECash_RPC::Log()->write(__METHOD__ . "({$section},{$company}) Called");
		
		if(!($this->security instanceof Security_6)) 
		{
//			ECash_RPC::Log()->write(__LINE__ . ": " . var_export($this,true));
			throw new RuntimeException(__METHOD__ . " Error: Invalid Login", 401);
		}
		
		if(!($this->acl instanceof ACL_2) || $this->restore_acl == mktime()	)
		{
			$this->restore_acl = strtotime("+ " . self::$max_acl_age . " seconds");
			if ($this->acl) unset($this->acl);
			$this->acl = ECash::getAcl();
			$this->acl->Set_System_Id($this->security->Get_System_ID());
			$this->acl->Fetch_User_ACL($this->security->Get_Agent_ID(), ECash_RPC::getCompanyId($company));
		}

//		ECash_RPC::Log()->write(__LINE__ . ": " . var_export($this,true));
		
		return $this->acl->Acl_Access_Ok($section, ECash_RPC::getCompanyId($company));
		
	}
}
