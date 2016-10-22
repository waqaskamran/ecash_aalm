<?php

require_once ("mysql.4.php");
require_once ("config.4.php");
require_once ("config.6.php");
require_once ("setstat.3.php");
//require_once (LIB_DIR . "/Config.class.php");

class Stat
{
	private $log;
	private $queue_process;
	private $mysql;
	private $db;
	private $property_short;
	private $company_id;
//
	private $stat_info; //the stat_info object used
	private $set_stat; //the set_stat object used for setting stats
	private $application_id; //the application_id used for logging the hitting of stats
	private $track_info;
	private $prev_error_reporting_level; //Previous Error reporting level
	private $site_config; //Site Config object
//	

	// holds all statuses that should trigger a pulled stat, in progressive order
	private $status_chain_pulled;
	private $set_pulled_stat;
	private $set_react_stat;
	private $stat_chains;

	public function __construct()
	{
		$this->db = ECash::getMasterDb();
		$this->log = ECash::getLog();

			$this->company_id = ECash::getCompany()->CompanyId;
		/**
		 * MAJOR TODO!!!!!!!!!!!!!!!!!!!
		 * WHY THE FUCK DOES THIS PIECE OF SHIT RELY ON THE LOG KNOWING
		 * WHICH COMPANY IS BEING FOR WHY ME WHY ME
		 *
		 * - signed, john hargrove
		 */
		//$this->property_short = $this->log->context;

		// This was commented out in recash_prep, not sure whether this is needed, so I'm leaving this as is [benb]
		$this->property_short = $this->log->context;
		$qp = $this->Determine_Queue_Process();

		// the $status_chain_pulled array must be in progressive order -- values here
		// were modified according to what was in eCash 2.x [AM]

		$this->status_chain_pulled = array(
			'dequeued::verification::applicant::*root',
			'dequeued::underwriting::applicant::*root'
		);
		
		$stat_host = ECash::getConfig()->STAT_MYSQL_HOST;
		$stat_user = ECash::getConfig()->STAT_MYSQL_USER;
		$stat_pass = ECash::getConfig()->STAT_MYSQL_PASS;

		$this->mysql = new MySQL_4($stat_host, $stat_user, $stat_pass);
		//Site Config Database
		$this->mysql->db_info['db'] = 'management';

		$this->stat_chains = array(
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
			'approved::servicing::customer::*root' => 'funded',
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
			'sent::quickcheck::collections::customer::*root' => 'qc_sent',
			'cccs::collections::customer::*root' => 'cccs',
			'pending::prospect::*root' => 'pending',
			'addl::verification::applicant::*root' => 'additional_verification',
			'refi::servicing::customer::*root' => 'refi',
			//'canceled::servicing::customer::*root' => 'canceled'
                        'canceled::applicant::*root' => 'canceled'
		);

	}


	private function Get_Block($stat_info) {
	}


	/**
	* Ecash_Hit_Stat
	* Hits stats based on an application id, status, and whether the app is a react.
	* Serves as a wrapper for Setup_Stat and Hit_Stat
	*
	* @param integer $application_id which app
	* @param mixed   $status         array or :: separated string of full status chain
	* @param string  $stat_cashline
	* @throws Exception
	*/
	public function Ecash_Hit_Stat($application_id, $status, $is_react, $date = null)
	{
		$this->application_id = $application_id;
		$this->set_pulled_stat = false;
		$this->set_react_stat = false;
		
		//Get the status chain to be used
		if( is_array($status) )
		{
			$status_chain = implode('::' ,$status);
		}
		elseif( strstr($status, "::") )
		{
			$status_chain = $status;
		}
		else
		{
			$this->log->Write("Ecash_Hit_Stat: unrecognized status: ({$application_id},{$status},{$is_react})", LOG_INFO);
			throw new Exception( "Unrecognized status." );
		}
		// modify $this->set_pulled_stat to indicate if the 'pulled' stat should be hit [AM]
		
		//Do setup for the stat hits.
		if(!$this->Setup_Stat($application_id))
		{
			return false;
		}
		
		//Determine Which stat to hit
		$this->Check_Set_Pulled($application_id, $status_chain, $is_react);

		if (isset($this->stat_chains[$status_chain]))
		{
			$model = 'new';
			$stat_type = $this->stat_chains[$status_chain];

			// Kind of hacked in, but it's the simplest solution for now until
			// we can really clean all this up.
			if (($stat_type == 'funded') && ($is_react != '')) $stat_type = 'react_funded';
		}
		else
		{
			// nothing to do here -- either corrupt chain or not tracking it.
			$this->log->Write("Ecash_Hit_Stat: No stat corresponds to status chain ({$status_chain}) for account id {$application_id}", LOG_INFO);
			return false;
		}

		$this->log->Write("Ecash_Hit_Stat: called for account id: {$application_id} ({$status_chain}) ($stat_type)", LOG_INFO);
		
		//HIT THAT STAT!!
		$result = $this->Hit_Stat($stat_type);


		// This sets the new 'react_funded' stat.
		if ($stat_type == 'funded' && $is_react != '') 
		{
			$this->Hit_Stat('react_funded');
			$this->log->Write("Ecash_Hit_Stat: 'react_funded' stat hit with model 'new'.");
		}

		// This sets the old "pulled" stat
		if ($this->set_pulled_stat) 
		{
			$this->log->Write("Ecash_Hit_Stat: 'pulled_prospect' stat hit ({$this->stat_info->block_id}, {$this->stat_info->tablename}, {$this->site_config->stat_base}) for account id: {$application_id}", LOG_INFO);
			$this->Hit_Stat('pulled_prospect');

			if ($this->set_react_stat) 
			{
				$this->log->Write("Ecash_Hit_Stat: 'react_pull' stat hit ({$this->stat_info->block_id}, {$this->stat_info->tablename}, {$this->site_config->stat_base}) for account id: {$application_id}", LOG_INFO);
				$this->Hit_Stat('react_pull');

			}
		}				
		
	
		// restore previous error reporting level
		error_reporting($this->prev_error_reporting_level);

		return $result;
	}

	/**
	 * LoanAction_Hit_Stat
	 * A wrapper for Setup_Stat and Hit_Stat that hits statpro stats based on an application id and loan action id.
	 * 
	 *
	 * @param int $application_id the ID of the application you want to hit a stat for
	 * @param int $loan_action_id the ID of the loan action you want to use as your stat.
	 * @return unknown
	 */
	public function LoanAction_Hit_Stat($application_id, $loan_action_id)
	{
		$la_query = "
			-- eCash 3.5, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT name_short FROM loan_actions where loan_action_id = {$loan_action_id}
					";
		//$this->log->Write(__METHOD__ . "(): . {$la_query}");
		$res = $this->db->Get_Column($la_query);

		if(!count($res)) 
		{
			$this->log->Write(__METHOD__ . "() Error: loan action ID {$loan_action_id} not found. Stat not set", LOG_ERR);
			return false;
		}

		$stat_type = strtolower("epla_" . array_pop($res));
		$this->log->Write(__METHOD__ . "(): Stat to be set to {$stat_type}");
		//Do Setup for the stat hitting
		$this->Setup_Stat($application_id);
		//HIT THAT STAT!
		$result = $this->Hit_Stat($stat_type);

		// restore previous error reporting level
		error_reporting($this->prev_error_reporting_level);

		return $result;
	}
	
	/**
	 * Setup_Stat 
	 * Does the necessary prep work for hitting a stat.  This function must be run before running Hit_Stat!
	 *
	 * @param int $application_id The ID for the application you want to hit stats on.
	 * @return boolean Whether or not the Setup was successfully performed.
	 */
	public function Setup_Stat($application_id)
	{
		$this->application_id = $application_id;
		// scrub some session array elements to make sure residue isn't picked up from one statpro call to another
		if (isset($_SESSION['config'])) unset($_SESSION['config']);
		if (isset($_SESSION['statpro'])) unset($_SESSION['statpro']);
		if (isset($_SESSION['data'])) unset($_SESSION['data']);
		
		if (!$this->Reconnect()) 
		{
			$this->log->Write(__METHOD__ . "() reconnect failed: ({$application_id})", LOG_INFO);
			return false;
		}
		
		// preliminary business needed for the statpro stuff that's invoked within Set_Stat_1::Set_Stat
		$this->track_info = $this->Fetch_Track_Info($application_id);

		$_SESSION['statpro'] = array();
		if( !empty($this->track_info->track_id) )
		{
			$_SESSION['statpro']['track_key'] = $this->track_info->track_id;
			//$update_track_into_trans_reqd = false;
			$this->log->Write(__METHOD__ . "() Existing TrackID '" . $this->track_info->track_id .  "' was found for account id {$application_id}", LOG_INFO);
		}
		else
		{
			//$update_track_into_trans_reqd = true;
			$this->log->Write(__METHOD__ . "() No existing TrackID was found for account id {$application_id}", LOG_INFO);
		}

		$campaign = ECash::getFactory()->getData('Application')->getCampaignInfo($application_id);
		
		if ($campaign == null || empty($campaign->license_key)) 
		{
			$this->log->Write(__METHOD__ . "() Unable to locate campaign info for {$application_id}. No stat call can be made.");
			return false;
		}

		$promo_id		= $campaign->promo_id;
		$promo_sub_code = $campaign->promo_sub_code;
		$license_key	= $campaign->license_key;

		// inhibit notices in case they are currently enabled (e.g., on localhost) while saving prior state
		$this->prev_error_reporting_level = error_reporting(E_ALL ^ E_NOTICE);

		$config_6 = new Config_6($this->mysql);

		$this->site_config = $config_6->Get_Site_Config($license_key, $promo_id, $promo_sub_code);
		
		// TODO  stupid rc stat hack
		//if (EXECUTION_MODE != 'LIVE') $site_config->stat_base = "rc_" . $site_config->stat_base;
		if (EXECUTION_MODE != 'LIVE') 
		{
			if(! preg_match('/^rc_/', $this->site_config->stat_base)) 
			{
				$this->site_config->stat_base = "rc_" . $this->site_config->stat_base;
			}
		}


		if (Error_2::Check($this->site_config ) || strlen($this->site_config->site_name) == 0)
		{
			$this->log->Write(__METHOD__ . "() Unable to get Site_Config for account id: {$application_id}", LOG_ERR);
			return false;
		}
		$_SESSION['config'] = $this->site_config; // for statpro
	
		$this->stat_info = Set_Stat_3::Setup_Stats (null, $this->site_config->site_id, $this->site_config->vendor_id, 
						      $this->site_config->page_id, $promo_id, $promo_sub_code,
						      $this->site_config->promo_status, $date);
		$this->set_stat = new Set_Stat_3();
		$this->set_stat->Set_Mode(EXECUTION_MODE);
		return true;
	}
	
	/**
	 * Hit_Stat
	 * Hits statpro stats for an application.   
	 * Just pass the name of the stat that needs to be hit.
	 * Setup_Stat MUST BE RUN FIRST!
	 *
	 * @param string $stat the stat that's going to be hit (usually lower case)
	 * @param int $date_occurred a unixtimestamp for the date the action occcurred
	 * @return boolean true on success.
	 */
	public function Hit_Stat($stat, $date_occurred = NULL)
	{
		if(!isset($this->set_stat) || !isset($this->site_config))
		{
			$this->log->Write(__METHOD__ . "() '$stat' was not hit for {$application_id}. Setup_Stat needs to be run first", LOG_INFO);
			return false;
		}
		//set $application_id
		$application_id = $this->application_id;
		
		//Hit that stat!!!!!
		// Set_Stat($property_id, $event_key, $count = 1, $date_occurred = null)
		$this->set_stat->Set_Stat($this->site_config->property_id, $stat, 1, $date_occurred);
		
		$this->log->Write(__METHOD__ . "() '$stat' stat hit for account id: {$application_id}", LOG_INFO);

		if ($stat == 'funded' && isset($_SESSION['statpro']['statpro_obj']) && strlen(@$this->track_info->pwadvid))
		{
		    $_SESSION['statpro']['statpro_obj']->PW_Fund($this->track_info->pwadvid);
		}

		//Update track_key if needed.
		if ( empty($this->track_info->track_id) && !empty($_SESSION['statpro']['track_key']) )
		{
			// Update fresh track_key into transaction
			$this->Update_Track_Info($application_id, $_SESSION['statpro']['track_key']);
			$this->log->Write(__METHOD__ . "() TrackID '" . $_SESSION['statpro']['track_key'] .  "' assigned to account id {$application_id}", LOG_INFO);

			$_SESSION["statpro"]["statpro_obj"]->Set_Track_Space($this->property_short, $application_id);
			$this->log->Write(__METHOD__ . "() Set_Track_Space called for account id {$application_id}", LOG_INFO);
		}

		return true;
	}

	public function Fetch_Track_Info($application_id)
	{
		$app = ECash::getApplicationById($application_id);
		$return_obj = new stdclass();
		$return_obj->track_id = $app->track_id;
		$return_obj->pwadvid = $app->pwadvid;
		return $return_obj;
	}

	private function Update_Track_Info($application_id, $track_id)
	{
		$data = array('application_id' => $application_id,
		'track_id' => $track_id);

		$update_track_info_query = "
			UPDATE application
			 SET   track_id = '{$track_id}'
			 WHERE application_id = '{$application_id}'";

		try
		{
			$this->last_query = $update_track_info_query;
			$this->db->query($update_track_info_query);
		}
		catch(Exception $e)
		{
			throw $e;
		}

		return TRUE;
	}

	/**
	* Checks if a specified app has ever been set to the specified status
	*
	* @param integer $application_id
	* @param array   $status full status chain
	* @return integer # of instances of the status in status_history
	*/
	public function Check_Status_Hist($application_id, $status)
	{
		$status_sql = "";
		for( $x = 0 ; $x < count($status) ; ++$x )
			$status_sql .= "level{$x}='" . $this->db->quote($status[$x]) . "' AND ";

		// $status_sql = chop( $status_sql, " AND" );
		$status_sql = chop( trim($status_sql), 'AND' );  // DLH, 2005.11.18, see notes on chop_string_right() in common functions.

		$query = "
				SELECT
					count(*)
				FROM
					status_history
				WHERE
					application_id	= {$application_id}
					AND application_status_id =
					(
						SELECT asf.application_status_id
						 FROM  application_status_flat asf
						 WHERE ({$status_sql})
					)
				 ";

		return ($this->db->querySingleValue($query) > 0);
	}

	public function Reconnect() {
		if (!$this->mysql->Is_Connected()) 
		{
			try 
			{
				$this->mysql->Connect();
			} 
			catch (Exception $e) 
			{
				return false;
			}
		}
		return true;
	}


	public function Determine_Queue_Process()
	{
		$query = "
			SELECT ecash_process_type
			FROM company
			WHERE company_id = {$this->company_id}";
		
		return $this->db->querySingleValue($query);
	}

	/**
	 * Check whether or not we should hit a pulled stat. Modifies the values of $this->set_pulled_stat
	 * and $this->set_react_stat to taste.
	 * @author Andrew Minerd
	 * @param int $application_id Application ID
	 * @param string $status_chain String representation of the status
	 * @param bool $is_react Whether the application is a reactivation
	 * @return void
	 */
	protected function Check_Set_Pulled($application_id, $status_chain, $is_react)
	{

		// check to make sure we're moving to an eligble status
		if (in_array($status_chain, $this->status_chain_pulled))
		{

			// assume that we need to set the pulled stat
			$this->set_pulled_stat = TRUE;

			// make sure we haven't been in any status that would hit the pulled stat
			// this assumes $this->status_chain_pulled is in progressive order [AM]
			foreach ($this->status_chain_pulled as $check)
			{

				// checked this status upstream
				if ($check === $status_chain) break;

				// make sure we haven't been in any of the statuses triggering a pulled stat
				if (!$this->Check_Status_Hist($application_id, explode('::', $check)))
				{
					$this->set_pulled_stat = FALSE;
					break;
				}

			}

			if ($this->set_pulled_stat && $is_react != '') $this->set_react_stat = true;

		}

		return;

	}

}

?>
