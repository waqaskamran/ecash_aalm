<?php

  /**
   * export for decommission
   * created for Impact, also used for OPM
   */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once LIB_DIR . 'Payment_Card.class.php';
require_once SERVER_CODE_DIR . 'paydate_handler.class.php';

class Multi_Company_Export
{
	const TEST = FALSE;
	const TEST_LIMIT = '1000';
	const DIR = '/tmp/';
	const EXTENSION = '.csv';

	//'MethodName' => 'file_name'
	protected $export_files = array('Application' => 'application_data_file',
									'Card' => 'payment_card_numbers',
									'Schedule' => 'loan_schedule',
									'Comment' => 'loan_remarks',
									'Flag' => 'flags', 
									'LoanDocs' => 'loan_docs',
									'Rate' => 'loan_rate'
									);
	protected $statement_cache = array();
	protected $companies;
	protected $db;
	protected $factory;
	protected $test_extension;
	protected $date_normalizer;
	protected $paydate_handler;
	protected $inactive_statuses;
	public $company_name;

	protected function __construct()
	{
		$this->test_extension = '_limit' . self::TEST_LIMIT;
		$this->factory = ECash::getFactory();

		$this->date_normalizer = new Date_PayDateNormalizer_1(new Date_BankHolidays_1());
		$this->paydate_handler = new Paydate_Handler();
		
		if(self::TEST)
		{
			$this->db = ECash::getSlaveDB();
		}
		else
		{
			$this->db = ECash::getMasterDB();
			//added memory limit for running on TSS servers
			//some circular references were exceeding the 512M limit
			ini_set('memory_limit','1024M'); 
		}
	}

	public static function main()
	{
		$export_class = __CLASS__;
		$export = new $export_class();		
		$export->loadCompanies();
		$status_sql = $export->getStatusSQL();
		foreach($export->companies as $company)
		{
			if($company->active_status == 'active')
			{
				$export->company_name = $company->name_short;
				$values = $export->getInactiveStatuses();
				array_unshift($values, $company->company_id);

				foreach($export->export_files as $type => $filename)
				{
					foreach($status_sql as $active_status => $active_sql)
					{
						$statement = $export->getStatement($type, $active_status, $active_sql);
				
						$export->exportCSV(
							$type,
							$export->getFullPath($company->name_short, $filename, $active_status),
							$statement,
							$values);
					}
				}
			}
		}
	}

	protected function loadCompanies()
	{
		$this->companies = $this->factory->getReferenceList('Company');
	}

	/**
	 * These next two are very impact specific as they wanted their
	 * files additionally split into active/inactive apps.
	 */ 
	protected function getStatusSQL()
	{
		$ids = $this->getInactiveStatuses();
		$placeholders = substr(str_repeat('?,', count($ids)), 0, -1);
		return array('inactive' => "AND a.application_status_id in ({$placeholders})",
					 'active' => "AND a.application_status_id not in ({$placeholders})");
	}
	
	protected function getInactiveStatuses()
	{
		if(!isset($this->inactive_statuses))
		{
			$asf = $this->factory->getReferenceList('ApplicationStatusFlat');
			$this->inactive_statuses = array($asf->toId('withdrawn::applicant::*root'),
										  $asf->toId('denied::applicant::*root'),
										  $asf->toId('sent::external_collections::*root'),
										  );
		}
		return $this->inactive_statuses;
	}

	/**
	 * $active_status is added to account for active/inactive status queries
	 * $status_sql is added to pass on extra SQL
	 */
	protected function getStatement($type, $active_status, $status_sql)
	{
		$index = $type . '_' . $active_status;
		//if(!isset($this->statement_cache[$index]))
			$this->statement_cache[$index] = $this->{'get' . $type . 'Statement'}($status_sql);

		return $this->statement_cache[$index];
	}
	
	protected function getCardStatement($status_sql)
	{
		$query = "
select
	ci.application_id,
	if(ci.date_created = '0000-00-00 00:00:00', ci.date_modified, ci.date_created) as date_created,
	ct.name as card_type,
	ci.cardholder_name,
	ci.card_street,
	ci.card_zip,
--	NULL as sec,
	ci.expiration_date,
	ci.card_number
from card_info ci
join card_type ct on (ct.card_type_id = ci.card_type_id)
join application a on (ci.application_id = a.application_id)
where a.company_id = ?
{$status_sql}
		";
		
		if(self::TEST)
		{
			//hard limit the cards to 10 since they take forever to decrypt
			$query .= "order by application_id desc, date_created desc limit 10"; //. self::TEST_LIMIT
		}
		return $this->db->prepare($query);
	}

	protected function getScheduleStatement($status_sql)
	{
		$query = "
select event_schedule.application_id, 
	event_type.name, 
	if(transaction_status is null, event_schedule.event_status, transaction_status) as transaction_status,
	context,
	transaction_type.clearing_type,
	event_schedule.date_event as action_date, 
	event_schedule.date_effective as due_date, 
	(select sum(event_amount.amount) from 				event_amount 
		JOIN event_amount_type eat USING (event_amount_type_id)
		where event_amount.event_schedule_id = event_schedule.event_schedule_id
		and event_amount_type_id = 1
		)
	as amount_principal, 
	(select sum(event_amount.amount) from 				event_amount 
		JOIN event_amount_type eat USING (event_amount_type_id)
		where event_amount.event_schedule_id = event_schedule.event_schedule_id
		and event_amount_type_id = 2
		)
	as	amount_interest, 
	(select sum(event_amount.amount) from 				event_amount 
		JOIN event_amount_type eat USING (event_amount_type_id)
		where event_amount.event_schedule_id = event_schedule.event_schedule_id
		and event_amount_type_id = 3
		)
	as	amount_fee, 
	(select sum(event_amount.amount) from 				event_amount 
		JOIN event_amount_type eat USING (event_amount_type_id)
		where event_amount.event_schedule_id = event_schedule.event_schedule_id 
		and event_amount_type_id = 4
		)
	as	amount_irrecoverable, 
	ach_id, 
	date_request as return_date, 
	ach_return_code.name_short as return_code,
	transaction_register.date_modified
	from event_schedule
	join application a on (event_schedule.application_id = a.application_id)
	join event_type using (event_type_id) 
	left join transaction_register using (event_schedule_id) 
	left join transaction_type using (transaction_type_id)
	left join ach using (ach_id) 
	left join ach_report using (ach_report_id)
	left join ach_return_code using (ach_return_code_id)
	where event_schedule.company_id = ?
	{$status_sql}
	group by event_schedule.event_schedule_id
		";

		/**
		 * [#54798] Note on the "group by
		 * event_schedule.event_schedule_id" this was added to remove
		 * duplicate rows caused by a single event_schedule row for
		 * payments of type (credit_card, payout, payment_arranged)
		 * but then two transaction_register rows for interest &
		 * principle.
		 *
		 * It was verified that the two transaction_register rows are
		 * never out of sync as far as status is concerned:
		 *
		 * select tr1.transaction_register_id, tr1.transaction_status, tr2.transaction_register_id, tr2.transaction_status
		 * from transaction_register tr1
		 * join transaction_register tr2 on (tr2.event_schedule_id=tr1.event_schedule_id)
		 *
		 * So the group by was added to eliminate the duplicates
		 */
		
		if(self::TEST)
		{
			//AND transaction_register.transaction_register_id is not null
			$query .= "order by application_id desc, event_schedule.date_created desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);		
	}

	protected function getCommentStatement($status_sql)
	{
		$query = "
select
	a.application_id,
	concat(agent.name_first, ' ', agent.name_last) as agent_name,
	comment.date_created as timestamp,
	comment
from comment 
join agent using (agent_id)
join application a on (comment.application_id = a.application_id)
where comment.company_id = ?
{$status_sql}
		";
		
		if(self::TEST)
		{
			$query .= "order by application_id desc, comment.date_created desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);
	}

	protected function getApplicationStatement($status_sql)
	{
		$query = "
-- application_data_file.csv
select 
a.application_id, 
customer_id,
a.ssn, 
name_first,
name_last,
dob as date_of_birth,
legal_id_number,
legal_id_state,
email, 
street, 
unit, 
city, 
state, 
zip_code, 
phone_home, 
phone_cell, 
employer_name, 
job_title, 
phone_work, 
bank_aba, 
bank_account, 
bank_account_type, 
income_direct_deposit as direct_deposit,
income_direct_deposit,
a.date_created as date_bought, 
date_fund_actual as date_funded, 
date_first_payment, 
income_monthly, 
income_frequency, 
last_paydate,	
day_of_week,
	paydate_model,
	day_of_month_1,	
	day_of_month_2,
	week_1,
	week_2,
	NULL as pay_model_description,
	NULL as next_paydate_1,
	NULL as next_paydate_2,
fund_actual as fund_amount,
apr,
(select name from application_status where application_status_id = a.application_status_id) as loan_status, 
(SELECT SUBSTR(atd.tag_name, 4)
 FROM application_tags AS at
 JOIN application_tag_details AS atd ON (atd.tag_id = at.tag_id)
 WHERE at.application_id = a.application_id
 limit 1) AS lender_tag,
(SELECT count(*) FROM transaction_type join transaction_register using (transaction_type_id) where transaction_register.application_id = a.application_id and transaction_type.name_short = 'payment_service_chg' and transaction_register.transaction_status = 'complete') as renewal_number, 
(select sum(event_amount.amount) from 				event_amount 
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id) where event_amount.application_id = a.application_id and event_amount_type_id = 1 and tr.transaction_status = 'complete') as principal_balance, 
(select sum(event_amount.amount) from 				event_amount 
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id) where event_amount.application_id = a.application_id and event_amount_type_id = 2 and tr.transaction_status = 'complete') as interest_balance, 
(select sum(event_amount.amount) from 				event_amount 
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id) where event_amount.application_id = a.application_id and event_amount_type_id = 3 and tr.transaction_status = 'complete') as fee_balance, 
(SELECT
      es.date_effective  AS payment_date
                FROM
                    event_schedule es
                WHERE
                    es.application_id = a.application_id
                    AND (es.amount_principal < 0 OR es.amount_non_principal < 0)
                    AND es.date_effective <= CURDATE()
                    AND es.event_status = 'registered'
                GROUP BY
                    date_effective
                ORDER BY
                    date_effective DESC
                LIMIT 1) as last_payment_date, 
(SELECT 
      date_format(es.date_effective, '%m/%d/%Y') as due_date
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = a.application_id  
                    AND et.event_type_id = es.event_type_id  
					AND es.event_status = 'scheduled'
                    AND et.name_short IN ('payment_service_chg',
					                      'repayment_principal',
					                      'payout',
					                      'paydown'
										  )  
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective
                LIMIT 1) as next_payment_date, 
(SELECT 
      abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = a.application_id  
                    AND et.event_type_id = es.event_type_id  
					AND es.event_status = 'scheduled'
                    AND et.name_short IN ('payment_service_chg',
					                      'repayment_principal',
					                      'payout',
					                      'paydown'
										  )  
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective
                LIMIT 1) as next_payment_amount, 
(select name_full from personal_reference where personal_reference.application_id = a.application_id limit 1) as reference_1_name, 
(select phone_home from personal_reference where personal_reference.application_id = a.application_id limit 1)  as reference_1_phone, 
(select relationship from personal_reference where personal_reference.application_id = a.application_id limit 1) as reference_1_relationship, 
(select name_full from personal_reference where personal_reference.application_id = a.application_id limit 1,1) as reference_2_name, 
(select phone_home from personal_reference where personal_reference.application_id = a.application_id limit 1,1) as reference_2_phone, 
(select relationship from personal_reference where personal_reference.application_id = a.application_id limit 1,1) as reference_2_relationship,
si.name as site_name,
cust.password,
ci.promo_id,
ci.promo_sub_code,
ci.campaign_name,
a.ip_address,
a.date_hire as employed_since
from application a
join campaign_info ci on (ci.application_id = a.application_id)
left join campaign_info ci2 on (ci2.application_id=ci.application_id and ci2.campaign_info_id > ci.campaign_info_id)
left join site si on (ci.site_id = si.site_id)			
left join customer cust using (customer_id)
where a.company_id = ?
and ci2.campaign_info_id is null
{$status_sql}
		";
		if(self::TEST)
		{
			$query .= "order by application_id desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);
	}

    protected function getRateStatement($status_sql)
	{
		$query = "
		SELECT a.ssn,r.* 
		FROM rate_override r 
		join application a on (a.application_id = r.application_id)
		where a.company_id = ?
		{$status_sql}
		";
		if(self::TEST)
		{
			$query .= "order by a.application_id desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);	
	}

	/**
	 * This shouldn't be here, it should be in
	 * getApplicationStatement, but OPM already signed off on that
	 * file's format.  On the next company export, I recommend that
	 * this is removed and the column is added to the application
	 * query [JustinF][#54798]
	 */
	protected function getFlagStatement($status_sql)
	{
		$query = "
select af.application_id, name_short
from application_flag af
join flag_type ft on (ft.flag_type_id = af.flag_type_id)
join application a on (af.application_id = a.application_id)
where a.company_id = ?
{$status_sql}	
			";
		if(self::TEST)
		{
			$query .= "order by af.application_id desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);
	}

	protected function getLoanDocsStatement($status_sql)
	{
		$doc_array = array('mls' => 'Loan Document', 'cbnk' => "Loan Documents DE Payday', 'Loan Documents DE Title", 'fspl' => 'FSPL Loan Documents Bundle');
		//[#54798] get the latest loan document for each app
		//this url might be different for different companies
//@todo add loan doc name short lookup array for company
		$doc_name = $doc_array[$this->company_name];
		$url = 'http://live.ecash' . ECash::getConfig()->COOKIE_DOMAIN . '/?module=funding&action=show_pdf&archive_id=';
		$query = "
select d1.application_id, concat('$url',d1.archive_id) as url
from document d1
join document_list dl on d1.document_list_id = dl.document_list_id
left join document d2 on (d2.application_id=d1.application_id and d2.date_created>d1.date_created and d2.document_list_id=d1.document_list_id)
join application a on (a.application_id = d1.application_id)
where a.company_id = ?
and dl.name_short in ('{$doc_name}')
and d2.document_id is null
{$status_sql}	
			";
echo $query;
		if(self::TEST)
		{
			$query .= "order by a.application_id desc limit ". self::TEST_LIMIT;
		}
		return $this->db->prepare($query);
	}
	
	protected function exportCSV($type, $filename, $statement, $values)
	{
		$fp = fopen($filename, 'w');

		$statement->execute($values);
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

		if($type == 'Card')
			echo 'Writing decrypted records to csv';
		else		
			echo 'Writing records to csv';

		$first_row = TRUE;
		foreach($rows as $row)		
		{
			if($type == 'Card')
			{
				$this->processCardRow($row);
			}
			elseif($type == 'Application')
			{
				$this->processApplicationRow($row);
			}
			elseif($type == 'Comment')
			{
				$this->processCommentRow($row);
			}

			if($first_row)
			{
				fputcsv($fp, array_keys($row));
				$first_row = FALSE;
			}
			
			fputcsv($fp, $row);
			echo '.';
		}

		fclose($fp);
		echo "\nFile is ready at {$filename}\n";		
	}
//@todo add process comment row to addslashes/remove end lines
	protected function processCommentRow(&$row)
	{
		
		$row['comment'] = str_replace("\n", "",$row['comment']);
		$row['comment'] = str_replace("\r", "",$row['comment']);
		$row['comment'] = str_replace('"', '',$row['comment']);
	}
	protected function processCardRow(&$row)
	{
		$name = Payment_Card::decrypt($row['cardholder_name']);
		/** Pulled by TonyM / MikeR
		if(preg_match('/(.*?)(\d{3})$/', trim($name), $matches))
		{
			$name = trim($matches[1]);
			$row['sec'] = $matches[2];
		}
		*/
		$row['cardholder_name'] = $name;
		$row['card_number'] = Payment_Card::decrypt($row['card_number']);	
	}

	protected function processApplicationRow(&$row)
	{

		$app_row = (object)$row;
		$this->paydate_handler->Get_Model($app_row);
		$row['pay_model_description'] = strip_tags($this->paydate_handler->Get_Paydate_String($app_row->model));

		$this->date_normalizer->setDirectDeposit($row['direct_deposit'] == 'yes' ? TRUE : FALSE);
		try
		{
			$pdc = new Date_PayDateCalculator_1(
				Date_PayDateModel_1::getModel($row['paydate_model'],
											  $row['day_of_week'],
											  $row['last_paydate'],
											  $row['day_of_month_1'],
											  $row['day_of_month_2'],
											  $row['week_1'],
											  $row['week_2']),
				$this->date_normalizer);
											  
			$row['next_paydate_1'] = date('Y-m-d', $pdc->current());
			$row['next_paydate_2'] = date('Y-m-d', $pdc->next());
//decrpty password
//$crypt = new ECash_Models_Encryptor($this->db);
$row['password'] = crypt_3::Decrypt($row['password']);

		}
		catch(Exception $e)
		{
			print_r($row);
			//throw $e;
		}

		unset($row['paydate_model']);
		unset($row['day_of_month_1']);
		unset($row['day_of_month_2']);
		unset($row['week_1']);
		unset($row['week_2']);
		unset($row['income_direct_deposit']);
	}
	
	protected function getFullPath($company_short, $filename, $xtra_name = NULL)
	{
		$filename = self::DIR . $company_short . '_' . $filename;
		if($xtra_name)
			$filename .= '_' . $xtra_name;
		if(self::TEST)
			$filename .= $this->test_extension;
		return $filename . self::EXTENSION;		
	}
}

Multi_Company_Export::main();

?>
