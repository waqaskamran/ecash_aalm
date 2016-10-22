<?php
/**
 * eCash_Event_Queue
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

class eCash_Event_Queue {
	
	static private $threadStack = array();
	static private $queueStack = array();
	static private $endOfScriptQueue;
	
	private $runningQueue = array();
	
	static public function Schedule(Server $server, eCash_Event_Registrand $registrand, $application_id, $optional_data_array = array())
	{
		switch (true) 
		{
			case ($registrand->getMode() & eCash_Event::Schedule ):
				/**
				 * add to cron schedule
				 */
				throw new Exception(__METHOD__ . " Error: Future Scheduling not yet implemented.");
				break;
						
			case ($registrand->getMode() & eCash_Event::EndOfQueue && self::getSpawningThread() != NULL ):
				/**
				 * get spawning thread and add event to it's queue
				 */
				if (!(self::$queueStack[self::getSpawningThread()] instanceof eCash_Event_Queue)) 
				{
					throw new UnderflowException(__METHOD__  . " Error: Unknown queue stack error. The thread listed as having spawned this trigger does not exist.");
				}
						
				self::$queueStack[self::getSpawningThread()]->qPush($server, $registrand, $application_id, $optional_data_array);
				
				break;
				
			case ($registrand->getMode() & eCash_Event::EndOfQueue && self::getSpawningThread() == NULL ):
			case ($registrand->getMode() & eCash_Event::Now ):
				/**
				 * add to current currently running queue
				 */
				if (!(self::$queueStack[self::getRunningThread()] instanceof eCash_Event_Queue)) 
				{
					self::$queueStack[self::getRunningThread()] = new eCash_Event_Queue;
				}
						
				self::$queueStack[self::getRunningThread()]->qPush($server, $registrand, $application_id, $optional_data_array);
				
				break;
				
			case ($registrand->getMode() & eCash_Event::EndOfScript ):
				/**
				 * add to end-of-script queue
				 */
				if (!(self::$endOfScriptQueue instanceof eCash_Event_Queue)) 
				{
					self::$endOfScriptQueue = new eCash_Event_Queue;
				}
				
				self::$endOfScriptQueue->qPush($server, $registrand, $application_id, $optional_data_array);
				
				
				break;
						
			default:
				/**
				 * bad mode
				 */
				throw new UnexpectedValueException(__METHOD__ . " Error: mode {$registrand->getMode()} is not valid.");
				
		}
		
	}
	
	static public function getRunningThread()
	{
		return end(self::$threadStack);
	}
	
	static public function getSpawningThread()
	{
		end(self::$threadStack);
		
		if (($curr = prev(self::$threadStack)) !== FALSE) 
		{
			return $curr;
		}
		
		return NULL;
		
	}
	
	static public function beginThread($id)
	{
		if(in_array($id,self::$threadStack)) 
		{
			throw new OutOfBoundsException (__METHOD__ . " Error: possible Recursive Trigger detected");
		}
		
		eCash_Event::Log()->Write(__METHOD__ ." Notice: Beginning thread {$id}");
		
		self::$threadStack[] = $id;
		
	}
	
	static public function endThread($id)
	{
		if(end(self::$threadStack) == $id) 
		{
			array_pop(self::$threadStack);
			
			eCash_Event::Log()->Write(__METHOD__ ." Notice: ending thread {$id}");
		
			return;
		}
		
		throw new OutOfBoundsException (__METHOD__ . " Error: Attempting to complete a thread that is not the currently designated thread.");
		
	}
	
	static public function runThread($id)
	{
		if(end(self::$threadStack) != $id) 
		{
			throw new OutOfBoundsException (__METHOD__ . " Error: Attempting to run a thread that is not the currently designated thread.");
		}
		
		if (!(self::$queueStack[self::getRunningThread()] instanceof eCash_Event_Queue)) 
		{
			throw new UnderflowException(__METHOD__  . " Error: Unknown queue stack error. The thread designated by this trigger does not exist.");
		}
		
		$rq = self::$queueStack[self::getRunningThread()];

		while($rq->qCount() > 0) 
		{
			list($server, $registrand, $application_id, $optional_data_array) = $rq->qShift();

			eCash_Event::Log()->write("Event " . self::getRunningThread() . " Triggered. Handled by " . get_class($registrand->getHandler()) );
			
			/**
			 * run queue item
			 */
			$registrand->getHandler()->setServer($server);
			$registrand->getHandler()->setApplicationId($application_id);
			$registrand->getHandler()->setOptionalData($registrand->addData($optional_data_array, FALSE));
			
			$registrand->getHandler()->execute();
						
		}
		
		$rq = NULL;
		unset(self::$queueStack[self::getRunningThread()]);
		
	}
	
	static public function runEndOfScriptThread()
	{
		if(!(self::$endOfScriptQueue instanceof eCash_Event_Queue)) 
		{
			return;
		}
		
		while(self::$endOfScriptQueue->qCount() > 0) 
		{
			try 
			{
				list($server, $registrand, $application_id, $optional_data_array) = self::$endOfScriptQueue->qShift();

				eCash_Event::Log()->write("End of Script Event Triggered by " . $registrand->getEvent() . " Handled by " . get_class($registrand->getHandler()) );
			
				/**
				 * run queue item
				 */
				$registrand->getHandler()->setServer($server);
				$registrand->getHandler()->setApplicationId($application_id);
				$registrand->getHandler()->setOptionalData($registrand->addData($optional_data_array, FALSE));
				
				$registrand->getHandler()->execute();
				
			} 
			catch (Exception $e) 
			{
				if (class_exists("Server",false) && $server instanceof Server) 
				{
					eCash_Event::Log()->write("Shutdown Function Exception: '" . get_class($e) . "' with message   '" . $e->getMessage() . "' in " . $e->getFile() . ":" . $e->getLine());
				}
			}
						
		}
	}
	
	private function qCount()
	{
		return count($this->runningQueue);
	}
	
	private function qPush(Server $server, eCash_Event_Registrand $registrand, $application_id, $optional_data_array = array())
	{
		if(!is_array($optional_data_array)) $optional_data_array = array();
		
		$args = array($server, $registrand, $application_id, $optional_data_array);
		
		return array_push($this->runningQueue, $args);

	}
	
	private function qPop()
	{	
		return array_pop($this->runningQueue);
	}
	
	private function qShift()
	{
		return array_shift($this->runningQueue);
	}
	
	private function qUnshift(Server $server, eCash_Event_Registrand $registrand, $application_id, $optional_data_array = array())
	{
		$args = array($server, $registrand, $application_id, $optional_data_array);
		
		return array_unshift($this->runningQueue, $args);
	}
	
	
}

register_shutdown_function(array("eCash_Event_Queue", "runEndOfScriptThread"));
