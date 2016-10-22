<?php


require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SERVER_CODE_DIR . 'edit.class.php');
require_once(LIB_DIR . 'common_functions.php');
require_once(SQL_LIB_DIR . "app_mod_checks.func.php");

class API_Schedule implements Module_Interface
{
	private $permissions;

	public function __construct(Server $server, $request, $module_name)
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
		$this->permissions = array(
			array('loan_servicing', 'customer_service',  'transactions'),
			array('loan_servicing', 'account_mgmt', 'transactions'),
			array('collections', 'internal', 'transactions'),
			array('fraud', 'watch', 'transactions'),
		);
	}

	public function get_permissions()
	{
		return $this->permissions;
	}

	public function Main()
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'schedule':
				return $this->get_scheduled($input->application_id);

			case 'arrangements':
				return $this->get_arrangements($input->application_id);

			case 'preview':
				$arrangement_events = $this->get_events_for_payment($input->payments[0],$input->application_id,'arrange_next');
				$schedule = Complete_Schedule($input->application_id, FALSE, $arrangement_events,TRUE);
				return $this->render_schedule($input->application_id, $schedule);

			case 'save_adjustment':
				$arrangement_events = $this->get_events_for_payment($input->next_payment_adjustment->rows[0],$input->application_id,'arrange_next');
				$new_schedule = Complete_Schedule($input->application_id, TRUE, $arrangement_events,TRUE);
				Set_Transaction_Lock_Info($input->application_id, $new_schedule);
				break;

			default:
				throw new Exception("Unknown action {$input->action}");

		}
	}

	private function get_arrangements($application_id)
	{
		$result = array();
		foreach ($this->get_scheduled($application_id) as $e)
		{
			if ($e->context === 'arrangement' || $e->context === 'partial')
			{
				$result[]=$e;
			}
		}
		return $result;
	}

	private function get_scheduled($application_id)
	{
		$schedule = Fetch_Schedule($application_id);
		$result = array();
		foreach ($schedule as $e)
		{
			if (($e->status === 'scheduled' || $e->status === 'registered') && strtotime($e->date_event) >= strtotime(Date('Y/m/d',time())))
			{
				$obj = new StdClass;
				$obj->type = $e->type;
				$obj->event_date = date('m/d/Y',strtotime($e->date_event));
				$obj->date = date('m/d/Y',strtotime($e->date_effective));
				$obj->amount = $e->principal + $e->service_charge + $e->fee;
				$obj->status = $e->status;
				$obj->context = $e->context;
				$result[]=$obj;
			}
		}
		return $result;
	}

	private function get_events_for_payment($payment, $application_id, $context = 'manual')
	{
		// paydate calculator is handy
	    $pd_calc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		// Mangle payment date?
		$payment->date = preg_replace('^(\d{1,2})/(\d{1,2})/(\d{4})^', '${3}-${1}-${2}', $payment->date);
		$remaining = -$payment->amount;
		$balance_info = Fetch_Balance_Information($application_id);

		$new_events = array();
		$amounts = array();
		
		if(in_array($payment->type, array('moneygram', 'money_order', 'credit_card', 'western_union', 'card_payment_arranged')))
		{
			$action_date = $payment->date;
			$due_date    = $payment->date;
		}
		else 
		{
			$action_date = $pd_calc->Get_Last_Business_Day($payment->date);
			$due_date    = $payment->date;
		}
		
		switch($payment->type)
		{
			case 'payment_arranged':
				$payment_type = 'payment_arranged';
				break;
			case 'card_payment_arranged':
			   	$payment_type = 'card_payment_arranged';
				break;
			case 'moneygram':
				$payment_type = 'moneygram';
				break;
			case 'money_order':
				$payment_type = 'money_order';
				break;
			case 'credit_card':
				$payment_type = 'credit_card';
				break;
			case 'western_union':
				$payment_type = 'western_union';
				break;
		}
		
		/**
		 * Fees
		 */
		if ($balance_info->fee_pending > 0)
		{
			//this event is getting deleted and shouldn't be
			$amount_for_this = max($remaining, -$balance_info->fee_pending);

			//[#34839] / [#27879] re-add fee payments here b/c
			//complete schedule now skips them for arranged payments
			if($context == 'arrange_next') 
			{
				if (($amount_for_this + $balance_info->service_charge_pending) > 0) 
				{
					$amounts[] = Event_Amount::MakeEventAmount('fee', $amount_for_this);
					$remaining -= $amount_for_this;
				}
			}
		}

		/**
		 * Service Charge / Interest
		 */
		if ($payment->interest + $balance_info->service_charge_pending > 0)
		{
			/* GF #21380
			 * This method was grabbing the current company rule set for
			 * whichever loan-type it was being passed. I changed it to
			 * grab the rule set specific to this app. This way when the company
			 * changes their global rules, it's not affecting this application.
			 */
			$application = ECash::getApplicationById($application_id);
			$rules = $application->getBusinessRules();
			$rate_calc = $application->getRateCalculator();

			if($rules['service_charge']['svc_charge_type'] === 'Daily')
			{
				/**
				 * Add the service charge payment amount
				 */
				$temp_schedule =  Fetch_Schedule($application_id);				
				//$amount = Interest_Calculator::scheduleCalculateInterest($rules, $temp_schedule, $due_date);
				$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($temp_schedule, FALSE, $rules);
				$amount = $rate_calc->calculateCharge($paid_to['principal'], $paid_to['date'], $due_date);
				if (($amount + $balance_info->service_charge_pending) > 0) 
				{
					$amount_for_this = max($remaining, -($amount + $balance_info->service_charge_pending));
					$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount_for_this);
					$remaining -= $amount_for_this;
				}
			}
			else 
			{
				$amount = ($rate_calc->getPercent() * $balance_info->principal_balance);

				if (($balance_info->service_charge_pending) > 0) 
				{
					$amount_for_this = max($remaining, -($balance_info->service_charge_pending));
					$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount_for_this);
					$remaining -= $amount_for_this;
				}
			}

			/**
			 * Principal
			 */
			//Create event even with zero amount to trigger interest being added
			$amounts[] = Event_Amount::MakeEventAmount('principal', $remaining);

			$agent = ECash::getAgent();
			$comment = "[Arrange Next Payment created by " . $agent->getFirstLastName() . "]";

			if (isset($payment->desc))
			{
				$comment .= " " . $payment->desc;
			}
			
			$new_events[] = Schedule_Event::MakeEvent(
				$action_date,
				$due_date,
				$amounts,
				$payment_type,
				$comment,
				'scheduled',
				$context,
				null,
				null,
				true);

			return $new_events;
		}
	}
	
	private function render_schedule ($application_id, $schedule)
	{
		// Fill in some missing data first
		foreach ($schedule as $row)
		{
			Schedule_Event::Set_Amounts_From_Event_Amount_Array($row, $row->amounts);
			$row->event_name = Get_Event_Type_Name($row->type, $this->server->company_id);
		}
		// We'll need the balance info too.
		$balance_info = Fetch_Balance_Information($application_id);
		// Use the appropriate object to get the html
		require_once(CLIENT_CODE_DIR . 'render_transactions_table.class.php');
		$trans_render = new Render_Transactions_Table();
		$formated_schedule = $trans_render->format_schedule(true,$schedule,null,null,$balance_info->total_pending);
		return $trans_render->Build_Schedule($formated_schedule);
	}
}
?>
