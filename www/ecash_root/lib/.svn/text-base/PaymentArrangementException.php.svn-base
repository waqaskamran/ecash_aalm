<?php

require_once(COMMON_LIB_DIR . 'general_exception.1.php');

/**
 * This exception is thrown when there are problems generating payment arrangements.
 *
 */
class eCash_PaymentArrangementException extends General_Exception 
{
	/**
	 * @var int
	 */
	private $application_id;
	
	/**
	 * @var string
	 */
	private $event_type;
	
	/**
	 * Creates an exception object
	 *
	 * @param string $message
	 * @param int $application_id
	 * @param string $event_type
	 */
	public function __construct($message, $application_id, $event_type = null)
	{
		$this->application_id = $application_id;
		$this->event_type = $event_type;
		
		parent::__construct($message);
	}

	/**
	 * Returns the application id that caused the exception.
	 *
	 * @return int
	 */
	public function getApplicationId()
	{
		return $this->application_id;
	}
	
	/**
	 * Returns the event type name of the event that caused the exception (if 
	 * available.)
	 *
	 * @return string
	 */
	public function getEventType()
	{
		return $this->event_type;
	}
}

?>