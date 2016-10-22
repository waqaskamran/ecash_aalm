<?php

require_once 'config.php';
require_once 'comm.iface.php';
require_once( SERVER_CODE_DIR . "server_factory.class.php" );
require_once( LIB_DIR . "request_timer.class.php" );

Class Comm_Class implements Comm
{
	public function Process_Data($request, $session_id = NULL)
	{
		Request_Timer::Start();
		$server = Server_Factory::get_server_class(isset($request->api) ? $request->api : null, $session_id);
		$GLOBALS["server"] = $server;

		// Moving this to a static on the ECash object so we're not bloating
		// the Session object and saving it to the DB.
		// $_SESSION['server'] = $server;
       	ECash::setServer($server);

		$server_return = $server->Process_Data($request);
		Request_Timer::Set_Request_Information($server->company_id, $server->agent_id, $server->Get_Active_Module(), isset($request->mode) ? $request->mode : '',  isset($request->action) ? $request->action : '', ECash::getTransport()->page_array);
		Request_Timer::Set_Log($server->log);
		Request_Timer::Set_Database(REQUEST_LOG_PATH);
		$return = clone $server_return;	
		Request_Timer::Stop();
		return($return);
	}
}
?>
