<?php
class ECash_ACHBatch_ValidatorTest_DebitCreditTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;

		$debit_list = array('repayment_principal', 'payment_service_chg', 'payment_fee_ach_fail', 'payment_arranged', 
					'full_balance', 'personal_check','paydown', 'cancel', 'payout', 'bad_data_payment_debt', 'manual_ach');
		$credit_list = array('loan_disbursement', 'refund', 'refund_3rd_party');

		foreach($transactions as $transaction)
		{
			if(in_array($transaction['event_name_short'], $debit_list) && $transaction['ach_type'] != 'debit')
			{
				$message = $transaction;
				$message['message'] = 'ACH transaction should be a debit, but is a credit';
				$this->messages[] = $message;
				$return_value = false;
				continue;
			}
			if(in_array($transaction['event_name_short'], $credit_list) && $transaction['ach_type'] != 'credit')
			{
				$message = $transaction;
				$message['message'] = 'ACH transaction should be a credit, but is a debit';
				$this->messages[] = $message;
				$return_value = false;
				continue;
			}
		}
		return $return_value;
	}

	public function getMessageArray()
	{
		return $this->messages;
	}

}



?>
