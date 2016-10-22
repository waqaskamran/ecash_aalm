<?php
/*
* Contains the connection info, file name and file pathing for the transport object
* and the Parser class and any information it needs to Parse a file 
*
*/
abstract class ECash_ACHReport_Config
{
	protected $date;
	protected $log;
	protected $company_id;
	protected $company_name_short;
	protected $db;
	public function __construct(ECash_Company $company)
	{
		$this->company_id = $company->company_id;
		$this->db = ECash::getMasterDb();
		$this->company_name_short = $company->name_short;
		$this->date = time();
		$this->setLog(ECash::getLog('ach'));
	}
	public function setDate($date)
	{
		$this->date = $date;
	}
	public function setDB($db)
	{
		$this->db = $db;
	}
	public function getDB()
	{
		return $this->db;
	}
	public function getDate()
	{
		return $this->date;
	}
	public function setLog(Applog $log)
	{
		$this->log = $log;
	}
	
	public function getCompanyId()
	{
		return $this->company_id;
	}
	
	public function getCompanyNameShort()
	{
		return $this->company_name_short;
	}
	
	public function getLog()
	{
		return $this->log;
	}
	

}


?>
