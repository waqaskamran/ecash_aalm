<?php
/**
 * eCash_Event
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

if(!defined("eCash_Event_DIR")) define("eCash_Event_DIR", realpath(dirname(__FILE__)));

require_once eCash_Event_DIR . "/Handler.class.php";
require_once eCash_Event_DIR . "/Queue.class.php";
require_once eCash_Event_DIR . "/Registry.class.php";
require_once eCash_Event_DIR . "/Registrand.class.php";

class eCash_Event {
	
	/**
	 * bitwise (yes bitwise, tho it doesn't look it) Scheduling constants
	 */
	const Now			= 1;
	const EndOfScript 	= 2;
	const EndOfQueue	= 3;
	const Schedule		= 4;
	
	static private $log_context;
	
	static public function Trigger(Server $server, $event_name, $application_id = NULL, $optional_data_array = array())
	{
		/**
		 * open event "thread"
		 */
		eCash_Event_Queue::beginThread($event_name);
		
		/**
		 * find event in registry
		 */
		$events = eCash_Event_Registry::getEvent($event_name);
		foreach ($events as $registrand) 
		{
			
			/**
			 * validate passed data
			 */
			if(is_array($optional_data_array)) $registrand->getHandler()->validate($optional_data_array);
			
			/**
			 * form callback and put into queue
			 */
			eCash_Event_Queue::Schedule($server, $registrand, $application_id, $optional_data_array);
			
		}

		try 
		{
		
			eCash_Event_Queue::runThread($event_name);
		
		} 
		catch (UnderflowException $u) 
		{
			// TODO Log that there is no registered handler for this event
//			self::Log()->Write(__METHOD__ . " Notice:  There is not registered handler for the event {$event_name}");
			self::Log()->Write(__METHOD__ . " Notice: " . $u->getMessage());
		}
			
		/**
		 * close event "thread"
		 */
		eCash_Event_Queue::endThread($event_name);
		
	}

	static public function Register($event_name, eCash_EventHandler $callback, $election = 0, $schedule = eCash_Event::Now, $optional_data = NULL) 
	{
		return eCash_Event_Registry::Register($event_name, $callback, $election, $schedule, $optional_data);
	}
	
	static public function Log()
	{
		if (!class_exists('Applog_Singleton')) require_once 'applog.singleton.class.php';
		
		if(!self::$log_context) self::$log_context = ( isset($_SESSION["Server_state"]["company"]) ) ? strtoupper($_SESSION["Server_state"]["company"]) : "";
		
		return Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY."/application_events", APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, self::$log_context);

	}
	
}
