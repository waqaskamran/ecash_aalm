<?php

/**
 * cronjob to populate analysis db on serenity on a nightly basis.
 * 
 * takes command line args
 * 
 * arg 1: mode
 * arg 2: company name_short
 * 
 * ex: php -q populate_analysis_db.php LIVE ufc
 */

chdir(dirname(__FILE__));

require_once('../../www/config.php');
require_once('mysqli.1.php');
require_once('analytics.php');

class Analysis_Batch
{
	private static $cashline_status_lookup = array(
		'ACTIVE' => 'active',
		'INACTIVE' => 'paid',
		'HOLD' => 'quickcheck',
		'COLLECTION' => 'internal_collections',
		'DENIED' => 'denied',
		'BANKRUPTCY' => 'bankruptcy',
		'INCOMPLETE' => 'unknown',
		'' => 'unknown',
		'PENDING' => 'unknown',
		'SCANNED' => 'external_collections',
		'WITHDRAWN' => 'withdrawn'
	);
	
	public static function Main($argc, $argv)
	{
		$valid_companies = array('mls');
		$valid_modes = array('LIVE', 'RC');
		
		$usage = "Usage: \nphp -q ".__FILE__." {".implode("|", $valid_modes)."} {" . implode("|", $valid_companies) . "}";
		
		if ($argc == 3 || $argc == 4)
		{
			$mode = strtoupper(trim($argv[1]));
			$company_name_short = strtolower(trim($argv[2]));
			
			if (!in_array($mode, $valid_modes) || !in_array($company_name_short,$valid_companies))
			{
				die ($usage);
			}
			
			$run_date = ($argc == 4) ? strtotime($argv[3]) : time();
			
		}
		else
		{
			die ($usage);
		}
		
		$analysis_batch = new Analysis_Batch($mode, $company_name_short);
		$analysis_batch->Execute($run_date);
	}
	
	private $mode;
	private $company;
	private $ecash_db;
	private $analytics;
	private $company_id;
	private $status_lookup;
	private $time_start;
	private $time_end;
	
	public function __construct($mode, $company_name_short)
	{
		
		$this->mode = $mode;
		$this->company = $company_name_short;
		$this->analytics = new Analytics($mode);
		
		$this->ecash_db = new MySQLi_1(SLAVE_DB_HOST, SLAVE_DB_USER, SLAVE_DB_PASS, SLAVE_DB_NAME, SLAVE_DB_PORT);
		$this->legacy_db = new MySQLi_1(SLAVE_DB_HOST, SLAVE_DB_USER, SLAVE_DB_PASS, SLAVE_DB_NAME, SLAVE_DB_PORT);
		
		$this->setCompanyId();
		
	}
	public function Execute($effective_date = NULL)
	{
		
		// convert to compatible date
		if ($effective_date === NULL)
		{
			$effective_date = date('Y-m-d H:i:s');
		}
		elseif (is_numeric($effective_date))
		{
			$effective_date = date('Y-m-d H:i:s', $effective_date);
		}
		
		$this->analytics->Begin_Batch($this->company, ANALYTICS_SYSTEM_ECASH);
		$this->analytics->Truncate_All();
		$this->time_start = time();
		
		$query = "
			select
				distinct(ssn) ssn
			from application ap
			where
				ap.company_id = {$this->company_id} AND
				ap.date_created < '{$effective_date}'
		";
		$result = $this->ecash_db->Query($query);
		
		$count = 0;
		$loan_count = 0;
		
		while ($ssn = $result->Fetch_Object_Row())
		{
			
			$query = "
				select
					archive_cashline_id cashline_id,
					application_id,
					ssn,
					name_last,
					name_first,
					name_middle,
					phone_home,
					phone_cell,
					phone_work,
					employer_name,
					street address_street,
					unit address_unit,
					city address_city,
					state address_state,
					zip_code address_zipcode,
					legal_id_number drivers_license,
					ip_address ip_address,
					email email_address,
					date_format(date_created, '%Y-%m-%d') date_origination,
					dob,
					income_frequency pay_frequency,
					income_monthly,
					bank_aba,
					bank_account
				from application ap
				where
					ap.company_id = {$this->company_id} AND
					ap.ssn = '{$ssn->ssn}' AND
					ap.date_created < '{$effective_date}'
				order by date_created desc
				limit 1
			";
			$rescm = $this->ecash_db->Query($query);
			
			$customer = $rescm->Fetch_Array_Row();
			
			try
			{
				$this->analytics->Begin_Customer($customer);
			}
			catch (Exception $e)
			{
				
				echo "WARNING: ", $e->getMessage(), "; skipping customer ({$ssn->ssn})...\n";
				var_dump($customer);
				
				$this->analytics->Abort_Customer();
				continue;
				
			}
			
			$query = "
				select
					ap.application_id,
					ap.archive_cashline_id,
					ap.ssn,
					asf.level0,
					asf.level0_name,
					asf.level1,
					asf.level2,
					asf.level3,
					asf.level4,
					asf.level5,
					date_fund_actual,
					fund_actual
				from application ap
				inner join application_status_flat asf on (
					asf.application_status_id = (
						select
							application_status_id
						from status_history sh
						where
							sh.application_id = ap.application_id AND
							sh.date_created < '{$effective_date}'
						order by date_created desc
						limit 1
					)
				)
				where
					ap.company_id = {$this->company_id}
					and ap.ssn = '{$ssn->ssn}'
					and ap.date_created < '{$effective_date}'
			";
			$resap = $this->ecash_db->Query($query);
			
			if (($count %100) == 0)
			{
				echo date("[Y-m-d H:i:s] ") . " $count (" . number_format($count / max(1,(time() - $this->time_start)),2) . ")\n";
			}
			
			while ($app = $resap->Fetch_Object_Row())
			{
				$status = $this->convertStatus($app->level0, $app->level0_name, $app->level1, $app->level2, $app->level3, $app->level4, $app->level5);
				
				if ($app->date_fund_actual !== NULL)
				//if (!in_array($status, array('unknown', 'denied', 'withdrawn')))
				{
					
					//echo "app: {$app->application_id} : $status ($app->level0, $app->level0_name, $app->level1, $app->level2, $app->level3, $app->level4, $app->level5)\n";
					
					$query = "
						select
							(
								select
									coalesce(sum(abs(amount_principal)), 0)
								from
									event_schedule es
								where
									es.application_id = {$app->application_id}
									and es.amount_principal < 0
									and exists (
										select
											*
										from transaction_register tr
										where
											tr.event_schedule_id = es.event_schedule_id
											and tr.date_created < '{$effective_date}'
											and
											(
												tr.transaction_status != 'failed'
												or not exists (
													select
														*
													from transaction_history th
													where
														th.transaction_register_id = tr.transaction_register_id
														and th.status_after = 'failed'
														and th.date_created < '{$effective_date}'
													limit 1
												)
											)
										limit 1
									)
							) principal_paid,
							(
								select
									coalesce(sum(abs(amount_non_principal)), 0)
								from
									event_schedule es
								where
									es.application_id = {$app->application_id}
									and es.amount_non_principal < 0
									and exists (
										select
											*
										from transaction_register tr
										where
											tr.event_schedule_id = es.event_schedule_id
											and tr.date_created < '{$effective_date}'
											and
											(
												tr.transaction_status != 'failed'
												or not exists (
													select
														*
													from transaction_history th
													where
														th.transaction_register_id = tr.transaction_register_id
														and th.status_after = 'failed'
														and th.date_created < '{$effective_date}'
													limit 1
												)
											)
										limit 1
									)
							) fees_paid,
							(
								select
									coalesce(sum(amount_non_principal), 0)
								from
									event_schedule es
								where
									es.application_id = {$app->application_id}
									and es.amount_non_principal > 0
									and exists (
										select
											*
										from transaction_register tr
										where
											tr.event_schedule_id = es.event_schedule_id
											and tr.date_created < '{$effective_date}'
											and
											(
												tr.transaction_status != 'failed'
												or not exists (
													select
														*
													from transaction_history th
													where
														th.transaction_register_id = tr.transaction_register_id
														and th.status_after = 'failed'
														and th.date_created < '{$effective_date}'
													limit 1
												)
											)
										limit 1
									)
							) fees_accrued,
							(
								select
									count(*)
								from event_schedule es
								join event_type et on (et.event_type_id = es.event_type_id)
								where
									es.application_id = {$app->application_id}
									and et.name_short in ('assess_service_chg')
									and es.event_status = 'registered'
									and es.date_created < '{$effective_date}'
							) current_cycle,
							(
								select
									count(*)
								from event_schedule es
								join event_type et on (et.event_type_id = es.event_type_id)
								where
									es.application_id = {$app->application_id}
									and et.name_short in ('assess_service_chg')
									and es.event_status = 'registered'
								  and es.date_event <=
								  (
								    select
								      ar.date_request
								    from ach
											join ach_report ar on (ar.ach_report_id = ach.ach_report_id)
								    where
								      application_id = {$app->application_id}
								      and ach_status = 'returned'
											and ar.date_request < '{$effective_date}'
										order by ach_date asc
										limit 1
								  )
							) first_return_cycle,
							(
								select
									cast(th.date_created as date)
								from transaction_register tr
									join transaction_history th on (th.transaction_register_id = tr.transaction_register_id)
								where
									tr.application_id = {$app->application_id}
									and th.status_after = 'complete'
									and th.date_created < '{$effective_date}'
								order by tr.date_created desc
								limit 1
							) date_last_completed_item
						";
						
					$resmt = $this->ecash_db->Query($query);
					$mt = $resmt->Fetch_Object_Row();
					
					$query = "
						select
							ar1.date_request first_return_date,
							arc1.name_short first_return_code,
							arc1.name first_return_msg,
							ar2.date_request last_return_date,
							arc2.name_short last_return_code,
							arc2.name last_return_msg
						from ach a1
							inner join ach a2 on (a2.application_id = a1.application_id)
							inner join ach_report ar1 on (ar1.ach_report_id = a1.ach_report_id)
							inner join ach_report ar2 on (ar2.ach_report_id = a2.ach_report_id)
							inner join ach_return_code arc1 on (arc1.ach_return_code_id = a1.ach_return_code_id)
							inner join ach_return_code arc2 on (arc2.ach_return_code_id = a2.ach_return_code_id)
						where
							a1.application_id = {$app->application_id}
							and a1.ach_status = 'returned'
							and a2.ach_status = 'returned'
							and ar1.date_request < '{$effective_date}'
							and ar2.date_request < '{$effective_date}'
						order by ar1.date_request asc, ar2.date_request desc
						limit 1";
					
					$resach = $this->ecash_db->Query($query);
					
					if ($resach->Row_Count() > 0)
						$ach = $resach->Fetch_Object_Row();
					else $ach = NULL;
								
					$loan = array();
					$loan['application_id'] = $app->application_id;
					$loan['status'] = $status;
					$loan['date_advance'] = $app->date_fund_actual;
					$loan['fund_amount'] = $app->fund_actual;
					$loan['principal_paid'] = $mt->principal_paid;
					$loan['fees_accrued'] = $mt->fees_accrued;
					$loan['fees_paid'] = $mt->fees_paid;
					$loan['first_return_pay_cycle'] = ($mt->first_return_cycle == 0 ? null : $mt->first_return_cycle);
					$loan['current_cycle'] = $mt->current_cycle;
					
					if ($status == 'paid')
					{
						$loan['date_loan_paid'] = $mt->date_last_completed_item;
					}
					
					if (!is_null($ach))
					{
						$loan['first_return_date'] = $ach->first_return_date;
						$loan['first_return_code'] = $ach->first_return_code;
						$loan['first_return_msg'] = $ach->first_return_msg;
						$loan['last_return_date'] = $ach->last_return_date;
						$loan['last_return_code'] = $ach->last_return_code;
						$loan['last_return_msg'] = $ach->last_return_msg;
					}
					
					if (!is_null($app->archive_cashline_id))
					{
						
						// this is the link between ldb and cashline_legacy
						$link_id = $app->application_id;
						
						/*
						 application is transient, so we have to grab the legacy data and merge it with the
						 $loan array we've been building.
						 */
						
						$query = "
							select
								*
							from cashline_legacy.loans ln
							where
								ln.application_id = {$app->application_id}
								and ln.loan_most_recent = 'true'
								and ln.company_id = {$this->company_id}
						";
						$rescl = $this->legacy_db->Query($query);
						
						if ($cl = $rescl->Fetch_Object_Row())
						{
							
							//print_r($loan);
							//print_r($cl);
							
							$loan['current_cycle'] += $cl->loan_cycle_count;
							$loan['fees_accrued'] += $cl->loan_fees_accrued;
							$loan['fees_paid'] += $cl->loan_fees_accrued - ($cl->loan_balance - ($cl->loan_amount - $cl->loan_principal_paid));
							$loan['principal_paid'] += $cl->loan_principal_paid;
							
							if ($status == 'paid' && !isset($loan['date_loan_paid']))
							{
								$loan['date_loan_paid'] = $cl->loan_date_paid;
							}
							
							if ($cl->loan_first_return > 0)
							{
								$loan['first_return_pay_cycle'] = $cl->loan_first_return;
							}
							
							if ($cl->loan_is_closed != 'true')
							{
								$loan['current_cycle']--;
								$loan['fees_accrued'] -= (($cl->loan_amount - $cl->loan_principal_paid) * 0.30);
							}
							
						}
						else
						{
							//throw new Exception('Missing archive data for transient application '.$app->application_id);
							echo 'WARNING: Missing archive data for transient application ', $app->application_id, "\n";
						}
						
					}
					
					//print_r($loan);
					
					try 
					{
						
						//echo "INSERTING ECASH LOAN.\n";
						
						$this->analytics->Add_Loan($loan);
						$loan_count++;
						
					}
					catch (Exception $e)
					{
						echo "WARNING: ", $e->getMessage(), " ignoring loan for application_id (", $app->application_id, ")\n";
						var_dump($loan);
					}
					
				}
				
			}
			
			if (isset($link_id))
			{
				
				$query = "
					select
						ln.*
					from cashline_legacy.loans ln
					where
						ln.company_id = {$this->company_id}
						and ln.application_id = {$link_id}
						and ln.loan_most_recent = 'false'
				";
				$resln = $this->legacy_db->Query($query);
				
				// process loans that finalized pre-conversion
				while ($ln = $resln->Fetch_Object_Row())
				{
					
					$loan = array();
					$loan['status'] = $this->convertCashlineStatus("INACTIVE");
					$loan['date_advance'] = $ln->loan_dispersment_date;
					$loan['fund_amount'] = $ln->loan_amount;
					$loan['principal_paid'] = $ln->loan_amount;
					$loan['fees_accrued'] = $ln->loan_fees_accrued;
					$loan['fees_paid'] = $ln->loan_fees_accrued;
					$loan['first_return_pay_cycle'] = ($ln->loan_first_return == 0 ? null : $ln->loan_first_return);
					$loan['current_cycle'] = $ln->loan_cycle_count;
					
					//echo "cashline loan .. : \n";
					//print_r($loan);
					
					try 
					{
						
						//echo "INSERTING CASHLINE LOAN\n";
						
						$this->analytics->Add_Loan($loan);
						$loan_count++;
						
					}
					catch (Exception $e)
					{
						echo "WARNING: ignoring cashline loan for application_id ({$app->application_id})\n";
					}
					
				}
				
			}
			
			$this->analytics->End_Customer();
			$count++;
			
			unset($link_id);
			
		}
		
		$this->analytics->End_Batch();
		
		echo "Finished in ", round(time() - $this->time_start, 4), " seconds.\n";
		echo "Inserted {$count} customers, and {$loan_count} loans.\n";
		
	}
	
	private function convertCashlineStatus($cashline_status)
	{
		if (isset(self::$cashline_status_lookup[$cashline_status]))
			return self::$cashline_status_lookup[$cashline_status];
		return 'unknown';
	}
	
	private function convertStatus($level0, $level0_name, $level1, $level2, $level3, $level4, $level5)
	{
		switch (true)
		{
			case ( // denied
				($level0 == 'denied' && $level1 == 'applicant' &&  $level2 == '*root')
			):
				return 'denied';
				
			case ( //external collections
				($level0 == 'sent' && $level1 == 'external_collections' && $level2 == '*root')
			):
				return 'external_collections';
			case ( //withdrawn
				($level0 == 'withdrawn' && $level1 == 'applicant' && $level2 == '*root')
			):
				return 'withdrawn';
			case ( // paid
				($level0 == 'paid' && $level1 == 'customer' && $level2 == '*root') ||
				($level0 == 'recovered' && $level1 == 'external_collections' && $level2 == '*root')
			):
				return 'paid';
			case ( //active
				($level0 == 'active' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'approved' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'hold' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'past_due' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root')
			):
				return 'active';
			case ( //internal_collections
				($level0 == 'new' && $level1 == 'collections' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'queued' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'dequeued' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'follow_up' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'internal_collections';
			case ( // quickcheck
				($level0 == 'ready' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'sent' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'quickcheck';
			case ( //bankruptcy
				($level0 == 'unverified' && $level1 == 'bankruptcy' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'verified' && $level1 == 'bankruptcy' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'bankruptcy';
			
			default : return 'unknown';
		}
	}
	
	private function setCompanyId()
	{
		$res = $this->ecash_db->Query("
			select
				company_id
			from company
			where name_short = '{$this->company}'
			and active_status = 'active'");
		
		if ($res->Row_Count() != 1)
		{
			throw new Exception("fatal error while fetching ecash company id");
		}
		
		$this->company_id = $res->Fetch_Object_Row()->company_id;
	}
}

Analysis_Batch::Main($_SERVER['argc'], $_SERVER['argv']);

?>
