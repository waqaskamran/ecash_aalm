<?php
/**
 * @package display.admin
 */

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{

	private $intercept_card;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();
		$this->intercept_card = $returned_data['intercept_card_values'];
	}

	public function Get_Module_HTML()
	{
		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();

			$fields->intercept_card_html = "";
			
			for ($j=1; $j <= 5; ++$j)
			{
				$fields->intercept_card_html .= "<tr>
									<th>{$j}</th>";

				for ($i='A'; $i <= 'J'; ++$i)
				{
					$fields->intercept_card_html .= 
						
						"<td><input type='text' size='1' id='intercept_cell_" . "{$i}" . "{$j}" . 
							"' class='cell' value=". $this->intercept_card[$i][$j] . "></td>";
				}
						
				
				$fields->intercept_card_html .= "	<th>{$j}</th>
								</tr>";
			}
			
			$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/admin_intercept_card.html");

			return $form->As_String($fields);
		}
	}
}

?>
