<?php

require_once LIB_DIR . "Document/Document.class.php";

class Nirvana_Trigger
{
	private $now; //reference time
	private $trigger_name;
	private $status;
	private $after_time;
	private $action_type;
	private $action_name;
	private $not_statuses = array();
	private $match_hour_start;
	private $match_hour_end;
	private $match_dow;
	private $sms;
	private $log;

	const ACTION_SMS = 'sms';
	const ACTION_EMAIL = 'email';
	const ACTION_EMAIL_XML = 'email-xml';
	const DOW_ALL = '1111111';	
	
	public function __construct($now, $trigger_name, $status, $after_time_string,  
								$action_type, $action_name,
								$not_statuses = NULL,
								$match_hour_start_string = NULL,
								$match_hour_end_string = NULL, $match_dow = NULL)
	{
		$this->now = $now;
		$this->trigger_name = $trigger_name;
		$this->status = $status;

		$this->after_time = $this->checkTime($after_time_string);

		$this->action_type = $action_type;
		if (( $this->action_type == self::ACTION_SMS ) && (ECash::getConfig()->SMS_SERVICE_GOOD)) 
			$this->sms = new ECash_Documents_SMS();
		
		$this->action_name = $action_name;
		
		if ( is_array( $not_statuses ) )
			$this->not_statuses = $not_statuses;
		else if ( $not_statuses )
			$this->not_statuses = array($not_statuses);


		$this->match_hour_start = $this->checkTime($match_hour_start_string);
		$this->match_hour_end = $this->checkTime($match_hour_end_string);
		
		if ( ! $match_dow )
			$this->match_dow = '1111111'; //look at every day
		else
			$this->match_dow = $match_dow;

		$this->log = ECash::getLog('nirvana');
	}

	private function checkTime($string)
	{
		if ( ! $string )
			return NULL;
		
		$result = strtotime($string, $this->now);

		if ( $result === FALSE )
			throw new Exception("Could not parse time string: {$string}");

		return $result;
	}
	
	public function getCanMatch()
	{
		//quick return
		if( ! $this->match_hour_start && ! $this->match_hour_end &&
			$this->match_dow = '1111111' )
			return TRUE;

		$dow = date('w', $this->now);

		if ( $this->match_dow[$dow] != '1' )
			return FALSE;
		
		if( $this->now < $this->match_hour_start ||
			$this->now >= $this->match_hour_end )
			return FALSE;

		return TRUE;
	}

	public function getNotStatuses() {
		if( is_array( $this->not_statuses ) )
			return $this->not_statuses;
		return array();
	}
	
	public function getHasNotStatuses()
	{
		if ( is_array( $this->not_statuses ) )
			return count( $this->not_statuses );
		return FALSE;
	}

	public function getStatus()
	{
		return $this->status;
	}
	
	public function getActionType()
	{
		return $this->action_type;
	}
	
	public function getActionName()
	{
		return $this->action_name;
	}

	public function getRangeStart()
	{
		$start_timestamp = strtotime('-7 days', $this->after_time); //asm 57

		//get the last_affected_date
		$log = ECash::getFactory()->getModel('NirvanaProcessLog');
		$loaded = $log->loadBy(array('trigger_name' => $this->trigger_name));
		//asm 57
		if ( $loaded && $log->last_affected_date )
		{
			$return = max($log->last_affected_date, $start_timestamp);
		}
		else
		{
			$return = $start_timestamp;
		}
		
		return $return;
		//////
		/*
		//for initial run look back to Nov 9, 2011 for certain campaigns
		if( $this->trigger_name == 'lead_accepted_5min' )
			return strtotime( '2011-12-03 09:49:00' );
		
		if( $this->trigger_name ==  'unsigned_app_5min' )
			return strtotime('Nov 9, 2011');
		
		//for testing
		//if( Nirvana::TEST_MODE )
		//	return strtotime( '-30 days', $this->after_time );
		
		//if there's no last run time, look back an day
		return strtotime( '-1 day', $this->now );
		*/
	}

	public function getRangeEnd()
	{
		return $this->after_time;
	}

	public function saveAffectedDate()
	{
		if(Nirvana::DRY_RUN)
			return;
		
		//flush any queued messages (before saving, incase something goes wrong)
		$this->flush();

		//save after_time as the last_effective_date
		$log = ECash::getFactory()->getModel('NirvanaProcessLog');
		$log->loadBy(array('trigger_name' => $this->trigger_name));
		$log->trigger_name = $this->trigger_name;
		$log->date_modified = time();
		$log->last_affected_date = $this->after_time;
		$log->save();

	}

	public function getName()
	{
		return $this->trigger_name;
	}

	public function send($application_id, $xml = NULL)
	{
		if ( Nirvana::DRY_RUN ) {
			echo __METHOD__ , ": {$this->trigger_name}, would send {$this->action_type} message {$this->action_name} to {$application_id}\n";
			return;
		}
		
		if (( $this->action_type == self::ACTION_SMS ) && (ECash::getConfig()->SMS_SERVICE_GOOD)) {
			$this->log->Write("Queueing SMS message {$this->action_name} for {$application_id}");
			$this->sms->queueTemplate( $application_id, $this->action_name );
		} elseif ( $this->action_type == self::ACTION_EMAIL ) {	
			$this->log->Write("Sending email message {$this->action_name} to {$application_id}");
			ECash_Documents_AutoEmail::Send($application_id, $this->action_name);
		} elseif ( $this->action_type == self::ACTION_EMAIL_XML ) {
			$this->log->Write("Sending email message {$this->action_name} to {$application_id}");
			$this->sendEmailWithXML( $application_id, $this->action_name, $xml );
		}

		//to test one (and only one)
		//die("sent one to $application_id");
	}

	private function sendEmailWithXML( $application_id, $document_name, $xml ) {		
		require_once CUSTOMER_LIB . "/autoemail_list.php";
		$doc_name = Get_AutoEmail_Doc(ECash::getServer(), $document_name);

		$this->log->write("Sending document $doc_name to application_id $application_id", LOG_WARNING);

	 	$doclist = ECash::getFactory()->getModel('DocumentList');		
	 	$doclist->getByNameShort(Nirvana::$COMPANY_ID, '0', $doc_name); //loan type ID '0'

		$package = new SimpleXMLElement($xml);
		$track_id = (string)$package->QUERY->TRACKKEY;

		$tokens = array(
			'Today' => date('m/d/Y'),
			'CustomerNameFirst' => (string)$package->QUERY->DATA->NAMEFIRST,
			'CustomerNameLast' => (string)$package->QUERY->DATA->NAMELAST,
			'CustomerStreet' => (string)$package->QUERY->DATA->STREET1,
			'CustomerCity' => (string)$package->QUERY->DATA->CITY,
			'CustomerState' => (string)$package->QUERY->DATA->STATE,
			'CustomerZip' => (string)$package->QUERY->DATA->ZIP,
		);
				
		$prpc = new ECash_Documents_Condor();
		$document = $prpc->Create($doclist->name, $tokens, true, $application_id, $track_id, null);

		$recp = array(
			'email_primary' => (string)$package->QUERY->DATA->EMAIL
		);
		$return_val = $prpc->Send($document['archive_id'], $recp, 'EMAIL',  NULL, NULL);
			
		if(!$return_val)
		{
			$this->log->write( 'Send Result: Document Failed to Send' );
		}
		else
		{
			$this->log->write( 'Send Result: Document Sent' );
		}
	}

	//for SMS templated messages that can send multiple at once
	private function flush()
	{
		if (
			($this->action_type == self::ACTION_SMS) && (ECash::getConfig()->SMS_SERVICE_GOOD)
		)
		{
			$this->sms->sendTemplates();
		}
	}
	
}