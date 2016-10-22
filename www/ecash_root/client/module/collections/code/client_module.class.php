<?php

require_once("collections_client.class.php");

//ecash module factory -- Funding
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$application_object = new Collections_Client($transport, $module_name);
		return ($application_object);
	}
}

?>
