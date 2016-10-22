<?php
require_once(LIB_DIR.'Achtransport/achtransport.class.php');
require_once(COMMON_LIB_DIR.'sftp.1.php');
/**
 * An HTTPS transport for dealing with batches and corrections.
 *
 */
class ACHTransport_SFTP extends ACHTransport 
{
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
?>
