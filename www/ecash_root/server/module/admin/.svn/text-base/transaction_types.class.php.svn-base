<?php

/**
 * @author Jeff Day
 *
 */
class Transaction_Type_Config
{
	/**
	 * @var Server
	 */
	private $server;

	/**
	 * @var stdClass
	 */
	private $request;

	/**
	 * @param Server $server
	 * @param stdClass $request
	 */
	public function __construct(Server $server, $request)
	{
        $this->server = $server;
        $this->request = $request;

        ECash::getTransport()->Set_Data($request);

	}
	
	/**
	 * Saves the flag type by either making a new one or by modifying the existing one
	 * depending on whether the flag_type_id is there.
	 * Doesn't do any verification since that's done on the front end.
	 *
	 */
	public function saveTransactionType()
	{
		$request = $this->request;
		$request->event_type_id = ECash_Models_EventType::loadby(array('name_short' => $request->event_type_id))->event_type_id;
		
		/* @var $flag_type ECash_Models_FlagType */
		if($request->transaction_type_id)
		{
			$model = ECash_Models_TransactionType::loadBy(array('transaction_type_id' => $request->transaction_type_id, 'company_id' => $this->server->company_id));
			$event_transaction = ECash_Models_EventTransaction::loadBy(array('transaction_type_id' => $model->transaction_type_id));
			
			if($request->event_type_id != $event_transaction->event_type_id)
			{
				if(!is_null($event_transaction)) {
					$event_transaction->delete();
				}
				$this->createEventTransaction($model, $request->event_type_id);
			}
		} 
		else 
		{
			$model = ECash::getFactory()->getModel('TransactionType');
			$model->date_modified = date('Y-m-d H:i:s');
			$model->date_created = date('Y-m-d H:i:s');
		}	
		$model->active_status = $request->active_status;
		$model->name = $request->name;
		$model->name_short = $request->name_short;
		$model->clearing_type = $request->clearing_type;
		$model->affects_principal = $request->affects_principal;
		$model->pending_period = $request->pending_period;
		$model->end_status = $request->end_status;
		$model->period_type = $request->period_type;
		$model->company_id = $this->server->company_id;
		$model->save();
		if(!$request->transaction_type_id)
		{
			$this->createEventTransaction($model, $request->event_type_id);
		}
		$request->result = 'Transaction type successfully saved';
		ECash::getTransport()->Set_Data($request);
	}
	
	private function createEventTransaction($model, $event_type_id)
	{
		$event_transaction = new ECash_Models_EventTransaction();
		$event_transaction->date_created = date('Y-m-d H:i:s');
		$event_transaction->company_id = $model->company_id;
		$event_transaction->active_status = 'active';
		$event_transaction->event_type_id = $event_type_id;
		$event_transaction->transaction_type_id = $model->transaction_type_id;	
		$event_transaction->save();	
	}

}

?>