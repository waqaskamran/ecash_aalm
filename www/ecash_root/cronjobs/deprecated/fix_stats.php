<?php

define ('STAT_MODEL', 'NEW');

function Main()
{
	global $server;
	$db	= ECash::getMasterDb();
	
	ini_set("max_execution_time",0);
	ini_set("memory_limit",-1);

	require_once(LIB_DIR."common_functions.php");
	require_once(COMMON_LIB_DIR.'mysql.4.php');
	require_once(COMMON_LIB_DIR.'setstat.2.php');
	require_once(SERVER_CODE_DIR.'campaign_info_query.class.php');
	
	$event_type_array = array(
		'denied::applicant::*root' => 'deny',
		'withdrawn::applicant::*root' => 'withdraw',
		'paid::customer::*root' => 'inactive_paid',
		'pending::external_collections::*root' => 'second_tier_pending',
		'sent::external_collections::*root' => 'second_tier_sent',
		'dequeued::underwriting::applicant::*root' => 'underwriting_dequeued',
		'queued::underwriting::applicant::*root' => 'underwriting_queued',
		'follow_up::underwriting::applicant::*root' => 'underwriting_followup',
		'dequeued::verification::applicant::*root' => 'verification_dequeued',
		'queued::verification::applicant::*root' => 'verification_queued',
		'follow_up::verification::applicant::*root' => 'verification_followup',
		'new::collections::customer::*root' => 'collections_new',
		'active::servicing::customer::*root' => 'funded', // For 2.7 ONLY
		'approved::servicing::customer::*root' => 'funded', // For 3.0 ONLY
		'funding_failed::servicing::customer::*root' => 'funding_failed',
		'past_due::servicing::customer::*root' => 'past_due',
		'arrangements_failed::arrangements::collections::customer::*root' => 'arrangements_failed',
		'current::arrangements::collections::customer::*root' => 'made_arrangements',
		'unverified::bankruptcy::collections::customer::*root' => 'bankruptcy_notified',
		'verified::bankruptcy::collections::customer::*root' => 'bankruptcy_verified',
		'dequeued::contact::collections::customer::*root' => 'collections_contact_dequeued',
		'queued::contact::collections::customer::*root' => 	'collections_contact_queued',
		'follow_up::contact::collections::customer::*root' => 'collections_contact_followup',
		'ready::quickcheck::collections::customer::*root' => 'qc_ready',
		'sent::quickcheck::collections::customer::*root' => 'qc_sent');

	$status_map = Fetch_Status_Map($db);
		
	// Parses most recent logs
	$cfg = new DB_MySQLConfig('reportdb.tss','readonly','notwrite','clk_statpro_data',3309);
	$stat_pro_db = $cfg->getConnection();
		
	$new_stats_event_map = Fetch_StatPro_Event_Map($stat_pro_db, $event_type_array);
	$pulled = file("27_funded_stats.log");
	
	$stat = new Stat($server);
	$apps = array();

	// Match events to StatPro DB... If they don't exist, add them to an array of stats that
	// need to be hit.  Note: They will hit both legacy and new stats.
	for($x = 0; $x < count($pulled); $x++)
	{
		$line = $pulled[$x];
		
		$full_reg  = "/(\d{4})\.(\d{2})\.(\d{2}) (\d{2}:\d{2}:\d{2}) \[(\d{1})\] \[(\w{2,3})\] Ecash_Hit_Stat\: called for ";
		$full_reg .= "(account id:|application_id:) (\d{7,8}) \((\S*)\)/";
		
		preg_match($full_reg, $line, $matches);
		$date = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
		$time = $matches[4];
		$company = $matches[6];
		$application_id = $matches[8];
		$status = $matches[9];
			
		if($status == "'CASHLINE_PRINT'")
			continue;
			
		$track_info = $stat->Fetch_Track_Info($application_id);
		$track_id = $track_info->track_id;
		$events = Find_StatPro_Events($stat_pro_db, $track_id);
		$event_id = $new_stats_event_map[$event_type_array[$status]];
		
		if($event_id === null)
			continue;

		$found = FALSE;
		// Search through the events for this track_id
		foreach($events as $e)
		{
			// Same event type
			if($e['event_type_id'] = $event_id) 
			{
				$tmp_date = date('Y-m-d', $e['date_occured']);
				$tmp_time = date('H:i:s', $e['date_occured']);

				// Same event date & within 15 minutes of eachother
				if(($tmp_date == $date) && (Within_Time_Limit($time, $tmp_time, 900))) 
				{
					$found = TRUE;
				}
			}
		}
		if($found != TRUE)
		{
			echo "Did not find event.  We should create one.";
			$status_id = Status_ID_Lookup($status_map, $status);
				
			$apps[] = array(	'application_id' => $application_id, 
								'date_created' => "$date $time",
								'application_status_id' => $status_id,
								'application_status_chain' => $status);
			echo "  Added $application_id with status $status_id on $date $time\n";
		}
	}
	
	echo "Found " . count($apps) . " unhit stats.\n";
	
	$i = 0; // Counter..

	// Run through and hit all the stats.
	foreach ($apps as $app)
	{
		$i++;
		$_SESSION = array();
		$application_id = $app['application_id'];
		$timestamp 		= strtotime($app['date_created']);
		$status_id 		= $app['application_status_id'];
		$status_chain	= $app['application_status_chain'];
		$is_react = (Is_React($db, $application_id) ? 'yes' : '');

		//var_dump($app);
		echo "Processing {$application_id} - Status: {$status_chain}, React: $is_react, {$app['date_created']}\n";
		$stat->Ecash_Hit_Stat($application_id, $status_chain, $is_react, $timestamp);
		
	}
	echo "Total of $i stats\n";
	
}

function Is_React($db, $application_id)
{
	$sql = "
			SELECT  application_id
			FROM    paperless_queue
			WHERE   application_id = $application_id
			AND	date_paperless_printed > '2006-07-18 00:00:00'";
	
	$result = $db->query($sql);
	if ($result->rowCount() > 0)
	{
		return true;
	}
	
	$sql = "
			SELECT  is_react
			FROM 	application
			WHERE	application_id = $application_id
	";
	$result = $db->query($sql);
	if ($row = $result->fetch(PDO::FETCH_OBJ))
	{
		return ($row->is_react == 'yes' ? true : false);
	}
	
	return false;
}

function Fetch_StatPro_Event_Map($db, $event_type_array)
{
			
	$sql = "
		SELECT event_type_id, event_type_key
		FROM event_type
		WHERE event_type_key IN ('". implode("','",$event_type_array) ."') ";
	
	$result = $db->query($sql);
	
	$event_map = array();
	
	while($row = $result->fetch(PDO::FETCH_ASSOC))
	{
		// One way
		$array_map[$row['event_type_key']] = $row['event_type_id'];
		
		// The Other
		$array_map[$row['event_type_id']] = $row['event_type_key'];
	}

	return $array_map;
	
}

function Find_StatPro_Events($db, $track_id)
{
	$floor = mktime(0,0,0,8,11,2006);
	$ceiling = mktime(23,59,59,8,11,2006);
		
	$sql = "
		SELECT el.event_type_id, el.date_occured, el.date_recorded, el.space_id
		FROM event_log el
		JOIN track AS t ON (el.track_id = t.track_id)
		WHERE t.track_key='{$track_id}'
		AND date_recorded BETWEEN $floor AND $ceiling ";
	$result = $db->query($sql);
	return $result->fetchAll(PDO::FETCH_ASSOC);
}

function Status_ID_Lookup($map, $status_string)
{
	foreach($map as $status)
	{
		if($status['chain'] == $status_string)
			return $status['id'];
	}
	return null;
}

function Within_Time_Limit($a, $b, $range)
{
	$ma = strtotime($a);
	$mb = strtotime($b);
	
	if($ma > $mb)
	{
		if(($ma - $mb) <= $range) {
			return true;
		} else { return false; }
	}
	else
	{
		if(($mb - $ma) <= $range) {
			return true;
		} else { return false; }
		
	}

}

?>
