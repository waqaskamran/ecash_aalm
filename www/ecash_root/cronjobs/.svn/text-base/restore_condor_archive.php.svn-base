<?php

require_once LIB_DIR . "Document/Document.class.php";
require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";

function Main($args)
{
	global $server;
	
	$doc_model = ECash::getFactory()->getModel("Document");
	$doc_list_ref =  ECash::getFactory()->getReferenceModel("DocumentListRef");
	
	$documents = $doc_model->loadAllBy(array("archive_id" => 0));

	foreach ($documents as $document)
	{
		$name = $doc_list_ref->toName($document->document_list_id);
		$app = eCash_Document::Get_Application_Data($server, $document->application_id);
		$send_arr = eCash_Document_DeliveryAPI_Condor::Map_Data($server, $app);

		$doc_id = eCash_Document_DeliveryAPI_Condor::Prpc()->Create($name, $send_arr, true, $app->application_id, $app->track_id, null);

		if ($doc_id['archive_id'])
		{
			$document->archive_id = $doc_id['archive_id'];
			$document->save();
		}
	}
}
