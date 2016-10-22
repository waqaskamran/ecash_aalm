<?php
/**
 * @package PBX
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Mar 22, 2007
 *
 * @version $Revision: 7727 $
 */

require_once LIB_DIR . "/Event/Event.class.php";

class eCash_PBX_Asterisk_EventHandler extends eCash_Event_Handler 
{
									
	static $uniq_stack = array();
	static $unlinked_stack = array();
	
	public function execute()
	{	
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );
//		eCash_PBX::Log()->write("Event : " . var_export($this->optional_data,true));
		
		switch (strtolower($this->optional_data['Event'])) 
		{
			case "link":
			case "unlink":
				$this->handleLink();
				break;
				
			case "dial":
				$this->handleDial();
				break;
				
			case "newchannel":
				$this->handleNewChannel();
				break;
				
			case "hangup":
				$this->handleHangup();
				break;

			default:
				$this->handleEvent();
		}
				
	}
	
	protected function getStackItem($id)
	{
		if (!self::$uniq_stack[$id]) {
			self::$uniq_stack[$id] = new _CDR_Object($this->optional_data['pbx']);
			self::$uniq_stack[$id]['uniqueid'] = $id;
		}
		
		return  self::$uniq_stack[$id];
		
	}
	
	protected function getRawData()
	{
		$raw_result = $this->optional_data;
		unset($raw_result['pbx']);
		unset($raw_result['agi']);

		eCash_PBX::Log()->write(var_export($raw_result,true));		
		
		return $raw_result;
		
	}
	
	protected function handleHangup()
	{
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );
				
		$uniq = $this->getStackItem($this->optional_data['Uniqueid']);

		$ev = new _CDR_Event($this->optional_data['pbx']);
		$ev['result'] = $this->getRawData();
		$uniq->append($ev);
				
		$uniq->__destruct();
		
		// this should bE moved:
		eCash_PBX::deregisterContact($this->optional_data['pbx']->getServer(), $ev['application_contact_id'], $ev['agent_id'] );
		
//		unset(self::$uniq_stack[$this->optional_data['Uniqueid']]);
		
	}
	
	protected function handleNewChannel()
	{
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );
				
		$uniq = $this->getStackItem($this->optional_data['Uniqueid']);

		$ev = new _CDR_Event($this->optional_data['pbx']);
		$ev['result'] = $this->getRawData();
		$uniq->append($ev);
		
		foreach ($this->getRawData() as $key => $val) {
			switch ($key) {
				case "Channel":
					if (preg_match("/^SIP\/(\d+)/", $val, $matches)) {
						$uniq['callerid'] = $matches[1];
					}
			}
		}						
				
		eCash_PBX::Log()->write("Events in {$this->optional_data['Uniqueid']}: " .var_export(count($uniq),true));		

		
	}
	
	protected function handleEvent()
	{
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );
				
		$uniq = $this->getStackItem($this->optional_data['Uniqueid']);

		$ev = new _CDR_Event($this->optional_data['pbx']);
		$ev['result'] = $this->getRawData();
		$uniq->append($ev);
		
		foreach ($this->getRawData() as $key => $val) {
			switch ($key) {
				case "CallerID":
					$uniq['callerid'] = $val;
			}
		}						
				
		eCash_PBX::Log()->write("Events in {$this->optional_data['Uniqueid']}: " .var_export(count($uniq),true));		

	}
	
	protected function handleDial()
	{
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );

		$ev = new _CDR_Event($this->optional_data['pbx']);
		$ev['result'] = $this->getRawData();

		$one = $this->getStackItem($this->optional_data['SrcUniqueID']);
		$one->append($ev);

		$two = $this->getStackItem($this->optional_data['DestUniqueID']);
		$two->append($ev);
		
		$one['link'] = $two;
		$two['link'] = $one;
		
		$ev->checkComplete();

	}
	
	protected function handleLink()
	{
		eCash_PBX::Log()->write("Event {$this->optional_data['Event']} handled by " . __METHOD__ );
		
		$ev = new _CDR_Event($this->optional_data['pbx']);
		$ev['result'] = $this->getRawData();

		$one = $this->getStackItem($this->optional_data['Uniqueid1']);
		$one->append($ev);

		$two = $this->getStackItem($this->optional_data['Uniqueid2']);
		$two->append($ev);
		
		$one['link'] = $two;
		$two['link'] = $one;
		
		foreach ($this->getRawData() as $key => $val) {
			switch ($key) {
				case "CallerID1":
					$one['callerid'] = $val;
			}
		}						
		
		foreach ($this->getRawData() as $key => $val) {
			switch ($key) {
				case "CallerID2":
					$two['callerid'] = $val;
			}
		}						
				
	}
	
}

class _CDR_Object extends ArrayIterator
{
	private $asterisk_id;

	private $agent_id;
	private $application_contact_id;
	private $phone;
	
	private $link;
	
	public function __construct(eCash_PBX $pbx)
	{
		$this->pbx = $pbx;
		
		parent::__construct(array(), 1);
		
	}
	
	public function __destruct()
	{
//	here for testing
//		if (!$this->agent_id) {
//			$tid = $this->asterisk_id - floor($this->asterisk_id);
//			$tid = substr($tid, 2, 4);
//			$this->offsetSet("agent_id", $tid);
//		}
//		
//		if (!$this->application_contact_id) {
//			$this->offsetSet("application_contact_id", floor($this->asterisk_id));
//		}
//		
		foreach ($this->getArrayCopy() as $event) {
			$event->setHistory(true);
		}
	}
	
	public function append($value)
	{
		$this->checkLink($value);
		
		return parent::append($value);
	}
	
	public function offsetSet($index, $value)
	{
		if (is_numeric($index) || $index == NULL) {
			$res = parent::offsetSet($index, $value);
			if ($value['callerid']) {
				$this->offsetSet("callerid", $value['callerid']);
			}
		}
		
		switch ($index) {
			case "uniqueid":
				return (!$this->asterisk_id) ? $this->asterisk_id = $value : TRUE ;
				
			case "phone":
			case "agent_id":
			case "application_contact_id":
				if(!$this->{$index}) $this->{$index} = $value;
				$this->checkPersistentData($index);
				$this->checkLink();
				break;
				
			case "callerid":
				$this->procCallerid($value);
				break;
				
			case "link":
				eCash_PBX::Log()->write(__METHOD__ . "(): Link");		
				if (!$this->link && $value instanceof _CDR_Object) {
					$this->link = $value;
				}

				$this->checkLink();
				
				break;
				
		}	
		
//		var_dump($this->getArrayCopy());
		
	}
	
	public function offsetGet($index)
	{
		if (is_numeric($index)) {
			return parent::offsetGet($index);
		}
		
		switch ($index) {
			case "uniqueid":
				return $this->asterisk_id;
				
			case "phone":
			case "agent_id":
			case "application_contact_id":				
				return $this->{$index};
		}			
		
	}
	
	private function procCallerid($value)
	{
		if (!is_numeric($value)) return;
		
		if (!$this->phone) {
			$this->offsetSet("phone", $value);
		}
		
		if (!$this->agent_id) {
			$res = $this->pbx->getRegisteredAgent($value);
			if($res) {
				$this->offsetSet("agent_id", $res);
			}
		}
		
		if (!$this->application_contact_id) {
			$res = $this->pbx->getRegisteredContact($value);
			if($res) {
				$this->offsetSet("application_contact_id", $res);
			}
		}		
		
	}
	
	private function checkLink($value = NULL)
	{
		if(!$value) $value = $this->link;
		
		if (!($value instanceof _CDR_Object || $value instanceof _CDR_Event)) {
			return;
		}
		
		foreach (array("agent_id", "application_contact_id", "phone") as $index) {
			if (!$this->{$index} && $value[$index]) {
				$this->offsetSet($index, $value[$index]);
				
			} elseif ($this->{$index} && !$value[$index]) {
				$value[$index] = $this->{$index};
				
			}
		}
		
	}
	
	private function checkPersistentData($index)
	{
		 if ((bool) $this->{$index}) {
		 	foreach ($this->getArrayCopy() as $event) {
		 		$event[$index] = $this->{$index};
		 	}
		 	return;
		 }
		 
		 foreach ($this as $event) {
		 	$item  = $event[$index];
		 	if ($item) {
		 		$this->{$index} = $item;
		 		break;
		 	}
		 }
		 
		 if ($item) {
		 	$this->checkPersistentData($index);
		 }
		 
	}
	
}

class _CDR_Event implements ArrayAccess 
{

	private $application_contact_id = 0;
	private $agent_id = 0;
	private $pbx_event;
	private $result = array();
	
	private $phone;
	
	private $is_complete = FALSE;
	private $do_not_update = TRUE;
	
	private $pbx;
	private $history_id;
	
	private $altered = array();
	
	public function __construct(eCash_PBX $pbx)
	{
		$this->pbx = $pbx;
	}
	
	public function offsetSet($index, $value)
	{
		eCash_PBX::Log()->write( __METHOD__ . "() Called: {$index}:" . var_export($value, true));
		$this->privateSet($index, $value);

		$this->checkComplete();		
	
	}
	
	private function privateSet($index, $value)
	{
		if (isset($this->history_id) && $this->offsetExists($index) && $this->{$index} != $value) {
			$this->altered[$index] = $index;
		}
		
		switch ($index) {
			case "application_contact_id":			
				$this->hist = ( $value > 0 && !$this->hist ) ? eCash_PBX_History::Factory($this->pbx, $value) : NULL;
				
			case "agent_id":
//			case "asterisk_id":
				if (!$this->{$index} && 
					is_numeric($value) &&
					$value > 0
					) 
				{
					$this->{$index} = $value;
				}				
				break;
				
			case "pbx_event":
				if ( !(bool) $this->{$index}) {
					$this->{$index} = $value;
					$this->do_not_update = FALSE;
				}
				break;
				
			case "result":
//				if ( ! (bool) count($this->result)) {
					if ($value['Event']) {
						$this->offsetSet("pbx_event", $value['Event']);
					}
					
					$this->{$index} = $value;
					
//				}
				break;
			
			case "link" :
				if ( $value instanceof _CDR_Event ) {
					$this->syncLink($value);
				}
				break;
			
			case "phone":
//				if ( !(bool) $this->{$index}) {
					$this->{$index} = $value;
//				}
				
			default:
				break;
		}
		
	}
	
	public function offsetGet($index)
	{
		if ($this->offsetExists($index)) {
			return $this->{$index};
		}
	}
	
	public function offsetExists($index)
	{
		switch ($index) {
			case "agent_id":
			case "application_contact_id":
			case "pbx_event":
			case "result":
			case "phone":
				return true;
				
			default:
				return false;
			
		}
	}
	
	public function offsetUnset($index)
	{
		return;
	}
		
	public function checkComplete()
	{
		if ($this->do_not_update || $this->is_complete) {
			return $this->is_complete;
		}
		
		$this->setHistory();
		
		if ($this->application_contact_id &&
			$this->agent_id &&
			$this->pbx_event &&
			$this->result
		) {
			$this->is_complete = TRUE;
		}
		
		return $this->is_complete;
		
	}
	
	private function syncLink(_CDR_Event $link)
	{
		foreach ($link as $key => $value) {
			if ($this->offsetExists($key)) {
				$this->privateSet($key, $value);
			}
		}
		
	}
	
	public function setHistory($force = FALSE)
	{
//		$curvalset = array(	"history_id" => $this->history_id,
//							"agent_id" => $this->agent_id,
//							"contact_id" => $this->application_contact_id,
//							"event" => $this->pbx_event,
//							"result" => $this->result,
//							"altered" => array_keys($this->altered)
//							);
//							
//		eCash_PBX::Log()->write( __METHOD__ ."() " . var_export($curvalset,true));
		
//		if ($force === true) {
			if (!($this->hist instanceof eCash_PBX_History)) {
				$this->hist = eCash_PBX_History::Factory($this->pbx, $this->application_contact_id);			
			}

//  // here for testing
//		$query = " -- ecash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
//				INSERT INTO pbx_history
//					(
//					date_created,
//					company_id,
//					application_id,
//					agent_id,
//					application_contact_id,
//					phone,
//					pbx_event,
//					result
//					)
//				VALUES
//					(
//					now(),
//					" . $this->pbx->getCompanyId() . ",
//					{$this->application_contact_id},
//					{$this->agent_id},
//					{$this->application_contact_id},
//					{$this->application_contact_id},
//					'{$this->pbx_event}',
//					'" . serialize($this->result) . "'
//					)
//		";
//		
//		$this->pbx->getSQL()->Query($query);
//		
//		$this->history_id =  $this->pbx->getSQL()->Insert_Id();
//		$this->altered = array();
//					
//		}
		
		if (!isset($this->history_id) && $this->pbx_event && $this->result) { //&& EXECUTION_MODE == 'LIVE') {

			if (!($this->hist instanceof eCash_PBX_History)) {
				$this->hist = eCash_PBX_History::Factory($this->pbx, $this->application_contact_id);			
			}
			
			$this->hist->setAgent($this->agent_id);
			$this->hist->setContact($this->application_contact_id);
			$this->history_id = $this->hist->addHistory($this->pbx_event, $this->phone, $this->result);
			
			$this->altered = array();
			
		} elseif (isset($this->history_id) && count($this->altered) ) { //&& EXECUTION_MODE == 'LIVE')) {
			
			$upd_sql_a = array();
			foreach ($this->altered as $index) {
				switch ($index) {
					case "application_contact_id":
						$upd_sql_a[] = "application_id = (select application_id from application_contact where application_contact_id = {$this->application_contact_id} limit 1)";
						
					default:
						$upd_sql_a[] = "{$index} = " . $this->pbx->getSQL()->Intelligent_Escape($this->{$index});
				}
			}
			
			$upd_sql = implode(", ", $upd_sql_a);
			
			$query = " -- ecash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
				UPDATE pbx_history SET {$upd_sql} WHERE pbx_history_id = {$this->history_id}
				";
			
			eCash_PBX::Log()->write($query);
			
			$this->pbx->getSQL()->Query($query);
			
			$this->altered = array();			
			
		}
				
	}
	
}


eCash_Event::Register("ASTERISK_EVENT_NEWCHANNEL", new eCash_PBX_Asterisk_EventHandler);
//eCash_Event::Register("ASTERISK_EVENT_ORIGINATE", new eCash_PBX_Asterisk_EventHandler);
//eCash_Event::Register("ASTERISK_EVENT_RESPONSE", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_NEWCALLERID", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_NEWSTATE", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_LINK", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_UNLINK", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_HANGUP", new eCash_PBX_Asterisk_EventHandler);
eCash_Event::Register("ASTERISK_EVENT_DIAL", new eCash_PBX_Asterisk_EventHandler);
//eCash_Event::Register("ASTERISK_EVENT_STATUS", new eCash_PBX_Asterisk_EventHandler);

?>
