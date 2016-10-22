<?php
/**
 * <CLASSNAME>
 * <DESCRIPTION>
 *
 * Created on Mar 22, 2007
 *
 * @package <PACKAGE>
 * @category <CATEGORY>
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 21421 $
 */

// Get common functions
require_once SERVER_CODE_DIR . '/server.class.php';
require_once LIB_DIR . '/common_functions.php';
require_once LIB_DIR . '/timer.class.php';

// skeletal Server class
class Server_Skeletal extends Server_Web
{
	public  $timer;
	public	$log;
	public  $company_id;
	public  $system_id;
	public  $company;
	public  $agent_id;
	
	public function __construct()
	{
		$this->agent_id   = Fetch_Agent_ID_by_Login(ECash::getMasterDb(), 'ecash');
		$agent = ECash::getAgentById($this->agent_id);
		ECash::setAgent($agent);
		$this->system_id = ECash::getSystemId();
		$this->loadCompanyList();
	}
	public function __destruct() {}
	
	public function Set_Company($company_name)
	{
		$this->company = $company_name;
		$this->company_id = Fetch_Company_ID_by_Name(ECash::getMasterDb(), $company_name);
		// Load the company specific configuration file
		$enterprise_prefix = ECash::getConfig()->ENTERPRISE_PREFIX;
		require_once(CUSTOMER_CODE_DIR . "{$enterprise_prefix}/Config/{$this->company}.php");
		$company_config = strtoupper($company_name).'_CompanyConfig';
		ECash::setConfig(new $company_config(ECash::getConfig()->getBaseConfig()));

	}

	public function Set_Log ($log)
	{
		$this->log = $log;
		$this->timer = new Timer($this->log);
	}

	public function Fetch_Company_List()
	{
		$sql = "SELECT company_id, name_short FROM company WHERE active_status = 'active'";
		$db = ECash::getMasterDb();
		$result = $db->query($sql);
		while ($result = $result->fetch(PDO::FETCH_OBJ))
		{
			$companies[$row->company_id] = $row->name_short;

			$info = array(	'ecash3_company' => (Is_ECash_3_Company($row->company_id) ? true : false ), 
							'name_short' => $row->name_short
							);

			$_SESSION['Server_state']['company_list'][$row->company_id] = $info;
		}
		
		return $companies;
	}

}
