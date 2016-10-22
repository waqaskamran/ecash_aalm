<?php

require_once("loan_servicing_client.class.php");

//ecash module factory -- Loan Serivicng
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$application_object = new Loan_Servicing_Client($transport, $module_name);
		return ($application_object);
	}
}

?>