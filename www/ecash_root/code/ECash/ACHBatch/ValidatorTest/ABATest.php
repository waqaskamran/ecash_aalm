<?php
class ECash_ACHBatch_ValidatorTest_ABATest implements ECash_ACHBatch_IValidator
{
	protected $messages;

	public function Validate(array $transactions)
	{
		$this->messages = array();
		$return_value = true;
		$sorted_transactions = array();
		foreach($transactions as $transaction)
		{
			if(!$this->validateAbaNumber($transaction['bank_aba'], $error))
			{
				$message = $transaction;
				$message['message'] = $error;
				$this->messages[] = $message;
				$return_value = false;
				
			}

		}
		return $return_value;
	}
	 /**
	  * Validates an ABA / Bank Routing Number
	  * 
	  * Does very basic checks and returns an array
	  * of error messages if it fails.
	  * 
	  * * Is it a number?
	  * * Is the string length 9 characters?
	  * * Is the check digit valid?
	  *
	  * @param integer $aba
	  * @param string &$error
	  * @return boolean
	  */
	 static public function validateAbaNumber($aba, &$error)
	 {
		// Make sure it's a number
		if (! is_numeric($aba))
		{
			$error = "ABA is not a number";
			return FALSE;
		}
  
		// Make sure it's length is 9 characters, no more, no less
		if (strlen($aba) != 9)
		{
			$error = "ABA is not 9 digits";
			return FALSE;
		}
 
		/**
		* Checksum validation from here:
		* 
		* http://en.wikipedia.org/wiki/Routing_transit_number#Internal_checksums
		* 
		* Note: Have to cast as an array so we can pull each digit.
		*/
  
		$aba = (string)$aba;
		$check = (3*($aba[0] + $aba[3] + $aba[6]) + 7*($aba[1] + $aba[4] + $aba[7]) + $aba[2] + $aba[5] + $aba[8]) % 10; 
		if ($check !== 0)
		{
			$error = "ABA Invalid Check Digit";
			return FALSE;
		}
 
		return TRUE;
	}

	public function getMessageArray()
	{
		return $this->messages;
	}

}



?>
