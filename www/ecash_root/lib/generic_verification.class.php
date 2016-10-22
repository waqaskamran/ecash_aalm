<?php

/**
 * This is just a shell of the funding verification functionality.
 * 
 */
require_once("verification.iface.php");

class Generic_Verification implements Funding_Verification_Interface 
{
	const CONFIRMED = 'CONFIRMED';
	const CONFIRMED_COMMENT = 'Application successfully verified.';
	const DECLINED = 'DECLINED';
	const DECLINED_COMMENT = 'Application failed verification.';
	const UNAVAILABLE = 'UNAVAILABLE';
	const UNAVAILABLE_COMMENT = 'Verification server currently unavailable.';
	
	protected $application_obj;
	protected $connection;
	protected $results;
	
	public function __construct($application)
	{
		$this->application_obj = $application;
	}
	
	public function runVerification()
	{
		//prepare connection
		$connection = $this->prepareConnection();
		
		//prepare data for submission
		$data = $this->prepareData();
		
		//Send verification request
		$response = $this->sendRequest($data);
		
		//process response
		$results = $this->processResponse($response);
		$this->results = $results;
		//return results
		return $results;
	}
	
	public function verificationRequired()
	{
		return false;
	}
	
	public function verified()
	{
		if(empty($this->results))
		{
			$this->runVerification();
		}
		
		if($this->results['status'] == self::CONFIRMED)
		{
			return true;
		}
		return false;
	}
	protected function prepareConnection()
	{
		return null;
	}
	
	protected function prepareData()
	{
		return null;
	}
	
	protected function sendRequest($data)
	{
		return 'response';
	}
	
	protected function processResponse($response)
	{
		$result = array(
			'status' => self::CONFIRMED,
			'comment' => self::CONFIRMED_COMMENT,
			);
		return $result;
	}
	
	
	
}