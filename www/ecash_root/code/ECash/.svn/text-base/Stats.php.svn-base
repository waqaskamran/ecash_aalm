<?php
//Not everything is requiring the files which just coincidentally happen to require files which require that file.
require_once ("setstat.3.php");
require_once ("error.2.php");

class ECash_Stats
{
	private $status_chain_pulled;
	private $stat_chains;
	
	/**
	 * Flag to indicate whether or not to hit a pulled or react stat
	 *
	 * @var boolean
	 */
	private $set_pulled_stat;
	private $set_react_stat;
	
	public function __construct()
	{	
		$this->setStatusMap();
		$this->setPulledMap();
	}
	
	protected function setStatusMap()
	{
		$this->stat_chains = array(
			'denied::applicant::*root' => 'deny',
			'withdrawn::applicant::*root' => 'withdraw',
			'paid::customer::*root' => 'inactive_paid',
			'settled::customer::*root' => 'inactive_settled',
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
			'cccs::collections::customer::*root' => 'cccs',
			'ready::quickcheck::collections::customer::*root' => 'qc_ready',
			'sent::quickcheck::collections::customer::*root' => 'qc_sent'				
		);
	}
	
	protected function setPulledMap()
	{
		//"process 2" is what it used to be called
		$this->status_chain_pulled = array(
			'dequeued::verification::applicant::*root',
			'dequeued::underwriting::applicant::*root');
	}
	
	/**
	 * hitStat
	 * Hits a stat, any stat.  You tell it which stat to hit, and it hits it!
	 * This allows for ad-hoc stat hitting, meaning stats that are not associated with a loan-type or an application status
	 * @param Ecash_Models_Application $application the application you wanna hit a stat on.
	 * @param String $stat_type the name of the stat you wanna hit!
	 */
	public function hitStat(Ecash_Application $application, $stat_type)
	{
		// inhibit notices in case they are currently enabled (e.g., on localhost) while saving prior state
		$prev_error_reporting_level = error_reporting(E_ALL ^ E_NOTICE);
		
		//do setup for stat hitting
		$set_stat = $this->getSetStat($application);
		
		//Hit the stat!
		$set_stat->Set_Stat($_SESSION['config']->property_id, $stat_type);
		
		// restore previous error reporting level
		error_reporting($prev_error_reporting_level);
	}
	
	/**
	 * hitStatStatus
	 *	Hits a stat based on the application's application status.
	 *	Pass it an application, and hit a stat for the application's status.
	 * @param ECash_Models_Application $application
	 * @return TRUE when finished running
	 */
	public function hitStatStatus(ECash_Application $application)
	{
		$company_list = ECash::getFactory()->getReferenceList('Company');
		$company = $company_list->toName($application->company_id);
				
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$timer_name = "Statistics App ID - " . $application->application_id;
		$timer = new Timer(get_log());
		$timer->Timer_Start($timer_name);
		
		$is_react = $application->is_react == 'no' ? '' : 'is_react';

		$this->set_pulled_stat = false;
		$this->set_react_stat = false;
		
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$status_chain = $status_list->toName($application->application_status_id);				

		// modify $this->set_pulled_stat to indicate if the 'pulled' stat should be hit [AM]
		$this->checkHitPulled($application->application_id, $status_chain, $is_react);

		if (isset($this->stat_chains[$status_chain]))
		{
			$stat_type = $this->stat_chains[$status_chain];
		}
		else
		{
			// nothing to do here -- either corrupt chain or not tracking it.
			get_log()->Write(__METHOD__ . "(): No stat corresponds to status chain ({$status_chain}) for account id {$application->application_id}", LOG_INFO);
			return false;
		}

		get_log()->Write(__METHOD__ . "(): called for account id: {$application->application_id} ({$status_chain}) ({$stat_type})", LOG_INFO);

		// inhibit notices in case they are currently enabled (e.g., on localhost) while saving prior state
		$prev_error_reporting_level = error_reporting(E_ALL ^ E_NOTICE);

		$set_stat = $this->getSetStat($application);

		$set_stat->Set_Stat($_SESSION['config']->property_id, $stat_type);
		
		//GForge #18544
		//PW is requiring an extra stat for reporting based on campaign and company
		if($stat_type == 'funded' && ! empty( $_SESSION['campaign']['campaign_name']))
		{
			$campaign_stat = $stat_type . '_' . $_SESSION['campaign']['campaign_name'];
			$set_stat->Set_Stat($_SESSION['config']->property_id, $campaign_stat);
			get_log()->Write(__METHOD__ . "(): '{$campaign_stat}' stat hit.");
		}

		//Organic OLP Processes
		$ecash_reacts = array( 'cs_react',
					'email_react',
					'ecashapp_react' );		

		//App Campaign Info
		$campaign = ECash::getFactory()->getData('Application')->getCampaignInfo($application->application_id);;
							  
		// This sets the new 'react_funded' stat.
		if ($stat_type == 'funded' && $is_react != '')
		{
			$set_stat->Set_Stat($_SESSION['config']->property_id, 'react_funded');
			get_log()->Write(__METHOD__ . "(): 'react_funded' stat hit.");
			
			/* GF #22158
       			 *
			 * We need to track PW Marketing reacts. We'll check to see if the OLP
			 * process is an online react, and also make sure that the react did come
			 * through a marketing site.
			 * 
			 * Note: In the case of a marketing react, it should hit all Stats up until this point
			 * (funded, react_funded, and react_funded_marketing)
			 */
			if(!in_array($application->olp_process, $ecash_reacts) && ($campaign['site_id'] != $application->enterprise_site_id))
			{
				$set_stat->Set_Stat($_SESSION['config']->property_id, 'react_funded_marketing');
				get_log()->Write(__METHOD__."(): 'react_funded_marketing' stat hit.");
			}
		}

		// This sets the old "pulled" stat
		if ($this->set_pulled_stat) 
		{
			get_log()->Write(__METHOD__ . "(): 'pulled_prospect' stat hit ({$stat_info->block_id}, {$stat_info->tablename}, {$site_config->stat_base}) for account id: {$application->application_id}", LOG_INFO);
			$set_stat->Set_Stat($_SESSION['config']->property_id, 'pulled_prospect');

			if ($this->set_react_stat) 
			{
				get_log()->Write(__METHOD__ . "(): 'react_pull' stat hit ({$stat_info->block_id}, {$stat_info->tablename}, {$site_config->stat_base}) for account id: {$application->application_id}", LOG_INFO);
				$set_stat->Set_Stat($_SESSION['config']->property_id, 'react_pull');
			}
		}

		get_log()->Write(__METHOD__ . "(): '{$stat_type}' stat hit for account id: {$application->application_id}", LOG_INFO);

		if($stat_type == 'funded')
		{
			$this->fundPW($application->pwadvid);
		}

		if($update_track_into_trans_reqd)
		{
			$this->updateTrack($application);
		}

		// restore previous error reporting level
		error_reporting($prev_error_reporting_level);

		return TRUE;
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
	protected function checkHitPulled($application_id, $status_chain, $is_react)
	{
		// check to make sure we're moving to an eligble status
		if(in_array($status_chain, $this->status_chain_pulled))
		{
			$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
			$status_id = $status_list->toId($status_chain);
			
			// assume that we need to set the pulled stat
			$this->set_pulled_stat = TRUE;

			// make sure we haven't been in any status that would hit the pulled stat
			// this assumes $this->status_chain_pulled is in progressive order [AM]
			foreach ($this->status_chain_pulled as $check)
			{
				// checked this status upstream
				if ($check === $status_chain) break;

				// make sure we haven't been in any of the statuses triggering a pulled stat
				if (!ECash::getFactory()->getModel('StatusHistory')->getStatusExists($application_id, $status_id))
				{
					$this->set_pulled_stat = FALSE;
					break;
				}

			}

			if ($this->set_pulled_stat && $is_react != '') $this->set_react_stat = true;
		}

	}
	
	/**
	 * hitStatLoanAction
	 * Hits a stat based on the loan action used
	 *
	 * @param ECash_Models_Application $application the application you wanna hit a stat for
	 * @param String $loan_action_name_short The name_short of the loan_action you wanna hit a stat for.
	 * @return boolean TRUE when successful, FALSE if not.
	 */
	public function hitStatLoanAction(ECash_Application $application, $loan_action_name_short)
	{
		if(!$loan_action_name_short) {
			get_log()->Write(__METHOD__ . "() Error: loan action ID {$loan_action_id} not found. Stat not set", LOG_ERR);
			return false;
		}

		$stat_type = strtolower("epla_" . $loan_action_name_short);
		get_log()->Write(__METHOD__ . "(): Stat to be set to {$stat_type}");

		// inhibit notices in case they are currently enabled (e.g., on localhost) while saving prior state
		$prev_error_reporting_level = error_reporting(E_ALL ^ E_NOTICE);

		$set_stat = $this->getSetStat($application);
		if ($set_stat) 
		{
			$set_stat->Set_Stat($_SESSION['config']->property_id, $stat_type);
		}
		if($update_track_into_trans_reqd)
		{
			$this->updateTrack($application);
		}

		// restore previous error reporting level
		error_reporting($prev_error_reporting_level);

		return true;
	}
	
	private function getSetStat(ECash_Application $application)
	{
		// scrub some session array elements to make sure residue isn't picked up from one statpro call to another
		if (isset($_SESSION['config'])) unset($_SESSION['config']);
		if (isset($_SESSION['statpro'])) unset($_SESSION['statpro']);
		if (isset($_SESSION['data'])) unset($_SESSION['data']);
		if (isset($_SESSION['campaign'])) unset($_SESSION['campaign']); //GF #18544

		$_SESSION['statpro'] = array();
		if(!empty($application->getModel()->track_id) )
		{
			$_SESSION['statpro']['track_key'] = $application->getModel()->track_id;
			$update_track_into_trans_reqd = false;
			get_log()->Write(__METHOD__ . "(): Existing TrackID '{$application->track_id}' was found for account id {$application->application_id}", LOG_INFO);
			
		}
		else
		{
			$update_track_into_trans_reqd = true;
			get_log()->Write(__METHOD__ . "(): No existing TrackID was found for account id {$application->application_id}", LOG_INFO);
		}

		$campaign = ECash::getFactory()->getData('Application')->getCampaignInfo($application->application_id);
		
		//GF #18544
		//Saving data for extra funded stat hit
		$_SESSION['campaign'] = $campaign;

		if (empty($campaign))
		{
			ECash::getLog()->Write(__METHOD__ . "(): Unable to locate campaign info for {$application->application_id}. No stat call can be made.");
			throw new Exception(__METHOD__ . "(): Unable to locate campaign info for {$application->application_id}. No stat call can be made.");
			return false;
		}

		$promo_id		= $campaign['promo_id'];
		$promo_sub_code = $campaign['promo_sub_code'];
		$license_key	= $campaign['license_key'];
		
		// inhibit notices in case they are currently enabled (e.g., on localhost) while saving prior state
		$prev_error_reporting_level = error_reporting(E_ALL ^ E_NOTICE);
				
		$stat_host = ECash::getConfig()->STAT_MYSQL_HOST;
		$stat_user = ECash::getConfig()->STAT_MYSQL_USER;
		$stat_pass = ECash::getConfig()->STAT_MYSQL_PASS;

		$scdb = new MySQL_4($stat_host, $stat_user, $stat_pass);
		$scdb->Connect();
	
		// The following is a quirk in how Config_6 is using MySQL_4
		$scdb->db_info['db'] = 'management';
	
		$scdb->Select('management');
		
		$config_6 = new Config_6($scdb);
		$site_config = $config_6->Get_Site_Config($license_key, $promo_id, $promo_sub_code);

		// TODO  stupid rc stat hack
		//if (EXECUTION_MODE != 'LIVE') $site_config->stat_base = "rc_" . $site_config->stat_base;
		if (EXECUTION_MODE != 'LIVE') 
		{
			if(!preg_match('/^rc_/', $site_config->stat_base)) 
			{
				$site_config->stat_base = "rc_" . $site_config->stat_base;
			}
		}

		if (Error_2::Check($site_config ) || strlen($site_config->site_name) == 0)
		{
			get_log()->Write(__METHOD__ . "(): Unable to get Site_Config for account id: {$application->application_id}", LOG_ERR);
			throw new Exception(__METHOD__ . "(): Unable to get Site_Config for account id: {$application->application_id}", LOG_ERR);
			return false;
		}
		$_SESSION['config'] = $site_config; // for statpro

		$stat_info = Set_Stat_3::Setup_Stats (null, $site_config->site_id, $site_config->vendor_id,
						      $site_config->page_id, $promo_id, $promo_sub_code,
						      $site_config->promo_status, $date);

		$set_stat = new Set_Stat_3();
		$set_stat->Set_Mode(EXECUTION_MODE);

		return $set_stat;
	}

	private function fundPW($peewad)
	{
		if (isset($_SESSION['statpro']['statpro_obj']) && strlen($peewad))
		{
		    $_SESSION['statpro']['statpro_obj']->PW_Fund($peewad);
		}		
	}

	private function updateTrack(ECash_Models_Application $application)
	{
		if (!empty($_SESSION['statpro']['track_key']) )
		{
			// Update fresh track_key into transaction
			$application->track_id = $_SESSION['statpro']['track_key'];
			$application->save();
			get_log()->Write(__METHOD__ . "() TrackID '{$_SESSION['statpro']['track_key']}' assigned to account id {$application->application_id}", LOG_INFO);

			$company_list = ECash::getFactory()->getReferenceList('Company');
			$company = $company_list->toName($application->company_id);
			
			$_SESSION["statpro"]["statpro_obj"]->Set_Track_Space($company, $application->application_id);
			get_log()->Write(__METHOD__ . "() Set_Track_Space called for account id {$application->application_id}", LOG_INFO);
		}
	}
	
}


?>
