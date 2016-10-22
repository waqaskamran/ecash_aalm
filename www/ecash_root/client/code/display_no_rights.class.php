<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

//ecash module
class Display_View
{
			
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
		return "You either do not have privileges to see any sections of this module<br>
		or the specific module you selected<br>
		Please contact your administrator.";
	}

}

?>