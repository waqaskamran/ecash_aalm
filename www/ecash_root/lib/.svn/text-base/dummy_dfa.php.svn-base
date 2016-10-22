<?php

require_once('dfa.php');

class DummyDFA extends DFA
{
	const NUM_STATES = 1;

	function __construct()
	{
		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(1);
		$this->initial_state = 0;
		$this->tr_functions = array( 0 => 'dummy');
		$this->transitions = array(0 => array( 0 => 1, 1=> 1));
		parent::__construct();
	}

	function dummy($parameters) { return true; }
	
	function State_1($parameters) { return true; }
	
}
?>