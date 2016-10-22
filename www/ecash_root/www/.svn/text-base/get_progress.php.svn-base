<?php
	include_once('config.php');
	require_once(COMMON_LIB_DIR . 'applog.1.php');

	if($_REQUEST['process'] && $_REQUEST['company_id'])
	{
		$batch = $_REQUEST['process'];
		$company_id = $_REQUEST['company_id'];

		$progress = new ECash_BatchProgress(ECash::getFactory(), $company_id, $batch);
		
		list($percent, $message) = $progress->getProgress();
		
		$obj = new stdClass();
		$obj->percent = $percent;
		$obj->message = $message;
		
		echo json_encode($obj);
		
	}
	else
	{
		echo "No data available.";
	}
?>
