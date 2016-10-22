<?php

declare (ticks = 1000);

require_once LIB_DIR . "/PBX/PBX.class.php";

$pbx = new eCash_PBX($server);
$ast = $pbx->getPBX();

function keep_listening(eCash_PBX_Asterisk $ast)
{
	if (!$ast->checkEventReader())
	{
		$ast->readEvents();
	}
	
}

register_tick_function("keep_listening", $ast);

function Main($args)
{
	global $ast;
	
	while (!$ast->checkEventReader()) { 
		$ast->readEvents();
		usleep(10000);
	}
}