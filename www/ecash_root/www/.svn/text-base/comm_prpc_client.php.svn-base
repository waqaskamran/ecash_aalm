<?php

require_once("config.php");
require_once(LIB_DIR . "comm.iface.php");
require_once("prpc/client.php");
require_once("null_session.1.php");

Class Comm_Prpc_Client implements Comm
{
	
	public function Process_Data($request, $session_id = NULL)
	{

		$session = new Null_Session_1 ();

		//Set the session name
		session_name ("ssid");

		session_set_save_handler
		(
			array (&$session, "Open"),
			array (&$session, "Close"),
			array (&$session, "Read"),
			array (&$session, "Write"),
			array (&$session, "Destroy"),
			array (&$session, "Garbage_Collection")
		);

		//Start the session
		session_start();

		$server = "prpc://" . URL_NMS . "/comm_prpc_server.php";
		$debug = TRUE;
		$trace_level = 32;
		$prpc_server = new Prpc_Client ($server, $debug, $trace_level);
		$transport_data =  $prpc_server->Process_Data($request, session_id());
		return $transport_data;
	}
	
}

?>