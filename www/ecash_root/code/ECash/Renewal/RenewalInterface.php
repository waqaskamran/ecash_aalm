<?php 

interface ECash_Renewal_RenewalInterface
{

	function getRolloverEligibility($application_id);

	/**
	 * createRollover
	 * Runs the whole Rollover process on an application.
	 * Checks eligibility, does lender verification, and finally executes the rollover.
	 *
	 * @param int $application_id
	 * @param float $paydown_amount
	 * @return array
	 */
	function createRollover($application_id, $paydown_amount = 0);
	
	function requestRollover($application_id,$paydown_amount = 0);

	function getRequestEligibility($application_id);
	
	function getRolloverExecutionEligibility($application_id);
	/**
	 * getRolloverTerm
	 * A quick and easy way to see what rollover term the application is in.
	 *People are going to want this eventually. 
	 *
	 * @param unknown_type $application_id
	 * @return unknown
	 */
	function getRolloverTerm($application_id);


	
}

?>
