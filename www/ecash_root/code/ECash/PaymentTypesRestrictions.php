<?php

/* 
 * This will serve as a single abstraction layer for deciding whether
 * or not payment types are displayed for applications in various states
 *
 * This is kind of like the CFE engine, except it doesn't suck, and the
 * database isn't 100% normalized, it's designed for ease of reading/modifying
 * without the need to track 40 join tables.
 *
 * Here's the cheese. A payment type has to have 1 or more qualifying factors
 * and no disqualifying factors. For instance:
 * 
 * TODO: Give a for instance, or delete any references to this "for instance" case
 */

class ECash_PaymentTypesRestrictions
{
	protected $db;
	protected $module;
	protected $mode;
	protected $loan_type_model;
	protected $loan_type_id;
	protected $show_transactions;

	protected $payment_type_map;
	
	protected $app;
	protected $application_status;
	protected $schedule_status;
	

	function __construct($db, $application_id = NULL, $module = NULL, $mode = NULL, $loan_type_model = NULL, $loan_type_id = NULL, $show_transactions = NULL)
	{
		$this->db = $db;

		$this->application_id    = $application_id;
		$this->module            = $module;
		$this->mode              = $mode;
		$this->loan_type_model   = $loan_type_model;
		$this->loan_type_id      = $loan_type_id;
		$this->show_transactions = $show_transactions;
		
		$this->app = ECash::getApplicationById($this->application_id);
		$this->application_status = $this->app->getStatus();

		$current_schedule = Fetch_Schedule($this->application_id);
		$this->schedule_status 	= Analyze_Schedule($current_schedule, TRUE);

		$model = ECash::getFactory()->getModel('PaymentTypeList');

		$model->loadBy(array());

		$this->payment_type_map = array();

		foreach($model as $payment_type)
		{
			$this->payment_type_map[$payment_type->name_short] = $payment_type->payment_type_id;
		}
	}

	function evaluateCondition($lvalue, $operator, $rvalue)
	{
		// IF not cached
		if (!isset($this->cached[strtolower($lvalue)]))
		{
			switch (strtolower($lvalue))
			{
				case 'module':
					$q_lvalue = strtolower($this->module);
					break;

				case 'mode':
					$q_lvalue = strtolower($this->mode);
					break;

				case 'level0_name':
					$q_lvalue = strtolower($this->application_status->level0_name);
					break;
					
				case 'level0':
					$q_lvalue = strtolower($this->application_status->level0);
					break;

				case 'level1':
					$q_lvalue = strtolower($this->application_status->level1);
					break;

				case 'level2':
					$q_lvalue = strtolower($this->application_status->level2);
					break;

				case 'level3':
					$q_lvalue = strtolower($this->application_status->level3);
					break;

				case 'loan_type_model':
					$q_lvalue = strtolower($this->loan_type_model);
					break;

				case 'loan_type':
					$lt  = ECash::getFactory()->getModel('LoanType');
					$lt->loadBy(array('loan_type_id' => $app->loan_type_id)); 
		
					$q_lvalue = strtolower($lt->name_short);
					break;
				case 'has_lien_fee':
					$q_lvalue = ($this->schedule_status->has_lien_fee) ? "true" : "false";
					break;

				case 'has_transfer_fee':
					$q_lvalue = ($this->schedule_status->has_transfer_fee) ? "true" : "false";
					break;

				case 'posted_and_pending_principal':
					$q_lvalue = $this->schedule_status->posted_and_pending_principal;
					break;

				case 'posted_and_pending_total':
					$q_lvalue = $this->schedule_status->posted_and_pending_total;
					break;
		
				case 'balance':
					$balance = Fetch_Balance_Information($this->application_id);
					$q_lvalue = $balance->total_pending;

					break;
				case 'status':
					break;

				case 'show_transactions':
					$q_lvalue = ($this->show_transactions) ? "true" : "false";
					break;

				case 'num_scheduled_events':
					$q_lvalue = $this->schedule_status->num_scheduled_events;
					break;

				case 'ach_allowed':
					// Get flags
					$flags = new Application_Flags(null, $this->application_id);
					$my_flags = $flags->Get_Active_Flag_Array();

					$ach_allowed = TRUE;

					if (in_array('has_fatal_ach_failure', array_keys($my_flags)))
					{
						$ach_allowed = FALSE;
					}

					$q_lvalue = ($ach_allowed) ? "true" : "false";

					break;

				case 'card_allowed':
					// Get flags
					$flags = new Application_Flags(null, $this->application_id);
					$my_flags = $flags->Get_Active_Flag_Array();

					$card_allowed = TRUE;

					if (in_array('has_fatal_card_failure', array_keys($my_flags)))
					{
						$card_allowed = FALSE;
					}

					$q_lvalue = ($card_allowed) ? "true" : "false";

					break;
				
				// Dumb as hell
				case 'can_chargeback':
					$q_lvalue = ($this->schedule_status->can_chargeback) ? "true" : "false";
					break;

				case 'can_chargeback_reversal':
					$q_lvalue = ($this->schedule_status->can_reverse_chargeback) ? "true" : "false";
					break;

				case 'has_arrangements':
					break;
				case 'has_schedule':
					break;
				case 'has_disbursement':
					break;
				default:
					throw new Exception("Invalid lvalue type for ECash_PaymentTypes->evaluateCondition (lvalue = {$lvalue})");
			}

			// Premature optimization rocks!!!1
			$this->cached[strtolower($lvalue)] = $q_lvalue;
		}
		else
		{
			// use teh cached copyz
			$q_lvalue = $this->cached[strtolower($lvalue)];
		}

		$q_rvalue = strtolower($rvalue);

		switch ($operator)
		{
			case '==':
				if ($q_lvalue == $q_rvalue)
					return TRUE;
				break;
			case '!=':
				if ($q_lvalue != $q_rvalue)
					return TRUE;
				break;
			case '>':
				if ($q_lvalue > $q_rvalue)
					return TRUE;
				break;
			case '<':
				if ($q_lvalue < $q_rvalue)
					return TRUE;
				break;
			case '>=':
				if ($q_lvalue >= $q_rvalue)
					return TRUE;
				break;
			case '<=':
				if ($q_lvalue <= $q_rvalue)
					return TRUE;
				break;
		}

		return FALSE;
	}

	function checkIfAllowed($payment_type)
	{
		if (!in_array($payment_type, array_keys($this->payment_type_map)))
			return FALSE;

		// Load up conditions for this payment type
		$conditions = ECash::getFactory()->getModel('PaymentTypeConditionList');
	
		$payment_type_id = $this->payment_type_map[$payment_type];
		$conditions->loadBy(array('payment_type_id' => $payment_type_id, 'loan_type_id' => $this->loan_type_id));

		$qualified    = FALSE;
		$disqualified = FALSE;


		// Walk through the conditions, evaluate them against application data
		// if they fail conditions, return FALSE, and don't display the payment option
		foreach ($conditions as $condition)
		{
			// Don't let inactive conditions affect anything;
			if ($condition->is_active == 0)
				continue;

			if ($condition->payment_type_condition_type == 'qualifying')
				if ($this->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue))
					$qualified = TRUE;

			if ($condition->payment_type_condition_type == 'disqualifying')
				if ($this->evaluateCondition($condition->lvalue, $condition->operator, $condition->rvalue))
					$disqualified = TRUE;
		}	

		if ($disqualified == TRUE)
			return FALSE;
		
		if ($qualified == TRUE)
			return TRUE;

		
		return FALSE;
	}
}


?>
