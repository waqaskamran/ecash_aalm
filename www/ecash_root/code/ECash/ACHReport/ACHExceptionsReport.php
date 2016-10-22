<?php

class ECash_ACHReport_ACHExceptionsReport
{
	private $exceptions = array();
	private $db;
	private $company_id;
	public function __construct(DB_IConnection_1 $db,$company_id)
	{
		$this->company_id = $company_id;
		$this->db = $db;
	}
	public function addException(ECash_ACHReport_ACHException $exception)
	{
		$this->exceptions[] = $exception; 
	}
	
	public function getExceptions()
	{
		return $this->exceptions;
	}
	
	public function hasExceptions()
	{
		if(count($this->exceptions))
		{
			return true;
		}
		return false;
	}
	public function storeExceptions()
	{
		
		$effective_entry_date = date('Y-m-d');


		foreach ($this->exceptions as $exception) 
		{
			$ach_exception = ECash::getFactory()->getModel('AchException');
			$ach_exception->date_created 	= $effective_entry_date;
			$ach_exception->return_date		= $effective_entry_date;
			$ach_exception->recipient_id	= $exception->ach_id;
			$ach_exception->recipient_name	= $recipient_name;
			$ach_exception->ach_id			= $ach_id;
			$ach_exception->debit_amount	= $debit_amount;
			$ach_exception->credit_amount	= $credit_amount;
			$ach_exception->reason_code		= $exception->reason_code;
			$ach_exception->company_id		= $this->company_id;
			$ach_exception->save();
		}
	}
}
?>