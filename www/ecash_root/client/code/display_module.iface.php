<?php

interface Display_Module
{
	public function __construct(ECash_Transport $object, $module_name);
	
	public function Get_Header();
	
	public function Get_Body_Tags();

	public function Get_Hotkeys();

	public function Get_Menu_HTML();

	public function Get_Module_HTML();
}

?>
