<?php
/**
 * eCash_RPC_SOAP
 * SOAP extension for eCash_RPC
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

class eCash_RPC_SOAP extends SoapServer
{
	static public function Factory($class_name = NULL, $process = FALSE, $persist = FALSE, $strict = FALSE)
	{
		
		if($class_name && class_exists($class_name, FALSE) && is_subclass_of($class_name, "SoapServer")) 
		{
			
			$obj = new $class_name;
			
		} 
		else 
		{
			/**
			 * The soap server must be able to load the wsdl,
			 * so create a token that will allow the soap server to bypass authentication
			 */
			$sct_ary = eCash_RPC::genSelfWsdlToken();

			$wsdl_uri = (strpos(ECASH_RPC_URI, "?") !== FALSE) ? ECASH_RPC_URI . "&sct=" . $sct_ary[1] . "&wsdl" : ECASH_RPC_URI . "?sct=" . $sct_ary[1] . "&wsdl";
			
			eCash_RPC::Log(__METHOD__ ."(): Self WSDL URI \n---\n" . var_export($wsdl_uri,true) . "\n---\n");
			
			$obj = new eCash_RPC_SOAP($wsdl_uri);
			
		}
		
		if ($class_name && (!class_exists($class_name, FALSE) || !($obj instanceof $class_name))) 
		{
			$obj->setClass($class_name, $persist);
		}		
		
		if($process === TRUE) 
		{
			$obj->handle();
		}
			
//		eCash_RPC::Log()->write("SoapServer Dump Line ".__LINE__.":\n---\n" . var_export($obj,true) . "\n---\n");
//		eCash_RPC::Log()->write("Backtrace\n---\n" . var_export(debug_backtrace(),true) . "\n---\n");
		return $obj;		
		
	}	
	
	static protected function wsdlReflect($class_name)
	{

		$xClass = new StdClass;

		$refClass = new ReflectionClass($class_name);
		$refMeth = $refClass->getMethods();
		$xClass->methods = array();	
	
		foreach ($refMeth as $meth) 
		{

			if($meth->isPublic() !== TRUE || $meth->isConstructor() || $meth->isDestructor() || strrpos($meth->getName(),"__") === 0) 
			{
				continue;
			}
			
			$xMeth = new StdClass;
			$xMeth->name = $meth->getName();
			
			if($meth->getNumberOfParameters() > 0) 
			{
				$xMeth->args = array();
				
				$refParams = $meth->getParameters();
				foreach ($refParams as $param) 
				{
					$xArg = new StdClass;
					$xArg->name = $param->getName();
					if ($param->isArray()) 
					{
						$xArg->type = "array";
					} 
					else 
					{
						$xArg->type = "string";
					}
					$xMeth->args[] = $xArg;
				}
				
			}

			$xMeth->response = new stdClass;
			$xMeth->response->type = "string";
			
			$xClass->methods[] = $xMeth;
			
		}
		
		return $xClass->methods;
		
	}
	
	static public function generateWSDL($class_name)
	{
		/**
		 * build xml to be xsl transformed into wsdl
		 */
		$serviceDef = new DOMDocument('1.0','UTF-8');
		$serviceDef->formatOutput = true;

		$sDefService = $serviceDef->appendChild($serviceDef->createElement("service"));
		$sDefService->setAttribute("name", $class_name);
		$sDefService->setAttribute("url", ECASH_RPC_URI);

		/**
		 * if class conforms to eCash_iWSDL interface, build from that
		 * otherwise, reflect on it
		 */

//		if (is_subclass_of($class_name, "eCash_iWSDL")) {
		if(is_callable(array($class_name, "getRPCMethods"))) 
		{
			$methods = call_user_func(array($class_name, "getRPCMethods"));					
		} 
		else 
		{
			$methods = self::wsdlReflect($class_name);
		}
				
		foreach ($methods as $method) 
		{
			$methDef = $sDefService->appendChild($serviceDef->createElement("method"));
			$methDef->setAttribute("name", $method->name);
						
			if(isset($method->args) && is_array($method->args)) 
			{
				$argGrp = $methDef->appendChild($serviceDef->createElement("arguments"));
				foreach ($method->args as $arg) 
				{
					$argDef = $argGrp->appendChild($serviceDef->createElement("argument"));
					$argDef->setAttribute("name",$arg->name);
					$argDef->setAttribute("type",$arg->type);								
				}
			}	

			if (isset($method->response)) 
			{
				$resDef = $methDef->appendChild($serviceDef->createElement("response"));
				$resDef->setAttribute("name", $method->response->name);
				$resDef->setAttribute("type", $method->response->type);
			}
			
		}					
					
		$xsl = new DOMDocument('1.0');
		$xsl->load(WWW_DIR . "/xsl/wsdl.xsl");
		$proc = new XSLTProcessor();
		$proc->importStyleSheet($xsl);

		header("Content-type: text/xml");
		echo $proc->transformToXML($serviceDef);
//		echo $serviceDef->saveXML();
		exit;
		
	}
	
	public function handle()
	{
		try 
		{
			$res = parent::handle();
			
	//		eCash_RPC::Log()->write("SoapServer Dump Line ".__LINE__.":\n---\n" . var_export($this,true) . "\n---\n");
	
			return $res;
			
		} 
		catch (Exception $e) 
		{
			eCash_RPC::Log("Uncaught " . get_class($e) . ": $e->getMessage() \n---\n" . var_export($e,true). "\n---\n");
			throw new SoapFault(get_class($e), $e->getMessage(), $e->getFile() . ":" . $e->getLine(), $e->getTraceAsString() );
		}
	}
	
	public function setClass($class_name, $persist = FALSE)
	{
		parent::setClass($class_name);

		if($persist === TRUE) 
		{
			$this->setPersistence(eCash_RPC::PersistenceSession);
		}
		
	}
}
