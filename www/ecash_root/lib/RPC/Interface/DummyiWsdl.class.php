<?php
/**
 * eCash_RPC_Interface_DummyiWsdl
 * Example Class to Demonstrate eCash_RPC
 *
 * Created on Feb 7, 2007
 *
 * @package eCash
 * @category RemoteProcedure
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once dirname(realpath(__FILE__)) . "/Dummy.class.php"; 

class eCash_RPC_Interface_DummyiWsdl implements eCash_iWSDL 
{

	public static function getRPCMethods()
	{
		$method = new stdClass;
		$method->name = "reflect";
		
		$arg = new stdClass;
		$arg->name = "val";
		$arg->type = "string";
		
		$method->args[] = $arg;
		
		$method->response = new stdClass;
		$method->response->type = "string";
		
		
		$method2 = new stdClass;
		$method2->name = "increment";
		$method2->response->type = "string";	
		
		return array($method, $method2);
		
	}

}

