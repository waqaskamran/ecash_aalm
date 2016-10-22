<?php
class ECash_ACHBatch_ValidatorTest_AmountsTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;
		foreach($transactions as $transaction)
		{
			if(($transaction['amount_principal'] > 0 && $transaction['amount_interest'] < 0)
				|| ($transaction['amount_principal'] > 0 && $transaction['amount_fees'] < 0)
				|| ($transaction['amount_principal'] < 0 && $transaction['amount_interest'] > 0)
				|| ($transaction['amount_principal'] < 0 && $transaction['amount_fees'] > 0)
				|| ($transaction['amount_interest'] < 0 && $transaction['amount_fees'] > 0)
				|| ($transaction['amount_interest'] > 0 && $transaction['amount_fees'] < 0))
			{
				$message = $transaction;
				$message['message'] = 'Mixed Amounts, ACH transaction has a debit and credit';
				$this->messages[] = $message;
				$return_value = false;
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
