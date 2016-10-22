<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';
require_once SQL_LIB_DIR . 'util.func.php';

class Holidays
{
	private $transport;
	private $request;
	private $server;

	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->transport = ECash::getTransport();
	}

	public function Display()
	{
		//$data->holidays = $this->server->Fetch_Full_Holiday_List();
		$data['holidays'] = Fetch_Full_Holiday_List();
		ECash::getTransport()->Set_Data($data);

		return TRUE;
	}

}

?>
