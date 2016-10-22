<?php
class ECash_CardBatch_ValidatorTest_DupTest implements ECash_CardBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;
		$sorted_transactions = array();
		foreach($transactions as $transaction)
		{
			if(empty($sorted_transactions[$transaction['application_id']]))
			{
				$sorted_transactions[$transaction['application_id']] = array();
				$sorted_transactions[$transaction['application_id']][$transaction['event_name_short']] =  array();
				$sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']] = $transaction;
			}
			elseif(empty($sorted_transactions[$transaction['application_id']][$transaction['event_name_short']]))
			{
				$sorted_transactions[$transaction['application_id']][$transaction['event_name_short']] = array();
				$sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']] = $transaction;
			}
			elseif(empty($sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']]))
			{
				$sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']] = $transaction;
			}
			else
			{
				if($sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']]['origin_id'] == $transaction['origin_id'])
				{				
					$message = $transaction;
					$message['message'] = 'Duplicate Transaction of Event ' . $sorted_transactions[$transaction['application_id']][$transaction['event_name_short']][$transaction['amount']]['event_schedule_id'];
					$this->messages[] = $message;
					$return_value = false;
				}
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
