<?php
// Deterministic Finite Automaton

class DFA
{	
	protected $states;
	protected $tr_functions;
	protected $transitions;
	protected $initial_state;	
	protected $final_states;	
	protected $descriptions;
	protected $log_prefix;
	protected $details;
	protected $application;
	// For a run
	protected $current_state;
	
	// For logging
	protected $log;

	public function __construct()
	{
		// Hash-index the lookup arrays, 
		// b/c it's faster for lookup speed
		$this->details = array();
		$this->states = array_flip($this->states);  
		$this->final_states = array_flip($this->final_states);
		if (!isset($this->states[$this->initial_state])) throw new Exception("Initial state not in state list");
	}

	public function run($parameters)
	{
		// Add Application ID to Logging!!
		$this->log_prefix = '';
		if(isset($parameters->application_id))
		{
			$this->application = ECash::getApplicationById($parameters->application_id);
			$this->log_prefix = "[AppId:{$parameters->application_id}]";
		}
		
		if (is_array($parameters)) $parameters = (object) $parameters;

		if (isset($parameters->log)) $this->log = $parameters->log;
		
		$this->current_state = $this->initial_state;
		
		$parameters->current_state = $this->initial_state;
		
		$this->Log("Starting State: {$this->current_state}");
		while (!isset($this->final_states[$this->current_state]))
		{
			$this->Log("Current State: {$this->current_state} ({$this->tr_functions[$this->current_state]})");
			$function = $this->tr_functions[$this->current_state];
			$result = $this->$function($parameters);
			$this->Log("For Function ".$this->tr_functions[$this->current_state]. ": {$result}");
			$new_state = $this->transitions[$this->current_state][$result];
			if ($new_state === null) 
			{
				throw new Exception("$log_prefix Did not return a valid value for this state");
			} 
			else 
			{
				$this->Log("Transition: {$this->current_state} => {$new_state}");
				$this->current_state = $new_state;
				$parameters->current_state = $new_state;
			}
		}
		$this->Log("Returning Data from end state {$this->current_state} {$this->tr_functions[$this->current_state]}");
		return ($this->take_action($this->current_state, $parameters));		
	}

	public function getDFADetails()
	{
		return $this->details;
	}
	// I would like this to be able to recreate the DFA instance from the constituent
	// pieces, perchance our documentation gets lost or whatever.
	public function generate_dfa_map() {
		if (!isset($this->states) || !isset($this->initial_state) ||
		    !isset($this->final_states) || !isset($this->tr_functions) ||
		    !isset($this->transitions)) return null;

		$str = "Initial state: {$this->initial_state}\n\n";
		foreach($this->states as $s) 
		{
			$str .= "State {$s}: {\n";
			if (isset($this->descriptions))
				$str .= "Description: {$this->descriptions[$s]}\n";
			if (isset($this->final_states[$s])) 
			{
				$str .= "Final state: Yes\n";
			} 
			else 
			{
				$str .= "Final state: No\n";
				$str .= "test function: {$this->tr_functions[$s]}\n";
				foreach ($this->transitions[$s] as $result => $tr) 
				{
					$str .= "\t{$result} -> {$tr}\n";
				}			       
			}
			$str .= "}\n\n";
		}
		return $str;
	}

	// For now, we completely separate out the different actions to take in the different states.
	// It may be slower, but it's logically easier to debug.
	function take_action($state, $parameters) 
	{
		$response_function = empty($this->tr_functions[$state])? "State_{$state}" : $this->tr_functions[$state];
		return( $this->$response_function($parameters) );
	}

	public function SetLog($log) {
		$this->log = $log;
	}
	
	public function Log($message, $level = LOG_DEBUG) {
		
		if(! isset($this->log))
			return;
		
		// This will put the appropriate prefix in.
		if(isset($this->log_prefix)) 
		{

			$this->log->Write($this->log_prefix . ' ' . $message, $level );
			$this->details[] = ($this->log_prefix . ' ' . $message);
		} 
		else 
		{
			$this->log->Write($message, $level );
			$this->details[] = ($message);
		}
	}
	
}
?>
