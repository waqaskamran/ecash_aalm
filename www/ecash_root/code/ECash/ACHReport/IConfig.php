<?php
/*
* Contains the connection info, file name and file pathing for the transport object
* and the Parser class and any information it needs to Parse a file 
*
*/
interface ECash_ACHReport_IConfig
{
	public function __construct(ECash_Company $company);
	
	public function setDate($date);
	
	public function setLog(Applog $log);

	public function getLog();
	
	public function getDate();
	
	
}


?>
