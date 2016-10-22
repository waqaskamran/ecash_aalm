<?php
/**
 * eCash_Event_Registry
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

class eCash_Event_Registry {
	
	static private $registry = array();
	
	static public function Register($event_name, eCash_EventHandler $callback, $election = 0, $schedule = eCash_Event::Now, $optional_data = NULL)
	{
		if($schedule == NULL) 
		{
			$schedule = eCash_Event::Now;
		}
		
		if($election == NULL) 
		{
			$election = 0;
		}
		
		if(!isset($registry[$event_name])) 
		{
			self::$registry[$event_name] = array();
		}
		
		if(!isset($registry[$event_name][$schedule])) 
		{
			self::$registry[$event_name][$schedule] = array();
		}
		
		eCash_Event::Log()->Write(__METHOD__ . " Notice: Registering handler " . get_class($callback) . " for event {$event_name}");
		/**
		 * create registrand
		 */
		$registrand = new eCash_Event_Registrand;
		$registrand->setEvent($event_name);
		$registrand->setMode($schedule);
		$registrand->setOrder($election);
		$registrand->setHandler($callback);
		if (is_array($optional_data)) $registrand->addData($optional_data);
		
		/**
		 * find election order
		 */
		if(!$election) 
		{
			$election = count(self::$registry[$event_name][$schedule]);
		}
		
		/**
		 * place into election order
		 */
		self::sortElection(self::$registry[$event_name][$schedule], $election);
		
		if(!isset(self::$registry[$event_name][$schedule][$election])) 
		{
			self::$registry[$event_name][$schedule][$election] = $registrand;
			
		} else

		{
			throw new LogicException(__METHOD__ . " Error: the event handler for {$event_name}:{$schedule}:{$election} could not be inserted.");
			
		}
		
	}
	
	public static function getRegistry()
	{
		return self::$registry;
		
	}

	public static function getEvent($event_name)
	{
		$event = array();
		if(is_array(self::$registry[$event_name])) 
		{
			foreach (self::$registry[$event_name] as $sublist) 
			{
				$event = array_merge($event, array_reverse($sublist));
			}
		}
		
		return $event;
		
	}
	
	private static function sortElection(&$sublist, $election)
	{
		/**
		 * if space exists, huzzah
		 */
		if(!isset($sublist[$election])) 
		{
			return;
		}
		
		/**
		 * if next space full, shift up to make space
		 */
		if (isset($sublist[($election + 1)])) 
		{
			self::sortElection($sublist, $election + 1);
		}
		
		/**
		 * if next space exists shift existing spot up one
		 */
		if(!isset($sublist[($election + 1)])) 
		{
			$t = $sublist[$election];
			$t->setOrder($election + 1);
			unset($sublist[$election]);
			$sublist[$election + 1] = $t;
			
		} else

		{
			throw new LogicException(__METHOD__ . " Error: the event handler for {$event_name}:{$schedule}:{$election} could not be shifted for insertion.");			
		}
		
	}
	
}
