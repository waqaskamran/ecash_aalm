<?php

function main()
{
	
		
	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		DELETE FROM queue WHERE queue_name = 'Skip Trace'";
	
	MySQLi_1e::Get_Instance()->Query($query);	
	
	
}


?>
