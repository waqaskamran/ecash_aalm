<?php

require_once(LIB_DIR . "common_functions.php");
require_once(SQL_LIB_DIR . "application.func.php");
require_once(SERVER_MODULE_DIR . "/admin/docs_config.class.php");
require_once(LIB_DIR . "/Document/DeliveryAPI/Condor.class.php");

/**
 * Gets list of failed documents from Condor, and updates the document table.
 *
 */
function Main()
{
	global $server;
	$company_id = $server->company_id;

	$db = ECash::getMasterDb();	
	
	$start_date = getLastProcessTime($db, 'get_failed_documents', 'completed');

	if (NULL === $start_date)
	{
		$start_date = '20070101000000';
	}
	else if (EXECUTION_MODE !== 'LIVE') // local and rc time will be off, so...
	{
		$start_date = substr($start_date, 0, 8) . '000000';
	}

	$pid = Set_Process_Status($db, $company_id, 'get_failed_documents', 'started');
	$failed_documents = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Failed(NULL, $start_date, NULL);

	if (!is_array($failed_documents))
	{
		Set_Process_Status($db, $company_id, 'get_failed_documents', 'failed', NULL, $pid);
		return;
	}

	$mssql_db = ECash::getAppSvcDB();
	foreach ($failed_documents as $doc)
	{
		$archive_id = $doc->archive_id;
		$event_type = 'failed';

		$query = "CALL sp_update_document_event_type_by_archive_id (".$event_type.",".$archive_id.")";
		$mssql_db->query($query);
	}

	Set_Process_Status($db, $company_id, 'get_failed_documents', 'completed', NULL, $pid);
}

?>
