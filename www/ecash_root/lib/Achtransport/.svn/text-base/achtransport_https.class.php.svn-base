<?php
require_once(LIB_DIR.'Achtransport/achtransport.class.php');

/**
 * An HTTPS transport for dealing with batches and corrections.
 *
 */
class ACHTransport_HTTPS extends ACHTransport 
{
	/**
	 * An array containing options to pass to curl.
	 *
	 * @var array
	 */
	private $curlopt = array();
	
	/**
	 * An array containing a query string for the last request.
	 *
	 * @var array
	 */
	private $lastRequestArray = array();
	
	/**
	 * Creates a curl connection and sends the batch.
	 * 
	 * @throws ACHTransport_CouldNotSendBatch_Exception
	 * @param string $localfile
	 * @param string $remotefile
	 * @param string $result
	 * @return bool
	 */
	/*
	protected function _sendBatch($localfile, $remotefile, &$result) {
		$this->curlopt['CURLOPT_POSTFIELDS']['filename'] = '@'.$localfile;
		$this->curlopt['CURLOPT_POSTFIELDS']['force'] = 'T';

		$h_curl = curl_init($remotefile);
		
		foreach ($this->curlopt as $option => $value) {
			$return = curl_setopt($h_curl, constant($option), $value);
		}
		
		$result = curl_exec($h_curl);
		curl_close($h_curl);
		$this->lastRequestArray = $this->curlopt['CURLOPT_POSTFIELDS'];
		$this->resetTransport();
		
		if ($result === false) {
			throw new ACHTransport_CouldNotSendBatch_Exception(curl_error($h_curl));
		} else {
			$responseVars = array();
			//split out response (query string format) into an array
			parse_str($result, $responseVars);
			
			if (isset($responseVars['ER']) && $responseVars['ER'] == '0') {
				return true;
			} else {
				return false;
			}
		}
	}
	*/

			/* 
			// II hit
			$result = 	'<?xml version="1.0"?>
						<xdoc>
    							<errorcode>0</errorcode>
    							<errordesc>Successful.</errordesc>
    							<filesize>3760</filesize>
    							<invalidchars>3</invalidchars>
    							<refnum>TSTAB0828.06</refnum>
    							<filesummary>
        							<amounts>
            								<creditamount>5242.82</creditamount>
            								<debitamount>5242.82</debitamount>
        							</amounts>
        							<counts>
            								<addendacount>0</addendacount>
            								<batchcount>5</batchcount>
            								<creditcount>19</creditcount>
            								<debitcount>2</debitcount>
        							</counts>
    							</filesummary>
    							<extendedhours>F</extendedhours>
    							<processedwithextendedhours>F</processedwithextendedhours>
						</xdoc>
					';
			*/

	
	//MANTIS:12417 added 3 parameters $card_val_for_hit2, need to comment existing _sendBatch
	/**
	 * Sends the batch data and sets the $result value
	 *
	 * <b>Revision History</b>
	 * <ul>
	 *     <li><b>2008-01-25 - tonyc</b><br>
	 *         Added isset() check to errorcode testing as intval(NULL) = 0
	 *     </li>
	 * </ul>
	 *
	 * @throws ACHTransport_CouldNotSendReport_Exception
	 * @return bool
	 */
	protected function _sendBatch($localfile, $remotefile, &$result, $card_val1_for_hit2, $card_val2_for_hit2, $card_val3_for_hit2) 
	{
		$this->curlopt['CURLOPT_POSTFIELDS']['response1'] = $card_val1_for_hit2;
		$this->curlopt['CURLOPT_POSTFIELDS']['response2'] = $card_val2_for_hit2;
		$this->curlopt['CURLOPT_POSTFIELDS']['response3'] = $card_val3_for_hit2;
		$this->curlopt['CURLOPT_POSTFIELDS']['filename'] = '@'.$localfile;
		$this->curlopt['CURLOPT_POSTFIELDS']['force'] = 'T';

		$h_curl = curl_init($remotefile);

		$curlopt = $this->curlopt;
		unset($curlopt['CURLOPT_POSTFIELDS']['login']);
		unset($curlopt['CURLOPT_POSTFIELDS']['pass']);
		unset($curlopt['CURLOPT_POSTFIELDS']['source']);
		foreach ($curlopt as $option => $value) {
			$return = curl_setopt($h_curl, constant($option), $value);
		
		}
		// get xml response
		$result = curl_exec($h_curl);

		curl_close($h_curl);
		$this->lastRequestArray = $this->curlopt['CURLOPT_POSTFIELDS'];
		$this->resetTransport();

		// parse xml response
		require_once('minixml/minixml.inc.php');
		$mini = new MiniXMLDoc();
		$mini->fromString($result);
		$hit2_returned_array = $mini->toArray();
		$err_code_hit2 = intval($hit2_returned_array["xdoc"]["errorcode"]);

		if (!isset($hit2_returned_array["xdoc"]["errorcode"]) || $err_code_hit2 != 0) 
		{
			throw new ACHTransport_CouldNotSendBatch_Exception(curl_error($h_curl));
		} 
		else 
		{
			// convert array data to depricated query string format
			$result = $this->getQueryStringFromArray($hit2_returned_array);
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
	protected function _retrieveReport($remotefile, $report, &$contents, $card_val1_for_hit2, $card_val2_for_hit2, $card_val3_for_hit2) 
	{

		if($report == 'returns') 
		{
			$report = "RET";
		} 
		else 
		{
			$report = "COR";
		}
		
		$this->curlopt['CURLOPT_POSTFIELDS']['report'] = $report;
		$this->curlopt['CURLOPT_POSTFIELDS']['format'] = 'CSV';
		$this->curlopt['CURLOPT_POSTFIELDS']['response1'] = $card_val1_for_hit2;
		$this->curlopt['CURLOPT_POSTFIELDS']['response2'] = $card_val2_for_hit2;
		$this->curlopt['CURLOPT_POSTFIELDS']['response3'] = $card_val3_for_hit2;
		
		$h_curl = curl_init($remotefile);

		foreach ($this->curlopt as $option => $value) 
		{
			curl_setopt($h_curl, constant($option), $value);
		}

		$contents = curl_exec($h_curl);
		curl_close($h_curl);
		
		$this->lastRequestArray = $this->curlopt['CURLOPT_POSTFIELDS'];
		$this->resetTransport();
		
		if ($contents === false) 
		{
			throw new ACHTransport_CouldNotRetrieveReport_Exception(curl_error($h_curl));
		} 
		else 
		{
			$matches = array();
			if (preg_match("/ER=/", $contents, $matches)) 
			{
				return false;
			} 
			else 
			{
				return true;
			}
		}
	}
	
	/**
	 * Initializes all global curl options
	 *
	 * @return null
	 */
	protected function _initTransport() {
		//store curl opts that will be constant
		$this->curlopt['CURLOPT_POST'] = 1;
		$this->curlopt['CURLOPT_VERBOSE'] = 0;
		$this->curlopt['CURLOPT_RETURNTRANSFER'] = true;
		$this->curlopt['CURLOPT_TIMEOUT'] = 300;
		$this->curlopt['CURLOPT_SSL_VERIFYPEER'] = 0;
		$this->curlopt['CURLOPT_SSL_VERIFYHOST'] = 2;
	}
	
	/**
	 * Save username and password. Throw away the server.
	 * 
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 * @return null
	 */
	protected function _connect($server, $username, $password, $port = null) {
		$this->curlopt['CURLOPT_POSTFIELDS'] = array(
			'login' => $username,
			'pass' => $password,
		);
	}
	
	/**
	 * Sets a batch key to be used in sending test batches and retrieving test 
	 * results.
	 *
	 * @param string $batchKey
	 * @return null
	 */
	public function setBatchKey($batchKey) {
		$this->curlopt['CURLOPT_POSTFIELDS']['source'] = $batchKey;
	}
	
	/**
	 * Sets the date to retrieve a report for.
	 * 
	 * JN [06-19-2008] - Added optional end date parameter to give us the ability to pull report ranges.
	 *
	 * @param string $sdate
	 * @param string $edate
	 * @return null
	 */
	public function setDate($sdate, $edate=NULL) 
	{
		if(!$edate)
		{
			$edate = $sdate;
		}
		
		$this->curlopt['CURLOPT_POSTFIELDS']['sdate'] = date("Ymd", strtotime($sdate));
		$this->curlopt['CURLOPT_POSTFIELDS']['edate'] = date("Ymd", strtotime($edate));
	}
	
	/**
	 * Sets the company id to retrieve a report for.
	 *
	 * @param int $companyId
	 * @return null
	 */
	public function setCompanyId($companyId) {
		$this->curlopt['CURLOPT_POSTFIELDS']['compid'] = $companyId;
	}
	
	/**
	 * Returns an array containing all of the query string fields sent in the 
	 * last request.
	 *
	 * @return array
	 */
	public function getLastRequestArray() {
		return $this->lastRequestArray;
	}
	
	/**
	 * Resets curl options into the transport back to the default.
	 *
	 * @see _initTransport()
	 * @return null
	 */
	private function resetTransport() {
		$username = $this->curlopt['CURLOPT_POSTFIELDS']['login'];
		$password = $this->curlopt['CURLOPT_POSTFIELDS']['pass'];
		$port = $this->curlopt['CURLOPT_POSTFIELDS']['port'];
		
		
		$this->_initTransport();
		$this->_connect('', $username, $password, $port);
	}

	/**
	 * Returns a preformatted query string using the data in the passed array.
	 *
	 * @param array $data This should be the Intercept xml response converted 
	 *                    by MiniXMLDoc::toArray()
	 * @return string Preformatted query string, or empty string
	 */
	private function getQueryStringFromArray($data)
	{
		$str = '';

		if (isset($data['xdoc']['filesummary']))
		{
			$str .= 'BC=' . $data['xdoc']['filesummary']['counts']['batchcount']. '&'
			     .  'DC=' . $data['xdoc']['filesummary']['counts']['debitcount'] . '&'
			     .  'CC=' . $data['xdoc']['filesummary']['counts']['creditcount'] . '&'
			     .  'CA=' . $data['xdoc']['filesummary']['amounts']['creditamount'] . '&'
			     .  'DA=' . $data['xdoc']['filesummary']['amounts']['debitamount'] . '&'
			     .  'AC=' . $data['xdoc']['filesummary']['counts']['addendacount'] . '&'
			     .  'FS=' . $data['xdoc']['filesize'] . '&'
			     .  'IC=' . $data['xdoc']['invalidchars'] . '&'
			     .  'REF=' . $data['xdoc']['refnum'] . '&'
			     .  'ER=' . $data['xdoc']['errorcode'] . '&'
			;
		}

		return $str;
	}

}
?>
