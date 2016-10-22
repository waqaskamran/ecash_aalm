<?php
require_once(SQL_LIB_DIR . "scheduling.func.php");

	class ECash_NightlyEvent_QueueLostApps extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = null; //ADD YOSELF A BUSINESS RULE!!!!!!!!!!!!!!!!
		protected $timer_name = 'Queue_Lost_Apps';
		protected $process_log_name = 'queue_lost_apps';
		protected $use_transaction = FALSE;
		
		public function __construct()
		{
			$this->classname = __CLASS__;
			
			parent::__construct();
		}
		

		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$this->QueueLostApps($this->today);
		}

		
		/**
		 * Queues arrangements that have no pending events, are in made arrangements status, and do not exist/aren't pending in any queues
		 * 
		 */
		private function QueueLostApps($run_date)
		{
			//get applications that qualify
			$holidays = Fetch_Holiday_List();
			$pdc = new Pay_Date_Calc_3($holidays);
			
		 	$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		 	$collected_collection_statuses = array();
		 	$collected_collection_statuses[] = $status_list->toId('current::arrangements::collections::customer::*root');
		 	$collected_collection_statuses[] = $status_list->toId('queued::contact::collections::customer::*root');
		 	
		 	
		 	$collections_list = implode(',',$collected_collection_statuses);
			
			$business_rules = new ECash_BusinessRulesCache(ECash::getMasterDb());

		 	
		 	//This query's an abomination, it's gonna take freaking forever to run.  It's very possible that we'll have to 
		 	//Rework this somehow due to scaling issues in HMS.  Seriously, It takes like 3 minutes to run on Live! (Also, that means there's an assload of lost apps)
		 	//TODO: Fix this!  There! all better.
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT 
				    count(es.event_schedule_id) as 'events',
				    count(tr.transaction_register_id) as 'transactions',
				    count(ntsqe.queue_entry_id) as 'time_sensitive_queues',
				    count(naqe.queue_entry_id) as 'my_queues',
				    app.application_id
				FROM 
				    application app
				LEFT JOIN
				    event_schedule es ON es.application_id = app.application_id AND es.event_status = 'scheduled'
				LEFT JOIN
				    transaction_register tr ON tr.application_id = app.application_id AND tr.transaction_status = 'pending'
				LEFT JOIN 
				    n_agent_queue_entry naqe ON (naqe.related_id = app.application_id AND (naqe.date_expire > NOW() OR naqe.date_expire IS NULL))
				LEFT JOIN
				    n_time_sensitive_queue_entry ntsqe ON (ntsqe.related_id = app.application_id AND (ntsqe.date_expire > NOW() OR ntsqe.date_expire IS NULL))
				WHERE
				    app.application_status_id IN ({$collections_list})
				AND
					app.company_id = {$this->company_id}
				GROUP BY app.application_id
				HAVING events = 0 AND transactions = 0 AND my_queues = 0 AND time_sensitive_queues = 0
				";

			$result = $this->db->Query($query);

			$qm = ECash::getFactory()->getQueueManager();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$application_id = $row->application_id;
				$application = ECash::getApplicationById($application_id);
				$flags = $application->getFlags();
		
				$affiliations = $application->getAffiliations();
				$currentAffiliation = $affiliations->getCurrentAffiliation('collections', 'owner');
				if(!empty($currentAffiliation))
				{
					$agent = $currentAffiliation->getAgent();
				}

				//add to myqueue/collections queue depending on after partial rule
				$rule_set = $business_rules->Get_Rule_Set_Tree($application->rule_set_id);

				//Blah blah corporate culture, blah blah sacrifices, blah blah so I commented out the business rules and hardcoded it.
				$days_forward = 0;
				$action = 'My Queue';			
				$inactivity_expiration = 2; //business days.
				
				$date_available = $pdc->Get_Calendar_Days_Forward($run_date, $days_forward);

				$date_inactive_expiration = $inactivity_expiration >= 1 ? strtotime($pdc->Get_Calendar_Days_Forward($date_available,$inactivity_expiration)) : null;			
				
			
				//update account to collections contact status
				Update_Status(null, $application_id, array('queued','contact','collections','customer','*root'), NULL, NULL, false);
							
				//remove from queues, just in case.  We don't want any CFE rules screwing things up for us!
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
				
				if(!empty($agent))
				{
					switch($action)
					{
						//Yes, I know there's only one action right now, but it's just a matter of time before they want
						//something strange and different!
						case 'My Queue':
						default:
							$agent->getQueue()->insertApplication($application, 'collections', $date_inactive_expiration, strtotime($date_available));
							//if adding to myqueue add to collections queue with a delay of X days determined by myqueue inactivity rule
							if ($date_inactive_expiration) 
							{
								
								//We need to insert it into either the collections fatal or collections general depending...
								if (!$flags->get('has_fatal_ach_failure') && !$flags->get('has_fatal_card_failure')) 
								{
									$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
									$queue_item->DateAvailable = $date_inactive_expiration;
									$qm->moveToQueue($queue_item, 'collections_general');
								}
								else 
								{
									$queue_item = $qm->getQueue('collections_fatal')->getNewQueueItem($application_id);
									$queue_item->DateAvailable = $date_inactive_expiration;
									$qm->moveToQueue($queue_item, 'collections_fatal');
								}
							}
							$this->log->Write("{$application_id} in a collections status, but no queues. Inserting into Agent {$agent->getAgentId()}'s {$action} ON {$date_available}");
						break;
					}
				}
				else 
				{  
					//It's not in any queues, it has no controlling agent, its totally lost! I don't care where you want it to go. I'm gonna throw it in the collections queue!
					//We need to insert it into either the collections fatal or collections general depending...
					if (!$flags->get('has_fatal_ach_failure') && !$flags->get('has_fatal_card_failure')) 
					{
						$queue_item = $qm->getQueue('collections_general')->getNewQueueItem($application_id);
						$queue_item->DateAvailable = $date_inactive_expiration;
						$qm->moveToQueue($queue_item, 'collections_general');
					}
					else 
					{
						$queue_item = $qm->getQueue('collections_fatal')->getNewQueueItem($application_id);
						$queue_item->DateAvailable = $date_inactive_expiration;
						$qm->moveToQueue($queue_item, 'collections_fatal');
					}					
					$this->log->Write("{$application_id} is a lost arrangement! No controlling agent!! Inserting into Collections Queue ON {$date_available}");
				}
				
				//Hey!  They don't have a fatal ACH flag!  We need to schedule a full-pull, otherwise they might go into limbo!
				if(!$flags->get('has_fatal_ach_failure') && !$flags->get('has_fatal_card_failure'))
				{
					//Schedule a full pull if it doesn't have a fatal flag!!!!!
					
					if($date_pair = $this->getFullPullDate($application))
					{
						//Schedule_Full_Pull($application_id, NULL, NULL, $date_pair['event'], $date_pair['effective']);
					}
				}
			}
			//Shoop Da Woop!
		}

		//Centralizing the function that grabs the date for the full pull!
		function getFullPullDate($application)
		{
			$application_id = $application->application_id;
			$rules = $application->getBusinessRules();
			$reattempt_date = $rules['failed_pmnt_next_attempt_date']['full_pull'];
			$delay = (is_array($rules['full_pulls'])) ? $rules['full_pulls']['days_delinquent'] : $rules['full_pulls'];
	
			$date_pair = getReattemptDate($reattempt_date, $application, $delay);
			$this->log->Write("Scheduling Full Pull on {$application_id} for {$date_pair['event']} - {$date_pair['effective']} based on the rule '{$reattempt_date}' with a delay of {$delay}");
			return $date_pair;
			
		}
		
		
	}

?>
