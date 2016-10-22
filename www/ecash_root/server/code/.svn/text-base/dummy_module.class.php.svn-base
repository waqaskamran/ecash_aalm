<?php
require_once('module_interface.iface.php');

/**
 * Used to fake the Module Interface.  Currently used in the Edit Class when
 * modifying customer information and running against the Fraud check.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
class Dummy_Module implements Module_Interface
{
	private $server;
	private $request;
	private $module_name;
	
	public function __construct(Server $server, $request, $module_name)
	{
		$this->server = $server;
		$this->request = $request;
		$this->module_name = $module_name;
	}
	
	public function Main()
	{
		
	}
	
	public function Register_Action_Handler($object, $method) {
              $this->callbacks[] = Array($object, $method);
    }
}

?>