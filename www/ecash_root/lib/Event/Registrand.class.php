<?php
/**
 * eCash_Event_Registrand
 * A general event mechanism to throw, catch, and handle applications events for eCash 3.0
 *
 * Created on Nov 8, 2006
 *
 * @package eCash_Event
 * @category Application Event Handler
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

class eCash_Event_Registrand {
	
	protected $event;
	protected $handler;
	protected $order 			= 0;
	protected $mode 			= eCash_Event::Now;
	protected $optional_data 	= array();
	
	public function setEvent($event_name)
	{
		$this->event = $event_name;	
	}
	
	public function getEvent()
	{
		return $this->event;
	}
	
	public function setOrder($order)
	{
		if(!is_numeric($order)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: schedule order election must be numeric");
		}
		
		$this->order = $order;
		
	}
	
	public function getOrder()
	{
		return $this->order;
	}
	
	public function setMode($mode)
	{
		if(!in_array($mode,array(eCash_Event::Now, eCash_Event::EndOfScript, eCash_Event::EndOfQueue, eCash_Event::Schedule))) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: Invalid Schedule mode");
		}
		
		$this->mode = $mode;
		
	}
	
	public function getMode()
	{
		return $this->mode;
	}
	
	public function setHandler(eCash_EventHandler $handler)
	{
		$this->handler = $handler;	
	}
	
	public function getHandler()
	{
		return $this->handler;
	}
	
	public function addData($data, $replace = TRUE)
	{
		$od = $this->optional_data;
		
		if(!is_array($data)) 
		{
			throw new InvalidArgumentException(__METHOD__ . " Error: Optional Data Array must be an array");
		}
		
		foreach ($data as $key => $value) 
		{
			if(is_numeric($key)) 
			{
				throw new UnexpectedValueException(__METHOD__ . " Error: Optional Data Array must be keyed by strings");
			}
			
			$od[$key] = $value;
			
		}
		
		if($replace === TRUE) 
		{
			$this->optional_data = $od;
			
		} else

		{
			return $od;
			
		}
		
	}
	
	public function setData($data)
	{
		$this->resetData();
		$this->addData($data);
	}
	
	public function resetData()
	{
		$this->optional_data = array();
	}
	
	public function getData()
	{
		return $this->optional_data;
	}
}
