<?php

require_once(dirname(__FILE__) . '/../www/config.php');
require_once(LIB_DIR.'common_functions.php');
require_once(CLIENT_CODE_DIR . 'display_application.class.php');

// Do some trickery to fake the parent DFA class so we can get the DFA members.
ini_set('include_path', dirname(__FILE__) . '/:' . ini_get('include_path'));

require_once('dfa.php');
require_once('ecash_dfa.php');

$graph_dir = CUST_DIR . 'www/dfa-graphs/';

if (!file_exists($graph_dir))
{
	mkdir($graph_dir, 0777);
}

chdir(CUSTOMER_LIB);
foreach (glob('*_dfa.php') as $dfa_file)
{
	// Get information
	$dfa_return = @include_once(CUSTOMER_LIB . $dfa_file);

	if (!$dfa_return)
	{
		echo 'Unable to process ' . $dfa_file . "\n";
		continue;
	}
}

ECash::getFactory()->getClass('DFA_CompleteSchedule');
$returns_dfa = ECash::getFactory()->getClassString('DFA_Returns');
new $returns_dfa(new stdClass());

foreach (get_declared_classes() as $class)
{
	if (!is_subclass_of($class, 'DFA') && !is_subclass_of($class, 'ECash_DFA'))
	{
		continue;
	}

	echo "{$class}\n";

	$graph_path = $graph_dir . $class;

	$null = null;
	$dfa = new $class($null);
	$dot = getDiGraph($dfa->final_states, $dfa->tr_functions, $dfa->transitions);

	// Output information
	file_put_contents($graph_path . '.dot', $dot);
	chmod($graph_path . '.dot', 0666);

	// Create image
	exec('dot -Tpng -o "' . $graph_path . '.png" "' . $graph_path . '.dot"');

	echo 'Processsed ' . $class . "\n";
}

function getDiGraph($final_states, $tr_functions, $transitions)
{
	$digraph = "digraph G {\nrankdir=TB;\n\n";

	foreach ($final_states as $final_state)
	{
		$digraph .= '"' . $final_state . "\" [style=filled,color=green];\n";
	}

	$digraph .= "\n";

	foreach ($tr_functions as $state => $name)
	{
		$digraph .= '"' . $state . "\" [label = \"{$state}: {$name}\" ];\n";
	}

	$digraph .= "\n";

	foreach ($transitions as $start => $results)
	{
		foreach ($results as $value => $end)
		{
			$digraph .= $start . ' -> ' . $end . " [ label = \"{$value}\" ];\n";
		}
	}

	$digraph .= "\n}\n";

	return $digraph;
}

?>
