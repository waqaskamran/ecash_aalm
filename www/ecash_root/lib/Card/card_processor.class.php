<?php

class Card_Processor
{

	private $card_proc;
	private $server;
	private $db;
	private $pdc;
	private $cash_report_enabled;
	private $cso_enabled;
	
	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash::getMasterDb();
		
		$holidays = Fetch_Holiday_List();
		$this->pdc = new Pay_Date_Calc_3($holidays);

		$this->card_proc = Card::Get_Card_Process($server, 'batch');
	}

	// The $obj in this isn't really necessary, it's mostly to return a message when 
	// the method is called from the user interface, but for the nightly cronjob
	// at most it'll get logged.
	public function Process_Cards()
	{
		$obj = new stdClass();

		$date = date("Y-m-d", strtotime("now"));
		$this->card_proc->Set_Closing_Timestamp($date);
		$stamp = $this->card_proc->Get_Closing_Timestamp($date);		
		
		Record_Current_Scheduled_Events_To_Register($date, NULL, NULL, 'card');

		// we can shortcut the process for credit cards
		$card_receipt = $this->Process();
		$obj->message = $obj->message 
			."The payment card closing time has been set to {$stamp}\n"
			."The payment card debits have been processed: ".$card_receipt['status']."\n";
		$obj->closing_time = $stamp;
		
		return($obj);
	}

	public function Process()
	{
		$today = date('Y-m-d');
		$tomorrow = $this->pdc->Get_Next_Business_Day($today);

		$card_receipt = $this->card_proc->Do_Batch('debit', $tomorrow);
		return($card_receipt);
	}

	public function Verify($data)
	{
		$card_receipt = $this->card_proc->Verify_Card($data);
		return($card_receipt);
	}

	public function Charge($data)
	{
		$card_receipt = $this->card_proc->Charge_Card($data);
		return($card_receipt);
	}
}

?>
