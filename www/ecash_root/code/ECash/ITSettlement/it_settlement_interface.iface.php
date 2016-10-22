<?php
/**
 * Interface for IT Settlement reports
 *
 */
interface IT_Settlement_Interface
{
	/**
	 * Constructor
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server);
	
	function generateReport($start_date,$end_date,$report_date);
	
	function regenerateReport($report_id);
	
	function sendReport($report_id);
	
	function fetchReportsList();
	
	
}

?>