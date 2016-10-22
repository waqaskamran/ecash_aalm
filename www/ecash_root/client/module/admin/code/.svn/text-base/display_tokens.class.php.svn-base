<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $tags;
	private $msg;


	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$data = ECash::getTransport()->Get_Data();

	}

	public function Get_Header()
	{
		$js = new Form(ECASH_WWW_DIR.'js/token_management.js');
		$js2 = new Form(ECASH_WWW_DIR.'js/prototype-1.5.1.1.js');
		$js3 = new Form(ECASH_WWW_DIR.'js/json.js');
		return parent::Get_Header() . '<script type="text/javascript">' . $js2->As_String() . $js3->As_String() . '</script>' . $js->As_String();
	}

	public function Get_Module_HTML()
	{

		$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/token_management.html");

		return $form->As_String();
		
	}
}

?>
