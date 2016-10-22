<?php
class ECash_ACHBatch_ValidatorTest_DueDateTest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$today = Date('Y-m-d');
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);	
		$next_business_day = $pdc->Get_Next_Business_Day($today);		
		$this->messages = array();
		$return_value = true;
		foreach($transactions as $transaction)
		{
			if(strtotime($transaction['action_date']) == strtotime($transaction['due_date']))
			{
				$message = $transaction;
				$message['message'] = 'Due Date equals Action Date for ACH transaction';
				$this->messages[] = $message;
				$return_value = false;
			}
			if($transaction['due_date'] != $next_business_day)
			{
				$message = $transaction;
				$message['message'] = 'Due Date not on next Business Day of today for ACH transaction';
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
