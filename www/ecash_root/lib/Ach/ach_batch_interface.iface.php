<?php

/**
 * Interface to ACH Batch classes
 *
 */
interface ACH_Batch_Interface
{
	/**
	 * Enter description here...
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server);
	
	/**
	 * Enter description here...
	 *
	 */
	public function Initialize_Batch();
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $batch_type
	 * @param unknown_type $batch_date
	 */
	public function Do_Batch($batch_type, $batch_date, $ach_provider_id); //asm 80
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $batch_id
	 * @param unknown_type $batch_date
	 */
	public function Resend_Failed_Batch($batch_id, $batch_date);
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $date_current
	 */
	public function Get_Closing_Timestamp ($date_current);
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $date_current
	 */
	public function Set_Closing_Timestamp ($date_current);
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $date_current
	 */
	public function Has_Sent_ACH ($date_current);
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 */
	public function Fetch_ACH_Batch_Stats ($start_date, $end_date);
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $batch_date
	 */
	public function Preview_ACH_Batches ($batch_date);
}

?>
