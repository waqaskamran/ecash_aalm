<?php
/**
 * @package scheduling
 */
require_once(SERVER_CODE_DIR.'event_amount.class.php');

/**
 * A class to manage functions that allocate amounts for transactions based on 
 * various conditions.
 *
 */
class AmountAllocationCalculator
{
	
	/**
	 * Return event amounts for types and amounts specified in the array.
	 *
	 * @param array $amounts
	 * @return array
	 */
	static public function generateGivenAmounts(Array $amounts)
	{
		return self::generateAmounts($amounts);
	}

	static public function removeCreditAmounts(Array $amounts, Array $adjustment_order = array('principal', 'service_charge', 'fee'))
	{
		$new_amounts = array();
		$amount_to_adjust = 0;
		foreach ($amounts as $event_amount_type => $amount)
		{
			if ($amount > 0)
			{
				$new_amounts[$event_amount_type] = 0;
				$amount_to_adjust += $amount;
			}
			else 
			{
				$new_amounts[$event_amount_type] = $amount;
			}
		}
		
		if ($amount_to_adjust > 0)
		{
			foreach ($adjustment_order as $event_amount_type)
			{
				if (!empty($new_amounts[$event_amount_type]) && $new_amounts[$event_amount_type] < 0 && $amount_to_adjust > 0)
				{
					$adjustment = min(-$new_amounts[$event_amount_type], $amount_to_adjust);
					$new_amounts[$event_amount_type] += $adjustment;
					$amount_to_adjust -= $adjustment;
				}
			}
		}
		
		return $new_amounts;
	}

	/**
	 * Returns event amounts for types and amounts specified in the array.
	 *
	 * @param array $amounts
	 * @return array
	 */
	static private function generateAmounts(Array $amounts)
	{
		$eventAmounts = array();
		foreach ($amounts as $eventAmountType => $amount)
		{
			if ($amount != 0)
			{
				$eventAmounts[] = Event_Amount::MakeEventAmount($eventAmountType, $amount);
			}
		}
		
		return $eventAmounts;
	}
	
	/**
	 * Return amounts for a debit based on the provided balances for the 
	 * account and the amount of the payment.
	 *
	 * @param Array $balance
	 * @param float $amount
	 * @return array
	 */
	static public function generateAmountsFromBalance($amount, &$balance)
	{
		$amountCounter = abs($amount);
		
		$fee = ($balance['fee'] < 0 ? 0 : min($amountCounter, $balance['fee']) );
		$amountCounter -= $fee;
		
		$serviceCharge = ($balance['service_charge'] < 0 ? 0 : min($amountCounter, $balance['service_charge']) );
		$amountCounter -= $serviceCharge;	
			
		$principal = $amountCounter;
		
		$balance['principal'] -= $principal;
		$balance['service_charge'] -= $serviceCharge;
		$balance['fee'] -= $fee;
		$balance['irrecoverable'] -= $irrecoverable;
		
		return self::generateAmounts(array(
			'principal' => -$principal,
			'service_charge' => -$serviceCharge,
			'fee' => -$fee,
		));
	}

	/**
	 * Returns reversal transaction event amounts of the specified transaction 
	 * for the specified amount.
	 *
	 * @param object $e
	 * @param float $amount
	 * @return array
	 */
	static public function generateAmountsForAReversal($amount, &$transactionBalance, $type = 'fee', $ischargebackreversal =false)
	{
		$amountToReverse = array_sum($transactionBalance);
		/*
		if (abs($amountToReverse) > abs($amountToReverse + $amount))
		{
			throw new Exception("Attempted to reverse a transaction [{$totalToReverse}] by an amount [{$totalAmount}] that would take the transactions away from 0.");
		}
		*/
		
		$amountCounter = abs($amount);
		
//		$principal = min($amountCounter, abs($transactionBalance['principal']));
//		$principalAdjustment = $principal % 50;
//		$principal -= $principalAdjustment;
//		$amountCounter -= $principal;
//echo '<pre>' . print_r($transactionBalance,true) . '</pre>';
		if ($type == 'fee')
		{
			$fee = min($amountCounter, abs($transactionBalance['fee']));
			$amountCounter -= $fee;
			
			$serviceCharge = min($amountCounter, abs($transactionBalance['service_charge']));
			$amountCounter -= $serviceCharge;
			

			
			$irrecoverable = min($amountCounter, abs($transactionBalance['irrecoverable']));
			$amountCounter -= $irrecoverable;
			
			$principal = min($amountCounter,  abs($transactionBalance['principal']));
			$amountCounter -= $principal;
		}
		else
		{
			$principal = min($amountCounter,  abs($transactionBalance['principal']));
			$amountCounter -= $principal;
			
			//$serviceCharge += $amountCounter;
		}
		if ($ischargebackreversal)
		{
			$sign = -1;
		}
		else
		{
			$sign = 1;
		}
		
		$transactionBalance['principal'] += $sign * $principal;
		$transactionBalance['service_charge'] += $sign * $serviceCharge;
		$transactionBalance['fee'] += $sign * $fee;
		$transactionBalance['irrecoverable'] += $sign * $irrecoverable;
		
		return self::generateAmounts(array(
			'principal' => $sign * $principal,
			'service_charge' => $sign * $serviceCharge,
			'fee' => $sign * $fee,
			'irrecoverable' => $sign * $irrecoverable,
		));
	}
	
	static public function generateInternalAdjustment($adjustmentType, $amount, &$balance)
	{
//		if ($adjustmentType != 'principal')
//		{
//			$adjustmentType = 'service_charge';
//		}
		
		if ($amount > 0)
		{
			$balance[$adjustmentType] += $amount;
			return self::generateAmounts(array($adjustmentType => $amount));
		}
		else
		{
			$amountCounter = abs($amount);
			
			if ($adjustmentType == 'principal')
			{
				$principal = min($amountCounter, $balance['principal']);
				$amountCounter -= $principal;
			}

			if ($adjustmentType == 'fee')
			{
				$fee = min($amountCounter, $balance['fee']);
				$amountCounter -= $fee;
			}

			if ($adjustmentType == 'service_charge')
			{
				$serviceCharge = min($amountCounter, $balance['service_charge']);
				$amountCounter -= $serviceCharge;
			}
			
			
			if ($adjustmentType != 'fee')
			{
				$fee = min($amountCounter, $balance['fee']);
				$amountCounter -= $fee;
			}
			

			if ($adjustmentType != 'service_charge')
			{			
				$serviceCharge = min($amountCounter, $balance['service_charge']);
				$amountCounter -= $serviceCharge;
			}
			
			$principal += $amountCounter;
			
			$balance['principal'] -= $principal;
			$balance['service_charge'] -= $serviceCharge;
			$balance['fee'] -= $fee;
			
			return self::generateAmounts(array(
				'principal' => -$principal,
				'service_charge' => -$serviceCharge,
				'fee' => -$fee,
			));
		}
	}

	public static function createBalanceAdjustments(array $balance)
	{
		$working_balance = $balance;
		$surplus = 0;
		
		//determine the total surplus
		foreach($working_balance as $type => $amount)
		{
			if($amount < 0)
			{
				$surplus += $amount;
				$working_balance[$type] = 0;
			}
		}

		//calculate adjustments
		if($surplus < 0)
		{
			//waterfall in this order, leaving principal negative if necessary
			$order = array('fee', 'service_charge', 'principal');

			foreach($order as $type)
			{
				if($working_balance[$type] > 0 && $surplus < 0)
				{
					//only adjust to zero, not below
					$adjustment = (($working_balance[$type] + $surplus) < 0) ? -$working_balance[$type] : $surplus;
					//make the adjustment
					$working_balance[$type] += $adjustment;
					$surplus -= $adjustment;
				}
			}

			//allow leftovers to remain in principal
			if($surplus < 0)
			{
				$working_balance['principal'] += $surplus;
			}
		}

		//make adjustments
		$adjustments = array();
		if(count(array_diff($balance, $working_balance)))
		{
			foreach($working_balance as $type => $amount)
			{
				if($working_balance[$type] != $balance[$type])
				{	
					$adjustments[] = Event_Amount::MakeEventAmount($type, $working_balance[$type] - $balance[$type]);
				}
			}
		}	
		return $adjustments;
	}
}
