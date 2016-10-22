<?php

/**
 * Interface to ACH Return classes
 *
 */
interface Second_Tier_Incoming_Interface
{
	public function __construct(Server $server);
	
	public function Process_Second_Tier_Incoming ($end_date, $report_type, $override_start_date = NULL);
}

?>