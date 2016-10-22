<?php

require_once(SQL_LIB_DIR."/queues.lib.php");
require_once(SERVER_CODE_DIR."/email_queue.class.php");

class Queue_Configuration
{

protected $queues;
protected $all_queues = array();
protected $application_queues = array();
protected $automated_queues = array();
protected $sort_orders = array();
protected $multi_queues = array();
protected $time_dependents = array();
protected $server = null;
protected $request = null;

public function __toString() {
return "Queue_Configuration";
}
	/**
	 * Create a new queue config object.
	 *
	 * @param Array $queues
	 */
	public function __construct()
	{

	}
	
	/**
	 * A function to Add A Queue
	 *
	 * @param Queue $queue
	 */
	public function addQueue($queue)
	{
		$this->queues[$queue->module][$queue->mode][$queue->short_name] = $queue;

		if(!in_array($queue->long_name, $this->all_queues) && $queue->should_add_to_all_queues_list())
		{
			$this->all_queues[] = $queue->long_name;
		}

		if(!in_array($queue->long_name, $this->application_queues) && $queue->should_add_to_application_queues_list())
		{
			$this->application_queues[] = $queue->long_name;
		}
		
		if($queue->is_automated && !in_array($queue->long_name, $this->automated_queues))
		{
			$this->automated_queues[] = $queue->long_name;
		}

		if(!isset($this->sort_orders[$queue->long_name]))
		{
			$this->sort_orders[$queue->long_name] = $queue->sort_order;
		}

		if($queue->is_multi && !in_array($queue->long_name, $this->multi_queues))
		{
			$this->multi_queues[$queue->short_name] = $queue->long_name;
		}

		if($queue->use_business_time && !in_array($queue->long_name, $this->time_dependents))
		{
			$this->time_dependents[] = $queue->long_name;
		}
	}

	public function __get($name)
	{
		return $this->$name;
	}
	/**
	 * A function to return the queues for a specific module and mode.
	 *
	 * @param Queue $queue
	 */
	public function getQueues($module_name, $mode)
	{
		if (!isset($this->queues[$module_name][$mode])) return null;
		return $this->queues[$module_name][$mode];
	}

    /**
     * A function to return a list of all the queues 
     */
    public function getApplicationQueues()
    {
        return $this->application_queues;
    }

    /**
     * A function to return a list of all the queues 
     */
    public function getAllQueues()
    {
        return $this->all_queues;
    }

	/**
	 * A function to return an Array of automated queues.
	 *
	 */
	public function getAutoQueues()
	{
		return $this->automated_queues;
	}

	/**
	 * A function to return an Array of queue sort orders.
	 *
	 */
	public function getSortOrders()
	{
		return $this->sort_orders;
	}

	/**
	 * A function to return an Array of Multi Company queues.
	 *
	 */
	public function getMultiCompanyQueues()
	{
		return $this->multi_queues;
	}

	/**
	 * A function to return whether or not a queue uses Business time.
	 *
	 * @param String $queue_name The name of the queue to check
	 */
	public function isTimeDependent($queue_name)
	{
		if(in_array($queue_name, $this->time_dependents))
		{
			return TRUE;
		}

		return FALSE;
	}

}

class Queue
{
	/**
	 * Used for determining which module the queue should
	 * be displayed in.
	 *
	 * @var string $module
	 */
	public $module;
	
	/**
	 * Used for determining which mode the queue should
	 * be displayed in.
	 *
	 * @var string $mode
	 */
	public $mode;

	/**
	 * Used for determining which module the queue should
	 * be linked to when accessed directly.  This is used
	 * as an override to the default $module for things
	 * like the Fraud module where the queue is displayed
	 * in both the Fraud and High Risk modes, but needs to
	 * point to it's own mode.
	 *
	 * @var string $link_module
	 */
	public $link_module;
	
	/**
	 * Used for determining which module the queue should
	 * be linked to when accessed directly.  This is used
	 * as an override to the default $mode for things
	 * like the Fraud module where the queue is displayed
	 * in both the Fraud and High Risk modes, but needs to
	 * point to it's own mode.	 *
	 * @var string $link_mode
	 */
	public $link_mode;
	
	public $short_name;
	public $long_name;
	public $display_name;
	public $sort_order = "ASC";
	public $is_multi = FALSE;
	public $use_business_time = TRUE;
	public $position_override = NULL;
	public $is_automated = TRUE;
	public $use_loan_type_restrictions = TRUE;


	public function __tostring()
	{
		return "Module {$this->module} mode {$this->mode} queue {$this->short_name} or {$this->long_name} as {$this->display_name}";
	}
	
	/**
	 * Create a new queue object
	 *
	 * @param String $module_name
	 */
	public function __construct($module = NULL, $mode = NULL, $short_name = NULL, $long_name = NULL, $display_name = NULL)
	{
 		if($module && $mode && $short_name && $long_name)
 		{
			$this->module = $module;
			$this->mode   = $mode;

			$this->link_module = $module;
			$this->link_mode   = $mode;

			$this->short_name   = $short_name;
			$this->long_name    = $long_name;
			$this->display_name = $display_name;
 		}
		
 		$this->action = 'get_next_application';
	}

	/**
	 * Get the count of apps in this queue
	 * @param server - required only if config HAS_LOANTYPE_RESTRICTION is true
	 */
	public function getCount()
	{
		// The logic is inverse for the call.  If we use Multi, the argument
		// of FALSE tells count_queue() not to include the company_id,
		// and if we don't use multi, to include the company_id
		$use_multi = ($this->is_multi == TRUE) ? FALSE : TRUE;
		
        if(ECash::getConfig()->HAS_LOANTYPE_RESTRICTION && $this->use_loan_type_restrictions === TRUE)
        {
			if ($this->server == null) 
			{
				throw new Exception("Queue class, getCount function - if config HAS_LOANTYPE_RESTRICTION you must call this object with SetServer(Server) before you call getCount ");
			}

            // Only get counts for loan_types that this agent has access to
            return count_queue($this->long_name, $use_multi, $this->server);
        }
        else
        {
            return count_queue($this->long_name, $use_multi);
        }
	}

	public function addParameters(Array $parameters)
	{
		foreach($parameters as $parameter => $value)
		{
			$this->{$parameter} = $value;
		}
		return TRUE;
	}

    /**
     * SetServer - stub for consistency in interface
     * 
     * @param $server - The server object for this object to use.
     */
    public function SetServer($server)
    {
        return $this->server = $server;
    }
    /**
     * SetRequest - setting our local server
     * 
     * @param $request - The request array for this object to use.
     */
    public function SetRequest($request)
    {
        return $this->request = $request;
    }

	public function should_add_to_application_queues_list () {
		return true;
	}

	public function should_add_to_all_queues_list () {
		return true;
	}

	// Stub
	public function handle_actions()
	{
		return false;
	}
}

class Email_Queue extends Queue
{

	private $queue_object;

	/**
	 * Create a new queue object
	 *
	 * @param String $module_name
	 */
	public function __construct($module = NULL, $mode = NULL, $short_name = NULL, $long_name = NULL, $display_name = NULL)
	{
		parent::__construct($module, $mode, $short_name, $long_name, $display_name);
		
		$this->action = 'get_next_email';
		$this->queue_object = null;
	}

	protected function getQueueObject()
	{
		if ($this->queue_object == null && $this->server != null && $this->request != null) {
			$this->queue_object = new Incoming_Email_Queue($this->server, $this->request);
		} 
		return $this->queue_object;
	}
	/**
	 * Get the count of apps in this queue
	 * @param server - required only if config HAS_LOANTYPE_RESTRICTION is true
	 */
	public function getCount()
	{
		return $this->getQueueObject()->Fetch_Queue_Count($this->short_name);
	}

    /**
     * SetServer - translator for incoming email queue
     * 
     * @param $server - The server object for this object to use.
     */
    public function SetServer($server)
    {

		parent::SetServer($server);
		if ($this->request != null && $this->server != null)
		{
   			return $this->getQueueObject()->SetServer($this->server);
		}
		return null;
    }

    /**
     * SetRequest - translator for incoming email queue object
     * 
     * @param $request - The request array for this object to use.
     */
    public function SetRequest($request)
    {
		parent::SetRequest($request);
		if ($this->request != null && $this->server != null)
		{
   			return $this->getQueueObject()->SetRequest($this->request);
		}
		return null;
    }

	// translate call to queue object within
	public function handle_actions($action)
	{
		$this->getQueueObject()->handle_actions($action);
	}

	/**
	 * An overridable function to Add A Queue to the all queues list.
	 *
	 * @param Queue $queue
	 */
	public function should_add_to_all_queues_list () {
		return true;
	}

	public function should_add_to_application_queues_list () {
		return false;
	}

	public function __toString() {
		return "Email_Queue " . $this->long_name;
	}
}

?>
