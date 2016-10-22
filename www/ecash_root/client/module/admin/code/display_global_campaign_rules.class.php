<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

class Display_View extends Admin_Parent
{
        private $global_campaign_rules;

        public function __construct(ECash_Transport $transport, $module_name)
        {
                parent::__construct($transport, $module_name);
                $returned_data = ECash::getTransport()->Get_Data();
                $this->global_campaign_rules = $returned_data->global_campaign_rules;
        }

        public function Get_Module_HTML()
        {
                switch ( ECash::getTransport()->Get_Next_Level() )
                {
                        case 'default':
                        default:
                                $fields = new stdClass();
                                $fields->global_campaign_rule_list = "";
                                if (count($this->global_campaign_rules) > 0)
                                {
                                        foreach ($this->global_campaign_rules as $global_campaign_rule)
                                        {
						$age = $global_campaign_rule["age"];
						$military = $global_campaign_rule["military"];
						$income_monthly = $global_campaign_rule["income_monthly"];
						//$bank_aba = $global_campaign_rule["bank_aba"];
						$bank_aba = "01-15, 21-32, 61-72";
						$street = $global_campaign_rule["street"];

                                                $fields->global_campaign_rule_list .= "<tr>" .
                                                        "<td style='text-align:left; width:400px'>$age</td>" .
							"<td style='text-align:left; width:400px'>$military</td>" .
							"<td style='text-align:left; width:400px'>$income_monthly</td>" .
							"<td style='text-align:left; width:400px'>$bank_aba</td>" .
							"<td style='text-align:left; width:400px'>$street</td>" .
                                                        
							"<td class='ci_edit_col'>" 
							. "<button onclick=\"edit_global_campaign_rule("
							. $age
							. "," . "'" . $military . "'"
							. "," . "'" . $income_monthly . "'"
							. "," . "'" . $bank_aba . "'"
							. "," . "'" . $street . "'"
							. ")\">Edit</button>"
							. "</td>" .
                                                        "</tr>";
                                        }

                                        $fields->global_campaign_rule_list .= "\n";
                                }
                                else
                                {
                                        $fields->global_campaign_rule_list .= "\n<br><br><b>No Global Campaign Rules found!</b>\n";
                                }

                                $form = new Form(CLIENT_MODULE_DIR . $this->module_name."/view/admin_global_campaign_rules.html");

                                return $form->As_String($fields);
                }
        }
}

?>
