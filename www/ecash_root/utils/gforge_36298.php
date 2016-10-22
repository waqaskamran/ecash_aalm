<?php
/**
 * #GForge 36298 - Impact has two requests
 * 
 * 1. Move all accounts with a Fatal ACH flag into the Collections Contact status and
 *    the Fatal queue.  We're excluding statuses that are in a final state such as Withdrawn,
 *    Denied, Inactive Paid, etc.
 * 
 * 2. Move all Collections New accounts that are not already in the New queue into that queue.
 *    Older accounts (Last status change being 2008 or later) need to move to 2nd Tier (Pending).
 *
 * This script is written to be run by cronjobs/ecash_engine.php
 * 
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
function main()
{
	global $server;
	
	$db = ECash::getMasterDb();
	$company_id = ECash::getCompany()->company_id;

	$util = new DataFix_36298($db, $company_id);
	$util->run();
}

class DataFix_36298
{

	private $db;
	private $company_id;
	private $agent_id;
	
	public function __construct($db, $company_id)
	{
		if(empty($db))
		{
			die("Database Connection required!");
		}
		
		$this->db = $db;
		$this->company_id = $company_id;
		$this->agent_id = ECash::getAgent()->getAgentId();
	}

	public function run()
	{
		$this->handleFatalApplications();
		$this->handleCollectionsNewApps();
	}
	
	private function handleFatalApplications()
	{
		echo "Phase I: Applications with Fatal Flags\n";
		
		$applications = $this->getFatalApps();
		
		if(empty($applications))
		{
			return false;
		}
		
		// Grab our Collections Contact status ID for reference
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$contact_status = $asf->toId('queued::contact::collections::customer::*root');

		foreach($applications as $app)
		{
			$application_id = $app->application_id;

			// If their status isn't Collections Contact, change it
			if($application->application_status_id != $contact_status)
			{
				echo "[$application_id] Currently in {$app->status}, moving to Collections Contact.\n";
				$app = ECash::getApplicationByID($application_id);
				$app->application_status_id = $contact_status;
				$app->modifying_agent_id = $this->agent_id;
				$app->save();
			}

			// Move them to the Fatal Queue
			$queue_manager = ECash::getFactory()->getQueueManager();
			$fatal_queue = $queue_manager->getQueue("collections_fatal");

			$queue_item = new ECash_Queues_BasicQueueItem($application_id);
			$fatal_queue->insert($queue_item);
			echo "[$application_id] Added to Fatal Queue.\n";
		}

	}
	
	private function handleCollectionsNewApps()
	{
		// Get the queues we're going to be using.
		$queue_manager = ECash::getFactory()->getQueueManager();
		$general_queue 	= $queue_manager->getQueue("collections_general");
		$new_queue 		= $queue_manager->getQueue("collections_new");
		
		// Get the Application Status ID's for reference
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$second_tier_status = $asf->toId('pending::external_collections::*root');
		$contact_status = $asf->toId('queued::contact::collections::customer::*root');
		
		// Handle the OLD applications, anything with a status change older than 2009
		echo "Phase II: Old Collections New Applications\n";
		$applications = $this->getCollectionsNewApps('2009-01-01');
		if(!empty($applications))
		{
			foreach($applications as $app)
			{
				$application_id = $app->application_id;
				echo "[$application_id] Currently in {$app->status}, moving to Second Tier (Pending).\n";
				$app = ECash::getApplicationByID($application_id);
				$app->application_status_id = $second_tier_status;
				$app->modifying_agent_id = $this->agent_id;
				$app->save();
			}		
		}
		
		// If they've got scheduled events, put them into the Collections New queue, otherwise
		// toss them into the Collections General queue and put them into Collections Contact
		// status.D
		$applications = $this->getCollectionsNewApps();
		echo "Phase III: Collections New Applications\n";
		if(!empty($applications))
		{
			foreach($applications as $app)
			{
				// If they have scheduled events, put them into the Collections New queue
				if($app->num_scheduled > 0)
				{
					$application_id = $app->application_id;
					echo "[$application_id] has {$app->num_scheduled} events, moving to Collections New queue.\n";
					$queue_item = new ECash_Queues_BasicQueueItem($application_id);
					$new_queue->insert($queue_item);
				}
				else
				{
					// Move the rest into the Collections New queue
					$application_id = $app->application_id;
					echo "[$application_id] Currently in {$app->status}, moving to Colections Contact.\n";
					$app = ECash::getApplicationByID($application_id);
					$app->application_status_id = $contact_status;
					$app->modifying_agent_id = $this->agent_id;
					$app->save();
	
					// CFE *should* be doing this for us, but just in case...
					$queue_item = new ECash_Queues_BasicQueueItem($application_id);
					$general_queue->insert($queue_item);
				}
			}
		}
	}
	
	private function getFatalApps()
	{
		$sql = "
			/**
			 * Query for Impact / HCV / RBT #36298 - Collections Data Fix
			 * 
			 * The customer wishes to identify any account with a Fatal ACH
			 * flag that is not already in the Collections Contact status
			 * and is not in the Fatal queue and move them into that status
			 * and queue.  I've added additional filters to the query to ensure
			 * that accounts with the flag that are inactive or unable to move
			 * are excluded.  These exclusions include Inactive Paid / Settled
			 * statuses, Secont Tier (Sent), Funding Failed, and Withdrawn / Denied
			 * stauses which Impact agents have been using after an account is 
			 * Funding Failed.
			 *
			 * This query will eventually be used for the actual datafix.
			 */
			SELECT  a.application_id,
					a.application_status_id,
			        asf.name as status,
			        af.date_created as date_flagged,
			        (
			            SELECT COUNT(es.event_schedule_id)
			            FROM event_schedule AS es
			            WHERE es.application_id = a.application_id
			            AND es.event_status = 'scheduled'
			        ) AS num_scheduled
			FROM application as a
			JOIN application_status as asf on asf.application_status_id = a.application_status_id
			JOIN application_flag as af ON af.application_id = a.application_id
			JOIN flag_type AS ft ON ft.flag_type_id = af.flag_type_id
			WHERE a.company_id = {$this->company_id}
			AND   ft.name_short = 'has_fatal_ach_failure'
			AND   af.active_status = 'active'
			AND   asf.name NOT IN ( 'Inactive (Paid)', 'Inactive (Recovered)', 'Inactive (Settled)',
			                        'Bankruptcy Verified', 'Bankruptcy Notification', 'Chargeoff', 
			                        'Denied', 'Declined', 'Funding Failed', 'Second Tier (Sent)',
			                        'Withdrawn', 'Deceased Verified', 'Second Tier (Pending)', 
			                        'Collections (Dequeued)')
			AND NOT EXISTS (
			    SELECT 'X'
			    FROM n_time_sensitive_queue_entry AS qe
			    JOIN n_queue AS q ON q.queue_id = qe.queue_id
			    WHERE qe.related_id = a.application_id
			    AND q.name_short IN ('collections_fatal')
			    AND (qe.date_expire > CURRENT_TIMESTAMP OR qe.date_expire IS NULL)
			)
			-- HAVING num_scheduled = 0
			ORDER BY status
		";

		$applications = array();
		$result = $this->db->query($sql);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$applications[] = $row;			
		}

		return $applications;

	}
	
	private function getCollectionsNewApps($max_age_date = NULL)
	{
		if(!empty($age))
		{
			$age_sql = "AND date_application_status_set < '$age'";
		}
		else
		{
			$age_sql = '';
		}

		$sql = "
		/**
		 * Query for Impact / HCV / RBT #36298 - Collections Data Fix
		 * 
		 * The customer wishes to identify any account in a Collections New
		 * Status and put them ino the New queue.  I've discovered that there
		 * are a large number of accounts that do not have scheduled transactions
		 * so I've asked Impact if they would prefer those accounts get moved
		 * into the Collections Contact status.
		 *
		 * This query will eventually be used for the actual datafix.
		 */
		SELECT  a.application_id,
		        asf.name as status,
		        a.date_application_status_set,
		        af.date_created as date_flagged,
		        (
		            SELECT COUNT(es.event_schedule_id)
		            FROM event_schedule AS es
		            WHERE es.application_id = a.application_id
		            AND es.event_status = 'scheduled'
		        ) AS num_scheduled
		FROM application as a
		JOIN application_status as asf on asf.application_status_id = a.application_status_id
		LEFT JOIN application_flag as af ON af.application_id = a.application_id
		LEFT JOIN flag_type AS ft ON ft.flag_type_id = af.flag_type_id
		WHERE a.company_id = {$this->company_id}
		AND   (ft.name_short = 'has_fatal_ach_failure' OR ft.name_short IS NULL)
		AND   (af.active_status = 'inactive' OR af.active_status IS NULL)
		AND   asf.name = 'Collections New'
		AND NOT EXISTS (
		    SELECT 'X'
		    FROM n_time_sensitive_queue_entry AS qe
		    JOIN n_queue AS q ON q.queue_id = qe.queue_id
		    WHERE qe.related_id = a.application_id
		    AND q.name_short IN ('collections_new')
		    AND (qe.date_expire > CURRENT_TIMESTAMP OR qe.date_expire IS NULL)
		)
		HAVING num_scheduled = 0 $age_sql
		ORDER BY date_application_status_set ASC
		";

		$applications = array();
		$result = $this->db->query($sql);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$applications[] = $row;			
		}

		return $applications;
	}
	
}