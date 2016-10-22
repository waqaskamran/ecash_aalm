<?php

//ecash module factory -- Admin
class Client_Module
{
	public static function Get_Display_Module(ECash_Transport $transport, $module_name)
	{
		$mode = ECash::getTransport()->Get_Next_Level();
		$view = ECash::getTransport()->Get_Next_Level();

		require_once(CLIENT_MODULE_DIR . "${module_name}/code/display_${mode}.class.php");
		$application_object = new Display_View($transport, $module_name);
		$application_object->Set_Mode($mode);
		$application_object->Set_View($view);
		return ($application_object);
	}
}

?>
