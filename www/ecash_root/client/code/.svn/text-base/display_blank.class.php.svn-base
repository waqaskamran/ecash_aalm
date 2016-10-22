<?php

require_once(LIB_DIR. "form.class.php");
require_once("display_parent.abst.php");

//ecash module
class Display_View extends Display_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->module_name = $module_name;
		ECash::getTransport()->Get_Data();
	}

	public function Get_Header()
	{
		return NULL;
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
