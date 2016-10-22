<?php

/**
 * This is a datafix written for Impact / High Country to identify ach records that are
 * marked as 'returned' but do not have an associated ach_report_id.  This script will
 * attempt to identify the failure date using the first record in the transaction_history
 * table where the agent_id is zero, indicating it was updated by automated processing.
 * Using this failure date, the script will then try to identify an ach_report_id for
 * the same company and date and if found, will update the ach record with this new
 * ach_report_id.
 * 
 * I made use of a cache to try to reduce the number of queries made to the DB when 
 * trying to locate an ach_report_id for a given company and date.
 * 
 * In cases where there may be more than one ach_report_id for a given day, the script
 * will always return the first.  It may not be 100% accurate, but it's very close.
 * 
 * Instructions: run via ecash_engine.php in the cronjobs directory.  This will run for 
 * all companies, regardless of which one the cron runs under.
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 *
 */
function main()
{
	$db = ECash::getMasterDb();
	$df = new Associate_Missing_ACHReport($db);
	$df->run();
}

class Associate_Missing_ACHReport
{
	private $db;
	private $achreport_cache;
	
	private $query_count = 0;
	private $cache_hit = 0;
	private $failed_update = 0;
	private $successful_update = 0;
	
	public function __construct($db)
	{
		$this->db = $db;
	}
	
	public function run()
	{
		$result = $this->getUnassociatedACHRecords();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($row->date_failed === NULL)
			{
				echo "Could not find a proper fail date for {$row->ach_id}!\n";
				$this->failed_update++;
				continue;
			}
			
			if($ach_report_id = $this->findACHReport($row->company_id, $row->date_failed))
			{
				$this->updateAchRecord($row->ach_id, $ach_report_id);
			}
			else
			{
				$this->failed_update++;
				echo "Unable to find a report for $row->ach_id\n";
			}
		}
		
		$total = $this->successful_update + $this->failed_update;
		echo "\nTotal Transactions:      $total\n";
		echo "Succesful Updates:       {$this->successful_update}\n";
		echo "Unresolved Transactions: {$this->failed_update}\n";
		echo "Queries to ach_report:   {$this->query_count}\n";
		echo "Successful cache hits:   {$this->cache_hit}\n";
		
	}
	
	private function getUnassociatedACHRecords()
	{
		$sql = "
			SELECT  company_id,
				 	ach_id,
				 	(
				 		SELECT DATE_FORMAT(th.date_created, '%Y/%m/%d')
				 		FROM transaction_history AS th
				 		JOIN transaction_register AS tr ON tr.transaction_register_id = th.transaction_register_id
				 		WHERE tr.ach_id = ach.ach_id
				 		AND th.status_after = 'failed'
				 		AND th.agent_id = 0
				 		ORDER BY th.date_created ASC
				 		LIMIT 1
				 	) AS date_failed
			FROM ach
			WHERE ach_report_id IS NULL 
			AND ach_status = 'returned' 
			ORDER BY date_created ASC ";
		return $this->db->query($sql);
	}

	private function findACHReport($company_id, $date)
	{
		// search the cache first
		if($ach_report_id = $this->searchReportCache($date, $company_id))
		{
			return $ach_report_id;
		}
		else
		{
			if($ach_report_id = $this->queryReports($date, $company_id))
			{
				$this->addReportToCache($date, $company_id, $ach_report_id);
				return $ach_report_id;
			}
			return FALSE;
		}
	}
	
	private function searchReportCache($date, $company_id)
	{
		$date = $this->dateStringToNormalizedUnix($date);
		if(isset($this->achreport_cache[$company_id]))
		{
			if($ach_report_id = $this->achreport_cache[$company_id][$date])
			{
				$this->cache_hit++;
				return $ach_report_id;
			}
		}
		
		return FALSE;
		
	}
	
	private function addReportToCache($date, $company_id, $ach_report_id)
	{
		$date = $this->dateStringToNormalizedUnix($date);
		$this->achreport_cache[$company_id][$date] = $ach_report_id;
	}
	
	private function queryReports($date, $company_id)
	{
		$this->query_count++;
		$date_stamp = $this->dateStringToNormalizedUnix($date);
		$date = date('Y-m-d', $date_stamp);
		
		$sql = "
			SELECT ach_report_id 
			FROM ach_report 
			WHERE company_id = {$company_id}
			AND date_created BETWEEN '$date 00:00:00' AND '$date 23:59:59'
			AND report_status = 'processed'
			AND report_type = 'returns'
			ORDER BY ach_report_id ASC
			LIMIT 1 ";
		$result = $this->db->query($sql);
		return $result->fetch(PDO::FETCH_OBJ)->ach_report_id;
		
	}
	
	private function updateAchRecord($ach_id, $ach_report_id)
	{
		$this->successful_update++;
		$sql = "UPDATE ach SET ach_report_id = {$ach_report_id} WHERE ach_report_id IS NULL AND ach_id = {$ach_id}";
		$result = $this->db->query($sql);
	}
	
	private function dateStringToNormalizedUnix($date_string)
	{
		return strtotime(date('Y-m-d', strtotime($date_string)));
	}
	
}