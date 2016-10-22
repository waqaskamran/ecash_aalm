<?php
/**
 * Provides an API to allow business rules to control when a given crond task
 * should be run. The business rule must have the following parameters:
 * - Sunday
 * - Monday
 * - Tuesday
 * - Wednesday
 * - Thursday
 * - Friday
 * - Saturday
 * - Holiday
 *
 * Once you create a CronScheduler object simply call Define_Task() to add
 * tasks to the scheduler and then call Main() to launch the appropriate
 * tasks.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class CronScheduler
{

	/**
	 * Server object to be used by the tasks
	 *
	 * @var Server
	 */
	private $server;
	
	/**
	 * Holds a list of tasks to be completed
	 *
	 * @var array
	 */
	private $tasks = array();

	/**
	 * An indicator for whether there was a failure in one of the processes
	 *
	 * @var boolean
	 */
	static $has_failure = false;
	
	public function __construct(Server $server)
	{
		$this->server = $server;
	}


	/**
	 * Launches the CronScheduler.
	 *
	 * This function will execute any tasks that are allowed for the current
	 * day.
	 *
	 */
	public function Main($log) {
		foreach ($this->tasks as $task) 
		{
			/** @var $task CronScheduler_Task */
			$log->Write("Running Process: " . $task->Get_Function_Name());
			$task->Run($log);
		}
	}

	/**
	 * Adds a task to the scheduler.
	 *
	 * @param ECash_Nightly_Event $task
 	 * @return CronScheduler_Task
	 */
	public function Add_Task(ECash_Nightly_Event $task)
	{
		$task->setServer($this->server);
		$task->setCompanyShort($this->server->company);
		$task->setCompanyId($this->server->company_id);

		$this->tasks[] = new CronScheduler_Task($task);
		return $task;
	}

}

/**
 * This class contains all of the functionality to properly execute a task
 * using information passed to the task via CronScheduler::Define_Task()
 *
 * @see CronScheduler::Define_Task()
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class CronScheduler_Task {
	/**
	 * Provides a cached instance of an ACH object
	 *
	 * @see CronScheduler::Get_ACH()
	 * @var ACH
	 */
	static private $ach;

	/**
	 * Provides a cached instance of a business rule object
	 *
	 * @see CronScheduler::Get_Business_Rules()
	 * @var Business_Rule
	 */
	static private $business_rules;

	/**
	 * Name of the function containing the functionality for the task.
	 * Can be either a string or a callback array.
	 *
	 * @var mixed
	 */
	protected $function_name;

	/**
	 * Short name of the business rule controlling scheduling for this task.
	 *
	 * @var string
	 */
	protected $business_rule_name;

	/**
	 * The label to use for the task's timer.
	 *
	 * @var string
	 */
	protected $timer_name;

	/**
	 * The label to use for the task's process log.
	 *
	 * @var string
	 */
	protected $process_log_name;

	/**
	 * A flag indicating whether or not a transaction should be used with this task.
	 *
	 * @var bool
	 */
	protected $use_transaction;

	/**
	 * An array containing a callback and an array of paramters which will be
	 * used if a task exceptions out.
	 *
	 * @var array
	 */
	protected $failure;

	/**
	 * The ECash_Nightly_Event task object to run
	 *
	 * @var ECash_Nightly_Event
	 */
	protected $task;


	
	
	
	/**
	 * Creates a new task. Should not be called directly.
	 *
	 * @param ECash_Nightly_Event $task
	 */
	public function __construct(ECash_Nightly_Event $task) {
		
		$this->task = $task;
		
		$this->function_name = get_class($task);
		$this->business_rule_name = $task->getBusinessRuleName();
		$this->timer_name = $task->getTimerName();
		$this->process_log_name = $task->getProcessLogName();
		$this->use_transaction = $task->getUseTransactionFlag();
	}

	/**
	 * Sets a function to run with given parameters whenever a task fails with
	 * an exception.
	 *
	 * @param mixed $function
	 * @param string $parameters
	 */
	public function Set_Failure($function, $parameters) {
		$this->failure = array('function' => $function, 'parameters' => $parameters);
	}

	/**
	 * Executes the failure function. This should never be called directly.
	 *
	 */
	protected function Run_Failure() {
		if (!empty($this->failure)) 
		{
			call_user_func_array($this->failure['function'], $this->failure['parameters']);
		}
	}

	/**
	 * Runs a given task (if scheduled)
	 *
	 */
	public function Run($log) {

		$db = ECash::getMasterDb();
		if (!$this->Should_Run_Task()) 
		{
			$log->Write("Not supposed to run {$this->function_name} today");
			return;
		}
		$this->Start_Timer();
		try 
		{
			$this->Set_Process_Status('started');
			$log->Write("Starting $this->function_name");
			if ($this->use_transaction) $db->beginTransaction();

			$this->task->run();

			if ($this->use_transaction) $db->commit();
			$this->Set_Process_Status('completed');
		} 
		catch (Exception $e) 
		{
			CronScheduler::$has_failure = true;
			$log->Write("Cron Task Failed: {$this->function_name}");
			$log->Write($e->getMessage());
			$log->Write($e->getTraceAsString());
			$this->Run_Failure();
			if ($this->use_transaction) $db->rollBack();
			$this->Set_Process_Status('failed');
		}
		$this->Stop_Timer();
	}

	/**
	 * Starts the task's timer if necessary.
	 */
	protected function Start_Timer() {
		if (!empty($this->timer_name)) 
		{
			$this->Get_Server()->timer->Timer_Start($this->timer_name);
		}
	}

	/**
	 * Stops the task's timer if necessary.
	 */
	protected function Stop_Timer() {
		if (!empty($this->timer_name)) 
		{
			$this->Get_Server()->timer->Timer_Stop($this->timer_name);
		}
	}

	/**
	 * Sets the task's process status.
	 *
	 * @param string $status
	 */
	protected function Set_Process_Status($status) {
		if (!empty($this->process_log_name)) 
		{
			$this->Get_ACH()->Set_Process_Status($this->process_log_name, $status);
		}
	}

	/**
	 * Returns an instance of the server object.
	 *
	 * @return Server
	 */
	private function Get_Server() {
		return $this->task->getServer();
		//return $GLOBALS['server'];
	}

	/**
	 * Returns an instance of the ACH class.
	 *
	 * @return ACH
	 */
	private function Get_ACH() {
		if (empty(self::$ach)) 
		{
			//self::$ach = new ACH($this->Get_Server()); 
			self::$ach = new ACH_Utils($this->Get_Server());
		}

		return self::$ach;
	}
	
	public function Get_Function_Name()
	{
		return $this->function_name;
	}
	
	/**
	 * Determines whether or not a given task should run based on that task's
	 * business rules.
	 *
	 * @param CronScheduler_Task $task
	 * @return bool
	 */
	private function Should_Run_Task() {
		$rules = $this->Get_Business_Rules();
		
		$time = time();
		$today = date('Y-m-d', $time);
		$named_day = date('l', $time);
		
		// Cheating.. If the rule is set to NULL we're cheating
		// and not creating a business rule for it, so just run it.
		if(is_null($this->business_rule_name))
		{
			$this->Get_Server()->log->Write("Developer was lazy and didn't create a business rule!  Running task anyways..");
			return TRUE;
		}
		
		if(! isset($rules[$this->business_rule_name]))
		{
			$this->Get_Server()->log->Write("Can't find business rule name: {$this->business_rule_name}");
			return FALSE;
		}
		
		$days = $rules[$this->business_rule_name];

		//[#44294] check 'day_special' rule (overrides day of week & holidays)
		if(isset($days['Special Days']))
		{
			$special_days = explode(',', $days['Special Days']);	
			foreach($special_days as $special_day)
			{
				switch($special_day)
				{
					case 'last of month':						
						if(date('t', $time) == date('j', $time))
						{
							$this->Get_Server()->log->Write("Running task for special day 'last of month'");
							return TRUE;
						}
						break;
				}
			}
		}
		
		if (strtolower($days[$named_day]) == 'yes') 
		{
			if (strtolower($days['Holidays']) == 'no') 
			{
				$holidays = Fetch_Holiday_List();
				$check_day = strtotime("+1 day", $today);
				if (in_array($check_day, $holidays)) 
				{
					return FALSE;
				} 
				else 
				{
					return TRUE;
				}
			} 
			else 
			{
				return TRUE;
			}
		} 
		else 
		{
			return FALSE;
		}
	}

	/**
	 * Returns an instance of the Business_Rules object
	 *
	 * @return Business_Rules
	 */
	private function Get_Business_Rules() 
	{
		if (empty(self::$business_rules)) 
		{

			$business_rules = new ECash_Business_Rules(ECash::getMasterDb());
			$loan_type_id = $this->Get_Company_Loan_Type($business_rules);
			$rule_sets = $business_rules->Get_Rule_Sets();
			$rule_set_id = 0;
			foreach ($rule_sets as $rule_set) 
			{
				if ($rule_set->loan_type_id == $loan_type_id && (strpos($rule_set->name, 'Nightly Task Schedule') !== FALSE)) 
				{
					$rule_set_id = $rule_set->rule_set_id;
				}

			}
			if ($rule_set_id) 
			{
				self::$business_rules = $business_rules->Get_Rule_Set_Tree($rule_set_id);
			}
		}

		return self::$business_rules;
	}

	/**
	 * Returns the ID for the standard loan type for the current company.
	 *
	 * @param Business_Rules $business_rules
	 * @return int
	 */
	private function Get_Company_Loan_Type(ECash_Business_Rules $business_rules) {
		$company_id = $this->Get_Server()->company_id;
		$loan_types = $business_rules->Get_Loan_Types($company_id);

		
		foreach ($loan_types as $type) 
		{
			if ($type->name == 'Offline Processing Rules') 
			{
				return $type->loan_type_id;
			}
		}

		return 0;
	}

	/**
	 * Determines whether or not the given date is a holiday.
	 *
	 * @param int $day UNIX timestamp
	 * @return bool
	 */
	private function Is_Holiday($day = null) 
	{
		if (empty($day)) 
		{
			$day = date('Y-m-d', time());	
		}

		// Check holidays
		$holidays = Fetch_Holiday_List();
		if (in_array($day, $holidays)) 
		{
			return false;
		}

		//check weekdays
		$named_day = date('l',strtotime($day));
		if (in_array($named_day, array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'))) 
		{
			return true;
		} 
		else 
		{
			return false;
		}
	}
}

?>
