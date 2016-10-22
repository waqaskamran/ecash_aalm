<?php

  /**
   *
   * Fraud rules were commissioned to be global across CLK companies.
   * To facilitate this and still allow for fraud to be split later
   * on, we'll do the following:
   *
   * Put all fraud business rules under UFC (simply because they're
   * the first eCash 3.0 customer).  As long as as the "days to run"
   * ARE NOT SET for other companies this process will examine
   * company-wide fraud rules and not send out duplicate emails.  If
   * fraud rules do become company-specific this process will need to
   * be changed to not examine all (fraud and business) rules, but
   * just ones for that company.  JRF
   *
   */

//make this something retarded so it won't likely interfere with
//future defines in www/config.php
define('MY_CRON_DIR', dirname(__FILE__) . '/');

require_once('libolution/Mail/Trendex.1.php');
require_once(ECASH_COMMON_DIR . '/Fraud/FraudCheck.php');
require_once('applog.1.php');
require_once( 'acl.2.php');


class Fraud_Reminder
{
	private $recipients;
	private $business_rules;
	private $settings;
	private $server;
	private $db;
	private $log;
	private $mail;
	private $from_address;
	private $ecash_address;
	private $cached_rules = array();

	const HRS_WARN_FRAUD = "Fraud Exp. Warning Time";
	const HRS_WARN_RISK = "High Risk Exp. Warning Time";
	const EMAIL_WARN_FRAUD = "Fraud Exp. Warning Email";
	const EMAIL_WARN_RISK = "High Risk Exp. Warning Email";
	const EMAIL_EXP_FRAUD = "Fraud Expired Email";
	const EMAIL_EXP_RISK = "High Risk Expired Email";

	const HRS_WARN_DEFAULT = 48;
	const RULE_COMPONENT_NAME_SHORT = 'fraud_settings';
	const RULE_COMPANY_SHORT = 'ufc';

	public function __construct(Server $server)
	{
		$this->db = ECash::getMasterDb();
		$this->server = $server;
		$this->log = $server->log;
		//set the business rules object
		$this->business_rules = new ECash_Business_Rules($this->db);

		switch(EXECUTION_MODE)
		{
			case 'LIVE':
				$this->ecash_address = 'live.ecash.clkonline.com';
				break;

			case 'RC':
				$this->ecash_address = 'rc.ecash.clkonline.com';
				break;

			default:
			case 'LOCAL':
				$this->ecash_address = 'ecash3.0.ds08.tss';
				break;
		}
	}

	/* This is mostly garbage to create a stub server class so this
	 * file can be tested both from:
	 * ecash_engine.php -> nightly.php *AND*
	 * standalone from the CLI
	 */
	public static function main($argc, $argv)
	{
		if($argc < 2)
		{
			echo "Usage:   {$argv[0]} mode\n";
			echo "example: {$argv[0]} local-shd\n";
			exit(1);
		}
		else
		{
			$_ENV['ECASH_MODE'] = strtolower($argv[1]);
			require_once(MY_CRON_DIR."../www/config.php");
			require_once(LIB_DIR.'common_functions.php');
		}

		$sql = ECash::getMasterDb();
		$company_model = ECash::getFactory()->getModel('Company');
		$company_model->loadBy(array('name_short' => self::RULE_COMPANY_SHORT));

		require_once(MY_CRON_DIR ."../utils/mini-server.class.php");
		$server = new Server(new Applog('fraud_reminder'), $sql, $company_model->company_id);

		//use this same code if calling externally (i.e. not from CLI)
		exit(Fraud_Reminder::Call($server));
	}

	public static function Call(Server $server)
	{
		$reminder = new Fraud_Reminder($server);
		$reminder->Load_Server_Data();
		$reminder->Turn_Expired_Risk_Rules_Off();
		return($reminder->Send_Reminders());
	}
	
	public function Turn_Expired_Risk_Rules_Off()
	{
		//echo __METHOD__;
		//turn rules off
		$rules = $this->Expire_Rules();
		//echo '<!-- RISK APPS: ', print_r($apps, TRUE), ' -->';
		$fraud_query = new Application_Fraud_Query($this->server);
		$apps = $fraud_query->Get_Queue_Apps(FraudRule::RULE_TYPE_RISK);

		//echo "Examining apps:\n";
		//print_r($apps);
		
		require_once(SQL_LIB_DIR . "customer_contact.class.php");		
		$customer_contact = new Customer_Contact($this->db);

		//then make the changes
		foreach($rules as $rule_row)
		{
			//create a rule object
			$rule = new FraudRule($rule_row->fraud_rule_id, //rule_id
								  $rule_row->date_modified, //timestamp
								  $rule_row->date_created, //timestamp
								  false, //all these are now inactive
								  $rule_row->exp_date,
								  $rule_row->rule_type,
								  $rule_row->confirmed,
								  $rule_row->name,
								  $rule_row->comments);
			
			$checker = new FraudCheck($this->db, NULL, NULL, $rule);
                
			foreach($apps as $application)
			{		
				$results = $checker->processApplication($application);
				//echo '<!-- RISK APP: ', print_r($application, TRUE), "\nRules: ", print_r($results, TRUE), ' -->';
                        
				if(empty($results[FraudRule::RULE_TYPE_FRAUD]) && empty($results[FraudRule::RULE_TYPE_RISK]))
				{
					//echo "<!-- remove risk flags: ", print_r($application, TRUE),  " -->\n";
					//remove high-risk flags
					$customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'high_risk');
					$fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_RISK);
					//move to underwriting
					$this->Update_Status($application->application_id, array('queued', 'underwriting', 'applicant', '*root' ));
				}
				else
				{
					//remove risk flags (delete all) +
					$customer_contact->Remove_All_Of_Type($application->company_id, $application->application_id, 'high_risk');
					$fraud_query->Remove_Rule_Type_From_App($application->application_id, FraudRule::RULE_TYPE_RISK);
					//insert new risk flags
					$customer_contact->Add_Many_Columns($application->company_id, $application->application_id, $this->Get_Columns($results[FraudRule::RULE_TYPE_RISK]), 'high_risk');
					$fraud_query->Add_Rules_To_App($application->application_id, $this->Get_Rule_IDs($results[FraudRule::RULE_TYPE_RISK]));
					//those two ops should effectively update the (new) correct flags
				}
			}
		}
		//echo " done\n";
	}

	public function Send_Reminders()
	{
		
		//get rules
		$rules = $this->Load_Rules();

		$this->Send_Expiring(FraudRule::RULE_TYPE_FRAUD, $this->settings[self::EMAIL_WARN_FRAUD], $this->settings[self::HRS_WARN_FRAUD]);
		$this->Send_Expiring(FraudRule::RULE_TYPE_RISK, $this->settings[self::EMAIL_WARN_RISK], $this->settings[self::HRS_WARN_RISK]);
		$this->Send_Expired(FraudRule::RULE_TYPE_FRAUD, $this->settings[self::EMAIL_EXP_FRAUD]);
		$this->Send_Expired(FraudRule::RULE_TYPE_RISK, $this->settings[self::EMAIL_EXP_RISK]);
		
	}

	public function Load_Server_Data()
	{
		//echo __METHOD__;
		//load the company list for future queries
		//I want to kill myself for having to do this.. these server classes should be all combined
		if(method_exists($this->server, 'Fetch_Company_List'))
		{
			$this->server->Fetch_Company_List();
			$_SESSION['Server_state']['company_list'] = $this->server->company_list;
		}
		else
		{
			//the same session stuff from above will get set in here (ecash_engine.php):
			$this->server->Fetch_Company_IDs();
			$this->server->company_list = $_SESSION['Server_state']['company_list'];
		}

		$query = "select agent_id from agent where login = 'ecash_support' LIMIT 1";
		$result = $this->db->query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$_SESSION['agent_id'] = $row->agent_id;
		//echo " done\n";
	}
	
	private function Update_Status($application_id, $status)
	{
		//while this function is in lib/common_functions.php it is not
		//included by this file.  It will be included by other classes
		//after the appropriate config file is loaded
		Update_Status($this->server, $application_id, $status);
	}
	
	private function Get_Columns($rules)
	{
		$columns = array();
		foreach($rules as $rule)
		{
			$fields = $rule->getColumns();
			foreach($fields as $field)
			{
				//just keep this a unique list of columns
				$columns[$field] = NULL;
			}
		}
		return array_keys($columns);
	}
	
	private function Expire_Rules()
	{
		$rules = $this->Get_Expired(FraudRule::RULE_TYPE_RISK);
		
		$update = "update fraud_rule
					set active = 0
					where
						rule_type = '".FraudRule::RULE_TYPE_RISK."'
					and exp_date < unix_timestamp(date_add(current_date(), interval 1 day))
					and active = 1";

		$this->db->exec($update);

		//echo "Returning rules:\n";
		//print_r($rules);
		return $rules;
	}
	
	private function Send_Expiring($rule_type, $emails, $time)
	{
		$this->Send($this->Get_Expiring($rule_type, $time), $rule_type, $emails, FALSE);
	}

	private function Send_Expired($rule_type, $emails)
	{
		$this->Send($this->Get_Expired($rule_type, $time), $rule_type, $emails);
	}
	
	private function Send($rules, $rule_type, $emails, $expired = TRUE)
	{
		$expired_word = $expired ? 'expired' : 'expiring';
		//echo "{$expired_word} {$rule_type} rules found: " , count($rules), "\n";
		if(count($rules))
		{
			$list = "";
			foreach($rules as $rule)
			{
				// format the list
				$list .= "{$rule->name}\t{$rule->comments}\n";
			}

			foreach($emails as $address)
			{
				/** for OLE
				 * 'email_primary' => $address,
				 * 'email_primary_name' => 'eCash Support',
				 * 'from' => $this->from_address,
				 * 'site_name' => $this->ecash_address,
				 */
				$data = array('support_email' => $this->from_address,
							  'rules' => $list,
							  'ecash_address' => $this->ecash_address
						 	);

				// send the email
				if($expired)
				{
					$this->Send_Email("ECASH_{$rule_type}_EXPIRED", $address, $data);
				}
				else
				{
					$this->Send_Email("ECASH_{$rule_type}_WARNING", $address, $data);
				}					
			}
		}
	}

	

	private function Load_Rules()
	{
		//expiration warning buffer (24, 48, 72hrs)
		//recipient list for expiration warning
		//recipient list for expired
		//days to run is taken care of by cronscheduler

		$this->settings = $this->business_rules->Get_Rule_Set_Component_Parm_Values($this->server->company, self::RULE_COMPONENT_NAME_SHORT);
		//echo 'Business Rules: ';
		//print_r($this->settings);

		$indexes = array(self::HRS_WARN_FRAUD,
						 self::HRS_WARN_RISK,
						 self::EMAIL_WARN_FRAUD,
						 self::EMAIL_WARN_RISK,
						 self::EMAIL_EXP_FRAUD,
						 self::EMAIL_EXP_RISK);
		
		foreach($indexes as $idx)
		{
			//numeric values
			if($idx == self::HRS_WARN_FRAUD || $idx == self::HRS_WARN_RISK)
			{
				if(empty($this->settings[$idx]))
					$this->settings[$idx] = self::HRS_WARN_DEFAULT; //fallback default
			}
			//email lists
			else
			{
				if(empty($this->settings[$idx]))
				{
					$this->settings[$idx] = array();
				}
				else
				{					
					$this->settings[$idx] = split(",", $this->settings[$idx]);
				}
			}
		}

		$this->from_address = ECash::getConfig()->COMPANY_SUPPORT_EMAIL;
	}

	private function Get_Expiring($rule_type, $hours)
	{
		
		$query = "select fr.fraud_rule_id, fr.name, fr.comments
					from fraud_rule fr
					where rule_type = '{$rule_type}'
					and exp_date < unix_timestamp(DATE_ADD(now(),INTERVAL {$hours} HOUR))
					and exp_date > unix_timestamp(now())";

		$result = $this->db->query($query);
		$fraud_rules = array();
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$fraud_rules[$row->fraud_rule_id] = $row;
		}

		return $fraud_rules;		
	}

	private function Get_Expired($rule_type)
	{
		if(empty($this->cached_rules[$rule_type]))
		{			
			$query = "select *
						 from fraud_rule fr
						 where rule_type = '{$rule_type}'
						 and exp_date < unix_timestamp(date_add(current_date(), interval 1 day))
						 and active = 1";

			$result = $this->db->query($query);
			$this->cached_rules[$rule_type] = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$this->cached_rules[$rule_type][$row->fraud_rule_id] = $row;
			}
		}
		return $this->cached_rules[$rule_type];
	}

   	private function Send_Email($mail_id, $address, $tokens)
	{
		//echo "sending email: {$mail_id}, to {$address}\n";
		try
		{
			require_once(LIB_DIR . '/Mail.class.php');
			$response = eCash_Mail::sendMessage($mail_id, $address, $tokens);
		}
		catch( Exception $e )
		{
			$this->log->Write(print_r($e, TRUE) . "Could not connect to send email, {$mail_id} not sent" , LOG_ERR);
		}

		// log if we don't get a response
		if (!$response)
		{
			$this->log->Write("Bad response from eCash_Mail::sendMessage - email {$mail_id} not sent ", LOG_ERR);
		}
		
		//echo "recieved response {$response}\n";
	}
}


function Fraud_Reminder($server)
{
	//echo __FUNCTION__ . " called\n";
	Fraud_Reminder::Call($server);
}

// uncomment to run from the CLI:
//Fraud_Reminder::main($_SERVER['argc'], $_SERVER['argv']);

?>