<?php
/**
 * This class is used to display black box suppression rules for the admin GUI.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

require_once(LIB_DIR. 'form.class.php');
require_once('admin_parent.abst.php');
require_once(COMMON_LIB_DIR . 'ecash_admin_resources.php');

//ecash module
class Display_View extends Admin_Parent
{
	private $company_name;
	private $sup_lists;
	private $sup_rev_vals;
	private $sup_vals;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$returned_data = ECash::getTransport()->Get_Data();
		$this->company_name = $returned_data->company_name;
		$this->sup_lists = $returned_data->sup_list;
		$this->sup_rev_vals = $returned_data->sup_list_values;
		$this->sup_vals = $returned_data->sup_values;
	}

	/**
	 *
	 */
	public function Get_Header()
	{
		$fields = new stdClass();

		$id = 0;
		$fields->sup_lists = '[';
		$fields->sup_lists_id = '[';
		foreach($this->sup_lists as $sup_lists)
		{
			$fields->sup_lists .= "\t\t\n{"
				. 'id:"' . $id . '", '
				. 'list_id:"' . $sup_lists->list_id . '", '
				. 'name:"' . $sup_lists->name . '", '
				. 'type:"' . $sup_lists->type . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'field_name:"' . $sup_lists->field_name . '", ' 
				. 'date_created:"' . $sup_lists->date_created . '", ' 
				. 'date_modified:"' . $sup_lists->date_modified . '", ' 
				. 'loan_action:"' . $sup_lists->loan_action . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'revision_id:"' . $sup_lists->revision_id . '", ' 
				. 'date_revised:"' . $sup_lists->date_revised
				. '"},';
			$fields->sup_lists_id .= "\t\t\n{"
				. 'id:"' . $sup_lists->list_id . '", '
				. 'count_id:"' . $id . '", '
				. 'name:"' . $sup_lists->name . '", '
				. 'type:"' . $sup_lists->type . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'field_name:"' . $sup_lists->field_name . '", ' 
				. 'date_created:"' . $sup_lists->date_created . '", ' 
				. 'date_modified:"' . $sup_lists->date_modified . '", ' 
				. 'loan_action:"' . $sup_lists->loan_action . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'revision_id:"' . $sup_lists->revision_id . '", ' 
				. 'date_revised:"' . $sup_lists->date_revised
				. '"},';

			$id++;
		}
		$fields->sup_lists .= ']';
		$fields->sup_lists_id .= ']';

		// 
		$fields->sup_rev_vals .= '[';
		foreach($this->sup_rev_vals as $sup_rev_vals)
		{
			$fields->sup_rev_vals .= "\t\t\n{"
				. 'list_id:"' . $sup_rev_vals->list_id . '", '
				. 'revision_id:"' . $sup_rev_vals->revision_id . '", '
				. 'value_id:"' . $sup_rev_vals->value_id . '", '
				. 'value:"' . $sup_rev_vals->value . '"},';
		}
		$fields->sup_rev_vals .= ']';

		// 
		$fields->sup_vals .= '[';
		foreach($this->sup_vals as $sup_vals)
		{
			$fields->sup_vals .= "\t\t\n{"
				. 'id:"' . $sup_rev_vals->value_id . '", '
				. 'value:"' . $sup_rev_vals->value . '"},';
		}
		$fields->sup_vals .= ']';

		$js = new Form(ECASH_WWW_DIR."js/suppression_rules.js");

		return parent::Get_Header() . $js->As_String($fields);
	}

	public function Get_Module_HTML()
	{
		$fields = new stdClass();

		// 
		$fields->sup_rev_vals .= '[';
		foreach($this->sup_rev_vals as $sup_rev_vals)
		{
			$fields->sup_rev_vals .= "\t\t\n{"
				. 'list_id:"' . $sup_rev_vals->list_id . '", '
				. 'revision_id:"' . $sup_rev_vals->revision_id . '", '
				. 'value_id:"' . $sup_rev_vals->value_id . '", '
				. 'value:"' . $sup_rev_vals->value . '"},';
		}
		$fields->sup_rev_vals .= ']';

		// 
		$fields->sup_vals .= '[';
		foreach($this->sup_vals as $sup_vals)
		{
			$fields->sup_vals .= "\t\t\n{"
				. 'id:"' . $sup_rev_vals->value_id . '", '
				. 'value:"' . $sup_rev_vals->value . '"},';
		}
		$fields->sup_vals .= ']';

		$id = 0;
		$fields->sup_list_set = '';
		$fields->sup_lists = '[';
		$fields->sup_lists_id = '[';
		foreach($this->sup_lists as $sup_lists)
		{
			$fields->sup_lists .= "\t\t\n{"
				. 'id:"' . $id . '", '
				. 'list_id:"' . $sup_lists->list_id . '", '
				. 'name:"' . $sup_lists->name . '", '
				. 'type:"' . $sup_lists->type . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'field_name:"' . $sup_lists->field_name . '", ' 
				. 'date_created:"' . $sup_lists->date_created . '", ' 
				. 'date_modified:"' . $sup_lists->date_modified . '", ' 
				. 'loan_action:"' . $sup_lists->loan_action . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'revision_id:"' . $sup_lists->revision_id . '", ' 
				. 'date_revised:"' . $sup_lists->date_revised
				. '"},';
			$fields->sup_lists_id .= "\t\t\n{"
				. 'id:"' . $sup_lists->list_id . '", '
				. 'count_id:"' . $id . '", '
				. 'name:"' . $sup_lists->name . '", '
				. 'type:"' . $sup_lists->type . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'field_name:"' . $sup_lists->field_name . '", ' 
				. 'date_created:"' . $sup_lists->date_created . '", ' 
				. 'date_modified:"' . $sup_lists->date_modified . '", ' 
				. 'loan_action:"' . $sup_lists->loan_action . '", ' 
				. 'description:"' . $sup_lists->description . '", ' 
				. 'revision_id:"' . $sup_lists->revision_id . '", ' 
				. 'date_revised:"' . $sup_lists->date_revised
				. '"},';

			$fields->sup_list_set .=  '<tr>'
					. '<td class="ci_suppress_col" >' .
						$sup_lists->name
					. '</td>' 
					. '<td class="ci_revision_col" >' .
						$sup_lists->revision_id
					. '</td>'
					. '<td class="ci_type_col" >' .
						$sup_lists->type
					. '</td>'
					. '<td class="ci_desc_col" >' .
						$sup_lists->description
					. '</td>'
					. '<td class="ci_fields_col" >' .
						$sup_lists->field_name
					. '</td>'
					. '<td class="ci_action_col" >' .
						$sup_lists->loan_action
					. '</td>'
					. '<td class="ci_edit_col" >' 
						. '<button onclick="edit_suppression_list('
						. $sup_lists->list_id .','.$sup_lists->revision_id
						. ')"> EDIT </button>'
					. '</td>'
				. '</tr>';
			$id++;
		}
		$fields->sup_lists .= ']';
		$fields->sup_lists_id .= ']';

		$fields->company_name = $this->company_name;

		$form = new Form(CLIENT_MODULE_DIR.$this->module_name.'/view/suppression_rules.html');

		return $form->As_String($fields);
	}
}

?>
