<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

//ecash module
class Display_View extends Admin_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		parent::__construct($transport, $module_name);
		$data = ECash::getTransport()->Get_Data();
		$this->tags                    = $data->tags;
		$this->msg                     = $data->msg;
		$this->loan_types              = $data->loan_types;
		$this->payment_types           = $data->payment_types;
		$this->payment_type_conditions = $data->payment_type_conditions;
	}

	public function Get_Header()
	{
        $js2 = new Form(ECASH_WWW_DIR.'js/prototype-1.5.1.1.js');
        $js3 = new Form(ECASH_WWW_DIR.'js/json.js');

		return parent::Get_Header() . "<script type='text/javascript'>" . $js2->As_String($fields) . "</script>" . "<script type='text/javascript'>" .  $js3->As_String($fields) . "</script>";
	}

	public function Get_Module_HTML()
	{
		switch ( ECash::getTransport()->Get_Next_Level() )
		{
			case 'default':
			default:
			$fields = new StdClass();
	
			$fields->loan_type_select_list = "<select onChange='updatePaymentTypeConditions();' id='loanTypeSelect' name='loan_type'>";
			
			$fields->loan_type_select_list .= "<option value='invalid'>Select a loan type</option>\n";
			$fields->loan_type_select_list .= "<option value='all'>All (Uses rules from 1st loan type)</option>\n";

			foreach($this->loan_types as $loan_type)
			{
				$fields->loan_type_select_list .= "<option value='{$loan_type['loan_type_id']}'>{$loan_type['name']} (Company ID: {$loan_type['company_id']})</option>\n";
			}

			$fields->loan_type_select_list .= "</select>";

			$fields->payment_type_select_list = "<select onChange='updatePaymentTypeConditions();' id='paymentTypeSelect' name='payment_type'>";
			$fields->payment_type_select_list .= "<option value='invalid'>Select a payment type</option>\n";

			
			foreach($this->payment_types as $payment_type)
			{
				$fields->payment_type_select_list .= "<option value='{$payment_type['payment_type_id']}'>{$payment_type['name']}</option>\n";
			}

			$fields->payment_type_select_list .= "</select>";


			$form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/payment_types.html");

			return $form->As_String($fields);
		}
	}
}

?>
