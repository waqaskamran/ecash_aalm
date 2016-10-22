<?php

require_once("config.php");
require_once('common_functions.php');
require_once(LIB_DIR . "comm.iface.php");
require_once("prpc/server.php");

Class Comm_Prpc_Server extends Prpc_Server implements Comm
{
	function __construct()
	{
		parent::__construct();
	}

	public function Process_Data($request, $session_id = NULL)
	{
		$server = new Server($session_id);

		return $server->Process_Data($request);
	}

	
}

//do some required prpc stuff
$prpc_obj = new Comm_Prpc_Server();

$prpc_obj->_Prpc_Strict = TRUE;

$prpc_obj->Prpc_Process();

?>