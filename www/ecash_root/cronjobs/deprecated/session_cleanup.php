<?php

/* Run this at least every few days to remove old sessions from the database */

function Main()
{
	global $server;
	$db = ECash::getMasterDb();
	$log = $server->log;

	$expire_time = strtotime("-8 hours");
	$expire = date('Ymdhi', $expire_time);
	
	$log->Write("Cleaning up old sessions");
	
	$sql = "
	DELETE FROM session
	WHERE date_created < $expire";

	$affected = $db->exec($sql);
	$log->Write("Removed {$affected} session(s)");

}

?>
