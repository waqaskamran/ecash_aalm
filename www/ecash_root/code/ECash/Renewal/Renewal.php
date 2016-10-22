<?php 

abstract class ECash_Renewal_Renewal
{

	protected $pdc;
	protected $server;
	
	public function __construct()
	{
		$holidays = Fetch_Holiday_List();
		$this->pdc = new Pay_Date_Calc_3($holidays);
		$this->server = ECash::getServer();
	}
	
	protected function fixDate($date)
	{
		if(is_numeric(substr($date,-4)))
		{
			return substr($date, -4) . '-' . substr($date, 0, 5);
		}
		else 
		{
			return '1970-01-01';
		}
		
	}


	
}

?>
