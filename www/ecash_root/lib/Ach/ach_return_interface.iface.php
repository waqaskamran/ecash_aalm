<?php

/**
 * Interface to ACH Return classes
 *
 */
interface ACH_Return_Interface
{
	public function __construct(Server $server);
	
	public function Process_ACH_Report ($end_date, $report_type, $override_start_date = NULL);
}

?>