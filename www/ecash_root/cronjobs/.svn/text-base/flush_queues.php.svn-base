<?php

function Main()
{
	$db = ECash::getMasterDb();
	$company_id = ECash::getCompanyID();
	$pid = Set_Process_Status($db, $company_id, 'flush_queues', 'started');
	
	foreach (ECash::getFactory()->getQueueManager()->Queues as $queue)
	{
		echo "Flushing queue (" . $queue->Model->queue_id . ": " . $queue->Model->name_short.") ... ";
		$rows = 0;
		if ($queue instanceof ECash_Queues_BasicQueue)
		{
			$rows = $queue->flush();
		}
		echo "$rows rows flushed.\n";
	}
	
	Set_Process_Status($db, $company_id, 'flush_queues', 'completed', NULL, $pid);
}

?>
