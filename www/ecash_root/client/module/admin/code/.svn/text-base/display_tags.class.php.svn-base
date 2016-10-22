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
		$this->tags = $data->tags;
		$this->msg = $data->msg;
	}

	public function Get_Header()
	{
		$js = new Form(ECASH_WWW_DIR.'js/tags.js');

		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{

		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new stdClass();
			$fields->weight_list = "";
			$number_of_tags = 0;
			
			if ($this->msg) 
			{
				$fields->weight_list = '<tr>'.
										'<td style="text-align:left;" colspan="3"><b>'.htmlentities($this->msg).'</b></td>'.
										'</tr>';
			}
			if (count($this->tags) > 0)
			{
				$total = 0;				

				foreach ($this->tags as $tag)
				{
					$fields->weight_list .= '<tr>' . 
											'<td nowrap style="text-align:left;">'.htmlentities($tag->name).'</td>' . 
											 '<td style="text-align:left">'.
											 '<input type="text" size="5" name="weights['.$tag->tag_id.']" id="weights_'.$number_of_tags.'" value="'.htmlentities($tag->description).'" onKeyDown="return ValidateNumber(event);" onKeyUp="CalculateTotal();" onChange="InsertZero();"></td>' . 
											 '</tr>';
					
					$total += htmlentities($tag->description);
					$number_of_tags++;
				}

				$fields->weight_list .= 
											'<tr>' . 
											'<td nowrap class="align_left_bold";">_____________________</td>' . 
											'<td nowrap class="align_left_bold";">________</td>' . 
											'</tr>';
				
				
				
				$fields->weight_list .= 
											'<tr>' . 
											'<td nowrap class="align_left_bold";">Total Weight:</td>' . 
											'<td style="text-align:left">'.'<input type="text" readonly="true" size="5" name="total_weight" id="total_weight" value="'.$total.'"></td>' . 
											'</tr>';

				$fields->weight_list .= "\n";
			}
			else
			{
				$fields->weight_list .= '<tr><td colspan="3"><strong>No Investor Groups found!</strong></td></tr>';
			}
			$fields->number_of_tags = $number_of_tags;

			$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/tags.html");

			return $form->As_String($fields);
		}
	}
}

?>
