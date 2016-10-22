<?php
/**
 * eCash_Event_Handler
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

interface eCash_EventHandler {

	/**
	 * validate()
	 * 
	 * @param Array $data_array
	 * @return void
	 * 
	 * @throws RuntimeException
	 */
	public function validate($data_array = array());
	
	/**
	 * setServer()
	 * 
	 * @param Server $server
	 * @return void
	 */
	public function setServer(Server $server);
	
	/**
	 * setApplicationId()
	 * 
	 * @param integer $application_id
	 * @return void
	 */
	public function setApplicationId($application_id);
	
	/**
	 * setOptionalData()
	 * 
	 * @param Array $data_array
	 * @return void
	 * 
	 * @throws RuntimeException
	 */
	public function setOptionalData($data_array = array());
	
	/**
	 * execute()
	 * 
	 * @return void
	 * 
	 * @throws Exception
	 */
	public function execute();
	
}

abstract class eCash_Event_Handler implements eCash_EventHandler {

	protected $required = array();
	protected $type_validate = array();
	
	protected $server;
	protected $application_id;
	protected $optional_data = array();
									
	/**
	 * setServer()
	 * 
	 * @param Server $server
	 * @return void
	 */
	public function setServer(Server $server)
	{
		$this->server = $server;
	}
	
	/**
	 * setApplicationId()
	 * 
	 * @param integer $application_id
	 * @return void
	 */
	public function setApplicationId($application_id)
	{
		$this->application_id = $application_id;
	}
	
	/**
	 * setOptionalData()
	 * 
	 * @param Array $data_array
	 * @return void
	 * 
	 * @throws RuntimeException
	 */
	public function setOptionalData($data_array = array())
	{
		$this->validate($data_array);
		
		foreach($data_array as $name => $value) {
			$this->optional_data[$name] = $value;
		}
		
	}
	
	/**
	 * validate()
	 * 
	 * @param Array $data_array
	 * @return void
	 * 
	 * @throws RuntimeException
	 */
	public function validate($data_array = array())
	{
		if(!is_array($data_array)) {
			throw new RuntimeException(__METHOD__ . " Error: passed validation data must be an associative array.");
		}
		
		foreach($data_array as $name => $value) {
			if(isset($this->type_validate[$name]) && gettype($value) != $this->type_validate[$name]) {
				throw new RuntimeException(__METHOD__ . " Error: passed data {$name} is not of type {$this->type_validate[$name]}");
			}
		}
	}
		
}
