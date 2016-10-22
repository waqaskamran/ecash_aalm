<?php
class ECash_CardBatch_ValidatorTest_ZeroAmountTest implements ECash_CardBatch_IValidator
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
				$message['message'] = 'Is a transaction for an amount of Zero';
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
