<?php
/**
 * eCash_RPC_Interface_Dummy
 * Example Class to demonstrate eCash_RPC
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

require_once eCash_RPC_DIR . "/SOAP.class.php"; 

class eCash_RPC_Interface_Dummy 
{

	public $inc = 0;
	
	public function __construct()
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called");
	}
	
	public function reflect($val)
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called: Param: {$val}");
//		eCash_RPC::Log()->write(__METHOD__ . "(): Backtrace\n---\n" . var_export(debug_backtrace(),true) . "\n---\n");
		return $val;
	}
	
	public function increment()
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called");
		return $this->inc++;
	}
	
	public function __wakeup()
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called");
	}
	
	public function __sleep()
	{
		eCash_RPC::Log()->write(__METHOD__ . "() Called");
		return array("inc");
	}
	
}

