<?php

require_once(SERVER_CODE_DIR . "bureau_query.class.php");

class ECash_NightlyEvent_ResolvePortfolioSnapshotReport extends ECash_Nightly_Event
{
		// Parameters used by the Cron Scheduler
	protected $business_rule_name = 'resolve_portfolio_snapshot';
	protected $timer_name = 'Resolve_Portfolio_Snapshot';
	protected $process_log_name = 'resolve_portfolio_snapshot';
	protected $use_transaction = FALSE;
	
	public function run()
	{
		// Sets up the Applog, any other pre-requisites
		parent::run();

		$this->Resolve_Portfolio_Snapshot_Report($this->start_date);
	}

	private function Resolve_Portfolio_Snapshot_Report($run_date)
	{
		global $_BATCH_XEQ_MODE;

		//Only run this report once per enterprise (report query doesn't contain company_id or company restrictions ATM)
		$ach = new ACH_Utils($this->server);
		if(($company = $ach->Check_Enterprise_Has_Run($this->process_log_name, $run_date)) !== FALSE)
		{
			$this->log->Write("Skipping run of Resolve Portfolio Snapshot report. Already run by: {$company} [Mode: {$_BATCH_XEQ_MODE}]");
			return;
		}

		$this->log->Write("Executing resolve Portfolio Snapshot report data for run date {$run_date}. [Mode: {$_BATCH_XEQ_MODE}]");

		//latest crystal query from [#25563]
		//try not to die whilst looking below
		$query = "
SELECT
        t1.company as 'Company',
		t1.company_id,
        t1.application_id AS 'Application ID',
        LCASE(CONCAT(t1.name_first,' ',t1.name_last)) AS 'firstname lastname',
        t1.email AS 'E-mail',
		t1.zip_code AS 'Zip Code',
        (
            SELECT      COUNT(*)
            FROM        application ia
            JOIN        application_status alf ON (alf.application_status_id = ia.application_status_id)
            WHERE       ia.customer_id = t1.customer_id
            AND         t1.application_id != ia.application_id
            AND         alf.name = 'Inactive (Paid)'
            AND         t1.date_created > ia.date_created
        ) AS 'Number Inactive',
        t1.status as 'Current Status',
        t1.date_created AS 'Date Created',
        IF(t1.fund_date           IS NULL, 'N/A', t1.fund_date)      AS 'Funding Date',
        IFNULL(IF(t1.status_short = 'paid',t1.date_application_status_set, t1.payoff_date), 'N/A')    AS 'Payoff Date (Date of last payment in schedule)',
        REPLACE(t1.income_frequency,'_',' ') as 'Debit Frequency',
        IF(t1.fund_actual IS NULL,  'N/A', t1.fund_actual) AS 'Initial loan amount',
        IF(eab.posted_balance     IS NULL, 0, eab.posted_balance)    AS 'Loan balance',
        IF(eab.posted_principal   IS NULL, 0, eab.posted_principal)  AS 'Principal balance',
        IF(eab.posted_fee         IS NULL, 0, eab.posted_fee)        AS 'Fees balance',
        IF(eab.posted_interest    IS NULL, 0, eab.posted_interest)   AS 'Interest balance',
        IF(eab.pending_balance    IS NULL, 0, eab.pending_balance)   AS 'Pending balance',
        IF(eab.pending_principal  IS NULL, 0, eab.pending_principal) AS 'Pending principal',
        IF(eab.pending_fee        IS NULL, 0, eab.pending_fee)       AS 'Pending Fees',
        IF(eab.pending_interest   IS NULL, 0, eab.pending_interest)  AS 'Pending Interest',
        IF(eab.paid_balance       IS NULL, 0, eab.paid_balance)    AS 'Paid balance',
        IF(eab.paid_principal     IS NULL, 0, eab.paid_principal)    AS 'Paid principal',
        IF(eab.paid_fee           IS NULL, 0, eab.paid_fee)          AS 'Paid fees',
        IF(eab.paid_interest      IS NULL, 0, eab.paid_interest)     AS 'Paid interest',
        IFNULL(t1.payment1,'N/A')                AS  'Payment1',
        IFNULL(t1.payment2,'N/A')                  AS  'Payment2',
        IFNULL(t1.payment3,'N/A')                  AS  'Payment3',
        IFNULL(t1.payment4,'N/A')                  AS  'Payment4',
        IFNULL(t1.payment5,'N/A')                  AS  'Payment5',
        IFNULL(t1.payment6,'N/A')                  AS  'Payment6',
        IFNULL(t1.payment7,'N/A')                  AS  'Payment7',
        IFNULL(t1.payment8,'N/A')                  AS  'Payment8',
        IFNULL(t1.payment9,'N/A')                  AS  'Payment9',
        IFNULL(t1.payment10,'N/A')                 AS   'Payment10',
        IF(t1.last_complete      IS NULL, 'N/A',DATEDIFF(NOW(), t1.last_complete)) AS 'Days since last completed payment',
        IF(eab.num_failed IS NULL, 'N/A',eab.num_failed) AS 'Number of failed payments',
        IF(t1.last_return IS NULL, 'N/A',IF( t1.last_return = 'yes','Y','N')) AS 'Was last return fatal',
        IFNULL(eab.reattempt_total,0)   AS  'Successful Reattempts',
        IFNULL(eab.ach_total,0)  AS 'ACH Payments',
		IFNULL(eab.cs_total,0)            AS  'Customer Service Payments',
        IFNULL(eab.collections_total,0)       AS  'Collection Payments',
        IFNULL(eab.cc_total,0)    AS  'Credit Cards',
        IFNULL(eab.money_order_total,0)  AS  'Money Orders',
        IFNULL(eab.wire_total,0)    AS  'Wire Transfers',
        IFNULL(eab.adjustment_total,0) AS 'Manual Adjustments',
        IFNULL(eab.second_tier_total,0)    AS  'Second Tier Payments',
        t1.campaign_name AS 'Campaign Name',
        t1.promo_id AS 'Promo ID',
        t1.promo_sub_code AS 'Promo Sub-Code',
		t1.ssn_last_four as 'SSN Last 4',
		t1.rate_override as 'Interest Rate',
		'N' as 'Loan Increase',
        t1.bank_aba AS 'ABA',
        t1.bank_account AS 'Account #'
FROM 
(
    SELECT
        a.application_id AS application_id,
        a.name_first,
        a.name_last,
        a.income_frequency,
        a.email,
        a.zip_code,
		a.ssn_last_four,
		a.rate_override,
        a.bank_aba,
        a.bank_account,
		(
            SELECT  sh.date_created
			FROM    status_history AS sh
			JOIN    application_status_flat hss ON (hss.application_status_id = sh.application_status_id)
			WHERE   sh.application_id = a.application_id
            AND     hss.level0_name='Pending'
            ORDER BY sh.date_created ASC
            LIMIT 1
        ) AS date_created,
        a.fund_actual,
		c.company_id,
        c.name_short AS company,
	(
            SELECT ci.campaign_name
            FROM campaign_info AS ci
            WHERE ci.application_id = a.application_id
            AND ci.campaign_name IS NOT NULL
            ORDER BY (promo_id IN (10000, 33662)), campaign_info_id ASC
            LIMIT 1
        ) AS campaign_name,
	(
            SELECT ci.promo_id
            FROM campaign_info AS ci
            WHERE ci.application_id = a.application_id
            AND ci.campaign_name IS NOT NULL
            ORDER BY (promo_id IN (10000, 33662)), campaign_info_id ASC
            LIMIT 1
        ) AS promo_id,
	(
            SELECT ci.promo_sub_code
            FROM campaign_info AS ci
            WHERE ci.application_id = a.application_id
            AND ci.campaign_name IS NOT NULL
            ORDER BY (promo_id IN (10000, 33662)), campaign_info_id ASC
            LIMIT 1
        ) AS promo_sub_code,
        aps.name AS status,
        aps.application_status_id AS status_id,
        aps.name_short AS status_short,
        (
            SELECT es1.date_event
            FROM event_schedule AS es1
            LEFT JOIN event_type AS et ON et.event_type_id = es1.event_type_id
            WHERE es1.application_id = a.application_id
            AND es1.event_status = 'registered'
            AND et.name_short = 'loan_disbursement'
            ORDER BY es1.date_effective DESC
            LIMIT 1
        ) AS fund_date,
        (
            SELECT MIN(es1.date_effective)
            FROM event_schedule AS es1
            JOIN event_amount AS ea ON ea.event_schedule_id = es1.event_schedule_id
            WHERE es1.event_status = 'scheduled'
            AND es1.application_id = a.application_id
            AND ea.amount < 0
        ) AS next_due,
        (
            SELECT
                MAX(date_effective)
            FROM
                event_schedule
            WHERE application_id = a.application_id
            AND event_status = 'scheduled'
        ) AS payoff_date,
		date(a.date_application_status_set) as date_application_status_set,
        (
            SELECT MAX(es1.date_effective) AS last_due
            FROM event_schedule AS es1
            JOIN event_amount AS ea ON ea.event_schedule_id = es1.event_schedule_id
            JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
            WHERE es1.event_status = 'registered'
            AND tr.transaction_status = 'complete'
            AND es1.application_id = a.application_id
            AND ea.amount < 0
        ) AS last_complete,
        (
            SELECT arc.is_fatal
            FROM ach_return_code arc
            JOIN ach ON ach.ach_return_code_id = arc.ach_return_code_id
            WHERE ach.ach_status = 'returned'
            AND ach.application_id = a.application_id
          ORDER BY ach.date_modified desc, arc.date_modified desc
          LIMIT 1
        ) as last_return,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 0,1
        ) AS payment1,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 1,1
        ) AS payment2,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 2,1
        ) AS payment3,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 3,1
        ) AS payment4,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 4,1
        ) AS payment5,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 5,1
        ) AS payment6,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 6,1
        ) AS payment7,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 7,1
        ) AS payment8,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 8,1
        ) AS payment9,
        (
			SELECT
        		-SUM(IF(tr.transaction_status = 'complete', ea.amount,0)) + -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) + -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
            JOIN transaction_type tt ON tt.transaction_type_id = tr.transaction_type_id
		    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
		    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
	    	LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
		    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
			LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
		    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                AND ((tt.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated') OR es1.context = 'arrange_next' OR es1.context = 'payout') 
				AND tr.transaction_status != 'pending'                
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 9,1
        ) AS payment10,
        a.customer_id
    FROM      application AS a
    JOIN      company AS c ON (c.company_id = a.company_id)
    JOIN      application_status AS aps ON aps.application_status_id = a.application_status_id
    GROUP BY application_id
) AS t1
LEFT JOIN   
(
    SELECT
        ea.application_id,
        -SUM(IF(tr.transaction_status = 'complete' AND ea.amount < 0, ea.amount, 0)) AS paid_balance,
        -SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'principal' AND ea.amount < 0, ea.amount, 0)) AS paid_principal,
        -SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'service_charge' AND ea.amount < 0, ea.amount, 0)) AS paid_interest,
        -SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'fee' AND ea.amount < 0, ea.amount, 0)) AS paid_fee,
        SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'principal', ea.amount, 0)) AS posted_principal,
        SUM(IF((tr.transaction_status = 'pending' OR tr.transaction_status = 'complete') AND eat.name_short = 'principal', ea.amount, 0)) AS pending_principal,
        SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'service_charge', ea.amount, 0)) AS posted_interest,
        SUM(IF((tr.transaction_status = 'pending' OR tr.transaction_status = 'complete') AND eat.name_short = 'service_charge', ea.amount, 0)) AS pending_interest,
        SUM(IF(tr.transaction_status = 'complete' AND eat.name_short = 'fee', ea.amount, 0)) AS posted_fee,
        SUM(IF((tr.transaction_status = 'pending' OR tr.transaction_status = 'complete') AND eat.name_short = 'fee', ea.amount, 0)) AS pending_fee,
        SUM(IF(tr.transaction_status = 'complete', ea.amount, 0)) AS posted_balance,
        SUM(IF(tr.transaction_status = 'pending' OR tr.transaction_status = 'complete',  ea.amount, 0)) AS pending_balance,
        SUM(IF(tr.transaction_status = 'failed' AND ea.amount < 0,1,0)) AS num_failed,
		-SUM(IF((tt.name_short like 'adjustment_internal%' OR tt.name_short like 'writeoff_fee%') AND ea.amount < 0 AND tr.transaction_status = 'complete', ea.amount,0)) AS adjustment_total,
        -SUM(IF(tt.name_short like '%credit_card%' AND ea.amount < 0 AND tr.transaction_status = 'complete', ea.amount,0)) AS cc_total,
        -SUM(IF(tt.name_short like 'ext_recovery%' AND ea.amount < 0 AND tr.transaction_status = 'complete', ea.amount,0)) AS second_tier_total,
        -SUM(IF((tt.name_short like '%western_union%' OR tt.name_short like '%moneygram%') AND ea.amount < 0 AND tr.transaction_status = 'complete',ea.amount,0)) AS wire_total,
        -SUM(IF(tt.name_short like '%money_order%' AND ea.amount < 0 AND tr.transaction_status = 'complete', ea.amount,0)) AS money_order_total,
        -SUM(IF(tt.clearing_type = 'ach' AND ea.amount < 0 AND tr.transaction_status = 'complete', ea.amount, 0)) AS ach_total,
        -SUM(IF(tt.clearing_type = 'ach' AND ea.amount < 0 AND es.origin_id IS NOT NULL AND tr.transaction_status = 'complete',ea.amount, 0)) AS reattempt_total,
        -SUM(IF( 
			es.context IN ('partial', 'arrangement')
			AND ea.amount < 0 
			AND tr.transaction_status = 'complete'
			,ea.amount,0)) AS collections_total,
		-SUM(IF( 
			(
				tt.name_short in ('chargeback', 'chargeback_reversal', 'manual_ach', 'paydown', 'refund_fees', 'refund_princ')
				OR tt.name_short like 'payment_debt%'
				OR (es.context = 'manual' 
					AND tt.name_short not like 'adjustment_internal%'
					AND tt.name_short not like '%credit_card%'
					AND tt.name_short not like 'ext_recovery%'
					AND tt.name_short not like '%western_union%'
					AND tt.name_short not like '%moneygram%'
					AND tt.name_short not like '%money_order%'
				)	
			) 
			AND ea.amount < 0 
			AND tr.transaction_status = 'complete'
			AND es.context != 'arrange_next'
			,ea.amount,0)) AS cs_total
    FROM      event_amount AS ea
    JOIN      event_amount_type AS eat ON eat.event_amount_type_id = ea.event_amount_type_id
    JOIN      event_schedule AS es ON es.event_schedule_id = ea.event_schedule_id
    JOIN      event_type AS et ON et.event_type_id = es.event_type_id
    LEFT JOIN transaction_register AS tr ON tr.transaction_register_id = ea.transaction_register_id
    LEFT JOIN transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
    GROUP BY application_id
) AS eab ON (eab.application_id = t1.application_id )
ORDER BY company, (if(t1.name_first LIKE '%test%' OR t1.name_last LIKE '%test%',1,0)),  status DESC, `Application ID` ASC
	        ";
		$result = $this->db->query($query);

		$this->log->Write("Query returned " . $result->rowCount() . " rows");

		$fp = fopen('php://temp', 'w+');
        
		$tab = "\t";
		$first_row = TRUE;
		$bureau_query = new Bureau_Query($this->db, $this->log);
        
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{

			//This may add a significant amount of time to this event [#49280]
			//stolen from loan_data.class.php
			try{
			$inquiry_packages = $bureau_query->getData($row['Application ID'], $row['company_id']);
			unset($row['company_id']); //don't want this in the report
			if(count($inquiry_packages))
			{
				/**
				 * We retrieve packages Newest to Oldest, so stop on the first match
				 */
				foreach($inquiry_packages as $package) {
					switch ($package->name_short) {
						case 'datax':
							$uwResponse = new ECash_DataX_Responses_Perf();
							break;
						case 'factortrust':
							$uwResponse = new ECash_FactorTrust_Responses_Perf();
							break;
						case 'clarity':
							$uwResponse = new ECash_Clarity_Responses_Perf();
							break;
					}
					
					$uwResponse->parseXML($package->received_package);
					if($uwResponse->getLoanAmountDecision())
					{
					$row['Loan Increase'] = 'Y';
					}
					if($uwResponse->getAutoFundDecision())
					{
					$row['Loan Increase'] = 'Auto';
					}

				}
			}
			}
			catch(Exception $e)
			{
				
			}
			
			if($first_row)
			{
				fputcsv($fp, array_keys($row), $tab);
				$first_row = FALSE;
			}
			fputcsv($fp, $row, $tab);
		}
		rewind($fp);

		$insert = 'replace into resolve_portfolio_snapshot_report
				(report_date, result)
				values
				(?, COMPRESS(?))';

		$this->db->execPrepared($insert, array($run_date, stream_get_contents($fp)));
		
		fclose($fp);
		
	}	
	
}

?>
