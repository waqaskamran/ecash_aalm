<?php
class ECash_ACHBatch_ValidatorTest_CompanyTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;
		foreach($transactions as $transaction)
		{
			if($transaction['application_company_id'] <> $transaction['event_company_id'])
			{
				$message = $transaction;
				$message['message'] = 'Application\'s Company does not equal the Event\'s Company';
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
