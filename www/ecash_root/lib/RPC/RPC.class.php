<?php
/**
 * eCash_RPC
 * Skeletal SOAP/PRPC library 
 * 
 * eCash_RPC permits any class to be exposed to an authenticated RPC user via 
 * the automated choice of a SOAP Interface or PRPC interface
 *
 * Created on Feb 2, 2007
 *
 * @package eCash
 * @category RemoteProcedure
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

if (!defined("ECASH_RPC_URI")) define ("ECASH_RPC_URI", "http://" . $_SERVER['SERVER_NAME'] . "/" . $_SERVER['PHP_SELF'] . ( ($_SERVER['QUERY_STRING']) ? "?" . preg_replace("/(\&|\?)?(sct=?([^\&]*)?)?(\&|\?)?wsdl$/","",$_SERVER['QUERY_STRING']) : NULL));

if (!defined("eCash_RPC_DIR")) define ("eCash_RPC_DIR", dirname(__FILE__));

require_once eCash_RPC_DIR . "/HTTP.class.php";
require_once eCash_RPC_DIR . "/Auth.class.php";

interface eCash_iWSDL 
{

	public static function getRPCMethods();
	
}

class eCash_RPC 
{

	static private $token_salt = "2--fgn 53ie09dfihu34";
	
	const SystemName 	= "eCash_RPC";
	const SqlClass		= 'ECash::getMasterDb()';
	const PersistenceSession	= SOAP_PERSISTENCE_SESSION;
	const PersistenceRequest	= SOAP_PERSISTENCE_REQUEST;
	
	private static $log_context;
	
	public static function getCompanyId($company)
	{
		if(ECash::getCompany()->company_id) 
		{
			return ECash::getCompany()->company_id;
			
		} 
		else 
		{
			$sql = self::getSQL();
			$res = $sql->query("SELECT company_id FROM company WHERE name_short = '{$company}'");
			if ($dat = $res->fetch(PDO::FETCH_NUM))
			{
				$_SESSION['company_id'] = $dat[0];
				return $dat[0];
			}

			throw new Exception (__METHOD__ . " Error: Company {$company} not found", 500);
			
		}
	}
	
	public static function getSQL()
	{
		return ECash::getMasterDb();
	}
	
	public static function Factory($mode = "prpc", $class_name = NULL, $process = FALSE, $persist = FALSE, $strict = FALSE)
	{
		switch (strtolower($mode)) 
		{
			case "prpc":
				require_once eCash_RPC_DIR . "/PRPC.class.php";
				
				return eCash_RPC_PRPC::Factory($class_name, $process, $persist, $strict);
				
			case "soap":
				require_once eCash_RPC_DIR . "/SOAP.class.php";
				
				return eCash_RPC_SOAP::Factory($class_name, $process, $persist, $strict);
				
			case "wsdl":
				require_once eCash_RPC_DIR . "/SOAP.class.php";
				
				eCash_RPC_SOAP::generateWSDL($class_name);
				
			default:
				throw new InvalidArgumentException("Invalid RPC Mode", 500);
		}
	}	
	
	public static function loadClass($class_name)
	{
		if(preg_match("/_(\d+)$/",$class_name)) 
		{
			$tc = preg_replace("/(\w+)_(\d+)$/", "\\1.\\2", $class_name);
		}
		
		$file_base = str_replace("_","/",($tc) ? $tc : $class_name);

		if(
			strpos($file_base, "eCash/RPC/Interface") !== FALSE && 
			file_exists(eCash_RPC_DIR . "/Interface/" . str_replace("eCash/RPC/Interface","",$file_base) . ".class.php")
		) { 
			include_once eCash_RPC_DIR . "/Interface/" . str_replace("eCash/RPC/Interface","",$file_base) . ".class.php";
			
		} 
		elseif (

					strpos($file_base, "eCash/Custom") !== FALSE && 
					file_exists(CUSTOMER_LIB . "/" . str_replace("eCash/Custom","",$file_base) . ".class.php")
		) {

			require_once CUSTOMER_LIB . "" . str_replace("eCash/Custom","",$file_base) . ".class.php";
			
		} 
		elseif(class_exists($class_name)
		)
		{

		}
		else 
		{
			throw new Exception("Class {$class_name} not available for RPC Export", 404);
			
		}
		  
	}
	
	static public function Log()
	{
		if (!class_exists('Applog_Singleton')) require_once COMMON_LIB_DIR . '/applog.singleton.class.php';
		
		if(!self::$log_context) self::$log_context = ( isset($_SESSION["Server_state"]["company"]) ) ? strtoupper($_SESSION["Server_state"]["company"]) : "";
		
		return Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY."/rpc", APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, self::$log_context, 'TRUE');
		
	}
	
	/**
	 * Generate 3 tokens based upon last, current, and next hourly 
	 * minute that is divisible by 5. This will permit the SoapServer
	 * to generate and retrieve the WSDL w/o being forced to self-auth
	 *
	 * @return array
	 */
	static public function genSelfWsdlToken()
	{
		$diff = date("i") % 5;
		$p = date("i") - 5 - $diff;	
		$c = date("i") - $diff;
		$n = date("i") + 5 - $diff;	
		
		$poss[] = md5(self::$token_salt . date("zWtY") . $p);
		$poss[] = md5(self::$token_salt . date("zWtY") . $c);
		$poss[] = md5(self::$token_salt . date("zWtY") . $n);
		
		self::Log(__METHOD__ ."(): \n---\n" . var_export($poss,true) . "\n---\n");
		
		return $poss;
	}
}
