<?php
require_once("fraud_client.class.php");

//ecash module factory -- Fraud
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$application_object = new Fraud_Client($transport, $module_name);
		return ($application_object);
	}
}

?>
