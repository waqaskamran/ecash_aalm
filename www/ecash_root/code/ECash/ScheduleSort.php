<?php 

class ECash_ScheduleSort
{

	public static function sortDailyInterest($a, $b) 
	{
		// Converted Principal Balances first
		if ($a->type == 'converted_principal_bal') return -1;
		if ($b->type == 'converted_principal_bal') return 1;
	
		// Then converted service charges
		if ($a->type == 'converted_service_chg_bal') return -1;
		if ($b->type == 'converted_service_chg_bal') return 1;

                // Then Loan dispersements
		if ($a->type == 'loan_disbursement') return -1;
		if ($b->type == 'loan_disbursement') return 1;

		// Refi fowards at the end
		if ($a->type == 'refi_foward') return 1;
		if ($b->type == 'refi_foward') return -1;

		// All OTHER events, work as such:
		// 1) Sort by action date
		// 2) All debits for a matching action date are first, 
		//    unless they are of type loan_disbursement
	
		//[#30014] prevent things being out-of-order in the case that paydates
		//were switched after something like 'arrange next payment'
		if ($a->date_event != $b->date_event) 
		{
			return ((strtotime($a->date_event) < strtotime($b->date_event)) ? -1 : 1);
		} 
		elseif ($a->date_effective != $b->date_effective) 
		{
			return ((strtotime($a->date_effective) < strtotime($b->date_effective)) ? -1 : 1);
		} 
		else 
		{
	
			$orderarray = Array(
				'zero index is false which will not do',
				'loan_disbursement',
				'moneygram_disbursement',
				'check_disbursement',
				'assess_fee_ach_fail',
				'assess_service_chg',
				'payment_service_chg',
				'credit_card',
				'credit_card_princ',
				'chargeback',
				'chargeback_reversal',
				'assess_fee_lien',
				'assess_fee_delivery',
				'assess_fee_transfer',
				'payout'
			);
	
			if (array_search($a->type, $orderarray) && array_search($b->type, $orderarray)) 
			{
				$retval = 0;
				$retval = array_search($a->type, $orderarray) < array_search($b->type, $orderarray) ? -1 : 0;
				if ($retval != 0) return $retval;
				$retval = array_search($a->type, $orderarray) > array_search($b->type, $orderarray) ? 1 : 0;
				if ($retval != 0) return $retval;
			}
	
			if(abs($a->fee_amount) + abs($a->service_charge) < abs($b->fee_amount) + abs($b->service_charge))
				return 1;
			else if ($a->principal_amount != $b->principal_amount)
				return -1;
			
			$amta = $a->principal_amount + $a->fee_amount + $a->service_charge;
			$amtb = $b->principal_amount + $b->fee_amount + $b->service_charge;
			
			return (($amta < $amtb) ? -1 : 1);
		}
		return 0;
	}
	
	public static function sortFixedInterest($a, $b) 
	{
                // Converted Principal Balances first
                if ($a->type == 'converted_principal_bal') return -1;
                if ($b->type == 'converted_principal_bal') return 1;
                // Then converted service charges
                if ($a->type == 'converted_service_chg_bal') return -1;
                if ($b->type == 'converted_service_chg_bal') return 1;
                // Then Loan dispersements
                if ($a->type == 'loan_disbursement') return -1;
                if ($b->type == 'loan_disbursement') return 1;
                // Refi fowards at the end
                if ($a->type == 'refi_foward') return 1;
                if ($b->type == 'refi_foward') return -1;
	
		// All OTHER events, work as such:
		// 1) Sort by action date
		// 2) All debits for a matching action date are first, 
		//    unless they are of type loan_disbursement
	
		if ($a->date_event != $b->date_event) 
		{
			return ((strtotime($a->date_event) < strtotime($b->date_event)) ? -1 : 1);
		} 
		else 
		{
	
			if ($a->type == 'assess_fee_ach_fail') return -1;
			if ($b->type == 'assess_fee_ach_fail') return 1;
	
			if ("loan_disbursement"  == $a->type && "loan_disbursement"   != $b->type) return -1;
			if ("loan_disbursement"  == $b->type && "loan_disbursement"   != $a->type) return  1;
	
			if ("repayment_principal" == $a->type && "payment_service_chg" == $b->type) return  1;
			if ("repayment_principal" == $b->type && "payment_service_chg" == $a->type) return -1;
	
			if ("assess_service_chg" == $a->type && "payment_service_chg" == $b->type) return  1;
			if ("assess_service_chg" == $b->type && "payment_service_chg" == $a->type) return -1;
	
			if ("assess_service_chg" == $a->type && "h_nfatal_cashline_return" == $b->type) return  1;
			if ("assess_service_chg" == $b->type && "h_nfatal_cashline_return" == $a->type) return -1;
	
			// Reattempts have empty origin_id
			if (empty($a->origin_id) && !empty($b->origin_id) ) return 1;
			if (empty($b->origin_id) && !empty($a->origin_id) ) return -1;
	
			// Sort FEE items before principal items [Mantis:4615]
			if(strpos($a->type, 'fees') && strpos($b->type,'princ')) return -1;
			
			// Sort Entries for manual Payments and Chargebacks		
			if ("chargeback" == $a->type && "credit_card_princ" == $b->type) return 1;
			if ("chargeback" == $a->type && "credit_card" == $b->type) return 1;
			if ("credit_card_princ" == $a->type && "chargeback_reversal" == $b->type) return -1;
			if ("credit_card" == $a->type && "chargeback_reversal" == $b->type) return -1;
			if ("chargeback" == $a->type && "chargeback_reversal" == $b->type) return -1;
			
			// Make sure that 'scheduled' payments show below completed, etc.
			if ($a->status == 'scheduled' && in_array($b->status, array('new', 'pending', 'complete', 'failed') ) ) return 1;
			if ($b->status == 'scheduled' && in_array($a->status, array('new', 'pending', 'complete', 'failed') ) ) return -1;
			
			//if ("chargeback"  == $a->type && "chargeback_reversal"   != $b->type) return -1;
			//if ("chargeback"  == $b->type && "chargeback_reversal"   != $a->type) return  1;
			$amta = $a->principal_amount + $a->fee_amount;
			$amtb = $b->principal_amount + $b->fee_amount;
			return (($amta < $amtb) ? -1 : 1);
		}
		return 0;
	}
	
}
?>
