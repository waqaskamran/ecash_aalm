<?php
require_once(SQL_LIB_DIR.'/scheduling.func.php');
class ECash_ExternalBatches_QuickCheckBatch extends ECash_ExternalBatches_ExternalBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->before_status = array('ready', 'quickcheck', 'collections', 'customer', '*root' );
		$this->after_status  = array('sent', 'quickcheck', 'collections', 'customer', '*root');

		$this->filename = 'quickcheck'. $this->company_name_short . date('Ymd');
		$this->format   = 'csv';

		$this->headers  = FALSE;

		$this->dequeue  = TRUE;

		$this->background_columns[] = 'bank_aba';
		$this->background_columns[] = 'bank_account';
		$this->background_columns[] = 'bank_account_type';
		
		$this->sreport_type      = 'quickcheck';
		$this->sreport_data_type = 'quickcheck_batch';


	}

	public function getBalance()
	{
		if(!is_array($this->data) || empty($this->data))
		{
			return parent::getBalance();
		}

		foreach ($this->data as $record) 
		{
			$balance += $record['PaymentAmount'];
		}
		return $balance;
	}
	
	
	protected function createECLDRecord($application_data)
	{
		
		if($this->sreport_id == NULL)
		{
			throw new Exception("You need to save the report first!");
		}
		$application = ECash::getApplicationById($application_data['application_id']);

		$ecld = ECash::getFactory()->getModel('Ecld');		
		$ecld->date_created = date('Y-m-d H:i:s');
		$ecld->company_id = $this->company_id;
		$ecld->application_id = $application_data['application_id'];
		$ecld->ecld_file_id = $this->sreport_id;
		$ecld->event_schedule_id = $application_data['event_id'];
		$ecld->business_date = date('Y-m-d');
		$ecld->amount = $application_data['balance'];
		$ecld->bank_aba = $application_data['bank_aba'];
		$ecld->bank_account = $application_data['bank_account'];
		$ecld->bank_account_type = $application_data['bank_account_type'];
		$ecld->ecld_status = 'batched';
		$ecld->trans_ref_no = 'lolwut';
		$ecld->save();
		$this->updateProgress("Creating ECLD records for {$application->application_id}",.001);
		return $ecld->ecld_id;
		
	}

	//Will, why do we have a function for registering the transaction when there's a bunch of stuff in scheduling that does
	//similar functions?  SHUT UP! I HATE YOU! This uses the ecld_id!
	protected function registerQuickcheckEvent($application_data)
	{
		$transaction = ECash::getFactory()->getModel('TransactionRegister');		
		$transaction_type = ECash::getFactory()->getModel('TransactionType');
		$transaction_type->loadBy(array('name_short' => 'quickcheck', 'company_id' => $this->company_id));
		
		$tt_id = $transaction_type->transaction_type_id;
		$application_id = $application_data['application_id'];
		$ecld_id = $application_data['ecld_id'];
		$event_schedule_id = $application_data['event_id'];
		$amount = -$application_data['balance'];
		$effective_date = date('Y-m-d',strtotime("+1 days"));
//		$transaction->date_created = date('Y-m-d H:i:s');
//		$transaction->company_id = $this->company_id;
//		$transaction->application_id = $application_id;
//		$transaction->event_schedule_id = $event_schedule_id;
//		$transaction->ecld_id = $ecld_id;
//		$transaction->transaction_type_id = $tt_id;
//		$transaction->transaction_status = 'new';
//		$transaction->amount = $amount;
//		$transaction->date_effective = $effective_date;
//		$transaction->save();
//		
//		$transaction->transaction_status = 'pending';
//		$transaction->update();
//		
//		$event = ECash::getFactory()->getModel('EventSchedule');
//		$event->loadByKey($application_data['event_id']);
//		$event->event_status = 'registered';
//		$event->update();
		
		$register_query = "
			INSERT INTO transaction_register
			  (date_created,        company_id,          application_id,     event_schedule_id,
			   ecld_id,             transaction_type_id, transaction_status, amount,
			   date_effective)
			VALUES
			  (now(),               {$this->company_id}, {$application_id}, {$event_schedule_id},
			   {$ecld_id},          {$tt_id},            'pending',         {$amount},
			   {$this->db->quote($effective_date)})
			";
		$register_result = $this->db->query($register_query);
		$register_id = $this->db->lastInsertId();
		
		//Set_Loan_Snapshot($register_id,"pending");		
		
		$event_status_query = "
				UPDATE event_schedule
				  SET
				    event_status = 'registered'
				  WHERE 
				    event_schedule_id = {$event_schedule_id}
			";
		$this->db->exec($event_status_query);

		$event_amount_query = "
				UPDATE event_amount
				  SET
					transaction_register_id = {$register_id}
				  WHERE
					event_schedule_id = {$event_schedule_id}
			";
		$this->db->exec($event_amount_query);
		$this->updateProgress("Registering Quickcheck Event for {$application_id}",.001);
		return $register_id;
	}
	
	
	
	protected function createQuickCheckEvent($application_data)
	{

		$action = date('Y-m-d');
		$effective = date('Y-m-d',strtotime('+1 day'));
		$application_id = $application_data['application_id'];
		
		//OK, look before we create this event, let's do the right thing and truncate the schedule.  Better safe than sorry
		Remove_Unregistered_Events_From_Schedule($application_id);
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('fee', -$application_data['fee_balance']);
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$application_data['interest_balance']);
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$application_data['principal_balance']);
		
		$e = Schedule_Event::MakeEvent($action, $effective, $amounts, 'quickcheck', 
		"Demand Draft");
		$this->updateProgress("Adding Quickcheck event to {$application_id}'s schedule",.001);
		return Record_Event($application_id, $e);
	}
	
	//public function 
	protected function postprocess()
	{
		$this->updateProgress("Running Post processing on Quickcheck Batch",10);
		//Save the report to the database
		$this->saveToDb();
		
		//Do specific actions for each application
		foreach ($this->data as $application)
		{
			//Create the quickcheck transaction for the full balance of the application.
			$event_id = $this->createQuickCheckEvent($application);
			$application['event_id'] = $event_id;
			//Create the ecld records for each application
			$ecld_id = $this->createECLDRecord($application);
			$application['ecld_id'] = $ecld_id;
			//Create the transaction register record for the quickcheck transactions
			$transaction_register = $this->registerQuickcheckEvent($application);
			$application['transaction_id'] = $transaction_register;
			//Update the application's status
			$this->updateStatus($application['application_id']);
			//Remove it from queues maybe?  
		}

		return TRUE;
	}
}


?>