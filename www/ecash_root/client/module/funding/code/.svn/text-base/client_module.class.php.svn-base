<?php

require_once("funding_client.class.php");

//ecash module factory -- Funding
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$application_object = new Funding_Client($transport, $module_name);
		return ($application_object);
	}
}

?>