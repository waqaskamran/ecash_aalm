<?php
/**
 * GForge #40703 - Due to an issue with the rescheduling daemon running on each process server, 
 * there were a large number of accounts that were rescheduled multiple times creating duplicate
 * reattempt transactions.  This script looks through each scheduled ACH batch from the current day up
 * until X number of business days ahead, looking for duplicate origin_id's.  It will then remove
 * the newest copy, keeping the original intact. [BR]
 */

require_once("../www/config.php");
require_once("../sql/lib/scheduling.func.php");

$holidays = new Date_BankHolidays_1();
$pdc = new Date_Normalizer_1($holidays);
$db = ECash::getMasterDb();
$start_date = date('Y/m/d');

$df = new DataFix_40703($db, $pdc,  $start_date, 30);
$df->run();

class DataFix_40703
{
	private $db;
	private $pdc;
	private $start_date;
	private $depth;
	
	public function __Construct($db, $pdc, $start_date, $depth)
	{
		$this->db = $db;
		$this->pdc = $pdc;
		$this->start_date = strtotime($start_date);
		$this->depth = $depth;
	}
	
	public function run()
	{
		$day = $this->start_date;
		
		for($x = 0; $x < $this->depth; $x++ )
		{
			$day = $this->pdc->advanceBusinessDays($day, 1);
			$string_date = date("Y/m/d", $day);
			echo  "Running for $string_date\n";
			$event_list = $this->fetchBatchData($string_date);
			
			if(empty($event_list))
			{
				echo "\t- No events in batch for $string_date\n";
			}
			else
			{
				$duplicates = $this->idBatchDuplicates($event_list);
				if(! empty($duplicates))
				{
					$this->purgeDuplicates($duplicates);
				}
			}
		}
		
	}
	
	private function fetchBatchData($string_date)
	{
		$event_list = array();
		
		$query = "
		SELECT es.*
		FROM event_schedule AS es
		JOIN event_transaction AS evt ON evt.event_type_id = es.event_type_id
		JOIN transaction_type AS tt ON tt.transaction_type_id = evt.transaction_type_id
		WHERE es.date_event = '{$string_date}'
		AND es.event_status = 'scheduled'
		AND tt.clearing_type = 'ach'
		AND es.origin_id IS NOT NULL
		GROUP BY es.event_schedule_id
		ORDER BY es.origin_id, es.event_schedule_id ";
		
		$result = $this->db->Query($query);

		while ($event = $result->fetch(PDO::FETCH_OBJ)) 
		{
			$event_list[] = $event;
		}
		
		return $event_list;
	}
	
	private function idBatchDuplicates($event_list)
	{
		$seen = array();
		$dupes = array();
		
		foreach($event_list as $event)
		{
			if(! in_array($event->origin_id, $seen))
			{
				$seen[] = $event->origin_id;
			}
			else
			{
				echo "\t+ Found Duplicate: ";
				echo "[{$event->application_id}] [{$event->event_schedule_id}] [{$event->origin_id}]\n";
				
				$dupes[] = array($event->application_id, $event->event_schedule_id);
			}
		}
		
		return $dupes;
	}
	
	private function purgeDuplicates($duplicates)
	{
		foreach($duplicates as $event)
		{
			list($application_id, $event_schedule_id) = $event;
			echo "\t* Removing event $event_schedule_id from $application_id\n";
			//Remove_One_Unregistered_Event_From_Schedule($application_id, $event_schedule_id);
		}
	}
}