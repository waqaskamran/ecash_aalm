<?php
/*
* ACH Exceptions!  This is just an exception object!
* 
*
*/
class ECash_ACHReport_ACHException
{
	private $ach_id;
	private $description;
	private $reason_code;
	
	public function __construct()
	{
		
	}

	public function __get($name)
	{
		return $this->$name;
	}
	public function __set($name,$value)
	{
		$this->$name = $value;
	}
	public function __toString()
	{
		return "{$this->ach_id} - {$this->reason_code} - {$this->description}";
	}


}


?>
