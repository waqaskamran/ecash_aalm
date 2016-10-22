<?php
require_once(LIB_DIR.'Achtransport/achtransport.class.php');
/**
 * An FTP transport for dealing with batches and corrections.
 *
 */
class ACHTransport_FTP extends ACHTransport {
	/**
	 * An FTP object
	 * @var FTP_1
	 */
	private $FTP;
	
	/**
	 * A FTP connection
	 *
	 * @var resource
	 */
	private $h_FTP;
	
	
	/**
	 * Used to store the temporary directory
	 * used to store retrieved files
	 *
	 * @var string
	 */
	private $tmp_dir;
	
	
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
		
		$result = ftp_put($this->h_FTP, $remotefile, $localfile, FTP_ASCII);

		if ($result === false) {
			throw new ACHTransport_CouldNotSendBatch_Exception($this->FTP->get_error_msg());
		} else {
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
		if(! is_writable($this->tmp_dir))
		{
			throw new ACHTransport_CouldNotRetrieveReport_Exception("Cannot write to temporary directory {$this->tmp_dir}\n");
		}
		
		do
		{
			$tmp_filename = "_tmp_ftp_" . date('Ymdhis') . "_" . rand(11111, 99999);
			$tmp_path = $this->tmp_dir . "/" . $tmp_filename;
		} 
		while (file_exists($tmp_path));
		
		/**
		 * This ugly code checks for the existence of the file first
		 * to avoid throwing ugly errors.
		 */
		$path_separator = '/'; // Currently only works with sites using unix style paths
		
		$regex = "/\\" . $path_separator . "/";
		$path_array = preg_split($regex, $remotefile);
	
		$filename = array_pop($path_array);
		$path = implode($path_separator, $path_array);
	
		if($list = ftp_nlist($this->h_FTP, $path))
		{
			if(in_array($remotefile, $list))
			{
				if(! ftp_get($this->h_FTP, $tmp_path, $remotefile, FTP_ASCII))
				{
					throw new ACHTransport_CouldNotRetrieveReport_Exception("Error retrieving {$remotefile}\n");
				}
				
				if(! $contents = file_get_contents($tmp_path))
				{
					throw new ACHTransport_CouldNotRetrieveReport_Exception("Unable to locate temporary file {$tmp_path}\n");
				}
				
				unlink($tmp_path);
				
				return TRUE;
			}
			else
			{
				throw new ACHTransport_CouldNotRetrieveReport_Exception("File does not exist: $remotefile");
			}
		}
		else
		{
			throw new ACHTransport_CouldNotRetrieveReport_Exception("Invalid file path: $path");
		}
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
		// This should be elsewhere
		$this->tmp_dir = "/tmp";
	}
	
	/**
	 * Creates the
	 * 
	 * @return null
	 */
	protected function _connect($server, $username, $password, $port=null) 
	{

		if(! $this->h_FTP = ftp_connect($server, $port))
		{
			throw new ACHTransport_CouldNotConnect_Exception("Could not connect to host: '{$server}'\n");
		}
		
		if(! @ftp_login($this->h_FTP, $username, $password))
		{
			throw new ACHTransport_CouldNotConnect_Exception("Authentication Failure!  Username: '{$username}', Password: '{$password}'\n");
		}
		
		ftp_pasv($this->h_FTP, true);
		
	}
	
	public function __destruct()
	{
		ftp_close($this->h_FTP);
	}
}
?>