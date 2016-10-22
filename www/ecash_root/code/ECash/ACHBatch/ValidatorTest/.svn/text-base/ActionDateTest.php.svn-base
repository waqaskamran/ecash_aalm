<?php
class ECash_ACHBatch_ValidatorTest_ActionDateTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$today = Date('Y-m-d');
		$today_timestamp = strtotime($today);
		$this->messages = array();
		$return_value = true;
		foreach($transactions as $transaction)
		{
			if(strtotime($transaction['action_date']) < $today_timestamp)
			{
				$message = $transaction;
				$message['message'] = 'Action Date is in the Past';
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
