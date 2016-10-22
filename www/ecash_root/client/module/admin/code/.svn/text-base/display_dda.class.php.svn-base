<?php

require_once("admin_parent.abst.php");

class Display_View extends Admin_Parent
{
    public function Get_Header()
    {
		$data = parent::Get_Header();
        $data .= ECash::getTransport()->Get_Data()->header;
        return($data);
    }

    public function Get_Module_HTML()
    {
        $data = ECash::getTransport()->Get_Data();
        return($data->display);
    }
}

?>
