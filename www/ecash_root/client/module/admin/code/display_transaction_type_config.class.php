<?php

require_once(LIB_DIR. "form.class.php");
require_once("admin_parent.abst.php");

class Display_View extends Admin_Parent
{
	private $server;

	public function __construct(ECash_Transport $transport, $module_name)
	{
		global $server;
		$this->server = $server;
		parent::__construct($transport, $module_name);
	}

	public function Get_Header()
	{
		$data = ECash::getTransport()->Get_Data();

		return parent::Get_Header();
	}

	public function Get_Module_HTML()
	{
		$data = ECash::getTransport()->Get_Data();
		$retval =  file_get_contents(CLIENT_MODULE_DIR.$this->module_name."/view/transaction_type_config.html");
		
		$transaction_types = array();
		foreach(ECash_Models_TransactionTypeList::loadBy(array('company_id' => $this->server->company_id)) as $model) 
		{
			$transaction_type = array_values($model->getColumnData());
			$event_type = ECash_Models_EventTransaction::loadBy(array('transaction_type_id' => $model->transaction_type_id, 'company_id' => $this->server->company_id));
			if(!is_null($event_type))
				$transaction_type[] = $event_type->event_type_id;
			else 
				$transaction_type[] = '';
			$transaction_types[] = $transaction_type;
		}
		$event_types = array();
		foreach(ECash_Models_EventTypeList::loadBy(array(
			'active_status' => 'active', 
			'company_id' => $this->server->company_id
		)) as $event)
		{
			$event_types[] = array($event->event_type_id, $event->name_short);
		}
		
		/**
		 * this is a loop that could probably be integrated with the previous loop, but it's safer not to
		 */
		$transaction_event = array();
		$maxed_events = array();
		foreach(ECash_Models_EventTransactionList::loadBy(array('company_id' => $this->server->company_id, 'active_status' => 'active')) as $et)
		{
			if(in_array($et->event_type_id, $transaction_event))
			{
				$maxed_events[] = $et->event_type_id;
			}
			$transaction_event[$et->transaction_type_id] = $et->event_type_id;
		}
		$fields = array_keys($model->getColumnData());
		$fields[] = 'event_type_id';
		$retval = str_replace('%%%transaction_type_json%%%', json_encode($transaction_types), $retval);
		$retval = str_replace('%%%result%%%', $data->result, $retval);
		$retval = str_replace('%%%fields%%%', json_encode($fields), $retval);
		$retval = str_replace('%%%event_types%%%', json_encode($event_types), $retval);
		$retval = str_replace('%%%maxed_events%%%', json_encode($maxed_events), $retval);
		$retval = str_replace('%%%transaction_events%%%', json_encode($transaction_event), $retval);
		
		return $retval;
	}
}

?>