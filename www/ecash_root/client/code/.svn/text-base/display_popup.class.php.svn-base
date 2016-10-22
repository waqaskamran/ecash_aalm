<?php

require_once("data_format.1.php");
require_once("display.iface.php");
require_once(CLIENT_CODE_DIR . "display_utility.class.php");


//ecash module
class Display_Popup implements Display
{
	private $transport;

	protected $ach_card_payment_type_map = array(
	"loan_disbursement" => "card_disbursement",
	//"repayment_principal" => "card_repayment_principal",
	//"payment_service_chg" => "card_payment_service_chg",
	//"assess_fee_ach_fail" => "assess_fee_card_fail",
	//"payment_fee_ach_fail" => "payment_fee_card_fail",
	//"full_balance" => "card_full_balance",
	"paydown" => "card_paydown",
	"payout" => "card_payout",
	"payment_arranged" => "card_payment_arranged",
	"payment_manual" => "card_payment_manual",
	"refund" => "card_refund",
	//"cancel" => "card_cancel",
	"writeoff_fee_ach_fail" => "writeoff_fee_card_fail",
	"chargeback" => "card_chargeback",
	"chargeback_reversal" => "card_chargeback_reversal"
	);

	public function Do_Display(ECash_Transport $transport)
	{
		$this->data = ECash::getTransport()->Get_Data();
		$this->data->server_date = 	'<script type="text/javascript">
									var serverdate = "'.date('M j Y H:i:s').'";
									</script>';
		$this->data->serverdate = $this->data->server_date;
		$this->acl = ECash::getTransport()->acl;
		include_once(WWW_DIR . "include_js.php");
		if (is_array($this->data)) $this->data->JAVASCRIPT = include_js();
		else $this->data->JAVASCRIPT = include_js();

		$html = "";

		$stylesheet = "<link rel=\"stylesheet\" href=\"css/style.css\">";
		if (isset($this->data) && is_object($this->data))
		{
			$this->data->optional_tr = "";
			$this->data->stylesheet = $stylesheet;
		}
		else 
		{
			$this->data->stylesheet = $stylesheet;	
		}
		$next_level = ECash::getTransport()->Get_Next_Level();
		$this->transport = ECash::getTransport();

		switch($next_level)
		{
		case  'funding_decline':
 			$data = $this->data;
			$data->opts = $this->Build_Reasoning_Options($data->loan_action_types);
			$html = file_get_contents(CLIENT_VIEW_DIR . "funding_decline.html");
			$html = Display_Utility::Token_Replace($html, (array)$data);
		break;
		
		case "application_history":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_history.html");
			$history_html = $this->Build_Application_History();
			$call_history_html = '';
			if (!empty($this->data->call_history)) 
			{
				$call_history_html = $this->data->call_history->getHtmlDataOutput(new Data_Format_1);
			}
			$html = str_replace('%%%call_history%%%', $call_history_html, $html);
			$html = str_replace("%%%application_history%%%", $history_html, $html);
			$html = str_replace("%%%queue_history%%%", $this->Build_Queue_History(), $html);
			break;

		case "outgoing_call_dispositions":
			$data = ECash::getTransport()->Get_Data();
			$this->Build_Outgoing_Dispositions_List($data);

			$html = file_get_contents(CLIENT_VIEW_DIR . "outgoing_call_dispositions.html");
			$html = Display_Utility::Token_Replace($html, (array)$data);
			break;	

		case "application_audit_log":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_audit_log.html");
			$audit_html = $this->Build_Application_Audit_Log();
			$html = str_replace("%%%application_audit_log%%%", $audit_html, $html);
			break;	
		case "application_flag_history":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_flag_history.html");
			$flag_history_html = $this->Build_Application_Flag_History();
			$html = str_replace("%%%application_flag_history%%%",$flag_history_html,$html);
		break;
		case "application_flag_modify":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_flag_modify.html");
			$data = ECash::getTransport()->Get_Data();
			$html = str_replace("%%%flag%%%", $data->flag, $html);
			$html = str_replace("%%%flag_state%%%", $data->flag_state ? 'true' : 'false', $html);
			$html = str_replace("%%%module%%%", $data->module, $html);
			$html = str_replace("%%%mode%%%", $data->mode, $html);
			$html = str_replace("%%%flag_description%%%", $data->flag_description, $html);
			$html = str_replace("%%%application_id%%%", $data->application_id, $html);
			$html = str_replace("%%%permission_state%%%", $data->permission_state ? 'true' : 'false', $html);
			break;	

		case "dnl_audit_log":
			$html = file_get_contents(CLIENT_VIEW_DIR . "dnl_audit_log.html");
			$audit_html = $this->Build_DNL_Audit_Log();
			$html = str_replace("%%%dnl_audit_log%%%", $audit_html, $html);
			break;

		case "card_audit_log":
			$html = file_get_contents(CLIENT_VIEW_DIR . "card_audit_log.html");
			$audit_html = $this->Build_Card_Audit_Log();
			$html = str_replace("%%%card_audit_log%%%", $audit_html, $html);
		break;
		
		case "dnl":
			$html = file_get_contents(CLIENT_VIEW_DIR . "dnl.html");
			$dnl_body = $this->Build_DNL_Body($this->data->current_exists,$this->data->other_exists,$this->data->override_exists,$this->data->categories,$this->data->dnl_info);
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			$html = str_replace("%%%dnl_body%%%", $dnl_body, $html);
			break;
			
		case "login_lock":
			$html = file_get_contents(CLIENT_VIEW_DIR . "login_lock.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
			
		case "followup":
			$html = file_get_contents(CLIENT_VIEW_DIR . "followup.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "followup_info":
			$html = file_get_contents(CLIENT_VIEW_DIR . "followup_info.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "payment_arrangement_history":
			$html = file_get_contents(CLIENT_VIEW_DIR . "payment_arrangement_history.html");
			$pah_html = $this->Build_Payment_Arrangement_History();
			$html = str_replace("%%%payment_arrangement_history%%%", $pah_html, $html);
			break;
						
		case "wizard_error":
			$html = file_get_contents(CLIENT_VIEW_DIR . "wizard_error.html");
			break;

		case "complete":
			$html = file_get_contents(CLIENT_VIEW_DIR . "resolve_pending_items.html");
			$this->Render_Pending_Items();
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "import_account":
			$html = file_get_contents(CLIENT_VIEW_DIR . "import_account.html");
			break;			
				
		case "transaction_details":
			$html = file_get_contents(CLIENT_VIEW_DIR . "transaction_details.html");
			$this->data->mode_class = $this->data->mode;
			$this->Render_Transaction_Details();
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "generate_schedule":
			if(file_exists(CUSTOMER_LIB . "conversion/view/account_editor.html")) 
			{
				$html = file_get_contents(CUSTOMER_LIB . "conversion/view/account_editor.html");
			} 
			else 
			{
				$html = file_get_contents(CLIENT_MODULE_DIR . "conversion/view/account_editor.html");
			}
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->collections_agents_dropdown = '';
			
			foreach ($this->data->collectionsAgents as $agentId => $name)
			{
				$this->data->collections_agents_dropdown .= '<option value="'.$agentId.'">'.$name.'</option>';
			}
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "grace_period_arrangement":
			$html = file_get_contents(CLIENT_VIEW_DIR . "grace_period_arrangement.html");
			$this->data->action_name = "Add Grace Period Arrangement";
			$this->data->action_type = "grace_period_arrangement";
			$this->data->save_text = "Added Grace Period Arrangement";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
				
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;		

		case "refinance_ineligible":
			$html = file_get_contents(CLIENT_VIEW_DIR . "CSO_Refinance_Ineligible.html");

			$this->data->application_id = $_SESSION['current_app']->application_id;
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();

			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "rollover_request":
			$html = file_get_contents(CLIENT_VIEW_DIR . "Payday_renewal_Request.html");
			$this->data->action_name = "Rollover_Request";
			$this->data->action_type = "request_rollover";
			$this->data->application_id = $_SESSION['current_app']->application_id;
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();

			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "rollover":
			$html = file_get_contents(CLIENT_VIEW_DIR . "Payday_renewal.html");
			$this->data->action_name = "Rollover";
			$this->data->action_type = "rollover";
			
			$application_id = $_SESSION['current_app']->application_id;
			$this->data->save_text = "Renewal";
			$this->data->inline_text = "Renewal";

			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "refinance":
			$html = file_get_contents(CLIENT_VIEW_DIR . "CSO_Refinance.html");
			$this->data->action_name = "CSO_Refinance";
			$this->data->action_type = "refinance";
			
			$application_id = $_SESSION['current_app']->application_id;
			$application = ECash::getApplicationById($application_id);
			$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($application_id);
			$rate_calc = $application->getRateCalculator();
			
			$this->data->principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			$this->data->save_text = "Added Refinancing";
			$this->data->inline_text = "Refinanced";

			// Check to see if we require a paydown
			$max = intval($this->data->rules['service_charge']['max_renew_svc_charge_only_pmts']);

			//Rollovers is the rollover term -1, as the initial loan (with no rollover) is counted as 1
			$rollovers = $renewal_class->getRolloverTerm($application_id) - 1;

			$this->data->payment_amount = "0.00";

			if ($rollovers >= $max) //paydown required, motherlover;
			{
				$this->data->paydown_required = 'true';

				// Detect if we have a required minimum principal payment
				$percentage = $this->data->rules['principal_payment']['min_renew_prin_pmt_prcnt'];
				
				$this->data->payment_amount = $rate_calc->round($balance_info->principal_balance * ($percentage / 100));
			}
			else
				$this->data->paydown_required = 'false';

			$this->data->minimum_payment_tr = "";

			// Yes I know what I'm doing
			if ($this->data->paydown_required == 'true')
			{
				$this->data->minimum_payment_tr = "
					<tr>
						<td class='left'>Required Minimum Principal Payment:</td>
						<td class='right'>\${$this->data->payment_amount}</td>
					</tr>
				";
			}

			 

			if (isset($_SESSION['api_payment']))
			{
				$this->data->amount = $_SESSION['api_payment']->amount;
			} 
			else 
			{
				$this->data->amount = '';
			}

			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->outstanding_amount = $this->data->schedule_status->posted_and_pending_principal;
						
			$this->data->onload = "";
			$this->data->select_onclick = "";
			$this->data->scheduled_date_onchange = "";
			
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;		
		case "paydown":
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$rate_calc = $application->getRateCalculator();

			$html = file_get_contents(CLIENT_VIEW_DIR . "pay_down_and_out.html");
			$this->data->action_name = "Add Paydown";
			$this->data->action_type = "paydown";
			$this->data->principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			if($this->data->rules['next_action_date'] == 'next')
			{
				$this->data->first_date_checked = '';
				$this->data->next_date_checked = ' checked';
			}
			else
			{
				$this->data->first_date_checked = ' checked';
				$this->data->next_date_checked = '';
			}
			$this->data->save_text = "Added Paydown";
			$this->data->inline_text = "paid down";
			if (isset($_SESSION['api_payment']))
			{
				$this->data->amount = $_SESSION['api_payment']->amount;
			} 
			else 
			{
				$this->data->amount = '';
			}

			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->date_field = "";		
			
			$this->data->amount_tr = '
			<tr>
	  			<td class="right">Amount paid down:</td>
  				<td class="left"><input id="amount" name="amount" size="10" type="text" value="'.$this->data->amount.'"></td>
			</tr>
			<tr>
  				<td class="right">Description:</td>
  				<td class="left"><input name="payment_description" id="payment_description" type="text" size="30" maxlength="200" value=""></td>
			</tr>
			';
			
			$this->data->outstanding_amount_tr =
			'<tr>
   				<td class="right">Outstanding Amount:</td>
   				<td class="left">$&nbsp;'.$this->data->schedule_status->posted_and_pending_principal.'</td>
			</tr>';


			$this->data->onload = "";
			$this->data->today_onclick = "onclick=\"this.form.scheduled_date.value = '';\"";
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = ''\"";
			$this->data->select_onclick = "";
			$this->data->scheduled_date_onchange = "";

			$this->data->next_business_day_tr = '';

			if ($this->data->next_business_day != NULL)
			{
				$this->data->next_business_day_tr = "
				<tr>
					<td class=\"right\"><label for=\"today\">First Business Day ({$this->data->next_business_day})</label></td>
					<td class=\"left\"><input type=\"radio\" id=\"today\" name=\"edate\" {$this->data->today_onclick} value=\"{$this->data->next_business_day}\"{$this->data->first_date_checked}></td>
				</tr>
				";
			}			

			
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;		

		case "payment_card_payoff":
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$rate_calc = $application->getRateCalculator();
			
			$html = file_get_contents(CLIENT_VIEW_DIR . "payment_card_payoff.html");
			$this->data->action_name = "Payment Card Payoff";
			$this->data->action_type = "payment_card_payoff";
			$this->data->save_text = "Scheduled Payment Card Payoff";
			$this->data->inline_text = "Scheduled Payment Card Payoff";
			//$this->data->schedule_status = Analyze_Schedule(Fetch_Schedule($_SESSION['current_app']->application_id));
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->posted_principal = $this->data->schedule_status->posted_principal;
			$this->data->posted_fees = $this->data->schedule_status->posted_fees;
			$this->data->posted_total = $this->data->schedule_status->posted_total;
			$this->data->today_formatted = date('m/d/Y');
			$this->data->outstanding_amount_tr = 
			'
					<tr>
						<td class="right">Payoff Amount:</td>
						<td class="left">&nbsp; $<span id="payment_card_payoff_total"></span></td>
					</tr>
			';
			$this->data->amount_tr = '<input id="amount" name="amount" size="10" type="hidden" value="1">';
			$this->pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
			$this->data->today = Date('Y-m-d');
			$this->data->onload = "onload=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_business_day%%%'); document.getElementById('today').checked=true\"";
			$this->data->today_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_business_day%%%');\"";

			$this->data->next_action_date = $this->pdc->Get_Last_Business_Day($this->data->next_due_date);
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = ''\"";
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_due_date%%%');\"";
			$this->data->select_onclick = "onclick=\"get_payout_total('%%%action_type%%%', '%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',document.getElementById('scheduled_date').value);\"";
			$this->data->scheduled_date_onchange = "onchange=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',this.value);\"";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->date_field = "";

			$this->data->next_business_day_tr = '';

			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;		

		case "manual_ach":
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$rate_calc = $application->getRateCalculator();

			$html = file_get_contents(CLIENT_VIEW_DIR . "pay_down_and_out.html");
			$this->data->action_name = "Add ACH Payment";
			$this->data->action_type = "manual_ach";
			$this->data->principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			if($this->data->rules['next_action_date'] == 'next')
			{
				$this->data->first_date_checked = '';
				$this->data->next_date_checked = ' checked';
			}
			else
			{
				$this->data->first_date_checked = ' checked';
				$this->data->next_date_checked = '';
			}
			$this->data->save_text = "Additional ACH Payment";
			$this->data->inline_text = "to pay";
			$this->data->amount = '';

			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->date_field = "";		
			
			$this->data->amount_tr = '
			<tr>
	  			<td class="right">Amount to pay:</td>
  				<td class="left"><input id="amount" name="amount" size="10" type="text" value="'.$this->data->amount.'"></td>
			</tr>
			<tr>
  				<td class="right">Description:</td>
  				<td class="left"><input name="payment_description" id="payment_description" type="text" size="30" maxlength="200" value=""></td>
			</tr>
			';
			
			$this->data->outstanding_amount_tr =
			'<tr>
   				<td class="right">Outstanding Amount:</td>
   				<td class="left">$&nbsp;'.$this->data->schedule_status->posted_and_pending_principal.'</td>
			</tr>';


			$this->data->onload = "";
			$this->data->today_onclick = "onclick=\"this.form.scheduled_date.value = '';\"";
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = ''\"";
			$this->data->select_onclick = "";
			$this->data->scheduled_date_onchange = "";

			$this->data->next_business_day_tr = '';

			if ($this->data->next_business_day != NULL)
			{
				$this->data->next_business_day_tr = "
				<tr>
					<td class=\"right\"><label for=\"today\">First Business Day ({$this->data->next_business_day})</label></td>
					<td class=\"left\"><input type=\"radio\" id=\"today\" name=\"edate\" {$this->data->today_onclick} value=\"{$this->data->next_business_day}\"{$this->data->first_date_checked}></td>
				</tr>
				";
			}			

			
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;		

		case "payout":
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$rate_calc = $application->getRateCalculator();

			$html = file_get_contents(CLIENT_VIEW_DIR . "pay_down_and_out.html");
			$this->data->action_name = "Schedule Payout";
			$this->data->action_type = "payout";
			$this->data->save_text = "Scheduled Payout";
			$this->data->inline_text = "Scheduled Payout";
			//$this->data->schedule_status = Analyze_Schedule(Fetch_Schedule($_SESSION['current_app']->application_id));
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			$date_calc = '';
			if($this->data->rules['next_action_date'] == 'next')
			{
				$this->data->first_date_checked = '';
				$this->data->next_date_checked = ' checked';
				$date_calc = $this->data->next_due_date;
			}
			else
			{
				$this->data->first_date_checked = ' checked';
				$this->data->next_date_checked = '';
				$date_calc = $this->data->next_business_day;
			}
			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->posted_principal = $this->data->schedule_status->posted_principal;
			$this->data->posted_fees = $this->data->schedule_status->posted_fees;
			$this->data->posted_total = $this->data->schedule_status->posted_total;
			$this->data->outstanding_amount_tr = 
			'
					<tr>
						<td class="right">Payout Amount:</td>
						<td class="left">&nbsp; $<span id="payout_total"></span></td>
					</tr>
			';
			$this->data->amount_tr = '<input id="amount" name="amount" size="10" type="hidden" value="1">';
			$this->pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
			$this->data->today = Date('Y-m-d');
			$this->data->onload = "onload=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','{$date_calc}');\"";
			$this->data->today_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_business_day%%%');\"";

			$this->data->next_action_date = $this->pdc->Get_Last_Business_Day($this->data->next_due_date);
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = ''\"";
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_due_date%%%');\"";
			$this->data->select_onclick = "onclick=\"get_payout_total('%%%action_type%%%', '%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',document.getElementById('scheduled_date').value);\"";
			$this->data->scheduled_date_onchange = "onchange=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',this.value);\"";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
//			$this->data->posted_principal = $this->data->schedule_status->posted_principal;
//			$this->data->posted_fees = $this->data->schedule_status->posted_fees;
//			$this->data->posted_total = $this->data->schedule_status->posted_total;
			$this->data->date_field = "";

			$this->data->next_business_day_tr = '';

			if ($this->data->next_business_day != NULL)
			{
				$this->data->next_business_day_tr = "
				<tr>
					<td class=\"right\"><label for=\"today\">First Business Day ({$this->data->next_business_day})</label></td>
					<td class=\"left\"><input type=\"radio\" id=\"today\" name=\"edate\" {$this->data->today_onclick} value=\"{$this->data->next_business_day}\"{$this->data->first_date_checked}></td>
				</tr>
				";
			}


			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
		break;

		case "reattempt":
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$rate_calc = $application->getRateCalculator();
	
			$html = file_get_contents(CLIENT_VIEW_DIR . "pay_down_and_out.html");
			$this->data->action_name = "Schedule Reattempt";
			$this->data->action_type = "reattempt";
			$this->data->save_text = "Scheduled Reattempt";
			$this->data->inline_text = "Scheduled Reattempt";
			//$this->data->schedule_status = Analyze_Schedule(Fetch_Schedule($_SESSION['current_app']->application_id));
			$balance_info = Fetch_Balance_Information($_SESSION['current_app']->application_id);
			$this->data->interest_accrual_limit = $this->data->rules['service_charge']['interest_accrual_limit'];
			$this->data->svc_charge_percentage = $rate_calc->getPercent();
			$date_calc = '';
			if($this->data->rules['next_action_date'] == 'next')
			{
				$this->data->first_date_checked = '';
				$this->data->next_date_checked = ' checked';
				$date_calc = $this->data->next_due_date;
			}
			else
			{
				$this->data->first_date_checked = ' checked';
				$this->data->next_date_checked = '';
				$date_calc = $this->data->next_business_day;
			}

			$this->data->service_charge_balance_pending = $balance_info->service_charge_pending;
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->posted_principal = $this->data->schedule_status->posted_principal;
			$this->data->posted_fees = $this->data->schedule_status->posted_fees;
			$this->data->posted_total = $this->data->schedule_status->posted_total;
			/*
			$this->data->outstanding_amount_tr = 
			'
			<tr>
			<td class="right">Payout Amount:</td>
			<td class="left">&nbsp; $<span id="payout_total"></span></td>
			</tr>
			';
			*/
			$this->data->outstanding_amount_tr = '';
			$this->data->amount_tr = '<input id="amount" name="amount" size="10" type="hidden" value="1">';
			$this->pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
			$this->data->today = Date('Y-m-d');
			$this->data->onload = "onload=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','{$date_calc}');\"";
			$this->data->today_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_business_day%%%');\"";
	
			$this->data->next_action_date = $this->pdc->Get_Last_Business_Day($this->data->next_due_date);
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = ''\"";
			$this->data->next_onclick = "onclick=\"this.form.scheduled_date.value = '';get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%','%%%next_due_date%%%');\"";
			$this->data->select_onclick = "onclick=\"get_payout_total('%%%action_type%%%', '%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',document.getElementById('scheduled_date').value);\"";
			$this->data->scheduled_date_onchange = "onchange=\"get_payout_total('%%%action_type%%%','%%%action_type%%%_total',%%%principal%%%,'%%%start_date%%%',this.value);\"";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			//$this->data->posted_principal = $this->data->schedule_status->posted_principal;
			//$this->data->posted_fees = $this->data->schedule_status->posted_fees;
			//$this->data->posted_total = $this->data->schedule_status->posted_total;
			$this->data->date_field = "";
	
			$this->data->next_business_day_tr = '';
	
			if ($this->data->next_business_day != NULL)
			{
				$this->data->next_business_day_tr = "
				<tr>
				<td class=\"right\"><label for=\"today\">First Business Day ({$this->data->next_business_day})</label></td>
				<td class=\"left\"><input type=\"radio\" id=\"today\" name=\"edate\" {$this->data->today_onclick} value=\"{$this->data->next_business_day}\"{$this->data->first_date_checked}></td>
				</tr>
				";
			}
	
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
			
		case "recovery":
			$html = file_get_contents(CLIENT_VIEW_DIR . "single_payment_popup.html");
			$this->data->action_name = "Second Tier Recovery";
			$this->data->action_type = "recovery";
			$this->data->save_text = "2nd Tier Recovery";
			$this->data->inline_text = "recovered";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "writeoff":
		//	echo '<pre>'.print_r($this->data->schedule_status,true).'</pre>';
			
			$html = file_get_contents(CLIENT_VIEW_DIR . "single_payment_popup.html");	
			$this->data->action_name = "Bad Debt Writeoff";
			$this->data->action_type = "writeoff";
			$this->data->save_text = "Debt Writeoff";
			$this->data->inline_text = "to writeoff";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_and_pending_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_and_pending_fees : null;
			$this->data->posted_service_charge = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_and_pending_interest : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_and_pending_total : null;
			$this->data->amount = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_and_pending_total : null;

			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "refund":
			$html = file_get_contents(CLIENT_VIEW_DIR . "refund_payment_popup.html");
			$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());
			$refund_settings = $business_rules->Get_Rule_Set_Component_Parm_Values(ECash::getCompany()->name_short, 'refund_options');

			//default to TRUE if not set
			$this->data->refund_more = isset($refund_settings['refund_more']) && $refund_settings['refund_more'] == 'No' ?
				'false' : 'true';
			
			$this->data->action_name = "Refund Amount";
			$this->data->action_type = "refund";
			$this->data->save_text = "Refund";
			$this->data->inline_text = "to refund to customer";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$jsarray  =  "var tp_array = new Array();\n";
			$jsarray  .= "var ts_array = new Array();\n";
			for($i=0; $i<count($this->data->transaction_history); $i++)
			{
				$th = $this->data->transaction_history[$i];
				if($th->transaction_status == "complete" && $th->amount < 0 && strpos($th->name_short, "writeoff") === false)
				{
					$th->amount *= -1;
					if($th->affects_principal == "yes")
					{
						$jsarray .= "tp_array[{$th->transaction_register_id}] = {$th->amount}\n";
						$this->data->option_tp .= "<option value='{$th->transaction_register_id}'>{$th->transaction_register_id} \${$th->amount}</option>\n";
					}
					else 
					{
						$jsarray .= "ts_array[{$th->transaction_register_id}] = {$th->amount}\n";
						$this->data->option_ts .= "<option value='{$th->transaction_register_id}'>{$th->transaction_register_id} \${$th->amount}</option>\n";
					}
				}
			}
			$this->data->popup_js_array = $jsarray;
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "adjustment":
			$html = file_get_contents(CLIENT_VIEW_DIR . "adjustment.html");
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->posted_principal = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_principal : null;
			$this->data->posted_fees = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_fees : null;
			$this->data->posted_total = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_total : null;
			$this->data->posted_service_charge = isset($this->data->schedule_status) ? $this->data->schedule_status->posted_interest : null;
			
			// Give custom tokens a blank value
			$this->data->posted_lien_fees        = 0;
			$this->data->posted_delivery_fees    = 0;
			$this->data->display_lien_fee_tr     = "";
			$this->data->display_delivery_fee_tr = "";
			$this->data->lien_fee_option         = "";
			$this->data->delivery_fee_option     = "";
			
			// Only display lien fee adjustment if they have lien fees.
			if (Application_Has_Events_By_Event_Names($_SESSION['current_app']->application_id, array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien')) == true)
			{
				// Display the lien fee only if it exists
				$due = Fetch_Balance_Total_By_Event_Names($_SESSION['current_app']->application_id, array('assess_fee_lien','payment_fee_lien','writeoff_fee_lien'));

				$this->data->display_lien_fee_tr  = "<tr>\n";
				$this->data->display_lien_fee_tr .= "  <td class=\"right\"> Lien Fee Balance: </td>\n";
				$this->data->display_lien_fee_tr .= "  <td class=\"left\"> \$" . $due . "</td>\n";
				$this->data->display_lien_fee_tr .= "</tr>\n";

				$this->data->lien_fee_option = '<option value="lienfee">Lien Fees</option>';

				$this->data->posted_lien_fees = $due;
			}

			// Only display delivery fee adjustment if they have delivery fees.
			if (Application_Has_Events_By_Event_Names($_SESSION['current_app']->application_id, array('assess_fee_delivery','payment_fee_delivery','writeoff_fee_delivery')) == true)
			{
				// Display the lien fee only if it exists
				$due = Fetch_Balance_Total_By_Event_Names($_SESSION['current_app']->application_id, array('assess_fee_delivery','payment_fee_delivery','writeoff_fee_delivery'));

				$this->data->display_delivery_fee_tr  = "<tr>\n";
				$this->data->display_delivery_fee_tr .= "  <td class=\"right\"> Delivery Fee Balance: </td>\n";
				$this->data->display_delivery_fee_tr .= "  <td class=\"left\"> \$" . $due . "</td>\n";
				$this->data->display_delivery_fee_tr .= "</tr>\n";

				$this->data->delivery_fee_option = '<option value="deliveryfee">Delivery Fees</option>';

				$this->data->posted_delivery_fees = $due;
			}
			
			if (isset($this->data->preset_date))
			{
				$this->data->date_field = "value=\"".$this->data->preset_date."\" readonly";
				$this->data->date_anchor = "";
			}
			else
			{
				$this->data->date_field = "value=\"".date('m/d/Y')."\"";
				$this->data->date_anchor = "<a href=\"#\" onClick=\"javascript:PopCalendar1('adjustment_date', event, '" .date('m/d/Y') . "', false);\">(select)</a>";
			}
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "debt_company":
		case "debt_company_edit":
			$html = file_get_contents(CLIENT_VIEW_DIR . "debt_company.html");
			
			if($next_level == "debt_company_edit")
			{
				require_once("state_selection.2.php");
				$state_dd = new State_Selection();
				
				$debt_company = Get_Debt_Company($this->data->debt_company_id);

				$this->data->debt_company_id 	= $debt_company['company_id'];
				$this->data->debt_company_name	= $debt_company['company_name'];
				$this->data->debt_address_1		= $debt_company['address_1'];
				$this->data->debt_address_2		= $debt_company['address_2'];
				$this->data->debt_city			= $debt_company['city'];
				$this->data->debt_state			= $debt_company['state'];
				$this->data->debt_zip_code		= $debt_company['zip_code'];
				$this->data->debt_contact_phone	= $debt_company['contact_phone'];
				$this->data->debt_state_drop = $state_dd->State_Pulldown ("debt_company_state", 0, 0, isset($this->data->saved_error_data->debt_state) ? $this->data->saved_error_data->debt_state : $this->data->debt_state, true, "", 0, false, false, NULL, NULL, NULL,'debt_company_state');
				
				$this->data->action_type = "edit";
				$this->data->submit_type = "Save Debt Company";
			}
			else if($next_level == "debt_company")
			{
				require_once("state_selection.2.php");
				$state_dd = new State_Selection();
				$this->data->debt_company_id 	= "";
				$this->data->debt_company_name	= "";
				$this->data->debt_address_1		= "";
				$this->data->debt_address_2		= "";
				$this->data->debt_city			= "";
				$this->data->debt_state			= "";
				$this->data->debt_zip_code		= "";
				$this->data->debt_contact_phone	= "";
				$this->data->debt_state_drop = $state_dd->State_Pulldown ("debt_company_state", 0, 0, isset($this->data->saved_error_data->debt_state) ? $this->data->saved_error_data->debt_state : $this->data->debt_state, true, "", 0, false, false, NULL, NULL, NULL,'debt_company_state');
				$this->data->action_type = "add";
				$this->data->submit_type = "Add Debt Company";
			}
			
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			break;
		case "ext_recovery_reversal":
			$html = file_get_contents(CLIENT_VIEW_DIR . "{$next_level}_popup.html");
			$this->data->action_type = $next_level;
			$this->data->action_name = $next_level;
			$this->data->transaction_history = Gather_App_Transactions($_SESSION['current_app']->application_id);
			$jsarray  =  "var tr_array = new Array();\n";
          //  echo '<pre>'. print_r($this->data->transaction_history,true). '</pre>';
			for($i=0; $i<count($this->data->transaction_history); $i++)
            {
                $th = $this->data->transaction_history[$i];
             
				if(($th->name_short == "ext_recovery_princ" ||
				$th->name_short == "ext_recovery_fees") && ($th->amount < 0) )
				{
    	            $jsarray .= "tr_array[{$th->transaction_register_id}] = {$th->amount}\n";
	                $this->data->option_tr .= "<option value='{$th->transaction_register_id}'>{$th->transaction_register_id} $ {$th->amount}</option>\n";
				}
            }
            $this->data->popup_js_array = $jsarray;
			
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "chargeback":
			$html = file_get_contents(CLIENT_VIEW_DIR . "{$next_level}_popup.html");
			$this->data->action_type = $next_level;
			$this->data->action_name = $next_level;
			$jsarray  =  "var tr_array = new Array();\n";
			$schedule = Fetch_Schedule($_SESSION['current_app']->application_id);
		//	echo '<pre>'.print_r($schedule,true).'</pre>';
			$event_list = array();	
			$event_dates = array();
			for($i=0; $i<count($schedule); $i++)
            {
                $th = $schedule[$i];
                // We only want to get Credit Card Transactions for Chargeback
				// GF #11731: Also ignore failed credit card transactions. [benb]
                if(strstr($th->type, "credit_card") && $th->status != 'failed')
				{
					$event_list[$th->event_schedule_id] = 1;
					$event_dates[$th->event_schedule_id] = $th->date_event_display;
				}
				
            }
            foreach ($event_list as $e => $item)
            {
            	$transids = Fetch_Transaction_IDs_For_Event($e);
            	$event_amount = 0;
            	
            	foreach ($transids as $trans)
            	{
            		for($i=0; $i<count($this->data->transaction_history); $i++)
		            {
		            	$th = $this->data->transaction_history[$i];
		            	
		            	if($th->transaction_register_id == $trans)
		            	{
		            		$event_amount += $th->amount;
		            		break;
		            	}
		            	
		            }
            	}
                $jsarray .= "tr_array[{$e}] = {$event_amount}\n";
	            $this->data->option_tr .= "<option value='{$e}'>{$e} - {$event_dates[$e]} - $ {$event_amount} </option>\n";

            	
            }
            
            $this->data->popup_js_array = $jsarray;
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
			
		case "chargeback_reversal":			
			$html = file_get_contents(CLIENT_VIEW_DIR . "{$next_level}_popup.html");
			$this->data->action_type = $next_level;
			$this->data->action_name = $next_level;
			$jsarray  =  "var tr_array = new Array();\n";
			$schedule = Fetch_Schedule($_SESSION['current_app']->application_id);
		//	echo '<pre>'.print_r($schedule,true).'</pre>';
			$event_list = array();	
			$event_dates = array();
			for($i=0; $i<count($schedule); $i++)
            {
                $th = $schedule[$i];
                // We only want to get Chargeback Transactions for Chargeback Reversal
				if (in_array($th->type, array('chargeback','card_chargeback')))
				{
					$event_list[$th->event_schedule_id] = 1;
					$event_dates[$th->event_schedule_id] = $th->date_event_display;
				}
            }
            foreach ($event_list as $e => $item)
            {
            	$transids = Fetch_Transaction_IDs_For_Event($e);
            	$event_amount = 0;
            	
            	foreach ($transids as $trans)
            	{
            		for($i=0; $i<count($this->data->transaction_history); $i++)
		            {
		            	$th = $this->data->transaction_history[$i];
		            	if($th->transaction_register_id == $trans)
		            	{
		            		$event_amount += $th->amount;
		            		break;
		            	}
		            	
		            }
            	}
                $jsarray .= "tr_array[{$e}] = {$event_amount}\n";
	            $this->data->option_tr .= "<option value='{$e}'>{$e} - {$event_dates[$e]} - $ {$event_amount} </option>\n";

            	
            }
            
            $this->data->popup_js_array = $jsarray;
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "condor_doc":
			$html = ECash::getTransport()->Get_Data();
			break;

		case "quick_check_view_download":
			$html_file_location = CLIENT_MODULE_DIR . "collections/view/quick_checks_popup.html";
			$this->data->data_rows_concatenated = '';
			$i = 0;
			foreach( $this->data->data_rows as $row )
			{
				$this->data->data_rows_concatenated .= $this->Get_Quick_Check_Row_Html( $row, $i++ );
			}

			$form = new Form($html_file_location);
			$html = $form->As_String($this->data);
			break;

		case 'quick_check_resend_message':
			// I need to use a different HTML file for this but since it's just dummy data at this point, I'll wait.
			$html_file_location = CLIENT_MODULE_DIR . "collections/view/quick_checks_popup.html";

			$this->data->data_rows_concatenated = '<tr><td align="center" colspan="3" style="font-size:1.4em; color:red;">Batch: ' . $this->data->quick_checks_batch_id . ' has been resent</td></tr>';

			$form = new Form($html_file_location);
			$html = $form->As_String($this->data);
			break;
			
		case 'loan_action':
			$data = $this->data;
			$data->bgcolor = NULL;

			$data->type = $_GET["type"];
			if (!empty($_GET['loan_section'])) $data->loan_section = $_GET["loan_section"];
			else $data->loan_section = "";
			
			if (!empty($_GET['mode'])) $data->mode = $_GET['mode'];
			else $data->mode = '';
			
			if(!empty($_GET['mutually_exclusive'])) $data->input_type = "radio";
			else $data->input_type = "checkbox";
			
			$data->dd = null;
			switch($data->type)
			{
				case "Deny":
					$data->dd = "Denial Letter";
					$data->loan_action_type = "FUND_DENIED";			
					break;
				 
				case "Withdraw": 
					if($data->loan_section == "CS")
					{
						$data->dd = "Withdrawn Application";
						$data->loan_action_type = "CS_WITHDRAW";
					}
					else 
					{
						$data->dd = "Withdrawn Application";
						$data->loan_action_type = "FUND_WITHDRAW";			
					}			
					break;
				 
				case "Reverify": 
					if($data->loan_section == "CS")
					{
						$data->loan_action_type = "CS_REVERIFY";
					}
					break;
			
				case "InProcess": 
					$data->loan_action_type = "IN_PROCESS";
					break;
				
				case "Approve": 
					$data->loan_action_type = "FUND_APPROVE";
					break;
				 
				case "Dequeue": 
					$data->loan_action_type = "DEQUEUE";
			
				case "Release":
					// GF #12940: Fixed mistaken $data->loan_section instead of $loan_section, testing anyone? [benb]
					$data->loan_action_type = $data->loan_section;
					break;
			        
			}
			
			$data->loan_action_types = Get_Loan_Action_Types($data->loan_action_type);
			$data->opts = "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
			$data->lastopts = "";
			foreach ($data->loan_action_types as $item) 
			{
				if($item->name_short == "specify_other") 
				{
					$js = " onChange=\"javascript:Other_Reason_Swap(true);\" ";
					$data->checkbox = "<input type=\"{$data->input_type}\" name=loan_actions[] id=loan_actions value='{$item->loan_action_id}' {$js}>";
					$data->lastopts = "<tr bgcolor='lime'><td>{$data->checkbox}</td>";
					$data->lastopts .= "<td style='text-align: left'><font size='-1'>{$item->description}</font></td></tr>";
			
				}
				else
				{
					$data->bgcolor = is_null($data->bgcolor) ? "silver" : "white";
					$data->checkbox = "<input type=\"{$data->input_type}\" name=loan_actions[] id=loan_actions value='{$item->loan_action_id}'>";
					$data->opts .= "<tr bgcolor='{$data->bgcolor}'><td>{$data->checkbox}</td>";
					$data->opts .= "<td style='text-align: left'><font size='-1'>{$item->description}</font></td></tr>\n";
					$data->bgcolor = ($data->bgcolor == "white") ? null : $data->bgcolor;
				}
					
			}
			
			$data->opts .= "{$data->lastopts}</table>";
			if ((($data->type == "Deny") || ($data->type == "Withdraw")) && $data->dd) {
				$data->send_docs = '<td valign=bottom>
				<input type="checkbox" id="document_list" name="document_list" value="' . $data->dd . '" checked>Send Docs<bR>
				</td>';
			} else {
				$data->send_docs = '<td></td>';
			}
			$this->data = $data;

			$form = new Form(CLIENT_VIEW_DIR . "loan_action.html");
			$html = $form->As_String($data);
			break;
		
		case 'paydate_wizard':
			$form = new Form(CLIENT_VIEW_DIR . "paydate_wizard.html");

			//[#28571] paydate wizard changes need re-signing of documents (if not funded)
			$this->data->warn_esig = 'false';
			$application = ECash::getApplicationById($_SESSION['current_app']->application_id);
			$status = $application->getStatus();		
			if(in_array($status->level0, array('underwriting', 'verification'))
			   || in_array($status->level1, array('underwriting', 'verification')))
			{
				$this->data->warn_esig = 'true';
			}
			
			$html = $form->As_String($this->data);
			break;
			
		case 'dup_bank_account':
			$form = new Form(CLIENT_VIEW_DIR . "dup_bank_account.html");
			$html = $form->As_String($this->data);
			break;
		case 'dup_ip_address':
			$data = $this->data;
			
			//loop through the records and populate the tables.
			foreach ($data->records as $row) 
			{
			
					if ($row['date_created'])
					{
				 	    $cdate = substr($row['date_created'], 5, 2) . "/" .
						  	     substr($row['date_created'], 8, 2) . "/" .
							     substr($row['date_created'], 0, 4) . " " .
							     substr($row['date_created'],11, 2) . ":" .
							     substr($row['date_created'],14, 2) . ":" .
							     substr($row['date_created'],17, 2);
					}
					else
					{
					    $cdate = "";
					}
					$row['cdate'] = $cdate;
					if (strlen($row['unit']) > 0)
					{
						$address_line_1 = $row['street'] . ' ' . $row['unit'];
					}
					else
					{
						$address_line_1 = $row['street'];
					}
					
					$data->info_table .= "<tr>";
					$data->info_table .= "<td>" . $row['ssn']						. "</td>";
					$data->info_table .= "<td>" . ucwords(strtolower($row['application_id']))	. "</td>";
				  	$data->info_table .= "<td>" . ucwords(strtolower($row['name_first']))		. " " . ucwords(strtolower($row['name_last'])) . "</td>";
					$data->info_table .= "<td>" . ucwords(strtolower($address_line_1))		. "</td>";
					$data->info_table .= "<td>" . ucwords(strtolower($row['city']))			. "</td>";
					$data->info_table .= "<td>" . strtoupper($row['state'])					. "</td>";
					$data->info_table .= "<td>" . $cdate								. "</td>";
					$data->info_table .= "<td>" . $row['status']							. "</td>";
					$data->info_table .= "</tr>\n";	
			}
			

			$data->summary = "<br>Number of different applications with this IP Address : &nbsp;&nbsp; <b>$data->ip_count</b>\n";
		
			
			$form = new Form(CLIENT_VIEW_DIR.'dup_ip_address.html');
			$html = $form->As_String($data);
			break;
			
			
		case "place_in_hold_status":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_set_hold_status.html");
			$this->data->action_type = "place_in_hold_status";
			$this->data->action_name = "Place in Hold Status";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "return_from_service_hold":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_return_to_service.html");
			$this->data->action_type = "return_from_service_hold";
			$this->data->action_name = "Return From Service Hold";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "add_watch_status":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_watch_status.html");
			$_SESSION['previous_module'] = $_REQUEST['previous_module'];
			$_SESSION['previous_mode'] = $_REQUEST['previous_mode'];
			$this->data->agent_id = ECash::getTransport()->agent_id;
			$this->data->form_name = "add_watch_status";
			$this->data->action_name = "Add Watch Status";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->previous_module = $_SESSION['previous_module'];
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "remove_watch_status":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_watch_status.html");
			$_SESSION['previous_module'] = $_REQUEST['previous_module'];
			$_SESSION['previous_mode'] = $_REQUEST['previous_mode'];
			$this->data->agent_id = ECash::getTransport()->agent_id;
			$this->data->form_name = "remove_watch_status";
			$this->data->action_name = "Remove Watch Status";
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->previous_module = $_SESSION['previous_module'];
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "modify_received_document":
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->previous_module = $_REQUEST['previous_module'];
			$this->data->dispatch_history = $this->Render_Dispatch_History($this->data);
			$html = file_get_contents(CLIENT_VIEW_DIR . "received_document_modify_popup.html");
	   		$access_array = array($this->data->previous_module, $this->data->mode, 'documents', 'modify_document_details');
			$this->data->modify_document_display = 'display: none;';
			if ($this->acl->Acl_Check_For_Access($access_array))
			{
				$this->data->modify_document_display = 'display: block;';
			}		
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		//[#44204]
		case 'show_document':
			if($this->data->document instanceof ECash_Documents_Document)
				Display_Headers($this->data->document);
			else
				$html = $this->data->document;
			break;

		//END [#44204]

		case "post_debt_consolidation":
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->event_schedule_dropdown = $this->Render_Debt_Event_Dropdown($this->data->events);
			$html = file_get_contents(CLIENT_VIEW_DIR . "post_debt_consolidation.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case 'merge_customers':
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->old_application_list = $this->Build_Application_List($this->data->old_applications, true, $this->data->application_id);
			$this->data->new_application_list = $this->Build_Application_List($this->data->new_applications, false);
			$this->data->max_checkboxes = count($this->data->old_applications);
			$html = file_get_contents(CLIENT_VIEW_DIR . "customer_merge.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case 'split_customers':
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$this->data->old_application_list = $this->Build_Application_List($this->data->old_applications, true, $this->data->application_id);
			$this->data->max_checkboxes = count($this->data->old_applications);
			$html = file_get_contents(CLIENT_VIEW_DIR . "customer_split.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case 'compare_react':
			$this->data->mode_class = ECash::getTransport()->Get_Next_Level();
			$html = file_get_contents(CLIENT_VIEW_DIR . "customer_compare_react.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;

		case "pbx_dial_result":
			$html = file_get_contents(CLIENT_VIEW_DIR . "pbx_dial_result.html");
			$html = Display_Utility::Token_Replace($html, (array)$this->data);
			break;
		case "fraud_risk_rules":
			$data = $this->data;
			
			$data->fraud_rules_list  = 'None';
			$data->fraud_fields_list = 'None';
			$data->risk_rules_list   = 'None';
			$data->risk_fields_list  = 'None';

			if(!empty($data->fraud_rules))
			{
				$data->fraud_rules_list = addslashes(join("<br>", explode(";", $data->fraud_rules)));
			}
	
			if(!empty($data->fraud_fields))
			{
				$data->fraud_fields_list = $data->fraud_fields;
			}
	
			if(!empty($data->risk_rules))
			{
				$data->risk_rules_list = addslashes(join("<br>", explode(";", $data->risk_rules)));
			}
	
			if(!empty($data->risk_fields))
			{
				$data->risk_fields_list = $data->risk_fields;
			}

			$html = file_get_contents(CLIENT_VIEW_DIR . "fraud_risk_rules.html");
			$html = Display_Utility::Token_Replace($html, (array)$data);
			break;
		}
		
		echo $html;
	}
	
	protected function Render_Debt_Event_Dropdown($events) 
	{
		$options = array();
		$javascript = "<script type=\"text/javascript\">
			var amounts = [];
			amounts[0] = 0;
		";
		//only display the first three events
		foreach (array_slice($events, 0, 3) as $event) 
		{
			$amount = ($event->principal + $event->service_charge + $event->fee) * -1;
			$display_amount = number_format($amount, 2);
			$options[] = '<option value="'.$event->event_schedule_id.'">'.date('m/d/Y', strtotime($event->date_event)).
				" - {$event->event_schedule_id} (\${$display_amount})</option>";
			
			$javascript .= "amounts[{$event->event_schedule_id}] = {$amount};\n";
		}
		$javascript .= "</script>";
		$options = implode("\n", $options);
		$html = <<<END_HTML
			<input type="hidden" name="original_amount" id="original_amount" value="0" />
			<select name="event_schedule_id" id="event_schedule_id" onChange="document.getElementById('original_amount').value = amounts[this.options[this.selectedIndex].value];document.getElementById('actual_amount').value = amounts[this.options[this.selectedIndex].value];">
				<option selected="selected" value="0">Choose an event</option>
				{$options}
			</select>
			{$javascript}
END_HTML;
		return $html;
	}
	
	protected function Render_Dispatch_History($data) 
	{
		// How about some comments if you're going to write such ugly hackish code? [benb]

		
		// Part of it is understandable, condor is returning
		//
		// Array
		// (
		//    [debug] => 
		//    [trace] => 
		//    [result] => 
		// )
		//
		// on OLP docs and automated doc sending (from what I can discern)
		// and
		// 
		// Array
		// (
		//    [0] => stdClass Object
		//        (
		//            [sender] => 7021234567
		//            [recipient] => 7024929871
		//            [transport] => FAX
		//            [dispatch_date] => 2008-06-26 15:13:31
		//            [status] => done
		//            [status_type] => SENT
		//        )
		// If it was sent manually.

		// GF #15072: This now displays the proper recipient if that information is available [benb]

		$html .= "<div class=\"title_row\"><div style=\"width:120px;\">dispatch date</div><div style=\"width:240px;\">recipient</div><div style=\"float:left;width:60px;\">status</div><div style=\"float:left;width:50px;\">method</div></div>\n";
	
		// If $data->dispatch_history is not an object, that means it was sent from ecash (and not as an automated process?) [benb]
		// [#16479] Warning message given if dispatch_history isn't set.
		if (!is_object($data->dispatch_history) && is_array($data->dispatch_history))
		{
			foreach($data->dispatch_history as $item)
			{
				$status = strtoupper($item->status);
				$html .= "<div class=\"history_row\" style=\"color: #0A0;\">";
				$html .= "<div style=\"width:120px;\">{$item->dispatch_date}</div>";
				$html .= "<div style=\"width:240px; overflow: hidden;\">{$item->recipient}</div>";
				$html .= "<div style=\"float:left;width:60px;\">{$status}</div>";
				$html .= "<div style=\"float:left;width:50px;overflow:hidden;\">{$item->transport}</div></div>\n";
				if(strtoupper($item->status_type) == 'FAIL')
				{
					$html .= "<div class=\"history_row\" style=\"color:#F00; width:500px;\">Error Message: {$item->error_response}</div>\n";
				}

			}
		}
		else
		{
			$data->xfer_date = date("Y-m-d H:i:s", strtotime($data->alt_xfer_date . ' ' . substr($data->xfer_date, strpos($data->xfer_date, ' '))));
			$data->document_method = strtoupper($data->document_method);
			$data->event_type = (strtoupper($data->event_type) == 'RECEIVED') ? 'RCVD' : strtoupper($data->event_type);
			$info = ECash::getApplicationById($data->application_id);

			$html .= "<div class=\"history_row\" style=\"color: #0A0;\">";
			$html .= "<div style=\"width:120px;\">{$data->xfer_date}</div>";
			$html .= "<div style=\"width:240px;\">{$info->email}</div>";
			$html .= "<div style=\"float:left;width:60px;\">{$data->event_type}</div>";
			$html .= "<div style=\"float:left;width:50px;\">{$data->document_method}</div></div>\n";
		}

		return $html;
	}
	
	protected function Render_Transaction_Details()
	{
		$avs_result_ary = array(
			'A' => "Address (Street) matches, ZIP does not",
			'B' => "Address information not provided for AVS check",
			'E' => "AVS error",
			'G' => "Non-U.S. Card Issuing Bank",
			'N' => "No Match on Address (Street) or ZIP",
			'P' => "AVS not applicable for this transaction",
			'R' => "Retry—System unavailable or timed out",
			'S' => "Service not supported by issuer",
			'U' => "Address information is unavailable",
			'W' => "Nine digit ZIP matches, Address (Street) does not",
			'X' => "Address (Street) and nine digit ZIP match",
			'Y' => "Address (Street) and five digit ZIP match",
			'Z' => "Five digit ZIP matches, Address (Street) does not");

		// This is set in Display Overview.
		$read_only = $_SESSION['Transactions_Read_Only'];
		
		$transaction_fields = array( "transaction_register_id" => "Transaction Register ID",
					    "ach_id" => "ACH ID",
					    "authorization_code" => "Payment Card Authorization Code",
					    "card_process_id" => "Payment Card Process ID",
					    "ach_provider_name" => "ACH Provider",
					    "name" => "Transaction Name",
					    "confirmation_number" => "Confirmation Number",
					    "transaction_status" => "Transaction Status",
					    "return_date" => "Date Returned",
					    "return_code" => "Return Code",
					    "return_description" => "Return Description",
					    "reason_code" => "Declined Code", 
					    "response_text" => "Declined Response", 
					    "reason_text" => "Declined Reason", 
					    "avs_code" => "AVS Check Code",
					    "avs_reason" => "AVS Check Result",
					    "transaction_date_created" => "Date Created",
					    'transaction_date_modified' => 'Date Modified',
					    'date_effective' => 'Date Effective');
		$event_fields = array("event_schedule_id" => "Event Schedule ID",
				      "name" => "Event Name", 
				      "date_event" => "Action Date", 
				      "date_effective_formatted" => "Due Date", 
				      "principal" => "Principal Amount",
				      "service_charge" => "Interest Amount",
				      "fee" => "Fee Amount",
				      "irrecoverable" => "Irrecoverable Amount",
				      "event_status" => "Event Status",
				      "configuration_trace_data" => "Comment", 
				      "origin_id" => "Origin ID", 
				      "origin_group_id" => "Origin Group ID",
				      "context" => "Context",
					  'agent_name' => 'Affiliated Agent',
					  'is_shifted' => 'Shifted',
				      "debt_company_name" => "Debt Company",
				      "event_date_created" => "Date Created",
				      'event_date_modified' => 'Date Modified');
				   
		$str = "<tr><td colspan=\"2\" class=\"section\">Event Details</td></tr>";
		$e = $this->data->event;
		// Put in the event details
		$this->data->application_id = $e->application_id;
		$this->data->name_short = $e->name_short;
		$this->data->transactional_type = "event";
		$this->data->event_schedule_id = $e->event_schedule_id;
		$this->data->transactional_id = $e->event_schedule_id;
		if ($avs_result_ary[$e->avs_code]) $this->data->avs_reason = $avs_result_ary[$e->avs_code];

		$this->data->amount_principal = $e->amount_principal;
		$this->data->amount_non_principal = $e->amount_non_principal;
		$e->date_effective = date("m/d/Y", strtotime($e->date_effective));
		
		//   TRANSACTION MODIFICATION ACCESS!
		$delete_access = false;
		$delete_generated_access = false;
		$complete_access = false;
		$fail_access = false;

		// GF #13514: Determines deletion access for generated events [benb]
		$access_array = Array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'delete_generated_transaction');
		if ($this->acl->Acl_Check_For_Access($access_array))
		{
			$delete_generated_access = true;
		}		
		//Determine delete access
		$access_array = Array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'delete_transaction');
		if ($this->acl->Acl_Check_For_Access($access_array))
		{
			$delete_access = true;	
		}
		
		/** new complete/fail determination done by [#28418] */
		$fail_access_array = NULL;
		$complete_access_array = NULL;
		$tot = $e->amount_principal + $e->amount_non_principal;
		if(in_array($e->transaction_type, array('ach','card')))
		{
			if($tot > 0) //credit
			{
				$fail_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'fail_credit_ach');
				$complete_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'complete_credit_ach');
			}
			else //debit
			{
				$fail_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'fail_debit_ach');
				$complete_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'complete_debit_ach');
			}
		}
		else //non-ach
		{
			if($tot > 0) //credit
			{
				$fail_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'fail_credit_non_ach');
				$complete_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'complete_credit_non_ach');
			}
			else //debit
			{
				$fail_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'fail_debit_non_ach');
				$complete_access_array = array($this->data->module, $this->data->mode, 'transactions', 'transactions_overview', 'complete_debit_non_ach');
			}
		}
		//Determine fail access
		if ($this->acl->Acl_Check_For_Access($fail_access_array))
		{
			$fail_access = TRUE;	
		}
		//Determine Completion access
		if ($this->acl->Acl_Check_For_Access($complete_access_array)) 
		{
			$complete_access = TRUE;
		}
		
		//   END TRANSACTION MODIFICATION ACCESS!
		foreach ($event_fields as $ef => $desc)
		{
			if(isset($e->$ef)) 
			{
				$str .= " <tr onmouseover=\"return nd();\">\n";
				$str .= "  <td class=\"title\">{$desc}:&nbsp;</td>\n";
				$str .= "  <td class=\"data\">";
				$str .= is_string($e->$ef) ? ucfirst($e->$ef) : $e->$ef;
				$str .= "</td>\n";
				$str .= " </tr>\n";
			}
		}
		
		if (isset($e->transaction_register_id))
		{
			if (($history = Get_Transaction_History($e->transaction_register_id)) && $e->transaction_status != 'new')
			{
					
				$history_str = '<table class=transaction_history_hover border=1><th colspan=4>Transaction History</tr><tr><th>Status Before</th><th>Status After</th><th>Date</th><th>User</th></tr>';
	
				try
				{
					// get the username for the modifying agent ID
					$agent = ECash::getFactory()->getModel('Agent');
					$agent->loadBy(array('agent_id' => $e->modifying_agent_id));

					$agent_name = $agent->name_first . ' ' . $agent->name_last;
				}
				catch (Exception $e)
				{
					$agent_name = 'eCash System';
				}	
				
				$first_row = false;
				$first_agent = false;
				$html_rows = '';
				while ($row = $history->fetch(PDO::FETCH_OBJ)) 
				{
					if ($first_row === false) {
						$first_row = $row->status_before;
						if ($row->status_before == '') $row->status_before = 'N/A';
					}
					$html_rows .= '<tr><td>' . ucfirst($row->status_before) . '</td>';
					$html_rows .= '<td>' . ucfirst($row->status_after) . '</td>';
					$html_rows .= '<td>' . date('m/d/Y H:i:s', strtotime($row->date_created)) . '</td>';
					$html_rows .= '<td>' . $row->name_last .' '. $row->name_first . '</td></tr>';
				}
						
				if (($first_row === false) || ($first_row != '')) {
					// Pseudo Row
					$history_str .= "<tr><td>N/A</td>";
					$history_str .= "<td>New</td>";
					$history_str .= "<td>" . date('m/d/Y H:i:s', strtotime($e->date_created)) . "</td>";
					$history_str .= "<td>Unknown</td></tr>";
				}

				$history_str .= $html_rows.'</table>';
				$history->closeCursor();
				$str .= "<tr><td colspan=\"2\" class=\"section\" onmouseover=\"return overlib('{$history_str}', LEFT, ABOVE);\" >Transaction Details<br><span class=data >Mouse Over for History</span> </td></tr>";
			
			}
			else 
			{
				$str .= "<tr><td colspan=\"2\" class=\"section\" >Transaction Details</td></tr>";
			
			}
			$this->data->transaction_register_id = $e->transaction_register_id;
			$this->data->transactional_type = "transaction";
			$this->data->transactional_id = $e->transaction_register_id;
			foreach($transaction_fields as $tf => $desc)
			{
				if(isset($e->$tf)) 
				{
					$str .= " <tr onmouseover=\"return nd();\">\n";
					$str .= "  <td class=\"title\" >{$desc}:&nbsp;</td>\n";
					$str .= "  <td class=\"data\">";
					$str .= is_string($e->$tf) ? ucfirst($e->$tf) : $e->$tf;
					$str .= "</td>\n";
					$str .= " </tr>\n";
				}
			}
		}
		else
		{				
			$this->data->transaction_register_id = "";
		}
		
		//Let's initialize these fields first!!!!!!!!! [#10642]
		$this->data->modify_field = "";
		$this->data->modify_button = "";
		$this->data->remove_button = "";
		$this->data->ach_return_code_ddb = ""; //assembla 25
                $this->data->remove_non_ach_button = "";
		$this->data->switch_ach_card = "";
		$this->data->designate_ach_provider = ""; //asm 80

		$this->data->descriptive_fields = $str;
		
		// GF #13514: We're going to give them access rights to delete generated events. Removed #8491 workaround [benb]
		// If its scheduled, and not read only, evaluate whether they should have a remove/delete button
		if ($e->event_status == 'scheduled' && $read_only != TRUE)
		{
			// Not doing this yet.
			$this->data->modify_field = "<td>\n";
			$this->data->modify_field .= "</td>\n";
			$this->data->modify_field .= "<td>\n";
			$this->data->modify_field .= "</td>\n";
			$this->data->modify_button = "";

			// Only allow removal for generated events if they have that ACL
			// Only allow removal for non-generated events if they have that ACL
			if (($delete_access && $e->context != 'generated') || ($delete_generated_access && $e->context == 'generated'))
			{
				$this->data->remove_button = "<input type=\"button\" value=\"Remove Item\" onClick=\"ConfirmModify({$e->event_schedule_id}, 'event',{$e->application_id}, 'remove');\">\n";
			}
			
			if ($this->data->active_card_saved
				&& 
				(array_key_exists($e->name_short, $this->ach_card_payment_type_map)
				|| in_array($e->name_short, $this->ach_card_payment_type_map)
				)
			)
			{
				$this->data->switch_ach_card = "<input type=\"button\" value=\"ACH <=> Card\" onClick=\"ConfirmModify({$e->event_schedule_id}, 'event',{$e->application_id}, 'switch_ach_card_payment_type');\">\n";
			}

			//asm 80
			$access_designate = Array($this->data->module, $this->data->mode, 'application', 'designate_ach_provider');
			if(
				$this->acl->Acl_Check_For_Access($access_designate)
				&& 
				($e->transaction_type == 'ach')
			)
			{
				$providers = array();
				$pr_model = ECash::getFactory()->getModel('AchProvider');
				$pr_array = $pr_model->loadAllBy(array('active_status' => 'active',));
				foreach ($pr_array as $pr)
				{
					$providers[$pr->ach_provider_id] = $pr->name;
				}

				$designated_ach_provider_id = NULL;
				$date_event_formatted = date('Y_m_d', strtotime($e->date_event));
				$event_provider_model = ECash::getFactory()->getModel('EventScheduleAchProvider');
				$loaded = $event_provider_model->loadBy(array(
										//'event_schedule_id' => $e->event_schedule_id,
										'application_id' => $e->application_id,
										'date_event' => $date_event_formatted,
										'active_status' => 'active',
				));
				if ($loaded)
				{
					$designated_ach_provider_id = $event_provider_model->ach_provider_id;
				}

				$map = array();
				$ap_model = ECash::getFactory()->getModel('Application');
				$ap_model->loadBy(array('application_id' => $this->data->application_id,));
				$state = strtoupper($ap_model->state);

				$config_model = ECash::getFactory()->getModel('AchProviderConfig');
				$config_model_array = $config_model->loadAllBy(array('config_key' => 'ach_states',));
				if (count($config_model_array) > 0)
				{
					foreach ($config_model_array as $config_record)
					{
						$map[$config_record->ach_provider_id] = explode(",", $config_record->config_value);
					}
				}

				$this->data->designate_ach_provider  .= "Designate ACH Provider to the event:<br>\n";
				
				$this->data->designate_ach_provider .= "<select id=\"ach_provider_event\" name=\"ach_provider_event\">
									<option value=''>Any</option>";

				foreach ($providers as $ach_provider_id => $name)
				{
					$select = '';
					$disable = '';

					if (isset($designated_ach_provider_id) && ($designated_ach_provider_id == $ach_provider_id))
					{
						$select = 'SELECTED';
					}

					if (in_array($state, $map[$ach_provider_id]))
					{
						$disable = 'DISABLED';
					}

					$this->data->designate_ach_provider  .= "<option value='" . $ach_provider_id . "' " . $select . " " . $disable . ">" . $name . "</option>";
				}

				$this->data->designate_ach_provider  .= "</select>";
				$this->data->designate_ach_provider .= "<input type=\"button\" value=\"Designate\" onClick=\"ConfirmModify({$e->event_schedule_id}, 'event',{$e->application_id}, 'designate_ach_provider_to_event');\">\n";
			}
		}

                //asm 31
                if ($complete_access
			&& isset($e->transaction_register_id)
                        && !in_array($e->transaction_type, array('ach','card'))
                        //&&
                        //($e->transaction_status == 'complete'
                        //|| $e->transaction_status == 'pending'
                        //|| $e->transaction_status == 'failed'
                        //)
                )
                {
                        $this->data->remove_non_ach_button = "<input type=\"button\" class=\"button2\" value=\"Remove\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'remove non ach');\">\n";
                }

		//If its a registered transaction OR its a scheduled item that allows same-day completion, allow the user to complete or fail it!
		if (/* $e->transaction_status == 'pending' && */(isset($e->transaction_register_id) || $this->can_complete($e)) && ($read_only != true))
		{
			
			//Here is where we look for the # of quickchecks, whether we're in conversion or not,
			// and whether we're editing a quickcheck itself. A little work. Could be worse.
			if ((count($this->data->quickchecks) < 2) &&
			    ($this->data->mode_class == 'conversion'))
			{
				$this->data->modify_field = "
				<td><table><tr><td>If the QC is failed, send account to:</td></tr>
				<tr><td><input type=\"radio\" name=\"end_status\" id=\"end_status[]\" value=\"pending,external_collections,*root\">2nd Tier</td>
				<td><input type=\"radio\" name=\"end_status\" id=\"end_status[]\" value=\"ready,quickcheck,collections,*root\">QC Ready</td></tr></table></td>
				";
			}
			else
			{
				$this->data->modify_field = "";
			}
			
			// If they're quickchecks, show the ACH Return Codes
//			if($e->name_short == 'quickcheck')
//			{
//				$this->data->modify_button  = "<b>Fail with ACH Code:</b><br>\n";
//				$this->data->modify_button .= $this->Build_ACH_Return_Code_List() . "\n";
//			}
//			else
//			{
//					$this->data->modify_button  = "";
//			
//			}
			//assembla 25
			if(in_array($e->transaction_type, array('ach')) 
				&& 
				($e->transaction_status == 'complete' || $e->transaction_status == 'pending')
			)
			{
				$this->data->ach_return_code_ddb  = "<b>Fail with ACH Code:</b><br>\n";
				$this->data->ach_return_code_ddb .= $this->Build_ACH_Return_Code_List() . "\n";

				$fail_button = $fail_access ? "\"" : "disabled\" DISABLED";
				$complete_button = ($complete_access && ($e->transaction_status == 'pending')) ? "\"" : "disabled\" DISABLED";

				$this->data->modify_button .= "<input type=\"button\" class=\"button2".$fail_button. " value=\"Set to Failed\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'fail');\">\n";

				//$this->data->remove_button = "<input type=\"button\" class=\"button2disabled\" DISABLED value=\"Set to Complete\" >\n";
				$this->data->remove_button = "<input type=\"button\" class=\"button2".$complete_button. " value=\"Set to Complete\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'complete');\">\n";
			} elseif(in_array($e->transaction_type, array('card')) 
				&& 
				($e->transaction_status == 'complete' || $e->transaction_status == 'pending')
			)
			{
				$this->data->ach_return_code_ddb  = "<b>Fail with Card Code:</b><br>\n";
				$this->data->ach_return_code_ddb .= $this->Build_Card_Return_Code_List() . "\n";

				$fail_button = $fail_access ? "\"" : "disabled\" DISABLED";
				$complete_button = ($complete_access && ($e->transaction_status == 'pending')) ? "\"" : "disabled\" DISABLED";

				$this->data->modify_button .= "<input type=\"button\" class=\"button2".$fail_button. " value=\"Set to Failed\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'fail');\">\n";

				//$this->data->remove_button = "<input type=\"button\" class=\"button2disabled\" DISABLED value=\"Set to Complete\" >\n";
				$this->data->remove_button = "<input type=\"button\" class=\"button2".$complete_button. " value=\"Set to Complete\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'complete');\">\n";
			}
			else
			{
				//Build disabled buttons if necessary
				$fail_button = $fail_access ? "\"" : "disabled\" DISABLED";
				$complete_button = $complete_access ? "\"" : "disabled\" DISABLED";
			
				if ($e->transaction_status == 'complete')
				{	
					$this->data->modify_button .= "<input type=\"button\" class=\"button2".$fail_button. " value=\"Set to Failed\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'fail');\">\n";
					$this->data->remove_button = "<input type=\"button\" class=\"button2disabled\" DISABLED value=\"Set to Complete\" >\n";
		
				}
				else if ($e->transaction_status == 'failed')
				{	
					$this->data->modify_button .= "<input type=\"button\" class=\"button2disabled\" DISABLED value=\"Set to Failed\" >\n";
					$this->data->remove_button = "<input type=\"button\" class=\"button2".$complete_button. " value=\"Set to Complete\" onClick=\"ConfirmModify({$e->transaction_register_id}, 'transaction', {$e->application_id}, 'complete');\">\n";
				}
				else 
				{
					$this->data->modify_button .= "<input type=\"button\" class=\"button2".$fail_button." value=\"Set to Failed\" onClick=\"ConfirmModify(" . (empty($e->transaction_register_id) ? 'null' : $e->transaction_register_id) . ", 'transaction', {$e->application_id}, 'fail');\">\n";
					$this->data->remove_button = '<input type="button" class="button2'.$complete_button. " value=\"Set to Complete\" onClick=\"ConfirmModify(" . (empty($e->transaction_register_id) ? 'null' : $e->transaction_register_id) . ", 'transaction', {$e->application_id}, 'complete');\">\n";
				}
			}
		}
		$this->data->transactional_type = ucfirst($this->data->transactional_type);
	}

	protected function can_complete(stdClass $e)
	{
		if(($e->event_status == 'scheduled') &&
		($e->context != 'generated') && $e->date_event == date('m/d/Y') &&
		in_array($e->name_short, array('adjustment_internal', 'credit_card', 'moneygram', 'money_order', 'western_union'))) //Should be pulled from DB somehow to determine if event is ach or not [richardb]
	    {
	    	return true;
	    }
		return false;
	}
	protected function Get_Quick_Check_Row_Html($row, $row_number)
	{
		$class = $row_number % 2 == 0 ? 'align_left_alt' : 'align_left';

		$url = '/?mode=quick_checks&action=quick_check_download_subbatch&quick_checks_subbatch_id=' . $row->quick_checks_subbatch_id;
		$anchor = '<a href="' . $url . '">Download</a>';

		$result = "
		<tr>
			<td class='$class' style='text-align: center;'>$row->number_in_batch</td>
			<td class='$class'>$row->total</td>
			<td class='$class'>$row->status</td>
			<td class='$class'>" . $anchor . "</td>
		</tr>
		";

		return $result;
	}

	// Variable replacement callback function.  This got a little complicated, complain to Chris.  Fix later.
	protected function Replace($matches)
	{
		// Is it an edit layer?
		if( strpos($matches[0], "_edit%%%") )
		{
			$matches[1] = substr($matches[1], 0, -5);

			if( !empty($this->data->saved_error_data) && isset($this->data->saved_error_data->{$matches[1]}) )
			{
				$return_value = $this->data->saved_error_data->{$matches[1]};

			}
			elseif(isset($this->data->{$matches[1]}))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		else // Non edit replacement.
		{
			if(isset($this->data->{$matches[1]}))
			{
				$return_value = $this->data->{$matches[1]};
			}
			else
			{
				$return_value = $matches[0];
			}
		}
		return $return_value;
	}
	private function Build_DNL_Body($current_exists,$other_exists,$override_exists,$categories,$dnl_info)
	{
		if(!$current_exists)
				{
					
				
					$str1 = "<option value=\"";
					$str2 = "\">";
					$str3 = "</option>";
				
					$dnl_body .= "
				<table align=\"center\">
				<tr>
					<td><img src='/image/standard/i_do_not_loan.gif'></td>
					<td colspan=\"3\"><b>Do Not Loan</b></td>
				</tr>
				</table>
				<form method=\"post\" action=\"/\" class=\"no_padding\" name=\"do_not_loan\">
				<table align=\"center\">
				<tbody>
					<tr><td class=\"height\">&nbsp;</td></tr>
				     	<tr>
					<td class=\"align_left\">Category:</td>
					<td class=\"align_left\">
					<select name=\"dnl_category_drop_box\" id=\"dnl_category_drop_box\" onChange=\"javascript:Display_Other_Reason()\">
				";
					
					foreach($categories as $value)
					{
						$dnl_body .= ($str1 . $value->name . $str2 . ucwords(str_replace('_', ' ', $value->name)) . $str3);
					}
					
					$dnl_body .= "
					</select>
					</td>
					<td class=\"align_left\"><input type=\"text\" style=\"visibility: hidden;\" id=\"do_not_loan_other_specify\" name=\"do_not_loan_other_specify\" size=\"21\"></td>
					</tr>
					<tr><td class=\"height\">&nbsp;</td></tr>
				</tbody>
				</table>
				
				<table align=\"center\">
				<tbody>
				     	<tr>
					<td class=\"align_left\">Explanation: </td>
				     	<td class=\"align_left\"><input type=\"text\" id=\"do_not_loan_explanation\" name=\"do_not_loan_explanation\" size=\"40\"></td>    
					</tr>
					<tr><td class=\"height\">&nbsp;</td></tr>
				</tbody>
				</table>			
				 
				<table align=\"center\">
				<tbody>			
					<tr>
					<td><input type=\"button\" value=\"Cancel\" onClick=\"window.close();\"></td>&nbsp;&nbsp;
					<td><input type=\"button\" value=\"Submit\" class=\"button\" onClick=\"javascript:Set_DNL(document.getElementById('dnl_category_drop_box').value);\"></td>
					</tr>
				</tbody>
				</table>
				</form>
				";	
				}
				
				if($current_exists || $other_exists)
				{
				$html_before_title_alt = " 	<tr class=\"height\">
								<td class=\"align_left_alt_bold\" width=\"30%\">&nbsp;
							";
				$html_after_title_alt = " 					&nbsp;</td>
								<td class=\"align_left_alt\" width=\"5%\">&nbsp;</td>
								<td class=\"align_left_alt\" width=\"65%\">	
							";
				
				$html_before_title = 	" 	<tr class=\"height\">
								<td class=\"align_left_bold\" width=\"30%\">&nbsp;
							";
				
				$html_after_title = 	" 					&nbsp;</td>
								<td class=\"align_left\" width=\"5%\">&nbsp;</td>
								<td class=\"align_left\" width=\"65%\">	
							";
				$html_space_alt = "	<tr class=\"height\">
							<td class=\"align_left_alt_bold\" width=\"30%\">&nbsp;&nbsp;</td>
							<td class=\"align_left_alt\" width=\"5%\">&nbsp;</td>
							<td class=\"align_left_alt\" width=\"65%\"></td></tr>
						";
				
				$html_space = 	"	<tr class=\"height\">
							<td class=\"align_left_bold\" width=\"30%\">&nbsp;&nbsp;</td>
							<td class=\"align_left\" width=\"5%\">&nbsp;</td>
							<td class=\"align_left\" width=\"65%\"></td></tr>
						";
				
					$dnl_body .= "<table cellpadding=0 cellspacing=0 width=\"100%\">
						<tr>
						<td class=\"border\" align=\"left\" valign=\"top\">
						<table cellpadding=0 cellspacing=0 width=\"100%\">
				";
				
					if($current_exists)
					{
						$ind = 0;
						foreach($dnl_info as $key => $value)
						{
							if($dnl_info[$key]->company_id == $this->data->company_id)
							{
								$ind = $key;
								break;
							}
						}
				
						$comp_id = $dnl_info[$ind]->company_id;			
						$category = $dnl_info[$ind]->name;
						$explanation = $dnl_info[$ind]->explanation;
						$agent_name = ucwords($dnl_info[$ind]->name_last . ', ' . $dnl_info[$ind]->name_first);
						$date_created = $dnl_info[$ind]->date_created;
				
						unset($dnl_info[$ind]);
				
						$dnl_body .= "	<tr class=\"height\" bgcolor=\"#FFEFD5\">
							<td class=\"align_left_bold\" width=\"30%\">Current</td>
							<td width=\"5%\"><nobr>&nbsp;</nobr></td>
							<td class=\"align_right\" width=\"65%\"><input type=\"button\" value=\"Remove DNL\" class=\"button\" onClick=\"javascript:Remove_DNL();\"></td>
							</tr>
						";
						$dnl_body .= ($html_before_title_alt . "Name on Account:" . $html_after_title_alt . $this->data->name . "</td></tr>");
						$dnl_body .= ($html_before_title . "SSN:" . $html_after_title . $this->data->ssn . "</td></tr>");
						$dnl_body .= ($html_before_title_alt . "DNL Category:" . $html_after_title_alt . ucwords(str_replace('_', ' ', $category)) . "</td></tr>");
						$dnl_body .= ($html_before_title . "DNL Explanation:" . $html_after_title . $explanation . "</td></tr>");
						$dnl_body .= ($html_before_title_alt . "Agent ID:" . $html_after_title_alt . $agent_name . "</td></tr>");
						$dnl_body .= ($html_before_title . "DNL Set Date:" . $html_after_title . $date_created . "</td></tr>");
						$dnl_body .= $html_space_alt . $html_space;
					}
									
					if($other_exists)
					{ 
						$dnl_body .= "	<tr class=\"height\" bgcolor=\"#FFEFD5\">
							<td class=\"align_left_bold\" width=\"30%\">Other Companies</td>
							<td width=\"5%\"><nobr>&nbsp;</nobr></td>
							<td width=\"65%\"><nobr></nobr></td>
							</tr>
						";
						foreach($dnl_info as $key => $value)
						{
							$dnl_body .= ($html_before_title_alt . "Company:" . $html_after_title_alt . $dnl_info[$key]->company_name . "</td></tr>");
							$dnl_body .= ($html_before_title . "Name on Account:" . $html_after_title . $this->data->name . "</td></tr>");
							$dnl_body .= ($html_before_title_alt . "DNL Category:" . $html_after_title_alt . ucwords(str_replace('_', ' ', $dnl_info[$key]->name)) . "</td></tr>");
							$dnl_body .= ($html_before_title . "DNL Explanation:" . $html_after_title . $dnl_info[$key]->explanation . "</td></tr>");
							$dnl_body .= ($html_before_title_alt . "Agent ID:" . $html_after_title_alt . ucwords($dnl_info[$key]->name_last . ', ' . $dnl_info[$key]->name_first) . "</td></tr>");
							$dnl_body .= ($html_before_title . "DNL Set Date:" . $html_after_title . $dnl_info[$key]->date_created . "</td></tr>");
							$dnl_body .= $html_space_alt . $html_space;
						}
										
						if($override_exists)
							$dnl_body .= "	<tr class=\"height\">
								<td class=\"align_left\"><input type=\"button\" value=\"Remove Override DNL\" class=\"button\" onClick=\"javascript:Remove_Override_DNL();\"></td>
								</tr>
							";
						else
							$dnl_body .= "	<tr class=\"height\">
								<td class=\"align_left\"><input type=\"button\" value=\"Override DNL\" class=\"button\" onClick=\"javascript:Override_DNL();\"></td>
								</tr>
							";
					}
					$dnl_body .= "</table>
						</td>
						</tr>
						</table>
					";
				}
				return $dnl_body;
	}
	private function Build_Application_History()
	{
		$html = '<p>Application History:<br />';

		if( isset($this->data->application_history) && is_object($this->data->application_history) )
		{
			$html .= "<table width=\"500\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							<td>Status</td>
							<td>Agent</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data->application_history as $history_obj)
			{
				try
				{
					if($history_obj->agent_id == 0)
					{
						$agent_name = "N/A";
					}
					else
					{
						$agent = ECash::getAgentById($history_obj->agent_id);
						$agent_name = ucwords(strtolower("{$agent->model->name_last}, {$agent->model->name_first}"));
					}
				}
				catch (Exception $e)
				{
					// Agent was invalid, let's handle this gracefully
					$agent_name = "System, E";
				}

				$html .= "\n<tr style=\"background: #FFF3EB;\">";
				
				// [#17857]
				$modified_date = date('m/d/Y h:i:s A', strtotime($history_obj->date_created));
				// Might as well make them all uniform [benb]
				$html .= "<td title='{$modified_date} [{$timezone}]'>{$modified_date}</td>";

				try
				{
					$html .= "<td>{$history_obj->status->level0_name}</td>";
				}
				catch (Exception $e)
				{
					$html .= "<td>Error retrieving status</td>";
				}

				$html .= "<td>{$agent_name}</td>";

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html .= "Could not find any application history for that application.";
		}

		$html .= '</p>';

		return $html;
	}
		
	private function Build_Queue_History()
	{
		$html = '<p>Queue History:<br />';

		if( isset($this->data->queue_history) && is_array($this->data->queue_history) )
		{
			$html .= "<table width=\"500\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							<td>I/O</td>
							<td>Queue</td>
							<td>Agent</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data->queue_history as $history_obj)
			{
				if($history_obj->agent_id == 0)
				{
					$agent_name = "N/A";
				}
				else
				{
					$agent_name = ucwords(strtolower("{$history_obj->name_last}, {$history_obj->name_first}"));
				}

				$html .= "\n<tr style=\"background: #FFF3EB;\">";

				$html .= "<td title='{$history_obj->date_created} [{$timezone}]'>{$history_obj->date_created}</td>";
				$html .= "<td>{$history_obj->action}</td>";
				$html .= "<td>{$history_obj->queue}</td>";
				$html .= "<td>{$agent_name}</td>";

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html .= "Could not find any queue history for that application.";
		}

		$html .= '</p>';

		return $html;
	}

	private function Build_Outgoing_Dispositions_List($data)
	{
		// This function takes the information populated by models on the server side
		// and makes select lists out of it

		// Work
		$list = "";
		foreach($data->work_dispositions as $loan_action_id => $call_disposition)
		{
			$list .= "<option value=\"{$loan_action_id}\">" . htmlentities($call_disposition) . "</option>\n";
		}
		$data->work_dispositions_select_list = $list;

		// Cell
		$list = "";
		foreach($data->cell_dispositions as $loan_action_id => $call_disposition)
		{
			$list .= "<option value=\"{$loan_action_id}\">" . htmlentities($call_disposition) . "</option>\n";
		}
		$data->cell_dispositions_select_list = $list;

		// Home
		$list = "";
		foreach($data->home_dispositions as $loan_action_id => $call_disposition)
		{
			$list .= "<option value=\"{$loan_action_id}\">" . htmlentities($call_disposition) . "</option>\n";
		}
		$data->home_dispositions_select_list = $list;

		// Ref 1
		$list = "";
		foreach($data->ref_1_dispositions as $loan_action_id => $call_disposition)
		{
			$list .= "<option value=\"{$loan_action_id}\">" . htmlentities($call_disposition) . "</option>\n";
		}
		$data->ref_1_dispositions_select_list = $list;

		// Ref 2
		$list = "";
		foreach($data->ref_2_dispositions as $loan_action_id => $call_disposition)
		{
			$list .= "<option value=\"{$loan_action_id}\">" . htmlentities($call_disposition) . "</option>\n";
		}
		$data->ref_2_dispositions_select_list = $list;


	}

	
	private function Build_Application_Audit_Log()
	{
		
		if( isset($this->data->application_audit_log) && is_array($this->data->application_audit_log) )
		{
			//[#46878] show fund requested & fund qualified in audit log to compare to fund actual
			$html = '';
			if(property_exists($this->data, 'fund_requested') && property_exists($this->data, 'fund_qualified'))
			{
				$html .= "<table border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr>
							<td style=\"font-weight: bold; background: #F6C8A9;\">Fund Requested</td>
							<td style=\"background: #FFF3EB;\">{$this->data->fund_requested}</td>
						</tr>
						<tr>
							<td style=\"font-weight: bold; background: #F6C8A9;\">Fund Qualified</td>
							<td style=\"background: #FFF3EB;\">{$this->data->fund_qualified}</td>
						</tr>
						</table>
						<br/>
						";
			}
			
			$html .= "<table width=\"600\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							<td>Table</td>
							<td>Column</td>
							<td>Before</td>
							<td>After</td>
							<td>Agent</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data->application_audit_log as $audit_obj)
			{
				if(in_array($audit_obj->column_name, array('paydate_model', 'application_status_id', 'date_application_status_set'))) continue;
			
				if(empty($audit_obj->value_before) && empty($audit_obj->value_after)) continue;	

				//make the dates standard
				if(preg_match("/^[0-9]{4}-[0,1][0-9]-[0-9]{2}/", $audit_obj->value_before)) $audit_obj->value_before = date('m/d/Y', strtotime($audit_obj->value_before));
				if(preg_match("/^[0-9]{4}-[0,1][0-9]-[0-9]{2}/", $audit_obj->value_after)) $audit_obj->value_after = date('m/d/Y', strtotime($audit_obj->value_after));

				if($audit_obj->agent_id == 0)
				{
					$agent_name = "N/A";
				}
				else
				{
					$agent_name = ucwords(strtolower("{$audit_obj->name_last}, {$audit_obj->name_first}"));
				}

				$html .= "\n<tr style=\"background: #FFF3EB;\">";

				$html .= "<td title='" . $audit_obj->date_created . " [{$timezone}]'>" . date('m/d/Y h:i:s a', strtotime($audit_obj->date_created)) . "</td>";
				$html .= "<td> " . ucwords(str_replace("_"," ",$audit_obj->table_name)) . "</td>";
				$html .= "<td> " . ucwords(str_replace("_"," ",$audit_obj->column_name)) . "</td>";
				$html .= "<td>{$audit_obj->value_before}</td>";
				$html .= "<td>{$audit_obj->value_after}</td>";
				$html .= "<td>{$agent_name}</td>";

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html = "Could not find any application audit log for that application.";
		}
		return $html;
	}

	private function Build_Application_Flag_History()
	{
		
		if( isset($this->data->application_flag_history) )
		{
			$html = "<table width=\"600\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							<td>Application Flag</td>
							<td>Agent</td>
							<td>Action</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data->application_flag_history as $history)
			{
	
					$agent_name = ucwords(strtolower("{$history['name_last']}, {$history['name_first']}"));
	

				$html .= "\n<tr style=\"background: #FFF3EB;\">";

				$html .= "<td title='{$history['date_created']}'>{$history['date_created']}</td>";
				$html .= "<td>{$history['name']}</td>";
				$html .= "<td>{$agent_name}</td>";
				$html .= "<td>{$history['action']}</td>";
				

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html = "OH NOES!";
		}
		return $html;
	}
	
	
	private function Build_DNL_Audit_Log()
	{
		if( isset($this->data->dnl_audit_log) && is_array($this->data->dnl_audit_log) )
		{
			$html = "<table width=\"600\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							
							<td>Before</td>
							<td>After</td>
							<td>Agent</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data->dnl_audit_log as $audit_obj)
			{
				if($audit_obj->agent_id == 0)
				{
					$agent_name = "N/A";
				}
				else
				{
					$agent_name = ucwords(strtolower("{$audit_obj->name_last}, {$audit_obj->name_first}"));
				}

				$html .= "\n<tr style=\"background: #FFF3EB;\">";

				$html .= "<td title='{$audit_obj->date_created} [{$timezone}]'>{$audit_obj->date_created}</td>";
			//	$html .= "<td>{$audit_obj->table_name}</td>";
				$html .= "<td>{$audit_obj->value_before}</td>";
				$html .= "<td>{$audit_obj->value_after}</td>";
				$html .= "<td>{$agent_name}</td>";

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html = "Could not find any application audit log for that application.";
		}
		return $html;
	}
	
	private function Build_Card_Audit_Log()
	{
		$html = "<table width=\"600\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
				<tr style=\"font-weight: bold; background: #F6C8A9;\">
					<td>Date Changed</td>
					<td>Column</td>
					<td>Before</td>
					<td>After</td>
					<td>Agent</td>
				</tr>";

		$timezone = date('T');

		foreach($this->data->card_audit_log as $audit_obj)
		{
			$agent = ECash::getFactory()->getModel('Agent');
			$loaded = $agent->loadBy(array('agent_id' => $audit_obj->agent_id));
			if ($loaded)
			{
				$agent_name = $agent->name_first . ' ' . $agent->name_last;
			}
			else
			{
				$agent_name = "N/A";
			}

			$html .= "\n<tr style=\"background: #FFF3EB;\">";

			$html .= "<td title='{$audit_obj->date_created} [{$timezone}]'>{$audit_obj->date_created}</td>";
			$html .= "<td>{$audit_obj->column_name}</td>";

			if ($audit_obj->column_name == "cardholder_name" || $audit_obj->column_name == "card_number")
			{
				if (empty($audit_obj->value_before))
				{
					$before = $audit_obj->value_before;
				}
				else
				{
					$before = Payment_Card::decrypt($audit_obj->value_before);
				}
				$after = Payment_Card::decrypt($audit_obj->value_after);

				if ($audit_obj->column_name == "card_number")
				{
					if (empty($audit_obj->value_before))
					{
						$before = $audit_obj->value_before;
					}
					else
					{
						$before = substr($before, 0, 4) . "-****-****-" . substr($before, 12, 4);
					}
					$after = substr($after, 0, 4) . "-****-****-" . substr($after, 12, 4);
				}

				$html .= "<td>{$before}</td>";
				$html .= "<td>{$after}</td>";
			}
			else if ($audit_obj->column_name == "card_type_id")
			{
				$card_type = ECash::getFactory()->getModel('CardType');
				
				$loaded_before = $card_type->loadBy(array('card_type_id' => $audit_obj->value_before));
				if ($loaded_before)
				{
					$before = $card_type->name;
				}
				else
				{
					$before = NULL;
				}

				$loaded_after = $card_type->loadBy(array('card_type_id' => $audit_obj->value_after));
				if ($loaded_after)
				{
					$after = $card_type->name;
				}
				else
				{
					$before = "N/A";
				}

				$html .= "<td>{$before}</td>";
				$html .= "<td>{$after}</td>";
			}
			else
			{
				$html .= "<td>{$audit_obj->value_before}</td>";
				$html .= "<td>{$audit_obj->value_after}</td>";
			}

			$html .= "<td>{$agent_name}</td>";

			$html .= "\n</tr>";
		}

		$html .= "\n</table>";

		return $html;
	}

	private function Build_Payment_Arrangement_History()
	{
		
		if( isset($this->data->payment_arrangement_history) && is_array($this->data->payment_arrangement_history) )
		{
			$html = "<table width=\"800\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Created</td>
							<td>Payment Date</td>
							<td>Transaction Type</td>
							<td>Principal Amount</td>
							<td>Non Principal Amount</td>
							<td>Agent</td>
							<td>Status</td>
						</tr>";

			$timezone = date('T');
			foreach($this->data->payment_arrangement_history as $hist_obj)
			{

				$html .= "\n<tr style=\"background: #FFF3EB;\">";
				$html .= "<td title='{$hist_obj->Date_Created} [{$timezone}]'>{$hist_obj->Date_Created}</td>";
				$html .= "<td>{$hist_obj->Payment_Date}</td>";
				$html .= "<td>{$hist_obj->Transaction_Type}</td>";
				$html .= "<td>{$hist_obj->Principal_Amount}</td>";
				$html .= "<td>{$hist_obj->Non_Principal_Amount}</td>";
				$html .= "<td>{$hist_obj->Agent}</td>";
				$html .= "<td>{$hist_obj->Status}</td>";
				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html = "Could not find any payment arrangement history for that application.";
		}
		return $html;
	}
		
	
	private function Populate_Tokens($page, $tokens)
	{
		foreach($tokens as $token => $val)
		{
			$page = str_replace("%%%{$token}%%%", $val, $page);
		}
		return $page;
	}	

	private function Get_Manual_Payment_Data()
	{
		$data = ECash::getTransport()->Get_Data();
		$this->data = $data->schedule_status;
		$this->data->mode_class = ECash::getTransport()->Get_Next_Level();

	}

	private function Render_Pending_Items()
	{
		$str = "<table>";
		$pi = $this->data->pending_items;
		if (count($pi) == 0)
		{
			$str = "<tr><td><b>There are no pending items to resolve.</b></td></tr>";
			$this->data->save_disable = "disable";
		}
		else
		{
			$str .= "<tr>\n";
			$str .= " <th>Transaction</th>\n";
			$str .= " <th>Amount</th>\n";
			$str .= " <th>Date Due</th>\n";
			$str .= "</tr>\n";
			foreach ($this->data->pending_items as $idx => $item)
			{
				$str .= "<tr>";
				$str .= " <td>{$item->name}\n";
				$str .= " <input type=\"hidden\" id=\"item_{$idx}\" name=\"item_{$idx}\" value=\"tr{$item->transaction_register_id}\"></td>\n";
				$str .= " <td>{$item->due_date}</td>\n";
				$str .= " <td>{$item->amount}</td>\n";
				$str .= " <td><select id=\"tr{$item->transaction_register_id}\" name=\"tr{$item->transaction_register_id}\">\n";
				$str .= "    <option value=\"pending\">PENDING</option>\n";
				$str .= "    <option value=\"complete\">COMPLETE</option>\n";
				$str .= "    <option value=\"failed\">FAILED</option>\n";
				$str .= " </select></td>";
				$str .= "</tr>";
			}
			$this->data->save_disable = "";
		}
		$str .= "</table>\n";
		$this->data->pending_table = $str;
	}

	private function Build_ACH_Return_Code_List()
	{
		if(count($this->data->ach_return_codes > 0))
		{
			$html  = "<select id=\"ach_return_code_id\" name=\"ach_return_code_id\">\n";
			foreach($this->data->ach_return_codes as $arc => $name)
			{
				//$html .= "<option value=\"{$arc}\">{$arc}  &nbsp;-&nbsp; {$name}</option>\n";
				$html .= "<option value=\"{$arc}\">{$name}</option>\n"; //assembla 25
			}
			$html .= "</select>\n";
		}
		return($html);
	}

	private function Build_Card_Return_Code_List()
	{
		if(count($this->data->card_return_codes > 0))
		{
			$html  = "<select id=\"card_return_code_id\" name=\"card_return_code_id\">\n";
			foreach($this->data->card_return_codes as $arc => $name)
			{
				//$html .= "<option value=\"{$arc}\">{$arc}  &nbsp;-&nbsp; {$name}</option>\n";
				$html .= "<option value=\"{$arc}\">{$name}</option>\n"; //assembla 25
			}
			$html .= "</select>\n";
		}
		return($html);
	}
	
	/**
	 * Builds a table with decisioning reasons for CFC
	 *
	 * @param array $loan_action_types = an array of loan_actions
	 * @return String A table with all of the loan actions selectable through radio buttons
	 */
	private function Build_Reasoning_Options($loan_action_types)
	{
		$opts = "<table cellpadding=0 cellspacing=0 border=0 width=100%>\n";
		foreach ($loan_action_types as $item) 
		{
			$bgcolor = is_null($bgcolor) ? "silver" : "white";
			$radio = " <input type=radio name=loan_actions id='{$item->name_short}' value='{$item->loan_action_id}'>";
			$opts .= "<tr bgcolor='$bgcolor'><td>$radio</td>";
			$opts .= " <td style='text-align: left'> <label for='{$item->name_short}' title='{$item->description}'> <font size='-1'>{$item->description}</font></label></td></tr>\n";
			$bgcolor = ($bgcolor == "white") ? null : $bgcolor;
		}

		$opts .= "</table>";	
		
		return $opts;
	}
	
	
	/**
	 * Builds a table with a list of applications passed to the functions
	 *
	 * @param array $application_list - array of objects
	 * @return string HTML list of Applications
	 */
	private function Build_Application_List($application_list, $use_checkbox = false, $selected_application_id = null)
	{
		$list_html = '';

		$i = 0;

		foreach ($application_list as $a) 
		{
			$selected = ($a->application_id == $selected_application_id) ? 'CHECKED' : '';
			$customer_name = ucwords($a->name_last) . ', ' . ucwords($a->name_first);

			$checkbox = '';
			if($use_checkbox) $checkbox = "<input type=\"checkbox\" id=\"app[{$i}]\" name=\"app[{$i}]\" value=\"{$a->application_id}\" $selected >";

			$list_html .= "
		<tr>
			<td style=\"width: 30px; max-width: 30px; text-align: center; padding: 0px 5px;\">$checkbox</td>
			<td style=\"width: 90px; text-align: center; padding: 0px 5px;\">{$a->application_id}</td>
			<td style=\"width: 86px; text-align: center; padding: 0px 5px;\">{$a->ssn}</td>
			<td style=\"width: 145px; text-align: left; padding: 0px 8px;\">{$customer_name}</td>
			<td style=\"width: 145px; text-align: left; padding: 0px 8px;\"><div style=\"overflow: hidden;\"><a style=\"color: black; text-decoration: none;\" href=\"#\" title=\"{$a->email}\">{$a->email}</div></td>
			<td style=\"width: 145px; text-align: left; padding: 0px 8px;\"><div style=\"overflow: hidden;\"><a style=\"color: black; text-decoration: none;\" href=\"#\" title=\"{$a->employer_name}\">{$a->employer_name}</a></div></td>
			<td style=\"width: 155px; text-align: left; padding: 0px 5px;\"><div style=\"overflow: hidden;\">{$a->getStatus()->getApplicationStatus()}</div></td>
		</tr>";
			$i++;
		}

		return $list_html;
	}

}
?>
