<?php

interface Module_Interface
{
	public function __construct(Server $server, $request, $module_name);

	public function Main();
}

?>