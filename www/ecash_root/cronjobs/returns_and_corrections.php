<?php
/**
 * @package cronjobs
 * 
 * Changelog:
 * 
 * Ya Rly, that's all this does!
 */

function Main()
{
	
	$reports = array(); 
	$reports = ECash_ACHReport_ReportManager::getProcesses(ECash::getCompany()->company_id);
	//Based on the reports associated with the specified company, we will run through each of them
	foreach ($reports as $report)
	{
		//Instantiate the company config
		$config = ECash_ACHReport_ReportManager::getConfig(ECash::getCompany());
		$config->setDate(time());
		//The log gets set to ACH by default, but why take a chance?
		$config->setLog(ECash::getLog('ach'));
		//Instantiate that report class.
		$report_class = new $report['class_name']($config);
		//Assign it a process name, otherwise it will use the class name as it's process name.
		$report_class->setProcessName($report['name_short']);
		//check process of the report
		//Run the report
		$report_class->run();
	}
	//echo "\nDONE!";
}

?>
