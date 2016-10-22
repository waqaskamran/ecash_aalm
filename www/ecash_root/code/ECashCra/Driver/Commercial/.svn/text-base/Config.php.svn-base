<?php

class ECashCra_Driver_Commercial_Config
{

	protected $updateable_statuses = array(
		'sent::external_collections::*root',
		'recovered::external_collections::*root',
		'paid::customer::*root',
		'chargeoff::collections::customer::*root',
	);
	protected $cancellation_statuses = array(
		'withdrawn::applicant::*root',
		'denied::applicant::*root',
		'declined::prospect::*root',
		'disagree::prospect::*root',
		'funding_failed::servicing::customer::*root',
		'canceled::applicant::*root'
	);
	protected $prefund_statuses = array(
		'approved::servicing::customer::*root'
	);
	
	protected $active_status = 'active::servicing::customer::*root';
	
	protected $withdrawn_status = 'withdrawn::applicant::*root';
	
	protected function setupCompanyDatabaseConfigs()
	{
		return array(
			'mls'   => new DB_MySQLConfig_1('writer.mysql.atlas-lms.com', 'ecash', 'Hook6Zoh', 'ldb_mls'),
		);
	}
	
	protected function setupCompanyCredentials()
	{
/*
		return array(
		"mls" => array('TSS' => array('url' => 'https://rc.cra.verihub.com/', 'username'=>'aalm' ,'password'=>'pheP3Aqa'),
			'FT' =>  array('url' => 'https://stage.factortrust.com/webservices/loanreporting.aspx', 'username'=>'AALMST1' ,'password'=>'aalm456kg', 'store' => '0001', 'merchant' => '97155'),
			'CL' =>  array('url' => 'https://secure.clarityservices.com/tradelines/create_from_xml_test', 'username'=>'aalmconsultingutility' ,'password'=>'B$fe95@e', 'store' => '1211', 'merchant' => '0767', 'group' => '0535'))
		);
*/
		return array(
				"mls" => array(
						'TSS' => array('url' => 'https://cra.verihub.com/', 'username'=>'aalm' ,'password'=>'pheP3Aqa'),
						'FT' =>  array('url'=>'https://www.factortrust.com/webservices/loanreporting.aspx', 'username'=>'AALMST1', 'password'=>'aalm456kg', 'store'=>'0001', 'merchant'=>'97155'),
						'CLH-FT-1A' =>  array('url'=>'https://www.factortrust.com/webservices/loanreporting.aspx', 'username'=>'CLEARLAKEST1', 'password'=>'cl228946th', 'store'=>'0001', 'merchant'=>'97981'),
						'CL' =>  array('url' => 'https://secure.clarityservices.com/tradelines/create_from_xml', 'username'=>'aalmconsultingutility' ,'password'=>'B$fe95@e', 'store' => '1211', 'merchant' => '0767', 'group' => '0535')
				)
		);
	}
	
	////
	// END OF CONFIGURATION
	////
	
	protected $api_credentials;
	
	/**
	 * @var array
	 */
	protected $db_configs;
	
	/**
	 * @var string
	 */
	protected $company;
	
	protected $mode;
	
	public function __construct()
	{
		$this->db_configs = $this->setupCompanyDatabaseConfigs();
		$this->api_credentials = $this->setupCompanyCredentials();
		
		foreach ($this->db_configs as $alias => $config)
		{
			DB_DatabaseConfigPool_1::add($alias, $config);
		}
	}
	
	public function useArguments(array $arguments)
	{
		$company = array_shift($arguments);
		
		if (empty($this->db_configs[$company]))
		{
			throw new InvalidArgumentException('The specified company is not set up');
		}
		
		$this->company = $company;
		
		$mode = array_shift($arguments);
		
		$this->mode = $_ENV['ECASH_MODE'] = $mode;
	}
	
	public function getApiConfig($cra_source, $item)
	{
		return $this->api_credentials[$this->company][$cra_source][$item];
	}
	
	public function getCompany()
	{
		return $this->company;
	}
	
	public function getConnection()
	{
		return DB_DatabaseConfigPool_1::getConnection($this->company);
	}
	
	public function getWithdrawnStatus()
	{
		return $this->withdrawn_status;
	}
	
	public function getActiveStatus()
	{
		return $this->active_status;
	}
	
	public function getUpdateableStatuses()
	{
		return $this->updateable_statuses;
	}

	public function getCancellationStatuses()
	{
	    return $this->cancellation_statuses;
	}
	
	public function getPrefundStatuses()
	{
	    return $this->prefund_statuses;
	}

}

?>
