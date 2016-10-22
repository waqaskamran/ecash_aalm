<?php
/**
 * Replacement for nirvana web service
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once dirname(realpath(__FILE__)) . '/../server/code/UWLookup.php';
require_once 'nirvana/trigger.php';

class Nirvana
{
	//will only print out name of app ID & message to be sent, won't act
	const DRY_RUN = FALSE;

	//will only seek out applications with name_last like '%tsstest'
	const TEST_MODE = FALSE;

	//will limit the results from the query, limiting send count
	//note: send count may actually be lower due to programatical filtering
	const SEND_LIMIT = 0;
	
	public static $COMPANY_ID;

	private $db;
	private $log;
	private $bureau_inquiry;
	private $factory;
	private $asl;
	private $UWLookup;
	
	/**
	 * Skipped campaigns:
	 *     Decline Followup (Not needed per Jared)
	 *     Page 1 Drop (Not possible with current configuration)
	 */
	private $campaigns = array(
		'LeadAccepted',
		'UnsignedApp',
		'Deny',
		'Reactivation',
		'Refinance',
		'PaymentFailed',
		'PendingSecondTier',
		'PaymentReceipt',
	);

	const MYSQL_TIMESTAMP = 'Y-m-d H:i:s';

	public function __construct($server)
	{
		$now = floor(time()/(5*60)) * 5 * 60; // round time to nearest 5 minutes
		$this->db = ECash::getMasterDb();
		$this->log = $server->log;
		$this->bureau_query = new Bureau_Query($this->db, $this->log);
		$this->factory = ECash::getFactory();
		$this->asl = $this->factory->getReferenceList('ApplicationStatusFlat');
		$this->now = $now;
		self::$COMPANY_ID = $server->company_id;
		$this->UWLookup = new VendorAPI_Inquiry2UW_Lookup();
	}
	
	public function processCronJob()
	{
		foreach ( $this->campaigns as $campaign ) {
			$this->{'campaign' . $campaign}();
		}
	}

	private function campaignDeny()
	{
		$trigger = new Nirvana_Trigger($this->now,
									   'deny_20days',
									   'paid::customer::*root',
									   '-20 days',  
									   Nirvana_Trigger::ACTION_EMAIL_XML,
									   'DENIAL_LETTER_DATAX' );

		//custom query to find CRA denied
		$query = "
				SELECT bi.application_id, uncompress(bi.sent_package) as xml
				FROM bureau_inquiry_failed bi
				WHERE 
					bi.reason like '%CRA-D%'
					AND bi.company_id = ".self::$COMPANY_ID."
					AND bi.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
					AND bi.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
				";
				
		if ( self::TEST_MODE )
		{
			$query .= " AND ( uncompress(bi.sent_package) like '%tsstest%' OR
				uncompress(bi.sent_package) like '%TSSTEST%' ) ";
		}
		
		if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL_XML))
		{
			$query .= "
			AND NOT EXISTS
			(
			SELECT apf.application_field_id
			FROM application_field AS apf
			JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
			WHERE apf.table_name = 'application'
			AND apf.column_name = 'customer_email'
			AND afa.field_name IN ('do_not_contact','bad_info')
			AND apf.table_row_id=bi.application_id
			)
			";
		}
		
		if( self::SEND_LIMIT )
		{
			$query .= " LIMIT " . self::SEND_LIMIT;
		}

		if ( self::TEST_MODE )
		{
			$this->log->write("Deny query: \n{$query}");
		}
		
		$results = $this->db->query($query);
		while ($row = $results->fetch(PDO::FETCH_ASSOC))
		{
			$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
			//send emails & sms
			$trigger->send($row['application_id'], $row['xml']);
		}
		$trigger->saveAffectedDate();		
	}

	private function campaignReactivation()
	{
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'react_1hour',
								'paid::customer::*root',
								'-1 hour',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REACT_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'react_1day',
								'paid::customer::*root',
								'-1 day',  
								Nirvana_Trigger::ACTION_SMS,
								6788,
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'react_4days',
								'paid::customer::*root',
								'-4 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REACT_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'react_14days_email',
								'paid::customer::*root',
								'-14 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REACT_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'react_14days_sms',
								'paid::customer::*root',
								'-14 days',  
								Nirvana_Trigger::ACTION_SMS,
								6788,
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'react_30days',
								'paid::customer::*root',
								'-30 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REACT_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
						  );

		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				$not_statuses = $trigger->getNotStatuses();
				//convert to IDs
				foreach( $not_statuses as $idx => $status_name )
					$not_statuses[$idx] = $this->asl->toId($status_name);

				$query = "
				SELECT DISTINCT sh.application_id
				FROM status_history sh
				JOIN application app on (app.application_id = sh.application_id)
				LEFT JOIN do_not_loan_flag AS dnl ON (dnl.ssn = app.ssn AND dnl.active_status = 'active')
				LEFT JOIN react_affiliation af on af.application_id=sh.application_id
				LEFT JOIN status_history sh2 on sh2.application_id=af.react_application_id
					AND sh2.application_status_id in (".join(',', $not_statuses).")
				WHERE 
					sh.application_status_id = '".$this->asl->toId($trigger->getStatus())."'
				AND app.company_id = ".self::$COMPANY_ID."
				AND sh.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
				AND sh.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
				AND sh2.status_history_id is NULL
				AND dnl.dnl_flag_id IS NULL ";

				if ( self::TEST_MODE )
				{
					$query .= " AND app.name_last like '%tsstest' ";
				}
				
				if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'customer_email'
					AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'phone_cell'
					AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				
				if( self::SEND_LIMIT )
				{
					$query .= " LIMIT " . self::SEND_LIMIT;
				}

				if ( self::TEST_MODE )
					$this->log->write("Reactivation query: \n{$query}");
				
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);					
				}
				
				$trigger->saveAffectedDate();
			}
		}
	}

	private function campaignPendingSecondTier()
	{
		$triggers = array(

			new Nirvana_Trigger($this->now,
								'secondtier_5min',
								'pending::external_collections::*root',
								'-5 minutes',  
								Nirvana_Trigger::ACTION_EMAIL,
								'SIF_OFFER'),
		);
		
		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				//get ACH payment failures
				$query = "
				SELECT DISTINCT sh.application_id
				FROM status_history sh
				JOIN application app on (app.application_id = sh.application_id)
				WHERE 
					sh.application_status_id = '".$this->asl->toId($trigger->getStatus())."'
				AND app.company_id = ".self::$COMPANY_ID."
				AND sh.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
				AND sh.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'";


				if ( self::TEST_MODE )
				{
					$query .= " AND app.name_last like '%tsstest' ";
				}
				
				if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'customer_email'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'phone_cell'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				
				if( self::SEND_LIMIT )
				{
					$query .= " LIMIT " . self::SEND_LIMIT;
				}
				
				if ( self::TEST_MODE )
					$this->log->write("CIF Offer query: \n{$query}");
				
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);
				}			

				$trigger->saveAffectedDate();
			}
		}
	}
    
    private function campaignRefinance()
	{
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'refi_1hour',
								'active::servicing::customer::*root',
								'-1 hour',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REFI_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'refi_12hour_sms',
								'active::servicing::customer::*root',
								'-12 hour',  
								Nirvana_Trigger::ACTION_SMS,
								6787,
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'refi_7days',
								'active::servicing::customer::*root',
								'-7 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REFI_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									'withdrawn::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'refi_10days_sms',
								'active::servicing::customer::*root',
								'-14 days',  
								Nirvana_Trigger::ACTION_SMS,
								6787,
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'refi_30days',
								'active::servicing::customer::*root',
								'-30 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'REFI_OFFER',
								array(
									'agree::prospect::*root',
									'approved::servicing::customer::*root',
									'denied::applicant::*root',
									  ),
								'5:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
						  );

		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				$not_statuses = $trigger->getNotStatuses();
				//convert to IDs
				foreach( $not_statuses as $idx => $status_name )
					$not_statuses[$idx] = $this->asl->toId($status_name);

				$query = "
                    SELECT DISTINCT app.application_id
                    FROM status_history AS sh
                    JOIN application AS app ON (app.application_id = sh.application_id)
                    JOIN (
                        SELECT application_id, sum(amount) AS total
                        FROM transaction_register
                        WHERE transaction_status='complete'
                            OR transaction_type_id = 1
                        GROUP BY application_id
                    ) AS tr1 ON tr1.application_id = app.application_id
                    JOIN (
                        SELECT application_id, count(tr.transaction_type_id) AS total
                        FROM transaction_register AS tr
                        JOIN transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
                        WHERE transaction_status='complete'
                            AND amount < 0
                            AND tt.clearing_type != 'adjustment'
                        GROUP BY application_id
                    ) AS tr2 ON tr2.application_id = app.application_id
		    LEFT JOIN do_not_loan_flag AS dnl ON (dnl.ssn = app.ssn AND dnl.active_status = 'active')
                    WHERE sh.application_status_id = '".$this->asl->toId($trigger->getStatus())."'
			AND app.company_id = ".self::$COMPANY_ID."
                        AND sh.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
                        AND sh.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
                        AND tr1.total <200
			AND dnl.dnl_flag_id IS NULL 
                ";

				if ( self::TEST_MODE )
				{
					$query .= " AND app.name_last like '%tsstest' ";
				}
				
				if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'customer_email'
					AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'phone_cell'
					AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				
				if( self::SEND_LIMIT )
				{
					$query .= " LIMIT " . self::SEND_LIMIT;
				}

				if ( self::TEST_MODE )
					$this->log->write("Reactivation query: \n{$query}");
				
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);					
				}
				
				$trigger->saveAffectedDate();
			}
		}
	}

	private function campaignLeadAccepted()
	{
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'lead_accepted_5min',
								'pending::prospect::*root',
								'-5 minutes',  
								Nirvana_Trigger::ACTION_EMAIL,
								'LEAD_ACCEPTED' ),
			new Nirvana_Trigger($this->now,
								'lead_accepted_20min',
								'pending::prospect::*root',
								'-1 day',  
								Nirvana_Trigger::ACTION_SMS,
								6804,
								'approved::servicing::customer::*root' ),
		);
		
		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				$query = $this->buildQuery( $trigger );
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					//skip autofunded apps on this trigger
					if( $trigger->getName() == 'lead_accepted_1min' && $this->getIsAutoFunded($row['application_id'], $row['company_id']) )
						continue;
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);
				}			

				$trigger->saveAffectedDate();
			}
		}
	}

	private function campaignPaymentFailed()
	{
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'ach_return_5min',
								NULL,
								'-5 minutes',  
								Nirvana_Trigger::ACTION_EMAIL,
								'PAYMENT_FAILED' ),
			new Nirvana_Trigger($this->now,
								'ach_return_1hour',
								NULL,
								'-1 hour',  
								Nirvana_Trigger::ACTION_SMS,
								6808),
		);
		
		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				//get ACH payment failures
				$query = "
				select distinct h.application_id
				from transaction_history h
				join application app on (app.application_id = h.application_id)
				join transaction_register r on r.transaction_register_id=h.transaction_register_id
				join transaction_type t on t.transaction_type_id=r.transaction_type_id
				left join event_schedule e on e.origin_id=r.transaction_register_id
				where h.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
				AND app.company_id = ".self::$COMPANY_ID."
				and h.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
				and t.clearing_type!='adjustment'
				and h.status_after='failed'
				and e.event_schedule_id is null
				and r.amount < 0
				";

				if ( self::TEST_MODE )
				{
					$query .= " AND app.name_last like '%tsstest' ";
				}
				
				if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'customer_email'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'phone_cell'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				
				if( self::SEND_LIMIT )
				{
					$query .= " LIMIT " . self::SEND_LIMIT;
				}
				
				if ( self::TEST_MODE )
					$this->log->write("Payment Failed query: \n{$query}");
				
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);
				}			

				$trigger->saveAffectedDate();
			}
		}
	}


	private function campaignPaymentReceipt()
	{
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'payment_receipt_5min',
								NULL,
								'-5 minutes',  
								Nirvana_Trigger::ACTION_EMAIL,
								'PAYMENT_MADE' ),
		);
		
		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{
				//get ACH payment failures
				$query = "
				select distinct h.application_id
				from transaction_history h
				join application app on (app.application_id = h.application_id)
				join transaction_register r on r.transaction_register_id=h.transaction_register_id
				join transaction_type t on t.transaction_type_id=r.transaction_type_id
				left join event_schedule e on e.origin_id=r.transaction_register_id
				where h.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
				AND app.company_id = ".self::$COMPANY_ID."
				and h.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
				and t.clearing_type='card'
				and h.status_after='complete'
				and e.event_schedule_id is null
				and r.amount < 0
				";

				if ( self::TEST_MODE )
				{
					$query .= " AND app.name_last like '%tsstest' ";
				}
				
				if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'customer_email'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
				{
					$query .= "
					AND NOT EXISTS
					(
					SELECT apf.application_field_id
					FROM application_field AS apf
					JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
					WHERE apf.table_name = 'application'
					AND apf.column_name = 'phone_cell'
					AND afa.field_name IN ('do_not_contact','bad_info')
					AND apf.table_row_id=app.application_id
					)
					";
				}
				
				if( self::SEND_LIMIT )
				{
					$query .= " LIMIT " . self::SEND_LIMIT;
				}
				
				if ( self::TEST_MODE )
					$this->log->write("Payment Receipt query: \n{$query}");
				
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);
				}			

				$trigger->saveAffectedDate();
			}
		}
	}

	
	private function campaignUnsignedApp()
	{
        $not_this_status = array(
                'agree::prospect::*root',
                'approved::servicing::customer::*root',
                'denied::applicant::*root',
                'withdrawn::applicant::*root',
                'queued::verification::applicant::*root'
            );
		$triggers = array(
			new Nirvana_Trigger($this->now,
								'unsigned_app_5min',
								'pending::prospect::*root',
								'-5 minutes',  
								Nirvana_Trigger::ACTION_EMAIL,
								'UNSIGNED_APP_REQUEST',
								$not_this_status,
								'3:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'unsigned_app_45min',
								'pending::prospect::*root',
								'-45 minutes',  
								Nirvana_Trigger::ACTION_SMS,
								6801,
								$not_this_status,
								'3:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'unsigned_app_1day',
								'pending::prospect::*root',
								'-1 day',  
								Nirvana_Trigger::ACTION_EMAIL,
								'UNSIGNED_APP_REQUEST',
								$not_this_status,
								'3:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'unsigned_app_2days',
								'pending::prospect::*root',
								'-2 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'UNSIGNED_APP_REQUEST',
								$not_this_status,
								'3:00AM',
								'7:00PM',
								Nirvana_Trigger::DOW_ALL ),
			new Nirvana_Trigger($this->now,
								'unsigned_app_7days',
								'pending::prospect::*root',
								'-7 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'UNSIGNED_APP_REQUEST',
								$not_this_status),
			new Nirvana_Trigger($this->now,
								'unsigned_app_10days',
								'pending::prospect::*root',
								'-10 days',  
								Nirvana_Trigger::ACTION_SMS,
								6801,
								$not_this_status),
			new Nirvana_Trigger($this->now,
								'unsigned_app_21days',
								'pending::prospect::*root',
								'-21 days',  
								Nirvana_Trigger::ACTION_EMAIL,
								'UNSIGNED_APP_REQUEST',
								$not_this_status),			
		);

		foreach( $triggers as $trigger )
		{
			if ( $trigger->getCanMatch() )
			{			
				$query = $this->buildQuery( $trigger );
				$results = $this->db->query($query);
				while ($row = $results->fetch(PDO::FETCH_ASSOC))
				{
					//skip cra denied apps on this trigger
					if( $trigger->getName() == 'unsigned_app_5min' && $this->getIsCRADenied($row['application_id'], $row['company_id']) )
						continue;
					$this->log->Write(__FUNCTION__  . " Got App ID: {$row['application_id']}");
					//send emails & sms
					$trigger->send($row['application_id']);
				}			

				$trigger->saveAffectedDate();
			}
		}
								
	}

	private function buildQuery( Nirvana_Trigger $trigger )
	{
		$query = "
				SELECT
					sh.application_id,
					sh.company_id
				FROM
					status_history sh
				JOIN application app on (app.application_id = sh.application_id)
				LEFT JOIN do_not_loan_flag AS dnl ON (dnl.ssn = app.ssn AND dnl.active_status = 'active')
			";

		if($trigger->getHasNotStatuses()) {
			$not_statuses = $trigger->getNotStatuses();
			//convert to IDs
			foreach( $not_statuses as $idx => $status_name )
				$not_statuses[$idx] = $this->asl->toId($status_name);
				
			$query .= "
				LEFT JOIN status_history sh2 on (sh2.application_id = sh.application_id
					AND sh2.date_created > sh.date_created
					AND sh2.application_status_id in (".join(',', $not_statuses)."))
				";
		}

		$query .= "
				WHERE
					sh.application_status_id = '".$this->asl->toId($trigger->getStatus())."'
				AND sh.company_id = ".self::$COMPANY_ID."
				AND sh.date_created >= '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeStart())."'
				AND	sh.date_created < '".date(self::MYSQL_TIMESTAMP, $trigger->getRangeEnd())."'
				AND dnl.dnl_flag_id IS NULL 
			";

		if( $trigger->getHasNotStatuses() )
		{
			$query .= " AND sh2.status_history_id is NULL ";
		}

		if( self::TEST_MODE )
		{
			$query .= " AND app.name_last like '%tsstest' ";			
		}
		
		if (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_EMAIL))
		{
			$query .= "
			AND NOT EXISTS
			(
			SELECT apf.application_field_id
			FROM application_field AS apf
			JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
			WHERE apf.table_name = 'application'
			AND apf.column_name = 'customer_email'
			AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
			AND apf.table_row_id=app.application_id
			)
			";
		}
		elseif (($trigger->getActionType()) == (Nirvana_Trigger::ACTION_SMS))
		{
			$query .= "
			AND NOT EXISTS
			(
			SELECT apf.application_field_id
			FROM application_field AS apf
			JOIN application_field_attribute AS afa ON (afa.application_field_attribute_id = apf.application_field_attribute_id)
			WHERE apf.table_name = 'application'
			AND apf.column_name = 'phone_cell'
			AND afa.field_name IN ('do_not_contact','bad_info','do_not_market')
			AND apf.table_row_id=app.application_id
			)
			";
		}
		
		if( self::SEND_LIMIT )
		{
			$query .= " LIMIT " . self::SEND_LIMIT;
		}

		if ( self::TEST_MODE )
		{
			$name = $trigger->getName();
			$this->log->write("Built query for {$name}:\n{$query}");
		}
		return $query;
	}

	private function getIsAutoFunded($application_id, $company_id)
	{
		$inquiry_packages = $this->bureau_query->getData($application_id, $company_id);
		if(count($inquiry_packages))
		{
			/**
			 * We retrieve packages Newest to Oldest, so stop on the first match
			 */
			foreach($inquiry_packages as $package)
			{
                $application = ECash::getApplicationByID($application_id);

                $uwSource = $this->UWLookup->lookupUW($package->inquiry_type);
                switch ($uwSource) {
                   case 'DATAX':
                        $uwResponse = $application->getDataXPerf();
                        break;
                   case 'FT':
                        $uwResponse = $application->getFactorTrustPerf();
                        break;
                   case 'CL':
                        $uwResponse = $application->getClarityPerf();
                        break;
                }
                $uwResponse->parseXML($package->received_package);
                return $dataxResponse->getAutoFundDecision();
			}
        }
		return FALSE;
	}
	
	private function getIsCRADenied($application_id, $company_id)
	{
		$query = "SELECT 'X'
				FROM bureau_inquiry_failed bi
				WHERE 
					application_id = ?
				AND company_id = ?
				AND bi.reason like '%CRA-D%'";
		
		$st = $this->db->prepare($query);
		$st->execute(array($application_id, $company_id));
		$result = $st->fetchAll(PDO::FETCH_ASSOC);

		if(count($result))
			return TRUE;

		return FALSE;
	}
}

/**
 * MAIN processing code
 * Called by the cron job handler.
 */
function Main()
{
	global $server;

	$nirvana =	substr(basename(__FILE__), 0, -4); //remove '.php'
	//ecash_engine.php <company> <log> nirvana
	$count = intval(exec("ps -eo args | grep \"php\\([ A-Za-z0-9.\\/-]\+\\){$_SERVER['SCRIPT_FILENAME']} {$server->company} \\w\+ {$nirvana}\" | grep -v /bin/sh | grep -v grep | wc -l"));

	if($count > 1)
	{
		$server->log->write("{$nirvana} is already running for {$server->company}. Aborting this instance.", LOG_ERR);
		return;
	}

	$nirvana = new Nirvana($server);
	$nirvana->processCronJob();
}
