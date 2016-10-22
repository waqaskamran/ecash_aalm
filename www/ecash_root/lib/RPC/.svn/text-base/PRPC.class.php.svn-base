<?php
/**
 * eCash_RPC_PRPC
 * PRPC extension for eCash_RPC
 *
 * Created on Feb 5, 2007
 *
 * @package eCash
 * @category RemoteProcedure
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

if (!defined("eCash_RPC_DIR")) require_once LIB_DIR . "/RPC/RPC.class.php";

require_once COMMON_LIB_DIR ."/../lib5/prpc/server.php";

class eCash_RPC_PRPC extends Prpc_Server {

	protected $functions = array();
	
	protected $class_obj;
	protected $class_name;
	protected $class_constructor_args = array();
	
	protected $persistence = false;
	
	static public function Factory($class_name = NULL, $process = FALSE, $persist = FALSE, $strict = FALSE)
	{
		if($class_name && class_exists($class_name) && is_subclass_of($class_name, __CLASS__)) 
		{
			$obj = new $class_name;
			
		} 
		else 
		{
			$obj = new eCash_RPC_PRPC;
			
		}
		
		$obj->_prpc_strict = $strict;

		if($persist === TRUE) 
		{
			$obj->setPersistence(eCash_RPC::PersistenceSession);
		}
		
		if ($class_name) 
		{
			$obj->setClass($class_name);
		}		
		
		if($process === TRUE) 
		{
			$obj->handle();
		}
			
		return $obj;		
		
	}
	
	
	public function __construct($process = FALSE, $strict = FALSE)
	{

		try 
		{

			parent :: __construct($process, $strict);

		} 
		catch (Exception $e) 
		{

			eCash_RPC::Log("Uncaught " . get_class($e) . ": $e->getMessage() \n---\n" . var_export($e,true). "\n---\n");
			
			$this->_prpc_debug .= ob_get_clean();

			$status = new stdClass;
			$status->message = $e->getMessage();
			$status->code = $e->getCode();
			$status->file = $e->getFile();
			$status->line = $e->getLine();
			$status->trace = $e->getTrace();
			$status->string_trace = $e->getTraceAsString();
//			$status->screen_output = $this->_prpc_debug;

			$pack = $this->_Prpc_Pack(new Prpc_Result ($status, $this->_prpc_debug));

			header("Content-Type: octet/stream");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".strlen($pack));

			echo $pack;
			exit (0);

		}

	}

	/**
	 * this is overridden here b/c of the need to utilize the wrapper in __call
	 */

	function Prpc_Process ()
	{
		try 
		{
			ob_start ();
			
			$call = $this->_Prpc_Unpack ($GLOBALS['HTTP_RAW_POST_DATA']);

			$result = $this->__call($call->method, $call->arg);
		
		} 
		catch (Exception $e) 
		{
			
			eCash_RPC::Log("Uncaught " . get_class($e) . ": $e->getMessage() \n---\n" . var_export($e,true). "\n---\n");
			
			$result = new stdClass;
			$result->message = $e->getMessage();
			$result->code = $e->getCode();
			$result->file = $e->getFile();
			$result->line = $e->getLine();
			$result->trace = $e->getTrace();
			$result->string_trace = $e->getTraceAsString();
			
		}

		$this->_prpc_debug = ob_get_clean ();

		$pack = $this->_Prpc_Pack (new Prpc_Result ($result, $this->_prpc_debug));

		header ("Content-Type: octet/stream");
		header ("Content-Transfer-Encoding: binary");
		header ("Content-Length: ".strlen($pack));

		echo $pack;
		exit (0);
	}

	public function addFunction($functions)
	{
		if (!is_array($functions)) 
		{
			$functions = array($functions);
		}
		
		foreach ($functions as $function) 
		{
			if (!is_callable($function)) 
			{
				throw new Exception;
			}
			
			$this->functions[] = $function;
			
		}		
		
	}
	
	public function getFunctions()
	{
		
	}
	
	public function handle()
	{
		$this->Prpc_Process();
	}	
	
	public function setClass($class_name)
	{
		$args = func_get_args();
		array_shift($args);
		
		if(!class_exists($class_name, FALSE)) 
		{
			throw new Exception;
		}
		$this->class_name = $class_name;
		
		$this->class_constructor_args = $args;
	}
	
	public function setPersistence($mode)
	{
		switch ($mode) 
		{
			case eCash_RPC::PersistenceSession:
				$this->persistence = true;
				break;
			case eCash_RPC::PersistenceRequest:
			default:
				$this->persistence = false;
		}
	}
	
	public function __call($method, $args)
	{
//		eCash_RPC::Log()->write(__METHOD__ ."(): this \n---\n" . var_export($this,true) . "\n---\n");
		
		if ($this->class_name && is_callable(array($this->getClassObj(), $method))) 
		{
			$cb = array($this->getClassObj(), $method);
		} 
		elseif (in_array($method, $this->functions)) 
		{
			$cb = $method;
		} 
		elseif (is_callable(array($this, $method))) 
		{
			$cb = array($this, $method);
		} 
		else 
		{
			throw new BadMethodCallException("PRPC Function {$method} not found.", 500);
		}

		return call_user_func_array($cb, $args);
		
	}
	
	protected function getClassObj()
	{
		
		if (!$this->class_obj && 
			$this->class_name && 
			$this->persistence === TRUE &&
			isset($_SESSION[__CLASS__][serialize(array($this->class_name, $this->class_constructor_args))])
			) 
		{
//			eCash_RPC::Log()->write(__METHOD__ ."(): 1");	
			$this->class_obj = $_SESSION[__CLASS__][serialize(array($this->class_name, $this->class_constructor_args))];
				
		} 
		elseif ( !$this->class_obj && $this->class_name ) 
		{
//			eCash_RPC::Log()->write(__METHOD__ ."(): 2");	
			
			// I hate this:
			for ( $set_args = array() , $i = 0 ; $i < count($this->class_constructor_args) ;  $i++ ) 
			{
				$set_args[] = "\$this->class_constructor_args[{$i}]";
			}
			$ev = "\$this->class_obj = new " . $this->class_name ;
			$ev .= (count($set_args)) ? "(" . implode(", ", $set_args) .");" : ";";
			
			eCash_RPC::Log()->write(__METHOD__ ."()::eval(): $ev");	
						
			eval($ev);
			
			if ($this->persistence === TRUE && $this->class_obj) 
			{
				eCash_RPC::Log()->write(__METHOD__ ."() store in session");	
				
				$_SESSION[__CLASS__][serialize(array($this->class_name, $this->class_constructor_args))] = $this->class_obj;
			}		
			
		}
		
		if ($this->class_name && $this->class_obj instanceof $this->class_name ) 
		{
			return $this->class_obj;			
		} 
		
		throw new Exception (__METHOD__ ." Error: {$this->class_name} could not be initialized.", 500);
		
	}	
	
}
