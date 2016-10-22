<?php

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Json implements Display
{
	public function __construct()
	{
	}

	public function Do_Display(ECash_Transport $transport)
	{
		header("Content-type: text/plain");
		$data = ECash::getTransport()->Get_Data();
		unset($data->all_sections);
		print json_encode($data);
	}
}
?>
