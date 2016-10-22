<?php
/**
 * The base class for ACHTransport objects. 
 * 
 * To create a new transfer method for ACH batches you will need to extend 
 * this class. You must override _initTransport and _connect. Then you can 
 * override _sendBatch and _retrieveReport as needed. If you don't override one 
 * of those methods and it is called on your type a 
 * ACHTransport_FunctionNotImplemented_Exception will be thrown. You will also 
 * need to ensure that the class name of the new transport type begins with 
 * 'ACHTransport_'. Failure to do so will prevent the factory method 
 * ACHTransport::CreateTransport() from working.
 * 
 * You can instantiate a Tranport Type by calling either 
 * ACHTransport::CreateTransport() by passing the appropriate type (the 
 * classname of the type without the leading ACHTransport_) or you may call 
 * the Transport Type's constructor directly. The preferred method is by using 
 * the factory.
 * 
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @todo Ideally we should make this class flexible enough to deal with transporting
 *       other types of files (ie: quick checks)
 */
abstract class ACHTransport {
	/**
	 * A factory method creating the desired type of transport using the 
	 * given username and password.
	 *
	 * @param string $type
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param int $port
	 * @return ACHTransport
	 */
	final static public function CreateTransport($type, $server, $username, $password, $port = NULL) {
		$class = __CLASS__.'_'.$type;

		require_once(LIB_DIR . 'Achtransport/' . strtolower($class).".class.php");
		if (class_exists($class)) 
		{
			return new $class($server, $username, $password, $port);
		} 
		else 
		{
			throw new ACHTransport_TransportTypeNoExist_Exception($type);
		}
	}
	
	/**
	 * Creates a new transport using the given username and password.
	 *
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param int $port
	 */
	final public function __construct($server, $username, $password, $port = null) 
	{
		$this->_initTransport();
		$this->_connect($server, $username, $password, $port);
	}
	
	/**
	 * Determines if the transport supports a given method. This can be used 
	 * to make configuration of multiple transport types more transparent 
	 * without having to write a bunch of dummy functions.
	 * 
	 * @param string $methodName
	 * @return bool
	 */
	final public function hasMethod($methodName) 
	{
		return method_exists($this, $methodName);
	}
	
	/**
	 * Sends the batch located in the givent file (absolute paths) over the 
	 * transport to the given location. Returns true on success. Any data 
	 * returned will be stored in the $result variable.
	 *
	 * If a transport does not support this method it will throw a 
	 * ACHTransport_FunctionNotImplemented_Exception object.
	 * 
	 * If a transport encountered an error in sending that batch it will throw 
	 * a ACHTransport_CouldNotSendBatch_Exception.
	 * 
	 * @throws ACHTransport_FunctionNotImplemented_Exception
	 * @throws ACHTransport_CouldNotSendBatch_Exception
	 * @param string $file
	 * @param string $remotefile
	 * @param string $result
	 * @return bool
	 */
	final public function sendBatch($localfile, $remotefile, &$result, $card_val1_for_hit2=NULL, $card_val2_for_hit2=NULL, $card_val3_for_hit2=NULL) {
		return $this->_sendBatch($localfile, $remotefile, $result, $card_val1_for_hit2, $card_val2_for_hit2, $card_val3_for_hit2);
	}
	
	/**
	 * Retrieves a specified report from the given location over the transport 
	 * and stores it in the $contents string. Returns true on success.
	 *
	 * If a transport does not support this method it will throw a 
	 * ACHTransport_FunctionNotImplemented_Exception object.
	 * 
	 * If a transport encountered an error in retrieving that report it will throw 
	 * a ACHTransport_CouldNotRetrieveReport_Exception.
	 * 
	 * @throws ACHTransport_FunctionNotImplemented_Exception
	 * @throws ACHTransport_CouldNotRetrieveReport_Exception
	 * @param string $remotefile
	 * @param string $report
	 * @param string $contents
	 * @return bool
	 */
	final public function retrieveReport($remotefile, $report, &$contents, $card_val1_for_hit2=NULL, $card_val2_for_hit2=NULL, $card_val3_for_hit2=NULL) 
	{
		return $this->_retrieveReport($remotefile, $report, $contents, $card_val1_for_hit2, $card_val2_for_hit2, $card_val3_for_hit2);
	}
	
	/**
	 * Override this method in the transport type class to enable sending 
	 * batch files over that transport type. 
	 * 
	 * $localfile will represent the local file to send and $remotefile will 
	 * represent the location on the remote server to send the file to. 
	 * Return true on success and false on failure.
	 * 
	 * If any data is returned it will be stored in the &$result variable.
	 * 
	 * If sending is not supported by the transport do not override this 
	 * method.
	 * 
	 * If there was an error in sending the batch throw an
	 * ACHTransport_CouldNotSendBatch_Exception (or a child thereof) with an 
	 * appropriate message.
	 * 
	 * @throws ACHTransport_FunctionNotImplemented_Exception
	 * @param string $localfile
	 * @param string $remotefile
	 * @param string $result
	 * @return bool
	 */
	protected function _sendBatch($localfile, $remotefile, &$result) 
	{
		throw new ACHTransport_FunctionNotImplemented_Exception(__FUNCTION__);
	}
	
	/**
	 * Override this method in the transport type class to enable retrieving   
	 * specified reports over that transport type. 
	 * 
	 * $remotefile will represent the location on the remote server to 
	 * retrieve the report from. $report will represent the report to retrieve. 
	 * The contents of the file should be stored in $contents. Return true on 
	 * success and false on failure.
	 * 
	 * If recieving is not supported by the transport do not override this 
	 * method.
	 * 
	 * If there was an error in retrieving the report throw an
	 * ACHTransport_CouldNotRetrieveReport_Exception (or a child thereof) with an 
	 * appropriate message.
	 * 
	 * @throws ACHTransport_FunctionNotImplemented_Exception
	 * @param string $remotefile
	 * @param string $report
	 * @param string $contents
	 * @return bool
	 */
	protected function _retrieveReport($remotefile, $report, &$contents) 
	{
		throw new ACHTransport_FunctionNotImplemented_Exception(__FUNCTION__);
	}
	
	/**
	 * Return the directory contents of a path
	 *
	 * @param string $path
	 * @return array
	 */
	public function getDirectoryList($path)
	{
		throw new ACHTransport_FunctionNotImplemented_Exception(__FUNCTION__);
	}

	/**
	 * Override this method to do any initialization needed for a given 
	 * transport.
	 * 
	 * $username and $password should be the credentials for logging on to the 
	 * server.
	 * 
	 * If there are any problems initializing the transport that should be 
	 * addressed by client code throw an
	 * ACHTransport_CouldNotInitialize_Exception (or a child thereof) with an 
	 * appropriate message.
	 *
	 * @throws ACHTransport_CouldNotInitialize_Exception
	 * @return null
	 */
	abstract protected function _initTransport();
	
	/**
	 * Override this method to connect to the transport.
	 * 
	 * If there are any problems initializing the transport that should be 
	 * addressed by client code throw an
	 * ACHTransport_CouldNotConnect_Exception (or a child thereof) with an 
	 * appropriate message.
	 * 
	 * @throws ACHTransport_CouldNotConnect_Exception
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @param int $port
	 * @return null
	 */
	abstract protected function _connect($server, $username, $password, $port = null);
}

/**
 * Indicates that the requested Transport Type does not exists. This will 
 * commonly mean that the class for that transport type has not been 
 * included/required into the app.
 */
class ACHTransport_TransportTypeNoExist_Exception extends Exception  
{
	private $transportType;
	
	public function __construct($transportType) {
		parent::__construct('Transport type does not exist: '.$transportType.' (make sure you are including the type.)');
		$this->transportType = $transportType;
	}
	
	public function getFunctionName() {
		return $this->functionName;
	}
}

/**
 * Indicates that the Transport Type has not implemented a called function.
 */
class ACHTransport_FunctionNotImplemented_Exception extends Exception 
{
	private $functionName;
	
	public function __construct($functionName) {
		parent::__construct('This transport does not implement '.$functionName);
		$this->functionName = $functionName;
	}
	
	public function getFunctionName() {
		return $this->functionName;
	}
}

/**
 * Indicates that there was a problem initializing the transport.
 */
class ACHTransport_CouldNotInitialize_Exception extends Exception 
{
	public function __construct($msg) {
		parent::__construct('Could not initialize ACH Transport: '.$msg);
	}
}

/**
 * Indicates that there was a problem trying to connect to a server over the 
 * given transport.
 */
class ACHTransport_CouldNotConnect_Exception extends Exception 
{
	public function __construct($msg) {
		parent::__construct('ACH Transport Could Not Connect: '.$msg);
	}
}

/**
 * Indicates that the batch could not send.
 */
class ACHTransport_CouldNotSendBatch_Exception extends Exception 
{
	public function __construct($msg) {
		parent::__construct('Could not send batch: '.var_export($msg,true));
	}
}

/**
 * Indicates that a report could not be retrieved.
 *
 */
class ACHTransport_CouldNotRetrieveReport_Exception extends Exception 
{
	public function __construct($msg) {
		parent::__construct('Could not retrieve report: '.$msg);
	}
}
?>
