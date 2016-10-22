<?php
require_once(LIB_DIR.'Achtransport/achtransport.class.php');
require_once(COMMON_LIB_DIR.'sftp.1.php');
/**
 * An Overloaded SFTP transport for dealing with batches and corrections for Agean.
 *
 */
class ACHTransport_SFTP_AGEAN extends ACHTransport_SFTP {
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

		if ($result === false) {
			throw new ACHTransport_CouldNotSendBatch_Exception($this->sftp->get_error_msg());
		} else {
			return true;
		}
	}
	
	/**
	 * Creates a curl connection and retrieves the specified report.
	 * 
	 * Overloaded to account for Agean's combined return file
	 * 
	 * @throws ACHTransport_CouldNotRetrieveReport_Exception
	 * @param string $remotefile
	 * @param string $report
	 * @param string $contents
	 * @return bool
	 */
	protected function _retrieveReport($remotefile, $report, &$contents) 
	{
		$return_code_indicator = array(
			"returns"     => "R",
			"corrections" => "C"
			);

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
			else
			{
				$new_contents = "";
				$lines = explode("\n", $contents);
				foreach($lines as $line)
				{
					if(strlen($line) > 0)
					{
						$matches = array();
						preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
						$fields = $matches[0];
						// field 9 is the return code field in the ach commerce return format
						if(stristr($fields[9], $return_code_indicator[$report]))	
						{
							// Append line to output
							$new_contents .= $line;
						}
					}
				}

				$contents = $new_contents;
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
	protected function _connect($server, $username, $password, $port = null) {
		$sftp = new SFTP_1($server, $username, $password);
		$result = $sftp->connect();
		
		if (!$result) {
			$msg = "Server: '{$server}', Username: '{$username}', Password: '{$password}'\n";
			throw new ACHTransport_CouldNotConnect_Exception($msg . $sftp->get_error_msg());
		} else {
			$this->sftp = $sftp;
		}
	}
	
}
?>