<?php

class ECash_NightlyEvent_ResolveCurrentLeadStatusReport extends ECash_Nightly_Event
{
	// Parameters used by the Cron Scheduler
	protected $business_rule_name = 'resolve_current_lead_status_report';
	protected $timer_name = 'Resolve_Current_Lead_Status_Report';
	protected $process_log_name = 'resolve_lead_status';
	protected $use_transaction = FALSE;

	public function run()
	{
		// Sets up the Applog, any other pre-requisites
		parent::run();
		$this->Resolve_Current_Lead_Status_Report($this->start_date);
	}

	private function Resolve_Current_Lead_Status_Report($run_date)
	{
		global $_BATCH_XEQ_MODE;
		$this->log->Write("Executing Resolve Current Lead Status Report Data for run date {$run_date}. [Mode: {$_BATCH_XEQ_MODE}]");
		$db = ECash::getMasterDb();
		// Make sure this cron can be re-run safely without creating lots of duplicate records by
		// first deleting all records that already exist for yesterday's date.
		$sql = "
			delete from resolve_current_lead_status_report
			where date_bought = '{$run_date}'
		";

		$rows_deleted += $this->db->exec($sql);
		$this->log->Write("Deleted $rows_deleted rows to avoid inserting duplicates. [Mode: {$_BATCH_XEQ_MODE}]");

		// Report SQL
		$sql = "	-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				sh.company_id                                  AS company_id,
				UPPER(co.name_short)                           AS company,
				DATE(sh.date_created)                          AS date_bought,
				COUNT(*)                                       AS num_bought,
				SUM(IF(nq.name_short = 'unsigned_apps'
					AND ntsqe.date_expire > NOW(), 1, 0))      AS num_unsigned,
				SUM(
					IF(
						ass.level1 = 'prospect' AND
						(
								ntsqe.date_expire <= NOW()
							OR
								ntsqe.related_id IS NULL
						)
						,
						1,
						0
					)
				)                                                      AS num_expired,
				SUM(IF(ass.level0_name='Agree', 1, 0))                 AS num_agree,
				SUM(IF(ass.level0_name='Confirm Declined', 1, 0))      AS num_confirm_declined,
				SUM(IF(ass.level0_name='Disagree', 1, 0))              AS num_disagree,
				SUM(IF(ass.level0_name='Pending',1, 0))                AS num_pending,
				SUM(IF(ass.level0_name='Withdrawn',1,0))               AS num_withdrawn,
				SUM(IF(ass.level0_name='Denied',1,0))                  AS num_denied,
				SUM(
					IF(
							ass.level0 = 'customer'
						OR	
							ass.level1 = 'customer'
						OR
							ass.level2 = 'customer'
						OR
							ass.level3 = 'customer'
						,
						1,
						0
					)
				)                                              AS num_funded,
				SUM(IF(ass.level0_name='Funding Failed',1,0))  AS num_funding_failed
			FROM
				status_history sh
			JOIN
				application app ON (app.application_id = sh.application_id)
			JOIN
				application_status_flat ass ON (ass.application_status_id = app.application_status_id)
			JOIN
				application_status_flat hss ON (hss.application_status_id = sh.application_status_id)
			LEFT JOIN
				n_time_sensitive_queue_entry ntsqe ON (app.application_id = ntsqe.related_id)
			LEFT JOIN
				n_queue nq ON (nq.queue_id = ntsqe.queue_id)
			JOIN
				company co ON (co.company_id = sh.company_id)
			WHERE
				hss.level0_name='Pending'
			AND
				DATE(sh.date_created)='{$run_date}'
			GROUP BY
				sh.company_id, DATE(sh.date_created)
		";
				
		$result = $this->db->query($sql);
		$rows_inserted = 0;
		$rows_selected = 0;
		$insert = NULL;

		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			if (!$insert)
			{
				$query = "
					INSERT INTO resolve_current_lead_status_report
					(".join(', ', array_keys($row)).")
					VALUES (?".str_repeat(', ?', count($row) - 1) .")
				";
				$insert = $this->db->prepare($query);
			}

			$rows_selected ++;
				
			$insert->execute(array_values($row));
			$rows_inserted += $insert->rowCount();
		}

		$this->log->Write("Finished executing Resolve Current Lead Status Report. [rows_inserted: {$rows_inserted}/{$rows_selected}] [Mode: {$_BATCH_XEQ_MODE}]");

	}
}
?>
