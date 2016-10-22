<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{
	//private $holidays;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();
		//$this->holidays = $returned_data->holidays;
	}

	public function Get_Module_HTML()
	{
		$data = ECash::getTransport()->Get_Data();
		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			
			$data->results = (isset($data->results) && is_array($data->results))?$data->results:array();
			foreach ($data->results as $result) 
			{
				$fields->results .= "<br>{$result}";	
			}
			
			$data->errors = (isset($data->errors) && is_array($data->errors))?$data->errors:array();
			foreach ($data->errors as $error)
			{
				$fields->errors .="<br>{$error}";
			}
			
			$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/admin_nada.html");

			return $form->As_String($fields);
		}
	}
}

?>
