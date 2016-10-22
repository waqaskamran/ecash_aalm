<?php

require_once(SQL_LIB_DIR . "application.func.php");
function Main()
{
	
	global $server;
	$log = $server->log;
	
	/**
			$env = array(
				'ECASH_CUSTOMER_DIR' 	=> getenv('ECASH_CUSTOMER_DIR'),
				'ECASH_CUSTOMER' 		=> getenv('ECASH_CUSTOMER'),
				'ECASH_EXEC_MODE' 		=> getenv('ECASH_EXEC_MODE'),
				'ECASH_COMMON_DIR' 		=> getenv('ECASH_COMMON_DIR'),
				'LIBOLUTION_DIR'		=> getenv('LIBOLUTION_DIR'),
				'COMMON_LIB_DIR'		=> getenv('COMMON_LIB_DIR'),
				'COMMON_LIB_ALT_DIR'	=> getenv('COMMON_LIB_ALT_DIR'),
				'APPS_TO_PROCESS'		=> $num_apps,
				'BATCH_COMPANY'			=> $batch_company_ns
				
			);
	 */

	$num_apps = getenv('APPS_TO_PROCESS');
	$batch_company_ns = getenv('BATCH_COMPANY');
	
	$bct = ECash::getFactory()->getModel('ExternalBatchReport');
	$bct->loadBy(array('name_short' => $batch_company_ns));
				
	// Create a new external collections batch using the class in the DB
	if (!class_exists($bct->class_name))
		throw new Exception('Invalid class specified in DB for external_batch_report');

	$batch = new $bct->class_name(ECash::getMasterDb());
	$log->Write( "lolwut?! {$bct->class_name}");
	//Set the external batch company id, in case it needs it (like for 2nd tier)
	$batch->setExternalBatchReportId($bct->external_batch_report_id);
	//RUN IT!
	$batch->run($num_apps);

	return true;	
}

?>
