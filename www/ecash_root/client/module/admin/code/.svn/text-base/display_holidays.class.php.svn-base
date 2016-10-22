<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{
	private $holidays;


	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();
		$this->holidays = $returned_data->holidays;
	}

	public function Get_Module_HTML()
	{

		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			$fields->holiday_list = "";
			if (count($this->holidays) > 0)
			{
				foreach ($this->holidays as $holiday)
				{
					if ($holiday->holiday < date('Y-m-d'))
					{
						$rowclass = "holiday_list_alt";
					}
					else
					{
						$rowclass = "holiday_list";
					}
					$fields->holiday_list .= "\n<tr class='$rowclass'>" . 
											 "<td nowrap style='text-align:left; width:170px'>$holiday->name</td>" . 
											 "<td style='text-align:center; width:85px'>$holiday->holiday</td>" . 
											 "</tr>";
				}

				$fields->holiday_list .= "\n";
			}
			else
			{
				$fields->holiday_list .= "\n<br><br><b>No holidays found!</b>\n";
			}

			$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/admin_holidays.html");

			return $form->As_String($fields);
		}
	}
}

?>
