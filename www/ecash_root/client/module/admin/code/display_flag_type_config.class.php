<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

class Display_View extends Admin_Parent
{

	public function Get_Header()
	{
		$data = ECash::getTransport()->Get_Data();

		return parent::Get_Header();
	}

	public function Get_Module_HTML()
	{
		$data = ECash::getTransport()->Get_Data();
		$retval =  file_get_contents(CLIENT_MODULE_DIR.$this->module_name."/view/flag_type_config.html");
		
		$flag_types = array();
		$flag_type_model = ECash::getFactory()->getReferenceList("FlagType");
		foreach($flag_type_model as $model) 
		{
			$flag_types[] = array($model->date_modified, $model->date_created, $model->active_status, $model->flag_type_id, $model->name, $model->name_short);
		}
		$retval = str_replace('%%%flag_type_json%%%', json_encode($flag_types), $retval);
		$retval = str_replace('%%%result%%%', $data->result, $retval);
		
		return $retval;
	}
}

?>