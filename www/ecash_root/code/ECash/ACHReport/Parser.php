<?php
/*
* Parses a given file into a standard data object ECash_ACHReport_ReportData
* 
*
*/
abstract class ECash_ACHReport_Parser
{
	protected $log;
	protected $exceptions_report;
	
	public function __construct(Applog $log)
	{
		$this->log = $log;
	}

	
	public function setLog(Applog $log)
	{
		$this->log = $log;
	}
	
	public function setExceptionsReport($exceptions_report)
	{
		$this->exceptions_report = $exceptions_report;
	}
	
	public function getExceptionsReport()
	{
		return $exceptions_report;
	}

}


?>
