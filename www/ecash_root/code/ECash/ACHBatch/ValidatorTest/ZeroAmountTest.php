<?php
class ECash_ACHBatch_ValidatorTest_ZeroAmountTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;

		foreach($transactions as $transaction)
		{
			if($transaction['amount'] == 0)
			{
				$message = $transaction;
				$message['message'] = 'Is an ACH transaction for an amount of Zero';
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
