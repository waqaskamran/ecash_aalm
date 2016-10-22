<?php
require_once(COMMON_LIB_DIR.'sftp.1.php');


class ECash_Transport_SFTP implements ECash_Transport_ITransport
{
	public function construct($server, $user, $pass,$port)
	{

	}
	public function setPath($path)
	{
	
	}
	public function putFile($name, $file_contents)
	{

	}
	public function getfile($name)
	{
		$contents = '';
		
		$this->_retrieveReport($name, $report, $contents);
		return $contents;
	}
	
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
file_put_contents ( '/tmp/ach.log',__METHOD__.' || '.__FILE__,FILE_APPEND);
file_put_contents ( '/tmp/ach.log',LIB_DIR . 'Achtransport/' . strtolower($class).".class.php",FILE_APPEND);

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
	 * An SFTP object
	 * @var SFTP_1
	 */
	private $sftp;
	
	/**
	 * A sftp connection
	 *
	 * @var resource
	 */
	private $h_sftp;
	
	/**
	 * Uploads the batch to the given remote file.
	 * 
	 * $result is not used
	 * 
	 * @throws ACHTransport_CouldNotSendBatch_Exception
	 * @param string $localfile
	 * @param string $remotefile
	 * @param string $result
	 * @return bool
	 */
	protected function _sendBatch($localfile, $remotefile, &$result) {
		$result = $this->sftp->put_from_file($remotefile, $localfile);

		if ($result === false) 
		{
			throw new ACHTransport_CouldNotSendBatch_Exception($this->sftp->get_error_msg());
		} 
		else 
		{
			return true;
		}
	}
	
	/**
	 * Creates a curl connection and retrieves the specified report.
	 * 
	 * @throws ACHTransport_CouldNotRetrieveReport_Exception
	 * @param string $remotefile
	 * @param string $report
	 * @param string $contents
	 * @return bool
	 */
	protected function _retrieveReport($remotefile, $report, &$contents) 
	{
		/**
		 * This ugly code checks for the existence of the file first
		 * to avoid throwing ugly errors.
		 */
		$path_separator = '/'; // Currently only works with sites using unix style paths
		
		$regex = "/\\" . $path_separator . "/";
		$path_array = preg_split($regex, $remotefile);
	
		$filename = array_pop($path_array);
		$path = implode($path_separator, $path_array);
	
		$list = $this->sftp->get_file_list($path);
	
		if(isset($list[$filename]))
		{
			$contents = $this->sftp->get($remotefile);
			
			if ($contents === FALSE) 
			{
				throw new ACHTransport_CouldNotRetrieveReport_Exception($this->sftp->get_error_msg());
			}
			
			return TRUE;
		}
		else
		{
			throw new ACHTransport_CouldNotRetrieveReport_Exception("File does not exist: $remotefile");
			$contents = "File does not exist";
			return FALSE;
		}
	}

	/**
	 * Return the directory contents of a path
	 *
	 * @param string $path
	 * @return array
	 */
	public function getDirectoryList($path)
	{
		return $this->sftp->get_file_list($path);
	}

	/**
	 * Initializes all global curl options
	 *
	 * @param string $username
	 * @param string $password
	 * 
	 * @return null
	 */
	protected function _initTransport() {
	}
	
	/**
	 * Creates the Connection
	 * 
	 * @return null
	 */

	protected function _connect($server, $username, $password, $port=null) {
		if ($port != null) 
			$sftp = new SFTP_1($server, $username, $password, $port);
		else 
			$sftp = new SFTP_1($server, $username, $password);
		$result = $sftp->connect();
		
		if (!$result) 
		{
 			$msg = "Server: '{$server}', Port: '{$port}', Username: '{$username}', Password: '{$password}'\n";
			throw new ACHTransport_CouldNotConnect_Exception($msg . $sftp->get_error_msg());
		} 
		else 
		{
			$this->sftp = $sftp;
		}
	}
	
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
