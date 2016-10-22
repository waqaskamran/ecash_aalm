<?php

/**
 * Mock the DFA parent class to fake public access. This allows the grapher to get information from
 * each individual DFA directly.
 */
class DFA
{
	public $initial_state = 0;
	public $final_states = array();
	public $tr_functions = array();
	public $transitions = array();

	function __construct() {}
}

?>
