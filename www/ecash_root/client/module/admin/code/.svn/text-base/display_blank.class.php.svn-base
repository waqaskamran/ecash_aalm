<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->module_name = $module_name;
		$this->transport = ECash::getTransport();

		ECash::getTransport()->Get_Data();
	}

	public function Get_Header()
	{
		return parent::Get_Header();
	}

	public function Get_Body_Tags()
	{
		return NULL;
	}

	public function Get_Module_HTML()
	{
		return NULL;
	}
}

?>
