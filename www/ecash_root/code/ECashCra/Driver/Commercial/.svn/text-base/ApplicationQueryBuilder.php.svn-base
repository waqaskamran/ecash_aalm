<?php

class ECashCra_Driver_Commercial_ApplicationQueryBuilder
{
	const FAILED_REDISBURSEMENTS_APPLICATION_TEMP_TABLE = 'temp_failed_redisbursements_application';
	const STATUS_HISTORY_APPLICATION_TEMP_TABLE = 'temp_status_history_application';
	const RECOVERIES_TRANSACTION_TEMP_TABLE = 'temp_recoveries_transaction';
	const RECOVERIES_APPLICATION_TEMP_TABLE = 'temp_recoveries_application';
	
	const FT_REPORTING_START = '2015-03-30';
	
	/**
	 * Returns a query that will provide the application ID's with failed redisbursement transactions.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getFailedRedisbursementsApplicationsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
				sh.application_id
			FROM
				transaction_register AS sh
				INNER JOIN transaction_register AS tr
					ON sh.application_id = tr.application_id
					AND tr.transaction_status = 'failed'
					AND tr.date_effective < sh.date_effective
			WHERE
				sh.transaction_status = 'pending'
				AND sh.date_effective BETWEEN ? AND ?
				AND sh.date_created >= 20060606000000 -- #12345 No updates before 2006/06/06
				AND sh.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
		";
		
		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $company;
		
		return $query;
	}
	
	public function getFailedRedisbursementsQuery()
	{
		return "
			SELECT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				" . self::FAILED_REDISBURSEMENTS_APPLICATION_TEMP_TABLE . " a
		";
	}

	/**
	 * Returns a query that will provide the application ID's for new loans.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getNewLoanApplicationIDQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
				application_id
			FROM
				status_history sh
				JOIN application a USING (application_id)
			WHERE
				    sh.date_created BETWEEN (?) AND (?)
				AND sh.application_status_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				sh.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$active_status_id,
			$company
		);

		return $query;
	}

	/**
	 * Returns a query that will provide the application ID's for new loans.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getLoanPaymentApplicationIDQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
				application_id
			FROM
				transaction_register
				JOIN application a USING (application_id)
			WHERE
				    tr.date_modified BETWEEN (?) AND (?)
				AND tr.transaction_status = 'complete'
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				sh.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	////////////////////////////////////////////////////////////////////////FactorTrust
	/**
	 * Creates a payment timing view for the factor trust queries.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function makeFactorTrustPaymentTimingView($days, $date, array &$args, $num = '')
	{
		$query = "
        CREATE OR REPLACE VIEW payment_timing AS
			SELECT DISTINCTROW es.application_id,date_effective,es.event_status
				FROM event_schedule".$num." es
				JOIN event_type et USING(event_type_id)
				JOIN event_transaction etr USING(event_type_id)
				JOIN transaction_type tt USING(transaction_type_id)
				WHERE tt.clearing_type IN ('ach','card','external')
				ORDER BY application_id;
		";
        // ahead and behind two months
		$args = array(
		);   
		return $query;
	}
    
	/**
	 * Creates a payment detail view for the factor trust queries.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function makeFactorTrustPaymentDetailView(array &$args, $num = '')
	{
		$query = "
        CREATE OR REPLACE VIEW payment_transaction_detail AS
            SELECT DISTINCTROW tr.date_modified,
				tr.date_effective,
				tr.application_id,
				tt.clearing_type,
				tr.transaction_status,
				sum(tr.amount) as amount,
				-- arc.name_short as return_code,
				IF(tt.clearing_type = 'ach', arc.name_short, cp.reason_code) as return_code,
				tr.date_effective as date_event
                FROM transaction_register".$num." tr
                JOIN transaction_type tt USING(transaction_type_id)
                LEFT JOIN ach".$num." USING (ach_id)
                LEFT JOIN ach_return_code arc USING (ach_return_code_id)
		LEFT JOIN card_process AS cp USING (card_process_id)
		-- LEFT JOIN card_process_response AS cpr USING (reason_code)
	    WHERE tt.clearing_type IN ('ach','card','external')
            AND NOT(tt.name_short LIKE 'cancel%')
            AND tr.amount < 0
            GROUP BY tr.application_id, tr.date_effective, clearing_type, transaction_status
				ORDER BY tr.application_id;
		";

		$args = array(
		);   

		return $query;
	}

	/**
	 * Returns a query that will provide the new loan data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustNewLoanQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
                'NL' as Type,
                a.date_fund_actual AS TranDate,
                a.ssn as SSN,
                a.track_id AS AppID,
                a.application_id AS LoanID,
                a.date_fund_actual AS LoanDate,
                IF(a.date_fund_actual>a.date_first_payment,DATE_ADD(a.date_fund_actual,INTERVAL 30 DAY),a.date_first_payment) AS DueDate,
                0.00 AS PaymentAmt,
                a.fund_actual AS Balance,
				'' AS ReturnCode,
                '' as RollOverRef,
                '' as RollOverNumber,
                a.bank_aba as BankABA,
                a.bank_account AS BankAcct			
			FROM
				application a
				JOIN status_history ash ON (a.application_id=ash.application_id)
                JOIN application_status ast ON ast.application_status_id = ash.application_status_id
			WHERE
				ast.name_short = 'approved'
                AND ash.date_created BETWEEN (?) AND (?)
 				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustNewLoanQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'NL' as Type,
		DATE(a.date_application_status_set) AS TranDate,
		a.ssn as SSN,
		a.application_id AS AppID,
		a.application_id AS LoanID,
		DATE(a.date_application_status_set) AS LoanDate,
		IF(a.date_fund_actual>a.date_first_payment,DATE_ADD(a.date_fund_actual,INTERVAL 30 DAY),a.date_first_payment) AS DueDate,
		NULL AS PaymentAmt,
		a.fund_actual AS Balance,
		NULL AS ReturnCode,
		NULL as RollOverRef,
		NULL as RollOverNumber,
		a.bank_aba as BankABA,
		a.bank_account AS BankAcct			
		FROM application a
		JOIN status_history ash ON (ash.application_id=a.application_id)
		JOIN application_status ast ON (ast.application_status_id = ash.application_status_id)
		LEFT JOIN status_history ash1 ON (ash1.application_id=ash.application_id
		AND ash1.application_status_id = ash.application_status_id
		AND ash1.date_created < ash.date_created)
		WHERE ast.name_short = 'active'
		AND ash.date_created < '" . self::FT_REPORTING_START . "'
		AND ash.date_created BETWEEN (?) AND (?)
		AND ash1.status_history_id IS NULL
		AND a.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY a.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustNewLoanQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'NL' as Type,
		DATE(a.date_application_status_set) AS TranDate,
		a.ssn as SSN,
		a.application_id AS AppID,
		a.application_id AS LoanID,
		DATE(a.date_application_status_set) AS LoanDate,
		IF(a.date_fund_actual>a.date_first_payment,DATE_ADD(a.date_fund_actual,INTERVAL 30 DAY),a.date_first_payment) AS DueDate,
		NULL AS PaymentAmt,
		a.fund_actual AS Balance,
		NULL AS ReturnCode,
		NULL as RollOverRef,
		NULL as RollOverNumber,
		a.bank_aba as BankABA,
		a.bank_account AS BankAcct			
		FROM application a
		JOIN status_history ash ON (ash.application_id=a.application_id)
		JOIN application_status ast ON (ast.application_status_id = ash.application_status_id)
		LEFT JOIN status_history ash1 ON (ash1.application_id=ash.application_id
		AND ash1.application_status_id = ash.application_status_id
		AND ash1.date_created < ash.date_created)
		WHERE ast.name_short = 'active'
		AND ash.date_created > '" . self::FT_REPORTING_START . "'
		AND ash.date_created BETWEEN (?) AND (?)
		AND ash1.status_history_id IS NULL
		AND a.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY a.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the loan payment data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustPaymentQuery($date, $company, array &$args)
	{
		$query = "
            SELECT 
                'PM' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct			
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN
                (
                    SELECT date_effective,application_id,sum(amount) as amount
                    FROM payment_transaction_detail
                    GROUP BY application_id,date_effective
                ) as transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.date_effective BETWEEN (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustPaymentQuery($date, $company, array &$args)
	{
		$query = "
		SELECT
                'PM' as Type,
		DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                NULL AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                NULL AS LoanDate,
                NULL AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
                NULL AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                NULL as BankABA,
                NULL AS BankAcct		
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created < '" . self::FT_REPORTING_START . "')
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN
                (
		    SELECT date_modified,date_effective,application_id,sum(amount) as amount
                    FROM payment_transaction_detail
		    WHERE transaction_status = 'complete'
                    GROUP BY application_id,date_effective
                ) as transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE
		transDetailTbl.date_modified BETWEEN (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
		GROUP BY appTbl.application_id
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustPaymentQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT
                'PM' as Type,
				DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                NULL AS AppID,
                -- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
				appTbl.application_id AS LoanID,
                NULL AS LoanDate,
                NULL AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
				0 AS Balance,
                NULL AS ReturnCode,
                -- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
				appTbl.application_id AS RollOverRef,
		(CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
                NULL as BankABA,
                NULL AS BankAcct		
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN
                (
		    SELECT date_modified,date_effective,application_id,sum(amount) as amount
                    FROM payment_transaction_detail
		    WHERE transaction_status = 'complete'
                    GROUP BY application_id,date_effective
                ) as transDetailTbl on transDetailTbl.application_id = appTbl.application_id
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
            WHERE
		transDetailTbl.date_modified BETWEEN (?) AND (?)
		AND ash_start1.status_history_id IS NULL
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
		GROUP BY appTbl.application_id
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustPaymentDueDateModQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT
                'PM' as Type,
		DATE(ade.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                NULL AS AppID,
                -- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
				appTbl.application_id AS LoanID,
                NULL AS LoanDate,
                NULL AS DueDate,
                IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS PaymentAmt,
                0 AS Balance,
                NULL AS ReturnCode,
                -- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
				appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
                NULL as BankABA,
                NULL AS BankAcct		
            FROM
                application appTbl
		JOIN application_date_effective AS ade ON (ade.application_id = appTbl.application_id)
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
            WHERE
		ade.date_modified BETWEEN (?) AND (?)
		AND ash_start1.status_history_id IS NULL
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
		GROUP BY appTbl.application_id
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}

	/**
	 * Returns a query that will provide the roll over loan data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustRolloverQuery($date, $company, array &$args)
	{
		$query = "
            SELECT 
                'RO' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.track_id AS AppID,
                CONCAT(appTbl.application_id,'V',IF(transCountTbl.cnt>0,transCountTbl.cnt+1,1)) AS LoanID,
                max(regEventTbl.date_effective) AS LoanDate,
                IF(COALESCE(MIN(nextEventTbl.next_date),0)>0,min(nextEventTbl.next_date),DATE_ADD(max(regEventTbl.date_effective),INTERVAL 31 DAY)) AS DueDate,
                '0.00' AS PaymentAmt,
                balanceTbl.total_balance AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt+1,1) as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct		
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id, event_schedule_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT application_id,date_effective as next_date
                    FROM payment_timing
                    WHERE date_effective > (?)
                ) as nextEventTbl ON nextEventTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT application_id,date_effective
                                    FROM payment_timing
                    WHERE date_effective <= (?)
                ) as regEventTbl ON regEventTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE balanceTbl.total_balance > 0
                AND transDetailTbl.date_effective BETWEEN  (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	
	public function getFactorTrustRolloverQuery($date, $company, array &$args)
	{
		$query = "
            SELECT
                'RO' as Type,
		DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                IF(ash_start.date_created < '2015-03-03', appTbl.track_id, appTbl.application_id) AS AppID,
		IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
		DATE(transDetailTbl.date_modified) AS LoanDate,
		IFNULL(MIN(nextEventTbl.next_date),DATE_ADD(DATE(NOW()),INTERVAL 31 DAY)) AS DueDate,
                NULL AS PaymentAmt,
                balanceTbl.total_balance AS Balance,
                '' AS ReturnCode,
		IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                NULL as BankABA,
                NULL AS BankAcct		
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created < '" . self::FT_REPORTING_START . "')
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
			AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
			tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id, event_schedule_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT application_id,date_effective as next_date
                    FROM payment_timing
                    WHERE date_effective > (?)
                ) as nextEventTbl ON nextEventTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE
		balanceTbl.total_balance > 0
		AND transDetailTbl.transaction_status = 'complete'
		AND transDetailTbl.date_modified BETWEEN (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustRolloverQuery_CLH($date, $company, array &$args)
	{
		$query = "
            SELECT
                'RO' as Type,
		DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                appTbl.application_id AS AppID,
		-- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
		appTbl.application_id AS LoanID,
		DATE(transDetailTbl.date_modified) AS LoanDate,
		IFNULL(MIN(nextEventTbl.next_date),DATE_ADD(DATE(NOW()),INTERVAL 31 DAY)) AS DueDate,
                NULL AS PaymentAmt,
                balanceTbl.total_balance AS Balance,
                '' AS ReturnCode,
		-- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
		appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
                appTbl.bank_aba as BankABA,
		appTbl.bank_account AS BankAcct
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
			AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
			tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id, event_schedule_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT application_id,date_effective as next_date
                    FROM payment_timing
                    WHERE date_effective > (?)
                ) as nextEventTbl ON nextEventTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
            WHERE
		balanceTbl.total_balance > 0
		AND transDetailTbl.transaction_status = 'complete'
		AND ash_start1.status_history_id IS NULL
		AND transDetailTbl.date_modified BETWEEN (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustRolloverQueryDueDateMod_CLH($date, $company, array &$args)
	{
		$query = "
            SELECT
                'RO' as Type,
		DATE(ade.date_modified) AS TranDate,
                appTbl.ssn as SSN,
                appTbl.application_id AS AppID,
		-- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
		appTbl.application_id AS LoanID,
		DATE(ade.date_modified) AS LoanDate,
		IFNULL(MIN(nextEventTbl.next_date),DATE_ADD(DATE(NOW()),INTERVAL 31 DAY)) AS DueDate,
                NULL AS PaymentAmt,
                balanceTbl.total_balance AS Balance,
                '' AS ReturnCode,
		-- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
		appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
                appTbl.bank_aba as BankABA,
		appTbl.bank_account AS BankAcct
            FROM
                application appTbl
		JOIN application_date_effective AS ade ON (ade.application_id = appTbl.application_id)
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
			AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
			tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id, event_schedule_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT application_id,date_effective as next_date
                    FROM payment_timing
                    WHERE date_effective > (?)
                ) as nextEventTbl ON nextEventTbl.application_id = appTbl.application_id
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
            WHERE
		balanceTbl.total_balance > 0
		AND ash_start1.status_history_id IS NULL
		AND ade.date_modified BETWEEN (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the return data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustReturnQuery($date, $company, array &$args)
	{
		$query = "
            SELECT 
                'RI' as Type,
                transDetailTbl.date_modified AS TranDate,
                appTbl.ssn as SSN,
                appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
                IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
                IF(transDetailTbl.return_code>0,transDetailTbl.return_code,'R01') AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct
            FROM
                application appTbl
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT ptd.application_id,count(DISTINCT(ptd.date_effective)) as cnt,ptd2.date_effective
                    FROM payment_transaction_detail ptd
                        JOIN payment_transaction_detail ptd2 USING (application_id)
                    WHERE ptd.date_effective < ptd2.date_effective
                        AND ptd.transaction_status != 'failed'
                        AND ptd2.transaction_status = 'failed' 
                        AND ptd2.date_modified BETWEEN (?) AND (?)
                    GROUP BY ptd.application_id,ptd2.date_effective
                ) as transCountTbl ON transCountTbl.application_id = transDetailTbl.application_id AND transCountTbl.date_effective = transDetailTbl.date_effective
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.transaction_status = 'failed' 
				AND transDetailTbl.date_modified BETWEEN (?) AND (?)
 				AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustReturnQuery($date, $company, array &$args)
	{
		$query = "
            SELECT
                'RI' as Type,
		DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
		NULL AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                NULL AS LoanDate,
                NULL AS DueDate,
                NULL AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
		IFNULL(transDetailTbl.return_code,'R01') AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct		
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created < '" . self::FT_REPORTING_START . "')
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE
		transDetailTbl.date_modified BETWEEN (?) AND (?)
		AND transDetailTbl.transaction_status = 'failed'
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
		GROUP BY appTbl.application_id
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustReturnQuery_CLH($date, $company, array &$args)
	{
		$query = "
            SELECT
                'RI' as Type,
		DATE(transDetailTbl.date_modified) AS TranDate,
                appTbl.ssn as SSN,
		NULL AS AppID,
                -- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
				appTbl.application_id AS LoanID,
                NULL AS LoanDate,
                NULL AS DueDate,
                NULL AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
		IFNULL(transDetailTbl.return_code,'R01') AS ReturnCode,
                -- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
				appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct
            FROM
                application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
		    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
            WHERE
		transDetailTbl.date_modified BETWEEN (?) AND (?)
		AND transDetailTbl.transaction_status = 'failed'
		AND ash_start1.status_history_id IS NULL
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
		GROUP BY appTbl.application_id
 		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the charge off data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustChargeoffQuery($date, $company, array &$args)
	{
		$query = "
            SELECT DISTINCT
		'CO' as Type,
		ash.date_created AS TranDate,
		appTbl.ssn as SSN,
		appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
		'' AS LoanDate,
		'' AS DueDate,
		'0.00' AS PaymentAmt,
                IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
		'' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		appTbl.bank_aba as BankABA,
		appTbl.bank_account AS BankAcct			
            FROM
		application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
				LEFT JOIN 
				(
						SELECT
								tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
						FROM
								event_amount ea
								JOIN event_amount_type eat USING (event_amount_type_id)
								JOIN transaction_register tr USING(transaction_register_id)
						GROUP BY tr.application_id
				) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
				JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
            JOIN application_status ast ON ast.application_status_id = ash.application_status_id
            WHERE ast.name = 'Second Tier (Sent)'
				AND ash.date_created BETWEEN (?) AND (?)
 				AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustChargeoffQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'CO' as Type,
		DATE(ash.date_created) AS TranDate,
		appTbl.ssn as SSN,
		NULL AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
		NULL AS LoanDate,
		NULL AS DueDate,
		NULL AS PaymentAmt,
		NULL AS Balance,
		NULL AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		NULL as BankABA,
		NULL AS BankAcct			
		FROM
			application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created < '" . self::FT_REPORTING_START . "')
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
		JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
		JOIN application_status ast ON (ast.application_status_id = ash.application_status_id)
		LEFT JOIN status_history ash1 ON (ash1.application_id = ash.application_id
							AND ash1.application_status_id IN (132,134,190,111,112,130,131)
							AND ash1.date_created < ash.date_created)
		WHERE ast.name IN ('Collections Contact','Collections Rework','Second Tier (Pending)','Second Tier (Sent)','Bankruptcy Notification','Bankruptcy Verified')
			AND ash.date_created BETWEEN (?) AND (?)
			AND ash1.status_history_id IS NULL
 			AND appTbl.company_id = (
				SELECT company_id
				FROM company
				WHERE
				name_short = ?
				)
		GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustChargeoffQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'CO' as Type,
		DATE(ash.date_created) AS TranDate,
		appTbl.ssn as SSN,
		NULL AS AppID,
        -- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
		appTbl.application_id AS LoanID,
		NULL AS LoanDate,
		NULL AS DueDate,
		NULL AS PaymentAmt,
		IF(ast.name IN ('Second Tier (Sent)'),
		0,
		IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0)
		) AS Balance,
		NULL AS ReturnCode,
        -- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
		appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
		NULL as BankABA,
		NULL AS BankAcct			
		FROM
			application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
		JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
		JOIN application_status ast ON (ast.application_status_id = ash.application_status_id)
		LEFT JOIN status_history ash1 ON (ash1.application_id = ash.application_id
							AND ash1.application_status_id IN (132,134,190,192,122,130,131,111,112)
							AND ash1.date_created < ash.date_created)
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
		
		LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
		    WHERE tr.date_modified < (?)
                    GROUP BY tr.application_id
                ) AS balanceTbl ON balanceTbl.application_id = appTbl.application_id
		
		WHERE ast.name IN ('Collections Contact','Collections Rework','CCCS','Servicing Hold','Bankruptcy Notification','Second Tier (Pending)','Second Tier (Sent)')
			AND ash.date_created BETWEEN (?) AND (?)
			AND ash1.status_history_id IS NULL
			AND ash_start1.status_history_id IS NULL
 			AND appTbl.company_id = (
				SELECT company_id
				FROM company
				WHERE
				name_short = ?
				)
		GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustBankruptcyQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'BK' as Type,
		DATE(ash.date_created) AS TranDate,
		appTbl.ssn as SSN,
		NULL AS AppID,
        -- IF(duedatemodCountTbl.cnt > 0, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt), appTbl.application_id) AS LoanID,
		appTbl.application_id AS LoanID,
		NULL AS LoanDate,
		NULL AS DueDate,
		NULL AS PaymentAmt,
		NULL AS Balance,
		NULL AS ReturnCode,
        -- IF(duedatemodCountTbl.cnt > 1, CONCAT(appTbl.application_id,'-',duedatemodCountTbl.cnt - 1), appTbl.application_id) AS RollOverRef,
		appTbl.application_id AS RollOverRef,
                (CASE
		WHEN transCountTbl.cnt > 0 AND duedatemodCountTbl.cnt > 0 THEN transCountTbl.cnt + duedatemodCountTbl.cnt
		WHEN transCountTbl.cnt > 0 THEN transCountTbl.cnt
		WHEN duedatemodCountTbl.cnt > 0 THEN duedatemodCountTbl.cnt
		ELSE ''
		END) AS RollOverNumber,
		NULL as BankABA,
		NULL AS BankAcct			
		FROM
			application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
						AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
		JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
		JOIN application_status ast ON (ast.application_status_id = ash.application_status_id)
		LEFT JOIN status_history ash1 ON (ash1.application_id = ash.application_id
							AND ash1.application_status_id IN (132,134,190,192,122,130,131,111,112)
							AND ash1.date_created < ash.date_created)
		LEFT JOIN 
		(
			SELECT application_id,count(DISTINCT date_effective_after) as cnt
			FROM application_date_effective
			GROUP BY application_id
		) AS duedatemodCountTbl ON duedatemodCountTbl.application_id = appTbl.application_id
		WHERE ast.name IN ('Bankruptcy Verified')
			AND ash.date_created BETWEEN (?) AND (?)
			AND ash1.status_history_id IS NULL
			AND ash_start1.status_history_id IS NULL
 			AND appTbl.company_id = (
				SELECT company_id
				FROM company
				WHERE
				name_short = ?
				)
		GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the voided loan data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustVoidQuery($date, $company, array &$args)
	{
		$query = "
            SELECT DISTINCT
				'VO' as Type,
				ash.date_created AS TranDate,
				appTbl.ssn as SSN,
				appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
				'' AS LoanDate,
				'' AS DueDate,
				'0.00' AS PaymentAmt,
                IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
				'' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
				appTbl.bank_aba as BankABA,
				appTbl.bank_account AS BankAcct			
            FROM
				application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
				LEFT JOIN 
				(
                    SELECT
                            tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                            event_amount ea
                            JOIN event_amount_type eat USING (event_amount_type_id)
                            JOIN transaction_register tr USING(transaction_register_id)
                    GROUP BY tr.application_id
				) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
				JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
            JOIN application_status asp ON asp.application_status_id = appTbl.application_status_id
            JOIN status_history asq ON appTbl.application_id=asq.application_id
            JOIN application_status ast ON ast.application_status_id = asq.application_status_id
            WHERE ast.name_short = 'approved'
				AND (asp.name_short = 'withdrawn' OR asp.name_short = 'canceled')
				AND ash.date_created BETWEEN (?) AND (?)
 				AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ? )
        ";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustVoidQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'VO' as Type,
		DATE(ash.date_created) AS TranDate,
		appTbl.ssn as SSN,
		NULL AS AppID,
		IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
		NULL AS LoanDate,
		NULL AS DueDate,
		NULL AS PaymentAmt,
		NULL AS Balance,
		NULL AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		NULL as BankABA,
		NULL AS BankAcct			
		FROM
			application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
							AND ash_start.date_created < '" . self::FT_REPORTING_START . "')
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
		JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
		JOIN application_status asp ON asp.application_status_id = appTbl.application_status_id
		WHERE
			asp.name_short IN ('canceled','withdrawn','denied')
			AND ash.date_created BETWEEN (?) AND (?)
 			AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
					name_short = ? )
		GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getFactorTrustVoidQuery_CLH($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'VO' as Type,
		DATE(ash.date_created) AS TranDate,
		appTbl.ssn as SSN,
		NULL AS AppID,
		appTbl.application_id AS LoanID,
		NULL AS LoanDate,
		NULL AS DueDate,
		NULL AS PaymentAmt,
		NULL AS Balance,
		NULL AS ReturnCode,
                appTbl.application_id AS RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		NULL as BankABA,
		NULL AS BankAcct			
		FROM
			application appTbl
		JOIN status_history AS ash_start ON (ash_start.application_id=appTbl.application_id AND ash_start.application_status_id = 20
							AND ash_start.date_created > '" . self::FT_REPORTING_START . "')
		LEFT JOIN status_history AS ash_start1 ON (ash_start1.application_id=ash_start.application_id AND ash_start1.application_status_id = 20
						AND ash_start1.date_created < ash_start.date_created)
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_modified < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
		JOIN status_history ash ON (appTbl.application_id=ash.application_id AND appTbl.application_status_id = ash.application_status_id)
		JOIN application_status asp ON asp.application_status_id = appTbl.application_status_id
		WHERE
			asp.name_short IN ('canceled','withdrawn','denied')
			AND ash.date_created BETWEEN (?) AND (?)
			AND ash_start1.status_history_id IS NULL
 			AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
					name_short = ? )
		GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	// $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
	/**
	 * Returns a query that will provide the roll over loan pre-payment data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustRolloverPrePayQuery($date, $company, array &$args)
	{
		$query = "
            SELECT DISTINCTROW
                'PM' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                balanceTbl.total_balance AS PaymentAmt,
                0.00 AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct			
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE balanceTbl.total_balance > 0
                AND transDetailTbl.date_effective BETWEEN  (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                    name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
            $company
		);

		return $query;
	}
	*/
	public function getFactorTrustRolloverPrePayQuery($date, $company, array &$args)
	{
		$query = "
            SELECT DISTINCTROW
                'PM' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.application_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                balanceTbl.total_balance AS PaymentAmt,
                0.00 AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct			
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE balanceTbl.total_balance > 0
                AND transDetailTbl.date_effective BETWEEN  (?) AND (?)
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                    name_short = ?
                )
            GROUP BY appTbl.application_id
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
            $company
		);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the loan payment data required for factor trust.
	 *
	 * @param string $durration (number of days to wait before reporting)
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustOldZeroBalanceQuery($durration, $date, $company, array &$args)
	{
		$query = "
            SELECT 
                'PM' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.track_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct			
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status != 'failed'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.date_effective BETWEEN (?) AND (?)
                AND balanceTbl.total_balance <= 0
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
 		";

		$args = array(
            date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 23:59:59',
            date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustOldZeroBalanceQuery($durration, $date, $company, array &$args)
	{
		$query = "
            SELECT 
                'PM' as Type,
                transDetailTbl.date_effective AS TranDate,
                appTbl.ssn as SSN,
                appTbl.application_id AS AppID,
                IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),appTbl.application_id) AS LoanID,
                '' AS LoanDate,
                '' AS DueDate,
                -transDetailTbl.amount AS PaymentAmt,
                if(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
                '' AS ReturnCode,
                IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt-1),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
                IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
                appTbl.bank_aba as BankABA,
                appTbl.bank_account AS BankAcct			
            FROM
                application appTbl
                LEFT JOIN 
                (
                    SELECT application_id,count(DISTINCT date_effective) as cnt
                    FROM payment_transaction_detail
                    WHERE date_effective < (?)
                        AND transaction_status = 'complete'
                    GROUP BY application_id
                ) as transCountTbl ON transCountTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    WHERE tr.date_effective < (?)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.date_effective BETWEEN (?) AND (?)
                AND balanceTbl.total_balance <= 0
                AND appTbl.company_id = (
                    SELECT company_id
                    FROM company
                    WHERE
                        name_short = ?
                )
 		";

		$args = array(
            date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 23:59:59',
            date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$durration)).' 23:59:59',
			$company
		);

		return $query;
	}

	/**
	 * Returns a query that will provide the return data required for factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustReturnVoidQuery($date, $company, array &$args)
	{
		$query = "
            SELECT 
		'VO' as Type,
		transDetailTbl.date_effective AS TranDate,
		appTbl.ssn as SSN,
		appTbl.track_id AS AppID,
		IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt+1),appTbl.application_id) AS LoanID,
		'' AS LoanDate,
		'' AS DueDate,
		'0.00' AS PaymentAmt,
		IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
		'' AS ReturnCode,
		IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
		IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		appTbl.bank_aba as BankABA,
		appTbl.bank_account AS BankAcct
            FROM
                application appTbl
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT ptd.application_id,count(DISTINCT(ptd.date_effective)) as cnt,ptd2.date_effective
                    FROM payment_transaction_detail ptd
                        JOIN payment_transaction_detail ptd2 USING (application_id)
                    WHERE ptd.date_effective < ptd2.date_effective
                        AND ptd.transaction_status != 'failed'
                        AND ptd2.transaction_status = 'failed' 
                        AND ptd2.date_modified BETWEEN (?) AND (?)
                    GROUP BY ptd.application_id,ptd2.date_effective
                ) as transCountTbl ON transCountTbl.application_id = transDetailTbl.application_id AND transCountTbl.date_effective = transDetailTbl.date_effective
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.transaction_status = 'failed' 
				AND transDetailTbl.date_modified BETWEEN (?) AND (?)
 				AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustReturnVoidQuery($date, $company, array &$args)
	{
		$query = "
            SELECT 
		'VO' as Type,
		transDetailTbl.date_effective AS TranDate,
		appTbl.ssn as SSN,
		appTbl.application_id AS AppID,
		IF(transCountTbl.cnt>0,CONCAT(appTbl.application_id,'V',transCountTbl.cnt+1),appTbl.application_id) AS LoanID,
		'' AS LoanDate,
		'' AS DueDate,
		'0.00' AS PaymentAmt,
		IF(balanceTbl.total_balance>0,balanceTbl.total_balance,0) AS Balance,
		'' AS ReturnCode,
		IF(transCountTbl.cnt>1,CONCAT(appTbl.application_id,'V',transCountTbl.cnt),IF(transCountTbl.cnt>0,appTbl.application_id,'')) as RollOverRef,
		IF(transCountTbl.cnt>0,transCountTbl.cnt,'') as RollOverNumber,
		appTbl.bank_aba as BankABA,
		appTbl.bank_account AS BankAcct
            FROM
                application appTbl
                LEFT JOIN payment_transaction_detail transDetailTbl on transDetailTbl.application_id = appTbl.application_id
                LEFT JOIN 
                (
                    SELECT ptd.application_id,count(DISTINCT(ptd.date_effective)) as cnt,ptd2.date_effective
                    FROM payment_transaction_detail ptd
                        JOIN payment_transaction_detail ptd2 USING (application_id)
                    WHERE ptd.date_effective < ptd2.date_effective
                        AND ptd.transaction_status != 'failed'
                        AND ptd2.transaction_status = 'failed' 
                        AND ptd2.date_modified BETWEEN (?) AND (?)
                    GROUP BY ptd.application_id,ptd2.date_effective
                ) as transCountTbl ON transCountTbl.application_id = transDetailTbl.application_id AND transCountTbl.date_effective = transDetailTbl.date_effective
                LEFT JOIN 
                (
                    SELECT
                        tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance
                    FROM
                        event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
                    GROUP BY tr.application_id
                ) as balanceTbl ON balanceTbl.application_id = appTbl.application_id
            WHERE transDetailTbl.transaction_status = 'failed' 
				AND transDetailTbl.date_modified BETWEEN (?) AND (?)
 				AND appTbl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}

	/**
	 * Returns a query that will provide the active loan data required for initializing factor trust.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getFactorTrustActiveLoanQuery($num, $date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
                a.application_id AS LoanID,
                'NL' as Type,
                a.date_fund_actual AS TranDate,
                a.ssn as SSN,
                a.track_id AS AppID,
                a.date_fund_actual AS LoanDate,
                IF(a.date_fund_actual>=a.date_first_payment,DATE_ADD(a.date_fund_actual,INTERVAL 30 DAY),a.date_first_payment) AS DueDate,
                0.00 AS PaymentAmt,
                a.fund_actual AS Balance,
                '' AS ReturnCode,
                '' as RollOverRef,
                '' as RollOverNumber,
                a.bank_aba as BankABA,
                a.bank_account AS BankAcct	
			FROM
				application".$num." a
            ORDER BY a.application_id;
		";

		$args = array(
            $date. ' 00:00:00',
            $date. ' 00:00:00',
			$company
		);

		return $query;
	}
	*/
	public function getFactorTrustActiveLoanQuery($num, $date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
                a.application_id AS LoanID,
                'NL' as Type,
                a.date_fund_actual AS TranDate,
                a.ssn as SSN,
                a.application_id AS AppID,
                a.date_fund_actual AS LoanDate,
                IF(a.date_fund_actual>=a.date_first_payment,DATE_ADD(a.date_fund_actual,INTERVAL 30 DAY),a.date_first_payment) AS DueDate,
                0.00 AS PaymentAmt,
                a.fund_actual AS Balance,
                '' AS ReturnCode,
                '' as RollOverRef,
                '' as RollOverNumber,
                a.bank_aba as BankABA,
                a.bank_account AS BankAcct	
			FROM
				application".$num." a
            ORDER BY a.application_id;
		";

		$args = array(
            $date. ' 00:00:00',
            $date. ' 00:00:00',
			$company
		);

		return $query;
	}

	/**
	 * Returns a query that will provide the active loan payment data required to initialize factor trust.
	 *   Up to a certain date for testing purposes
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getFactorTrustActiveLoanPaymentsBalanceQuery($date, $company, array &$args)
	{
		$query = "
            SELECT
                transDetailTbl.application_id AS LoanID,
                transDetailTbl.date_effective AS TranDate,
                -transDetailTbl.amount AS PaymentAmt,
                SUM(eventAmountTbl.amount) + transDetailTbl.amount AS Balance
            FROM
                payment_transaction_detail transDetailTbl
                LEFT JOIN event_amount_view eventAmountTbl USING (application_id)
            WHERE transDetailTbl.transaction_status != 'failed'
                AND transDetailTbl.date_effective < ?
                AND DATE(transDetailTbl.date_event) > DATE(eventAmountTbl.date_effective)
            GROUP BY transDetailTbl.application_id, transDetailTbl.date_effective
            ORDER BY transDetailTbl.application_id, transDetailTbl.date_effective
 		";

		$args = array(
            $date. ' 00:00:00'
            );

		return $query;
	}

	/**
	 * Creates gathers a transaction amount view for the factor trust queries.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function makeFactorTrustEventAmountView(array &$args, $num = '')
	{
		$query = "
        CREATE OR REPLACE VIEW event_amount_view AS
            SELECT
                tr.application_id,DATE(tr.date_effective) as date_effective, ea.amount
            FROM
                event_amount".$num." ea
                JOIN event_amount_type eat USING (event_amount_type_id)
                JOIN transaction_register".$num." tr USING(transaction_register_id)
            WHERE eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed'
			ORDER BY application_id;
		";
        // ahead and behind three months
		$args = array(
		);   
		return $query;
	}

	/**
	 * Returns a query that will provide the active loan payment data required to initialize factor trust.
	 *   Up to a certain date for testing purposes
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getFactorTrustActiveLoanPaymentsTimingQuery($date, $company, array &$args)
	{
		$query = "
            SELECT
                transDetailTbl.application_id AS  LoanID,
                transDetailTbl.lastpay AS  LastPay,
                min(nextEventTbl.date_effective) AS DueDate
            FROM (
                    SELECT application_id, max(date_effective) as lastpay
                    FROM payment_transaction_detail
                    GROUP BY application_id
                ) transDetailTbl
                JOIN payment_timing as nextEventTbl USING (application_id)
            WHERE DATE(nextEventTbl.date_effective) >  DATE(transDetailTbl.lastpay)
            GROUP BY transDetailTbl.application_id
            ORDER BY transDetailTbl.application_id
            ";

		$args = array(
            $date. ' 00:00:00'
            );

		return $query;
	}
    
	/**
	 * Generic delete query designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function deleteFactorTrustApplicationSubTable(array &$args, $table, $num = '')
	{
		$query = "
            DROP TABLE IF EXISTS `".$table.$num."`;
            ";

		$args = array(
            );

		return $query;
	}

	/**
	 * One of five create table queries designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function createFactorTrustApplicationSubTable(array &$args, $num = '')
	{
		$query = "
            CREATE TABLE `application".$num."` (
              `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'This is the last date this row was modified',
              `date_created` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'This is the date this row was created',
              `company_id` int(10) unsigned NOT NULL default '0' COMMENT 'This is the company identifier for this record',
              `application_id` int(10) unsigned NOT NULL default '0',
              `customer_id` int(10) unsigned NOT NULL default '0',
              `version` smallint(6) NOT NULL default '1' COMMENT 'Version number of the State used by the VendorAPI',
              `archive_db2_id` int(10) unsigned default NULL,
              `archive_mysql_id` int(10) unsigned default NULL,
              `archive_cashline_id` int(10) unsigned default NULL,
              `login_id` int(10) unsigned default NULL,
              `is_react` enum('no','yes') NOT NULL,
              `loan_type_id` int(10) unsigned NOT NULL default '0',
              `rule_set_id` int(10) unsigned default NULL,
              `enterprise_site_id` int(10) unsigned NOT NULL default '0',
              `application_status_id` int(10) unsigned NOT NULL default '0',
              `date_application_status_set` timestamp NOT NULL default '0000-00-00 00:00:00',
              `date_next_contact` timestamp NULL default NULL,
              `ip_address` varchar(40) NOT NULL default '',
              `application_type` enum('paperless','paper') NOT NULL default 'paperless',
              `bank_name` varchar(100) NOT NULL default '',
              `bank_aba` varchar(9) NOT NULL default '',
              `bank_account` varchar(24) character set latin1 collate latin1_bin NOT NULL,
              `bank_account_type` enum('checking','savings') NOT NULL default 'checking',
              `date_fund_estimated` date default NULL,
              `date_fund_actual` date default NULL,
              `date_first_payment` date default NULL,
              `fund_requested` decimal(7,2) default NULL,
              `fund_qualified` decimal(7,2) NOT NULL default '0.00',
              `fund_actual` decimal(7,2) default NULL,
              `finance_charge` decimal(7,2) default NULL,
              `payment_total` decimal(7,2) default NULL,
              `apr` decimal(9,4) default NULL,
              `rate_override` decimal(7,4) unsigned default NULL,
              `income_monthly` decimal(7,2) NOT NULL default '0.00',
              `income_source` enum('employment','benefits','military','self_employment') NOT NULL default 'employment',
              `income_direct_deposit` enum('no','yes') NOT NULL default 'no',
              `income_frequency` enum('weekly','twice_monthly','bi_weekly','monthly') NOT NULL default 'weekly',
              `income_date_soap_1` date default NULL,
              `income_date_soap_2` date default NULL,
              `paydate_model` enum('dw','dwpd','dmdm','wwdw','dm','dwdm','wdw') NOT NULL default 'dw',
              `day_of_week` enum('sun','mon','tue','wed','thu','fri','sat') default NULL,
              `last_paydate` date default NULL,
              `day_of_month_1` tinyint(3) unsigned default NULL,
              `day_of_month_2` tinyint(3) unsigned default NULL,
              `week_1` tinyint(3) unsigned default NULL,
              `week_2` tinyint(3) unsigned default NULL,
              `track_id` varchar(40) default NULL,
              `agent_id` int(10) unsigned default NULL,
              `agent_id_callcenter` int(10) unsigned default NULL,
              `dob` varchar(16) character set latin1 collate latin1_bin NOT NULL,
              `ssn` varchar(12) character set latin1 collate latin1_bin NOT NULL,
              `legal_id_number` varchar(40) character set latin1 collate latin1_bin default NULL,
              `legal_id_state` char(2) default NULL,
              `legal_id_type` enum('dl','sid','pp') default NULL,
              `identity_verified` enum('unverified','verified') NOT NULL default 'unverified',
              `email` varchar(100) NOT NULL default '',
              `email_verified` enum('unverified','verified') NOT NULL default 'unverified',
              `name_last` varchar(50) NOT NULL default '',
              `name_first` varchar(50) NOT NULL default '',
              `name_middle` varchar(50) default NULL,
              `name_suffix` varchar(20) default NULL,
              `street` varchar(100) NOT NULL default '',
              `unit` varchar(10) default NULL,
              `city` varchar(30) NOT NULL default '',
              `state` char(2) NOT NULL default '',
              `county` varchar(30) default NULL,
              `zip_code` varchar(9) NOT NULL default '',
              `tenancy_type` enum('unspecified','own','rent') NOT NULL default 'unspecified',
              `phone_home` varchar(10) NOT NULL default '',
              `phone_cell` varchar(10) default NULL,
              `phone_fax` varchar(10) default NULL,
              `call_time_pref` enum('no preference','morning','afternoon','evening') NOT NULL default 'no preference',
              `contact_method_pref` enum('no preference','home phone','work phone','cell phone','email','usps') NOT NULL default 'no preference',
              `marketing_contact_pref` enum('no preference','phone','email','usps','no contact') NOT NULL default 'no preference',
              `employer_name` varchar(100) default NULL,
              `job_title` varchar(100) default NULL,
              `supervisor` varchar(50) default NULL,
              `shift` enum('day','swing','grave','other') default NULL,
              `date_hire` date default NULL,
              `job_tenure` decimal(4,2) default NULL,
              `phone_work` varchar(10) default NULL,
              `phone_work_ext` varchar(8) default NULL,
              `work_address_1` varchar(50) default NULL,
              `work_address_2` varchar(50) default NULL,
              `work_city` varchar(30) default NULL,
              `work_state` char(2) default NULL,
              `work_zip_code` varchar(9) default NULL,
              `employment_verified` enum('unverified','verified') NOT NULL default 'unverified',
              `pwadvid` varchar(40) character set latin1 collate latin1_bin default NULL,
              `olp_process` varchar(255) NOT NULL default 'email_confirmation',
              `is_watched` enum('no','yes') NOT NULL,
              `schedule_model_id` int(10) unsigned NOT NULL,
              `modifying_agent_id` int(10) unsigned NOT NULL default '1',
              `banking_start_date` date default NULL,
              `residence_start_date` date default NULL,
              `cfe_rule_set_id` int(10) unsigned default NULL,
              `price_point` decimal(7,2) default NULL,
              `bank_account_oldkey` varchar(24) character set latin1 collate latin1_bin default NULL,
              `dob_oldkey` varchar(16) character set latin1 collate latin1_bin default NULL,
              `age` tinyint(3) unsigned NOT NULL,
              `ssn_oldkey` varchar(12) character set latin1 collate latin1_bin default NULL,
              `legal_id_number_oldkey` varchar(40) character set latin1 collate latin1_bin default NULL,
              `encryption_key_id` int(10) unsigned default NULL,
              `ssn_last_four` varchar(4) default NULL,
              PRIMARY KEY  (`application_id`),
              UNIQUE KEY `idx_app_archive_db2` (`archive_db2_id`,`company_id`),
              UNIQUE KEY `idx_app_archive_mysql` (`archive_mysql_id`,`company_id`),
              UNIQUE KEY `idx_app_archive_cashline` (`archive_cashline_id`,`company_id`),
              KEY `idx_app_bank_acct_aba_ssn` (`bank_account`,`bank_aba`,`ssn`,`date_created`),
              KEY `idx_app_email_co_date` (`email`,`company_id`,`date_created`),
              KEY `idx_app_ruleset_date` (`rule_set_id`,`date_created`),
              KEY `idx_date_created` (`date_created`),
              KEY `idx_phone_cell` (`phone_cell`(6)),
              KEY `idx_track_id` (`track_id`(6)),
              KEY `idx_olp_process` (`olp_process`(5)),
              KEY `idx_phone_home` (`phone_home`(6)),
              KEY `idx_legal_id_number` (`legal_id_number`(6)),
              KEY `idx_app_custid` (`customer_id`),
              KEY `idx_app_co_date_status_set` (`company_id`,`date_application_status_set`),
              KEY `idx_app_status_co_stsdate` (`application_status_id`,`company_id`,`date_application_status_set`),
              KEY `idx_app_status_co_nxtdate` (`application_status_id`,`company_id`,`date_next_contact`),
              KEY `idx_app_ssn_co_app` (`ssn`,`company_id`),
              KEY `idx_app_lname_co_app` (`name_last`,`company_id`),
              KEY `idx_app_fname_co_app` (`name_first`,`company_id`),
              KEY `idx_app_lname_fname_co_app` (`name_last`,`name_first`,`company_id`),
              KEY `idx_app_login_app` (`login_id`,`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            ";

		$args = array(
            );

		return $query;
	}

	/**
	 * One of five create table queries designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function createFactorTrustEventScheduleSubTable(array &$args, $num = '')
	{
		$query = "
            CREATE TABLE `event_schedule".$num."` (
              `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
              `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
              `company_id` int(10) unsigned NOT NULL default '0',
              `application_id` int(10) unsigned NOT NULL default '0',
              `event_schedule_id` int(10) unsigned NOT NULL auto_increment,
              `event_type_id` int(10) unsigned NOT NULL default '0',
              `origin_id` int(10) unsigned default NULL,
              `origin_group_id` int(11) default NULL,
              `configuration_trace_data` varchar(255) default NULL,
              `amount_principal` decimal(7,2) NOT NULL default '0.00',
              `amount_non_principal` decimal(7,2) NOT NULL default '0.00',
              `event_status` enum('scheduled','registered','suspended') NOT NULL default 'scheduled',
              `date_event` date NOT NULL default '0000-00-00',
              `date_effective` date NOT NULL default '0000-00-00',
              `context` enum('arrangement','manual','generated','paydown','payout','cancel','reattempt','partial','arrange_next') NOT NULL default 'generated',
              `source_id` int(10) unsigned NOT NULL default '4',
              `is_shifted` tinyint(3) unsigned NOT NULL default '0',
              PRIMARY KEY  (`event_schedule_id`),
              KEY `idx_event_sched_app_date` (`application_id`,`date_event`,`event_schedule_id`),
              KEY `idx_event_sched_sts_app` (`event_status`,`application_id`),
              KEY `idx_event_sched_app_eff_sts` (`application_id`,`date_effective`,`event_status`),
              KEY `idx_event_sched_app_origin` (`application_id`,`origin_id`,`date_event`),
              KEY `idx_event_sched_app_origin_grp` (`application_id`,`origin_group_id`,`date_event`),
              KEY `idx_event_sched_app_eff` (`date_effective`,`application_id`),
              KEY `idx_event_sched_sts_etid` (`event_status`,`event_type_id`),
              KEY `idx_date_created_context` (`date_created`,`context`),
              KEY `idx_date_effective_context` (`date_effective`,`context`),
              KEY `id_event_sched_origin` (`origin_id`),
              KEY `id_application_id` (`application_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
            ";

		$args = array(
            );

		return $query;
	}

	/**
	 * One of five create table queries designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function createFactorTrustTransactionRegisterSubTable(array &$args, $num = '')
	{
		$query = "
            CREATE TABLE `transaction_register".$num."` (
              `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
              `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
              `company_id` int(10) unsigned NOT NULL default '0',
              `application_id` int(10) unsigned NOT NULL default '0',
              `transaction_register_id` int(10) unsigned NOT NULL auto_increment,
              `event_schedule_id` int(10) unsigned NOT NULL default '0',
              `ach_id` int(10) unsigned default NULL,
              `ecld_id` int(10) unsigned default NULL,
              `transaction_type_id` int(10) unsigned NOT NULL default '0',
              `transaction_status` enum('new','pending','complete','failed') NOT NULL default 'new',
              `amount` decimal(7,2) NOT NULL default '0.00',
              `date_effective` date NOT NULL default '0000-00-00',
              `source_id` int(10) unsigned NOT NULL default '4',
              `modifying_agent_id` int(10) unsigned NOT NULL default '1',
              PRIMARY KEY  (`transaction_register_id`),
              KEY `idx_trans_reg_app_date` (`application_id`,`date_effective`,`transaction_register_id`),
              KEY `idx_trans_reg_app_type_date` (`application_id`,`transaction_type_id`,`date_effective`),
              KEY `idx_trans_reg_sts_co_dt` (`transaction_status`,`company_id`,`date_effective`),
              KEY `idx_trans_reg_achid` (`ach_id`),
              KEY `idx_trans_reg_mdate` (`date_modified`),
              KEY `idx_trans_reg_eventsched_id` (`event_schedule_id`),
              KEY `idx_trans_reg_ecldid` (`ecld_id`),
              KEY `id_application_id` (`application_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
            ";

		$args = array(
            );

		return $query;
	}

	/**
	 * One of five create table queries designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function createFactorTrustAchSubTable(array &$args, $num = '')
	{
		$query = "
            CREATE TABLE `ach".$num."` (
              `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
              `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
              `company_id` int(10) unsigned NOT NULL default '0',
              `application_id` int(10) unsigned NOT NULL default '0',
              `ach_id` int(10) unsigned NOT NULL auto_increment,
              `ach_batch_id` int(10) unsigned NOT NULL default '0',
              `ach_report_id` int(10) unsigned default NULL,
              `origin_group_id` int(11) NOT NULL,
              `ach_date` date NOT NULL default '0000-00-00',
              `amount` decimal(7,2) NOT NULL default '0.00',
              `ach_type` enum('debit','credit','debit_arc') NOT NULL default 'debit',
              `bank_aba` varchar(9) NOT NULL default '',
              `bank_account` varchar(24) character set latin1 collate latin1_bin NOT NULL,
              `bank_account_type` enum('checking','savings') NOT NULL default 'checking',
              `ach_status` enum('created','batched','returned','processed') NOT NULL default 'created',
              `ach_return_code_id` int(10) unsigned default NULL,
              `ach_trace_number` varchar(15) NOT NULL default '',
              `bank_account_oldkey` varchar(24) character set latin1 collate latin1_bin default NULL,
              `encryption_key_id` int(10) unsigned default NULL,
              PRIMARY KEY  (`ach_id`),
              KEY `idx_ach_app_dt` (`application_id`,`ach_date`,`ach_id`),
              KEY `idx_ach_aba_account_co_dt` (`bank_aba`,`bank_account`,`company_id`,`ach_date`),
              KEY `idx_ach_batchid_origin_grp` (`ach_batch_id`,`origin_group_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
            ";

		$args = array(
            );

		return $query;
	}
    
	/**
	 * One of five create table queries designed prepare the database to divide the data tables
	 *  related to application payments into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function createFactorTrustEventAmountSubTable(array &$args, $num = '')
	{
		$query = "
            CREATE TABLE `event_amount".$num."` (
              `event_amount_id` bigint(20) unsigned NOT NULL auto_increment,
              `event_schedule_id` int(10) unsigned NOT NULL default '0',
              `transaction_register_id` int(10) unsigned NOT NULL default '0',
              `event_amount_type_id` int(10) unsigned NOT NULL default '0',
              `amount` decimal(7,2) NOT NULL default '0.00',
              `application_id` int(10) unsigned NOT NULL default '0',
              `num_reattempt` int(10) unsigned NOT NULL default '0',
              `company_id` int(10) unsigned NOT NULL default '0',
              `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
              `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (`event_amount_id`),
              KEY `idx_event_amt_app_esid` (`application_id`,`event_schedule_id`,`transaction_register_id`),
              KEY `idx_event_amt_eatid_appid_esid_trid` (`event_amount_type_id`,`application_id`,`event_schedule_id`,`transaction_register_id`),
              KEY `idx_event_amt_esid_eatid` (`event_schedule_id`,`event_amount_type_id`),
              KEY `idx_trid` (`transaction_register_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
            ";

		$args = array(
            );

		return $query;
	}
    
	/**
	 * Insert query designed to divide the application table into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function fillFactorTrustApplicationSubTable(array &$args, $div, $set, $num )
	{
		$query = "
            INSERT INTO application".$set.'_'.$num." 
            SELECT DISTINCTROW a.*
            FROM application".$set." a
            WHERE MOD(a.application_id, ".$div.") = ".$num;

		$args = array(
            );

		return $query;
	}
    
	/**
	 * Insert query designed to divide the transaction register table into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function fillFactorTrustTransactionRegisterSubTables(array &$args, $set, $num )
	{
		$query = "
                INSERT INTO transaction_register".$set.$num."
                SELECT DISTINCTROW tr.*
                FROM transaction_register".$set." tr
                WHERE tr.application_id IN
                    (SELECT application_id FROM application".$set.$num.");
            ";

		$args = array(
            );

		return $query;
	}
    
	/**
	 * Insert query designed to divide the event schedule table into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function fillFactorTrustEventScheduleSubTables(array &$args, $set, $num )
	{
		$query = "
            INSERT INTO event_schedule".$set.$num." 
            SELECT DISTINCTROW es.*
            FROM event_schedule".$set." es
            WHERE es.application_id IN
                (SELECT application_id FROM application".$set.$num.");
            ";

		$args = array(
            );

		return $query;
	}
    
	/**
	 * Insert query designed to divide the event schedule table into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function fillFactorTrustAchSubTables(array &$args, $set, $num )
	{
		$query = "
            INSERT INTO ach".$set.$num." 
            SELECT DISTINCTROW *
            FROM ach".$set."
            WHERE ach_id IN
                (SELECT ach_id FROM transaction_register".$set.$num.");
            ";

		$args = array(
            );

		return $query;
	}
    
	/**
	 * Insert query designed to divide the event amount table into byte (pun) sized parts to speed up the FactoTrust
	 *  initialize process
	 * 
	 * @param array $args
	 * @return string
	 */
	public function fillFactorTrustEventAmountSubTables(array &$args, $set, $num )
	{
		$query = "
            INSERT INTO event_amount".$set.$num." 
            SELECT DISTINCTROW *
            FROM event_amount".$set."
            WHERE transaction_register_id IN
                (SELECT transaction_register_id FROM transaction_register".$set.$num.");
            ";

		$args = array(
            );

		return $query;
	}

    public function getStatusHistoryQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['application_status_name'] = 'a.application_status_name';

		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				" . self::STATUS_HISTORY_APPLICATION_TEMP_TABLE . " AS a
				LEFT JOIN transaction_register tr
					ON tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				LEFT JOIN transaction_type tt
					ON tt.transaction_type_id = tr.transaction_type_id
			GROUP BY
				a.application_id
			HAVING
				-- We do not want to pick up customers with cancellations. This will be handled by the 'export_cancel' script
				COUNT(IF(tt.name_short LIKE 'cancel_%', tr.transaction_register_id, NULL)) = 0
		";

		return $query;
	}

	public function getCancellationTransactionsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT
				tl.application_id
			FROM
				transaction_ledger tl
			WHERE
				tl.date_created BETWEEN ? AND ?
				AND tl.date_created >= '2006-06-06 00:00:00' -- #12345 No updates before 2006/06/06
				AND tl.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
				AND tl.transaction_type_id IN (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE name_short IN ('cancel_fees', 'cancel_principal')
				)
			ORDER BY
				tl.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getRecoveriesTempTableQuery($date, $company, array &$args)
	{
		$query = "
			CREATE TEMPORARY TABLE " . self::RECOVERIES_TRANSACTION_TEMP_TABLE . " (INDEX (application_id))
			SELECT DISTINCT
				tr.application_id,
				IFNULL(SUM(tr.amount), 0) AS balance,
				IFNULL(SUM(-tl.amount), 0) AS recovery_amount
			FROM
				transaction_ledger tl
				LEFT JOIN transaction_register tr
					ON tr.application_id = tl.application_id AND tr.transaction_status = 'complete'
			WHERE
				tl.date_created BETWEEN ? AND ?
				AND tl.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
				AND tl.transaction_type_id IN ( SELECT transaction_type_id FROM transaction_type WHERE name_short LIKE 'ext_recovery%' )
			GROUP BY
				tl.application_id
			ORDER BY
				tl.date_created ASC
		";
		
		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);
		
		return $query;
	}
	
	public function getRecoveriesTempTableApplicationsQuery()
	{
		return "SELECT application_id FROM " . self::RECOVERIES_TRANSACTION_TEMP_TABLE;
	}

	public function getRecoveriesQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 't.balance';
		$fields['recovery_amount'] = 't.recovery_amount';

		$query = "
			SELECT DISTINCT
				{$this->buildFields($fields)}
			FROM
				" . self::RECOVERIES_TRANSACTION_TEMP_TABLE . " AS t
				JOIN " . self::RECOVERIES_APPLICATION_TEMP_TABLE . " AS a
					ON t.application_id = a.application_id
		";

		return $query;
	}

	/**
	 * This method is no longer used. It would be run as part of the react_fund_updates script.
	 * 
	 * @deprecated
	 */
	public function getFundedReacts($date, $company, $active_status_id, array &$args)
	{
		$query = "
			SELECT DISTINCT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				status_history sh
				JOIN application a USING (application_id)
			WHERE
				    sh.date_created BETWEEN (?) AND (?)
				AND sh.application_status_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			AND	a.is_react = 'yes'
			ORDER BY
				sh.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$active_status_id,
			$company
		);

		return $query;
	}

	protected function getCommonApplicationFields()
	{
		return array(
			'application_id' => 'a.application_id',
			'fund_date' => 'a.date_fund_actual',
			'fund_amount' => 'a.fund_actual',
			'date_first_payment' => 'a.date_first_payment',
			//'fee_amount' => 'a.fee_amount', //not present in AALM schema
			'employer_name' => 'a.employer_name',
			'employer_street1' => 'a.work_address_1',
			'employer_street2' => 'a.work_address_2',
			'employer_city' => 'a.work_city',
			'employer_state' => 'a.work_state',
			'employer_zip' => 'a.work_zip_code',
			'pay_period' => "IF(a.income_frequency = 'twice_monthly','semi_monthly',a.income_frequency)",
			'phone_work' => 'a.phone_work',
			'phone_ext' => 'a.phone_work_ext',
			'name_first' => 'a.name_first',
			'name_middle' => 'a.name_middle',
			'name_last' => 'a.name_last',
			'street1' => 'a.street',
			'street2' => 'a.unit',
			'city' => 'a.city',
			'state' => 'a.state',
			'zip' => 'a.zip_code',
			'phone_home' => 'a.phone_home',
			'phone_cell' => 'a.phone_cell',
			'email' => 'a.email',
			'ip_address' => 'a.ip_address',
			'dob' => 'a.dob',
			'ssn' => 'a.ssn',
			'track_id' => 'a.track_id',
			'driver_license_number' => 'a.legal_id_number',
			'driver_license_state' => 'a.legal_id_state',
			'bank_name' => 'a.bank_name',
			'bank_aba' => 'a.bank_aba',
			'bank_acct_number' => 'a.bank_account'
		);
	}

	protected function buildFields($fields)
	{
		$field_string = '';
		foreach ($fields as $label => $value)
		{
			$field_string .= "{$value} {$label},\n";
		}

		return substr($field_string, 0, -2);
	}
    
	/**
	 * Returns a query that will provide the new loan data required for clarity.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*		
	public function getClarityNewLoanQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
                '' as VoidStatus,
                'C1' as AccountType,
                'P' as PortfolioType,
                a.application_id AS LoanID,
                a.date_fund_actual AS LoanDate,
								es.date_effective AS FirstDate,
                '' AS LastDate,
                '' AS PastDate,
                '' AS CloseDate,
                0 AS PaymentAmt,
                a.fund_actual AS Balance,
                a.fund_actual AS Principal,
				es.amount AS SchedPayment,
                0 AS PastDue,
                'false' AS FirstPaymentBad,
                if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
                1 AS Duration,
                0 AS Rating,
		'------------------------' AS History,
                a.ssn as SSN,
                a.name_first as NameFirst,
                a.name_last as NameLast,
                a.dob as DOB,
                a.street as Street1,
                a.unit as Street2,
                a.city as City,
                a.state as State,
                a.zip_code as Zip,
                a.phone_home as PhoneHome,
                a.bank_account AS BankAcct,			
                a.bank_aba as BankABA,
                bi.trace_info AS AppID
			FROM
                application a
                JOIN status_history ash ON (a.application_id=ash.application_id)
                JOIN application_status ast ON ast.application_status_id = ash.application_status_id
                LEFT JOIN (
                    SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
                    FROM bureau_inquiry
                    JOIN bureau USING (bureau_id)
                    WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
                JOIN (
                    SELECT es1.date_effective as date_effective, es1.application_id as application_id, -(sum(es1.amount_principal+es1.amount_non_principal)) as amount
                    FROM event_schedule es1
                    JOIN (
                        SELECT application_id,min(date_effective) as date_effective
                        FROM event_schedule WHERE date_effective > (?)
                            AND ( event_type_id = 2 OR event_type_id = 3 )
                        GROUP BY application_id
                    ) es2 ON (es2.application_id = es1.application_id AND es2.date_effective = es1.date_effective)
                    WHERE es1.date_effective > (?)
                        AND ( event_type_id = 2 OR event_type_id = 3 ) 
                    GROUP BY application_id
                ) es ON (a.application_id=es.application_id)
			WHERE
				ast.name_short = 'active'
                AND ash.date_created BETWEEN (?) AND (?)
                AND a.application_id NOT IN
                    (SELECT application_id
                        FROM status_history ash2
                        JOIN application_status ast2 USING (application_status_id)
                        WHERE ast2.name_short = 'active'
                            AND ash2.date_created < (?))
 				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short =?
				)";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$company
		);

		return $query;
	}
	*/

	public function getClarityNewLoanQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		0 AS Rating,
		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		'' AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,

		IFNULL((SELECT 
		ROUND(ABS(SUM(ea1.amount)))
		FROM event_schedule AS es1
		JOIN event_amount AS ea1 ON (ea1.event_schedule_id=es1.event_schedule_id
		AND ea1.event_amount_type_id <> 4
		AND ea1.amount < 0)
		WHERE es1.application_id=a.application_id
		AND es1.event_status='scheduled'
		GROUP BY es1.date_effective
		ORDER BY es1.date_effective ASC LIMIT 1
		),
		ROUND(ABS(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status='complete', ea.amount, 0))))
		) AS SchedPayment,
		0 AS PaymentAmt,
		'' AS LastDate,
		ROUND(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status<>'failed', ea.amount, 0))) AS Balance,
		0 AS PastDue,
		'' AS PastDate,
		'' AS VoidStatus,
		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		'false' AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN status_history ash ON (a.application_id=ash.application_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)

		LEFT JOIN status_history ash1 ON (ash1.application_id=a.application_id
		AND ash1.application_status_id=a.application_status_id
		AND ash1.date_created < (?))

		WHERE
		a.application_status_id IN (20)
		AND ash.date_created BETWEEN (?) AND (?)
		AND c.name_short = ?
		AND ash1.status_history_id IS NULL
		-- AND a.application_id=901700745
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}

	/**
	 * Returns a query that will provide the payment data required for clarity.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getClarityApTransQuery($date, $app_id ,array &$args)
	{
		$query = "
            SELECT tr.application_id, tr.transaction_status, tr.date_effective, tr.date_modified, sum(tr.amount) sum_amount, tt.name_short, tt.clearing_type, es.context
            FROM transaction_register tr
                JOIN transaction_type tt USING (transaction_type_id)
                JOIN event_schedule es USING (event_schedule_id)
            WHERE tr.amount < 0
                AND tr.application_id = (?)
                AND tr.date_effective <= (?)
            GROUP BY tr.date_effective
            ORDER BY tr.date_effective";

		$args = array(
			$app_id,
			$date . ' 23:59:59'
		);

		return $query;
	}
  	/**
	 * Returns a query that will provide the payment data required for clarity.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getClarityApStatusQuery($date, $app_id ,array &$args)
	{
		$query = "
            SELECT sh.application_id, sh.application_status_id, max(sh.date_created) as date_created,st.name as status,pst.name as status_parent
            FROM status_history sh
                JOIN application_status st USING (application_status_id)
                JOIN application_status pst ON (st.application_status_id = pst.application_status_parent_id)
            WHERE sh.application_id = (?)
                AND sh.date_created <= (?)
            GROUP BY sh.application_id";

		$args = array(
			$app_id,
			$date . ' 23:59:59'
		);

		return $query;
	}
    
	/**
	 * Returns a query that will provide the list and data for reporting payments.
	 * 
	 * @param string $date
	 * @param string $app_id
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getClarityPaymentsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
			'' as VoidStatus,
			'C1' as AccountType,
			'P' as PortfolioType,
			a.application_id AS LoanID,
			a.date_fund_actual AS LoanDate,
			es.date_effective AS FirstDate,
			lp.last_payment_date AS LastDate,
			dt.delinquency_date AS PastDate,
			'' AS CloseDate,
			ABS(sum(tr.amount)) AS PaymentAmt,
			bal.total_balance AS Balance,
			prn.total_balance AS Principal,
			es.amount AS SchedPayment,
			dt.amount_past_due AS PastDue,
			if(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad,
			if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
			1 AS Duration,
			(CASE 
WHEN a.application_status_id IN (20,193) THEN '0' -- active, refi
WHEN a.application_status_id IN (132,134,190,192,125) THEN '#' -- collections
WHEN a.application_status_id IN (111,112,130,131,159,160,161) THEN '+' -- charged off
WHEN a.application_status_id IN (109,113,162,158) THEN '@' -- paid
WHEN a.application_status_id IN (194,19,124) THEN '@' -- cancel, withdrawn, funding failed
WHEN a.application_status_id IN (123,137,138) -- past due, coll new treated as past due
THEN
(
CASE
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) IN (0,1)) THEN '1'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 2 AND 9) THEN (SELECT DATEDIFF(NOW(),a.date_application_status_set))
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 10) THEN 'A'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 11) THEN 'B'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 12) THEN 'C'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 13) THEN 'D'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 14) THEN 'E'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 15) THEN 'F'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 16) THEN 'G'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 17) THEN 'H'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 18) THEN 'I'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 19) THEN 'J'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 20) THEN 'K'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 21) THEN 'L'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 22) THEN 'M'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 23) THEN 'N'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 24) THEN 'P'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 25) THEN 'Q'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 26) THEN 'R'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 27) THEN 'S'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 28) THEN 'T'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 29) THEN 'U'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 30 AND 59) THEN 'V'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 60 AND 89) THEN 'W'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 90 AND 119) THEN 'X'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 120 AND 149) THEN 'Y'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) >= 150) THEN 'Z'
ELSE 'Z'
END
)
ELSE '#'
END) 			AS Rating,
			a.ssn as SSN,
			a.name_first as NameFirst,
			a.name_last as NameLast,
			a.dob as DOB,
			a.street as Street1,
			a.unit as Street2,
			a.city as City,
			a.state as State,
			a.zip_code as Zip,
			a.phone_home as PhoneHome,
			a.bank_account AS BankAcct,			
			a.bank_aba as BankABA,
			bi.trace_info AS AppID
		FROM application a
                LEFT JOIN (
			SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
			FROM bureau_inquiry
			JOIN bureau USING (bureau_id)
			WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
                JOIN transaction_register tr ON (a.application_id=tr.application_id)
                JOIN transaction_type tt ON (tr.transaction_type_id=tt.transaction_type_id)
                JOIN (
			SELECT es1.date_effective as date_effective, es1.application_id as application_id, -(sum(es1.amount_principal+es1.amount_non_principal)) as amount
			FROM event_schedule es1
			JOIN (
				SELECT application_id,min(date_effective) as date_effective
				FROM event_schedule WHERE date_effective > (?)
					AND (event_type_id = 2 OR event_type_id = 3 )
				GROUP BY application_id
			) es2 ON (es2.application_id = es1.application_id AND es2.date_effective = es1.date_effective)
			WHERE es1.date_effective > (?)
			AND ( event_type_id = 2 OR event_type_id = 3 ) 
			GROUP BY application_id
                ) es ON (a.application_id=es.application_id)
                LEFT JOIN (
			SELECT ad.application_id,ad.amount_past_due,ad.delinquency_date,ad.status
			FROM (
				SELECT tr.application_id,sum(if(tr.transaction_status != 'failed',tr.amount,-tr.amount)) amount_past_due,min(tr.date_effective) delinquency_date, ast.name status
				FROM transaction_register tr
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es USING (event_schedule_id)
				JOIN application a ON (tr.application_id = a.application_id)
				JOIN application_status ast USING (application_status_id)
				WHERE ast.name NOT IN (
					'Funding Failed', 
					'Withdrawn', 
					'Inactive (Paid)', 
					'Inactive (Recovered)', 
					'Inactive (Internal Recovered)',
					'Cancel',
					'Inactive (Settled)'
				) AND (
					(es.context IN ('generated','manual') AND (tt.name !='ACH Fee Payment') AND tr.transaction_status = 'failed')
					OR ((es.context IN ('reattempt','manual','arrangement','arrange_next') OR (tt.name ='ACH Fee Payment'))  AND tr.transaction_status != 'failed')
					OR (tt.name ='ACH Fee')
				)AND tt.name_short !='converted_principal_bal'
				GROUP BY tr.application_id
				ORDER BY tr.application_id
			) ad 
			WHERE ad.amount_past_due > 0
                ) dt ON (a.application_id=dt.application_id)
                LEFT JOIN (
			SELECT tr.application_id,max(tr.date_effective) last_payment_date 
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tr.transaction_status != 'failed' 
				AND tr.date_effective < (?) 
				AND tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
                ) lp ON (a.application_id=lp.application_id)
                LEFT JOIN (
			SELECT tr.application_id,min(tr.date_effective) first_pay_date,transaction_status first_pay_status
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
			ORDER BY tr.application_id
                ) fpv ON (a.application_id=fpv.application_id)
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as bal ON bal.application_id = a.application_id
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short = 'principal' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as prn ON prn.application_id = a.application_id
		WHERE
			tr.date_effective BETWEEN (?) AND (?)
			AND tt.clearing_type IN ('ach','external')
			AND tr.amount < 0
 				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE name_short =?
				)
            GROUP BY a.application_id";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	
	public function getClarityPaymentsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		
		(CASE 
		WHEN a.application_status_id IN (20,125) THEN '0' -- active, refi, made_arr
		WHEN a.application_status_id IN (132,134,190) THEN '#' -- collections contact, rework
		WHEN a.application_status_id IN (111,112,130,131) THEN '+' -- charged off: 2nd Tier, Bankruptcy
		WHEN a.application_status_id IN (109,113,162,158) THEN '@' -- paid
		WHEN a.application_status_id IN (123,137,138) -- past due, coll new treated as past due
		THEN
		(
		CASE
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) IN (0,1)) THEN '1'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 2 AND 9) THEN (SELECT DATEDIFF(NOW(),a.date_application_status_set))
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 10) THEN 'A'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 11) THEN 'B'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 12) THEN 'C'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 13) THEN 'D'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 14) THEN 'E'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 15) THEN 'F'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 16) THEN 'G'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 17) THEN 'H'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 18) THEN 'I'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 19) THEN 'J'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 20) THEN 'K'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 21) THEN 'L'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 22) THEN 'M'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 23) THEN 'N'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 24) THEN 'P'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 25) THEN 'Q'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 26) THEN 'R'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 27) THEN 'S'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 28) THEN 'T'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 29) THEN 'U'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 30 AND 59) THEN 'V'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 60 AND 89) THEN 'W'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 90 AND 119) THEN 'X'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 120 AND 149) THEN 'Y'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) >= 150) THEN 'Z'
		ELSE 'Z'
		END
		)
		ELSE '#'
		END) AS Rating,

		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		'' AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,

		IFNULL((SELECT 
		ROUND(ABS(SUM(ea1.amount)))
		FROM event_schedule AS es1
		JOIN event_amount AS ea1 ON (ea1.event_schedule_id=es1.event_schedule_id
		AND ea1.event_amount_type_id <> 4
		AND ea1.amount < 0)
		WHERE es1.application_id=a.application_id
		AND es1.event_status='scheduled'
		GROUP BY es1.date_effective
		ORDER BY es1.date_effective ASC LIMIT 1
		),
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed')
		) AS SchedPayment,
		
		(SELECT ROUND(ABS(SUM(ea2.amount)))
		FROM transaction_register AS tr2
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'complete') AS PaymentAmt,

		(SELECT MAX(IF(tr2.transaction_type_id NOT IN (10,11) AND es2.context NOT IN ('manual'), tr2.date_effective, NULL)) 
		FROM event_schedule AS es2
		JOIN transaction_register AS tr2 USING (application_id, event_schedule_id)
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'complete') AS LastDate,
		
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed') AS Balance,
		
		(CASE
		WHEN a.application_status_id IN (20,125,109,113,162,158) THEN '0' -- active,refi, made_arr, 4 paid
		ELSE IFNULL((SELECT ROUND(SUM(ABS(tr1.amount)))
		FROM transaction_register AS tr1
		LEFT JOIN transaction_register AS tr2 ON (tr2.application_id=tr1.application_id
		AND tr2.date_effective > tr1.date_effective
		AND tr2.transaction_status='failed'
		AND tr2.amount < 0
		)
		WHERE tr1.application_id=a.application_id
		AND tr1.transaction_status='failed'
		AND tr1.amount < 0
		AND tr2.transaction_register_id IS NULL
		GROUP BY tr.date_effective
		),
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed')
		) END) AS PastDue,

		(CASE
		WHEN a.application_status_id IN (20,125,109,113,162,158) THEN NULL
		ELSE 
		-- (SELECT MAX(DATE(tr2.date_modified))
                (SELECT MAX(DATE(tr2.date_effective))
		FROM event_schedule AS es2
		JOIN transaction_register AS tr2 USING (application_id, event_schedule_id)
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'failed')
		END) AS PastDate,

		(CASE
		WHEN a.application_status_id IN (19,194) THEN 'DA'
		ELSE ''
		END) AS VoidStatus,

		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		
		IF(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		JOIN transaction_history AS th ON (th.application_id=a.application_id
		AND th.transaction_register_id=tr.transaction_register_id)
		JOIN event_type AS et ON (et.company_id=a.company_id
		AND et.event_type_id=es.event_type_id)
		JOIN transaction_type AS tt ON (tt.company_id=a.company_id
		AND tt.transaction_type_id=tr.transaction_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)
		LEFT JOIN (
		SELECT tr3.application_id, MIN(tr3.date_effective) AS first_pay_date, tr3.transaction_status AS first_pay_status
		FROM transaction_register tr3
		JOIN transaction_type tt3 USING (transaction_type_id) 
		WHERE tt3.clearing_type IN ('ach','card','external')
		AND tr3.amount < 0
		GROUP BY tr3.application_id
		ORDER BY tr3.application_id
		) fpv ON (a.application_id=fpv.application_id)

		WHERE
		a.application_status_id NOT IN (109,113,162,158,194,19) -- inactive (4), canceled, withdrawn
		AND tt.clearing_type IN ('ach','card','external')
		AND tr.transaction_status='complete'
		AND tr.amount < 0
		AND th.status_after='complete'
		AND th.date_created BETWEEN (?) AND (?)
		AND c.name_short = ?
		-- AND a.application_id=901573276
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the list and data for reporting returns.
	 * 
	 * @param string $date
	 * @param string $app_id
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getClarityPaymentsMissedQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
			'' as VoidStatus,
			'C1' as AccountType,
			'P' as PortfolioType,
			a.application_id AS LoanID,
			a.date_fund_actual AS LoanDate,
			es.date_effective AS FirstDate,
			lp.last_payment_date AS LastDate,
			dt.delinquency_date AS PastDate,
			'' AS CloseDate,
			ABS(sum(tr.amount)) AS PaymentAmt,
			if(bal.total_balance>0,bal.total_balance,0) AS Balance,
			if(prn.total_balance>0,prn.total_balance,0) AS Principal,
			es.amount AS SchedPayment,
			dt.amount_past_due AS PastDue,
			if(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad,
			if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
			1 AS Duration,
			(CASE 
WHEN a.application_status_id IN (20,193) THEN '0' -- active, refi
WHEN a.application_status_id IN (132,134,190,192,125) THEN '#' -- collections
WHEN a.application_status_id IN (111,112,130,131,159,160,161) THEN '+' -- charged off
WHEN a.application_status_id IN (109,113,162,158) THEN '@' -- paid
WHEN a.application_status_id IN (194,19,124) THEN '@' -- cancel, withdrawn, funding failed
WHEN a.application_status_id IN (123,137,138) -- past due, coll new treated as past due
THEN
(
CASE
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) IN (0,1)) THEN '1'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 2 AND 9) THEN (SELECT DATEDIFF(NOW(),a.date_application_status_set))
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 10) THEN 'A'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 11) THEN 'B'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 12) THEN 'C'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 13) THEN 'D'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 14) THEN 'E'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 15) THEN 'F'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 16) THEN 'G'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 17) THEN 'H'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 18) THEN 'I'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 19) THEN 'J'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 20) THEN 'K'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 21) THEN 'L'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 22) THEN 'M'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 23) THEN 'N'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 24) THEN 'P'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 25) THEN 'Q'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 26) THEN 'R'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 27) THEN 'S'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 28) THEN 'T'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 29) THEN 'U'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 30 AND 59) THEN 'V'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 60 AND 89) THEN 'W'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 90 AND 119) THEN 'X'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 120 AND 149) THEN 'Y'
WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) >= 150) THEN 'Z'
ELSE 'Z'
END
)
ELSE '#'
END) 			AS Rating,
			'------------------------' AS History,
			a.ssn as SSN,
			a.name_first as NameFirst,
			a.name_last as NameLast,
			a.dob as DOB,
			a.street as Street1,
			a.unit as Street2,
			a.city as City,
			a.state as State,
			a.zip_code as Zip,
			a.phone_home as PhoneHome,
			a.bank_account AS BankAcct,			
			a.bank_aba as BankABA,
			bi.trace_info AS AppID
		FROM application a
                LEFT JOIN (
			SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
			FROM bureau_inquiry
			JOIN bureau USING (bureau_id)
			WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
                JOIN transaction_register tr ON (a.application_id=tr.application_id)
                JOIN transaction_type tt ON (tr.transaction_type_id=tt.transaction_type_id)
                JOIN (
			SELECT es1.date_effective as date_effective, es1.application_id as application_id, -(sum(es1.amount_principal+es1.amount_non_principal)) as amount
			FROM event_schedule es1
			JOIN (
				SELECT application_id,min(date_effective) as date_effective
				FROM event_schedule WHERE date_effective > (?)
					AND (event_type_id = 2 OR event_type_id = 3 )
				GROUP BY application_id
			) es2 ON (es2.application_id = es1.application_id AND es2.date_effective = es1.date_effective)
			WHERE es1.date_effective > (?)
			AND ( event_type_id = 2 OR event_type_id = 3 ) 
			GROUP BY application_id
                ) es ON (a.application_id=es.application_id)
                LEFT JOIN (
			SELECT ad.application_id,ad.amount_past_due,ad.delinquency_date,ad.status
			FROM (
				SELECT tr.application_id,sum(if(tr.transaction_status != 'failed',tr.amount,-tr.amount)) amount_past_due,min(tr.date_effective) delinquency_date, ast.name status
				FROM transaction_register tr
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es USING (event_schedule_id)
				JOIN application a ON (tr.application_id = a.application_id)
				JOIN application_status ast USING (application_status_id)
				WHERE ast.name NOT IN (
					'Funding Failed', 
					'Withdrawn', 
					'Inactive (Paid)', 
					'Inactive (Recovered)', 
					'Inactive (Internal Recovered)',
					'Cancel',
					'Inactive (Settled)'
				) AND (
					(es.context IN ('generated','manual') AND (tt.name !='ACH Fee Payment') AND tr.transaction_status = 'failed')
					OR ((es.context IN ('reattempt','manual','arrangement','arrange_next') OR (tt.name ='ACH Fee Payment'))  AND tr.transaction_status != 'failed')
					OR (tt.name ='ACH Fee')
				)AND tt.name_short !='converted_principal_bal'
				GROUP BY tr.application_id
				ORDER BY tr.application_id
			) ad 
			WHERE ad.amount_past_due > 0
                ) dt ON (a.application_id=dt.application_id)
                LEFT JOIN (
			SELECT tr.application_id,max(tr.date_effective) last_payment_date 
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tr.transaction_status != 'failed' 
				AND tr.date_effective < (?) 
				AND tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
                ) lp ON (a.application_id=lp.application_id)
                LEFT JOIN (
			SELECT tr.application_id,min(tr.date_effective) first_pay_date,transaction_status first_pay_status
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
			ORDER BY tr.application_id
                ) fpv ON (a.application_id=fpv.application_id)
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as bal ON bal.application_id = a.application_id
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short = 'principal' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as prn ON prn.application_id = a.application_id
		WHERE
			tr.date_modified BETWEEN (?) AND (?)
			AND tr.transaction_status ='failed'
			AND tt.clearing_type IN ('ach','external')
			AND tr.amount < 0
 			AND a.company_id = (
				SELECT company_id
				FROM company
				WHERE name_short =?
			)
		GROUP BY a.application_id";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/

	public function getClarityPaymentsMissedQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		
		(CASE 
		WHEN a.application_status_id IN (20,125) THEN '0' -- active, refi, made_arr
		WHEN a.application_status_id IN (132,134,190) THEN '#' -- collections contact, rework
		WHEN a.application_status_id IN (111,112,130,131) THEN '+' -- charged off: 2nd Tier, Bankruptcy
		WHEN a.application_status_id IN (109,113,162,158) THEN '@' -- paid
		WHEN a.application_status_id IN (123,137,138) -- past due, coll new treated as past due
		THEN
		(
		CASE
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) IN (0,1)) THEN '1'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 2 AND 9) THEN (SELECT DATEDIFF(NOW(),a.date_application_status_set))
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 10) THEN 'A'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 11) THEN 'B'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 12) THEN 'C'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 13) THEN 'D'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 14) THEN 'E'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 15) THEN 'F'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 16) THEN 'G'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 17) THEN 'H'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 18) THEN 'I'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 19) THEN 'J'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 20) THEN 'K'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 21) THEN 'L'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 22) THEN 'M'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 23) THEN 'N'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 24) THEN 'P'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 25) THEN 'Q'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 26) THEN 'R'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 27) THEN 'S'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 28) THEN 'T'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) = 29) THEN 'U'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 30 AND 59) THEN 'V'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 60 AND 89) THEN 'W'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 90 AND 119) THEN 'X'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) BETWEEN 120 AND 149) THEN 'Y'
		WHEN (SELECT DATEDIFF(NOW(),a.date_application_status_set) >= 150) THEN 'Z'
		ELSE 'Z'
		END
		)
		ELSE '#'
		END) AS Rating,

		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		'' AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,

		IFNULL((SELECT 
		ROUND(ABS(SUM(ea1.amount)))
		FROM event_schedule AS es1
		JOIN event_amount AS ea1 ON (ea1.event_schedule_id=es1.event_schedule_id
		AND ea1.event_amount_type_id <> 4
		AND ea1.amount < 0)
		WHERE es1.application_id=a.application_id
		AND es1.event_status='scheduled'
		GROUP BY es1.date_effective
		ORDER BY es1.date_effective ASC LIMIT 1
		),
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed')
		) AS SchedPayment,
		
		(SELECT ROUND(ABS(SUM(ea2.amount)))
		FROM transaction_register AS tr2
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'complete') AS PaymentAmt,

		(SELECT MAX(IF(tr2.transaction_type_id NOT IN (10,11) AND es2.context NOT IN ('manual'), tr2.date_effective, NULL)) 
		FROM event_schedule AS es2
		JOIN transaction_register AS tr2 USING (application_id, event_schedule_id)
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'complete') AS LastDate,
		
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed') AS Balance,
		
		(CASE
		WHEN a.application_status_id IN (20,125,109,113,162,158) THEN '0' -- active,refi, made_arr, 4 paid
		ELSE IFNULL((SELECT ROUND(SUM(ABS(tr1.amount)))
		FROM transaction_register AS tr1
		LEFT JOIN transaction_register AS tr2 ON (tr2.application_id=tr1.application_id
		AND tr2.date_effective > tr1.date_effective
		AND tr2.transaction_status='failed'
		AND tr2.amount < 0
		)
		WHERE tr1.application_id=a.application_id
		AND tr1.transaction_status='failed'
		AND tr1.amount < 0
		AND tr2.transaction_register_id IS NULL
		GROUP BY tr.date_effective
		),
		(SELECT ROUND(ABS(SUM(ea1.amount)))
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = a.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed')
		) END) AS PastDue,

		(CASE
		WHEN a.application_status_id IN (20,125,109,113,162,158) THEN NULL
		ELSE 
		-- (SELECT MAX(DATE(tr2.date_modified))
                (SELECT MAX(DATE(tr2.date_effective))
		FROM event_schedule AS es2
		JOIN transaction_register AS tr2 USING (application_id, event_schedule_id)
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'failed')
		END) AS PastDate,

		(CASE
		WHEN a.application_status_id IN (19,194) THEN 'DA'
		ELSE ''
		END) AS VoidStatus,

		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		
		IF(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		JOIN transaction_history AS th ON (th.application_id=a.application_id
		AND th.transaction_register_id=tr.transaction_register_id)
		JOIN event_type AS et ON (et.company_id=a.company_id
		AND et.event_type_id=es.event_type_id)
		JOIN transaction_type AS tt ON (tt.company_id=a.company_id
		AND tt.transaction_type_id=tr.transaction_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)
		LEFT JOIN (
		SELECT tr3.application_id, MIN(tr3.date_effective) AS first_pay_date, tr3.transaction_status AS first_pay_status
		FROM transaction_register tr3
		JOIN transaction_type tt3 USING (transaction_type_id) 
		WHERE tt3.clearing_type IN ('ach','card','external')
		AND tr3.amount < 0
		GROUP BY tr3.application_id
		ORDER BY tr3.application_id
		) fpv ON (a.application_id=fpv.application_id)

		WHERE
		tt.clearing_type IN ('ach','card','external')
		AND tr.transaction_status='failed'
		AND tr.amount < 0
		AND th.status_after='failed'
		AND th.date_created BETWEEN (?) AND (?)
		AND c.name_short = ?
		-- AND a.application_id=901573276
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}

	/**
	 * Returns a query that will provide the list and data for reporting old paid off loans.
	 * 
	 * @param string $date
	 * @param string $app_id
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getClarityPaidInFullQuery($duration, $date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
			'' as VoidStatus,
			'C1' as AccountType,
			'P' as PortfolioType,
			a.application_id AS LoanID,
			a.date_fund_actual AS LoanDate,
			es.date_effective AS FirstDate,
			lp.last_payment_date AS LastDate,
			dt.delinquency_date AS PastDate,
			GREATEST(a.date_application_status_set,tr.date_modified) AS CloseDate,
			ABS(sum(tr.amount)) AS PaymentAmt,
			if(bal.total_balance>0,bal.total_balance,0) AS Balance,
			if(prn.total_balance>0,prn.total_balance,0) AS Principal,
			es.amount AS SchedPayment,
			dt.amount_past_due AS PastDue,
			if(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad,
			if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
			1 AS Duration,
			'@' AS Rating,
			a.ssn as SSN,
			a.name_first as NameFirst,
			a.name_last as NameLast,
			a.dob as DOB,
			a.street as Street1,
			a.unit as Street2,
			a.city as City,
			a.state as State,
			a.zip_code as Zip,
			a.phone_home as PhoneHome,
			a.bank_account AS BankAcct,			
			a.bank_aba as BankABA,
			bi.trace_info AS AppID
		FROM application a
                LEFT JOIN (
			SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
			FROM bureau_inquiry
			JOIN bureau USING (bureau_id)
			WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
                JOIN transaction_register tr ON (a.application_id=tr.application_id)
                JOIN transaction_type tt ON (tr.transaction_type_id=tt.transaction_type_id)
                JOIN (
			SELECT es1.date_effective as date_effective, es1.application_id as application_id, -(sum(es1.amount_principal+es1.amount_non_principal)) as amount
			FROM event_schedule es1
			JOIN (
				SELECT application_id,min(date_effective) as date_effective
				FROM event_schedule WHERE date_effective > (?)
					AND (event_type_id = 2 OR event_type_id = 3 )
				GROUP BY application_id
			) es2 ON (es2.application_id = es1.application_id AND es2.date_effective = es1.date_effective)
			WHERE es1.date_effective > (?)
			AND ( event_type_id = 2 OR event_type_id = 3 ) 
			GROUP BY application_id
                ) es ON (a.application_id=es.application_id)
                LEFT JOIN (
			SELECT ad.application_id,ad.amount_past_due,ad.delinquency_date,ad.status
			FROM (
				SELECT tr.application_id,sum(if(tr.transaction_status != 'failed',tr.amount,-tr.amount)) amount_past_due,min(tr.date_effective) delinquency_date, ast.name status
				FROM transaction_register tr
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es USING (event_schedule_id)
				JOIN application a ON (tr.application_id = a.application_id)
				JOIN application_status ast USING (application_status_id)
				WHERE ast.name NOT IN (
					'Funding Failed', 
					'Withdrawn', 
					'Inactive (Paid)', 
					'Inactive (Recovered)', 
					'Inactive (Internal Recovered)',
					'Cancel',
					'Inactive (Settled)'
				) AND (
					(es.context IN ('generated','manual') AND (tt.name !='ACH Fee Payment') AND tr.transaction_status = 'failed')
					OR ((es.context IN ('reattempt','manual','arrangement','arrange_next') OR (tt.name ='ACH Fee Payment'))  AND tr.transaction_status != 'failed')
					OR (tt.name ='ACH Fee')
				)AND tt.name_short !='converted_principal_bal'
				GROUP BY tr.application_id
				ORDER BY tr.application_id
			) ad 
			WHERE ad.amount_past_due > 0
                ) dt ON (a.application_id=dt.application_id)
                LEFT JOIN (
			SELECT tr.application_id,max(tr.date_effective) last_payment_date 
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tr.transaction_status != 'failed' 
				AND tr.date_effective < (?) 
				AND tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
                ) lp ON (a.application_id=lp.application_id)
                LEFT JOIN (
			SELECT tr.application_id,min(tr.date_effective) first_pay_date,transaction_status first_pay_status
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
			ORDER BY tr.application_id
                ) fpv ON (a.application_id=fpv.application_id)
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as bal ON bal.application_id = a.application_id
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short = 'principal' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as prn ON prn.application_id = a.application_id
		WHERE
			tr.date_modified BETWEEN (?) AND (?)
			AND bal.total_balance <= 0
			AND tt.clearing_type IN ('ach','external')
			AND tr.amount < 0
 			AND a.company_id = (
				SELECT company_id
				FROM company
				WHERE name_short =?
			)
		GROUP BY a.application_id";

		$args = array(
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 23:59:59',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 23:59:59',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 23:59:59',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 23:59:59',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 00:00:00',
			date('Y-m-d',strtotime($date)-(60*60*24*$duration)) . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	
	public function getClarityPaidInFullQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		'@' AS Rating,
		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		DATE(a.date_application_status_set) AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,
		'' AS SchedPayment,
		ROUND(SUM(IF(tr.amount<0 AND tr.transaction_status='complete', ABS(tr.amount), 0))) AS PaymentAmt,
		DATE(a.date_application_status_set) AS LastDate,
		-- ROUND(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status<>'failed', ea.amount, 0))) AS Balance,
		0 AS Balance,
		0 AS PastDue,
		'' AS PastDate,
		'' AS VoidStatus,
		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		IF(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)
		LEFT JOIN (
		SELECT tr3.application_id, MIN(tr3.date_effective) AS first_pay_date, tr3.transaction_status AS first_pay_status
		FROM transaction_register tr3
		JOIN transaction_type tt3 USING (transaction_type_id) 
		WHERE tt3.clearing_type IN ('ach','card','external')
		AND tr3.amount < 0
		GROUP BY tr3.application_id
		ORDER BY tr3.application_id
		) fpv ON (a.application_id=fpv.application_id)

		WHERE
		a.application_status_id IN (109,113,162,158)
		AND a.date_application_status_set BETWEEN (?) AND (?)
		AND c.name_short = ?
		-- AND a.application_id=901700745
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the list and data for reporting voided loans.
	 * 
	 * @param string $date
	 * @param string $app_id
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getClarityVoidsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
			'DA' as VoidStatus,
			'C1' as AccountType,
			'P' as PortfolioType,
			a.application_id AS LoanID,
			a.date_fund_actual AS LoanDate,
			'' AS FirstDate,
			'' AS LastDate,
			'' AS PastDate,
			a.date_application_status_set AS CloseDate,
			0 AS PaymentAmt,
			0 AS Balance,
			0 AS Principal,
			0 AS SchedPayment,
			'' AS PastDue,
			'false' AS FirstPaymentBad,
			if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
			1 AS Duration,
			'@' AS Rating,
			'------------------------' AS History,
			a.ssn as SSN,
			a.name_first as NameFirst,
			a.name_last as NameLast,
			a.dob as DOB,
			a.street as Street1,
			a.unit as Street2,
			a.city as City,
			a.state as State,
			a.zip_code as Zip,
			a.phone_home as PhoneHome,
			a.bank_account AS BankAcct,			
			a.bank_aba as BankABA,
			bi.trace_info AS AppID
		FROM application a
                LEFT JOIN (
			SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
			FROM bureau_inquiry
			JOIN bureau USING (bureau_id)
			WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
		JOIN application_status asp ON asp.application_status_id = a.application_status_id
		JOIN status_history asq ON a.application_id=asq.application_id
		JOIN application_status ast ON ast.application_status_id = asq.application_status_id
		WHERE ast.name_short = 'approved'
			AND (asp.name_short IN ('withdrawn','canceled'))
			AND a.date_application_status_set BETWEEN (?) AND (?)
 			AND a.company_id = (
				SELECT company_id
				FROM company
				WHERE name_short =?
			)
		GROUP BY a.application_id";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	
	public function getClarityVoidsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		'0' AS Rating,
		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		'' AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,
		'' AS SchedPayment,
		ROUND(SUM(IF(tr.amount<0 AND tr.transaction_status='complete', ABS(tr.amount), 0))) AS PaymentAmt,
		MAX(IF(tr.amount<0 AND tr.transaction_status='complete' AND tr.transaction_type_id NOT IN (10,11) AND es.context NOT IN ('manual'), tr.date_effective, NULL)) AS LastDate,
		ROUND(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status<>'failed', ea.amount, 0))) AS Balance,
		0 AS PastDue,
		'' AS PastDate,
		'DA' AS VoidStatus,
		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		IF(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)
		LEFT JOIN (
		SELECT tr3.application_id, MIN(tr3.date_effective) AS first_pay_date, tr3.transaction_status AS first_pay_status
		FROM transaction_register tr3
		JOIN transaction_type tt3 USING (transaction_type_id) 
		WHERE tt3.clearing_type IN ('ach','card','external')
		AND tr3.amount < 0
		GROUP BY tr3.application_id
		ORDER BY tr3.application_id
		) fpv ON (a.application_id=fpv.application_id)

		WHERE
		a.application_status_id IN (19,194)
		AND a.date_application_status_set BETWEEN (?) AND (?)
		AND c.name_short = ?
		-- AND a.application_id=901700745
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}
	
	/**
	 * Returns a query that will provide the list and data for reporting returns.
	 * 
	 * @param string $date
	 * @param string $app_id
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	/*
	public function getClarityWriteOffsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
			'' as VoidStatus,
			'C1' as AccountType,
			'P' as PortfolioType,
			a.application_id AS LoanID,
			a.date_fund_actual AS LoanDate,
			es.date_effective AS FirstDate,
			lp.last_payment_date AS LastDate,
			dt.delinquency_date AS PastDate,
			a.date_application_status_set AS CloseDate,
			ABS(sum(tr.amount)) AS PaymentAmt,
			if(bal.total_balance>0,bal.total_balance,0) AS Balance,
			if(prn.total_balance>0,prn.total_balance,0) AS Principal,
			es.amount AS SchedPayment,
			dt.amount_past_due AS PastDue,
			if(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad,
			if (a.income_frequency = '','bi_weekly',a.income_frequency) AS Frequency,
			1 AS Duration,
			'+' AS Rating,
			'------------------------' AS History,
			a.ssn as SSN,
			a.name_first as NameFirst,
			a.name_last as NameLast,
			a.dob as DOB,
			a.street as Street1,
			a.unit as Street2,
			a.city as City,
			a.state as State,
			a.zip_code as Zip,
			a.phone_home as PhoneHome,
			a.bank_account AS BankAcct,			
			a.bank_aba as BankABA,
			bi.trace_info AS AppID
		FROM application a
                LEFT JOIN (
			SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
			FROM bureau_inquiry
			JOIN bureau USING (bureau_id)
			WHERE bureau.name_short = 'clarity'
                ) bi ON (a.application_id=bi.application_id)
                JOIN transaction_register tr ON (a.application_id=tr.application_id)
                JOIN transaction_type tt ON (tr.transaction_type_id=tt.transaction_type_id)
                JOIN (
			SELECT es1.date_effective as date_effective, es1.application_id as application_id, -(sum(es1.amount_principal+es1.amount_non_principal)) as amount
			FROM event_schedule es1
			JOIN (
				SELECT application_id,min(date_effective) as date_effective
				FROM event_schedule WHERE date_effective > (?)
					AND (event_type_id = 2 OR event_type_id = 3 )
				GROUP BY application_id
			) es2 ON (es2.application_id = es1.application_id AND es2.date_effective = es1.date_effective)
			WHERE es1.date_effective > (?)
			AND ( event_type_id = 2 OR event_type_id = 3 ) 
			GROUP BY application_id
                ) es ON (a.application_id=es.application_id)
                LEFT JOIN (
			SELECT ad.application_id,ad.amount_past_due,ad.delinquency_date,ad.status
			FROM (
				SELECT tr.application_id,sum(if(tr.transaction_status != 'failed',tr.amount,-tr.amount)) amount_past_due,min(tr.date_effective) delinquency_date, ast.name status
				FROM transaction_register tr
				JOIN transaction_type tt USING (transaction_type_id)
				JOIN event_schedule es USING (event_schedule_id)
				JOIN application a ON (tr.application_id = a.application_id)
				JOIN application_status ast USING (application_status_id)
				WHERE ast.name NOT IN (
					'Funding Failed', 
					'Withdrawn', 
					'Inactive (Paid)', 
					'Inactive (Recovered)', 
					'Inactive (Internal Recovered)',
					'Cancel',
					'Inactive (Settled)'
				) AND (
					(es.context IN ('generated','manual') AND (tt.name !='ACH Fee Payment') AND tr.transaction_status = 'failed')
					OR ((es.context IN ('reattempt','manual','arrangement','arrange_next') OR (tt.name ='ACH Fee Payment'))  AND tr.transaction_status != 'failed')
					OR (tt.name ='ACH Fee')
				)AND tt.name_short !='converted_principal_bal'
				GROUP BY tr.application_id
				ORDER BY tr.application_id
			) ad 
			WHERE ad.amount_past_due > 0
                ) dt ON (a.application_id=dt.application_id)
                LEFT JOIN (
			SELECT tr.application_id,max(tr.date_effective) last_payment_date 
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tr.transaction_status != 'failed' 
				AND tr.date_effective < (?) 
				AND tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
                ) lp ON (a.application_id=lp.application_id)
                LEFT JOIN (
			SELECT tr.application_id,min(tr.date_effective) first_pay_date,transaction_status first_pay_status
			FROM transaction_register tr 
			JOIN transaction_type tt USING (transaction_type_id) 
			WHERE tt.clearing_type IN ('ach','external')
				AND tr.amount < 0
			GROUP BY tr.application_id
			ORDER BY tr.application_id
                ) fpv ON (a.application_id=fpv.application_id)
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
			JOIN event_amount_type eat USING (event_amount_type_id)
			JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as bal ON bal.application_id = a.application_id
                LEFT JOIN 
                (
			SELECT
				tr.application_id, SUM( IF( eat.name_short = 'principal' AND tr.transaction_status != 'failed', ea.amount, 0)) total_balance
			FROM event_amount ea
                        JOIN event_amount_type eat USING (event_amount_type_id)
                        JOIN transaction_register tr USING(transaction_register_id)
			WHERE tr.date_effective < (?) 
			GROUP BY tr.application_id
                ) as prn ON prn.application_id = a.application_id
		JOIN application_status asp ON asp.application_status_id = a.application_status_id
		WHERE asp.name = 'Second Tier (Sent)'
			AND a.date_application_status_set BETWEEN (?) AND (?)
 			AND a.company_id = (
				SELECT company_id
				FROM company
				WHERE name_short =?
			)
		GROUP BY a.application_id";

		$args = array(
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$date . ' 23:59:59',
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	*/
	
	public function getClarityWriteOffsQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		a.name_first as NameFirst,
		a.name_last as NameLast,
		a.dob as DOB,
		a.ssn as SSN,
		a.phone_home as PhoneHome,
		a.street as Street1,
		a.unit as Street2,
		a.city as City,
		a.state as State,
		a.zip_code as Zip,
		'P' as PortfolioType,
		'C1' as AccountType,
		(CASE 
		WHEN a.income_frequency = 'weekly' THEN 'B'
		WHEN a.income_frequency = 'bi_weekly' THEN 'B'
		WHEN a.income_frequency = 'twice_monthly' THEN 'E'
		WHEN a.income_frequency = 'monthly' THEN 'M'
		ELSE 'B'
		END) AS Frequency,
		1 AS Duration,
		'+' AS Rating,
		-- a.date_application_status_set AS account-information-date,
		a.date_fund_actual AS LoanDate,
		DATE(a.date_application_status_set) AS CloseDate,
		IFNULL(a.fund_actual,0) AS Principal,
		'' AS SchedPayment,
		ROUND(SUM(IF(tr.amount<0 AND tr.transaction_status='complete', ABS(tr.amount), 0))) AS PaymentAmt,
		MAX(IF(tr.amount<0 AND tr.transaction_status='complete' AND tr.transaction_type_id NOT IN (10,11) AND es.context NOT IN ('manual'), tr.date_effective, NULL)) AS LastDate,
		ROUND(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status<>'failed', ea.amount, 0))) AS Balance,
		
		IFNULL((SELECT ROUND(SUM(ABS(tr1.amount)))
		FROM transaction_register AS tr1
		LEFT JOIN transaction_register AS tr2 ON (tr2.application_id=tr1.application_id
		AND tr2.date_effective > tr1.date_effective
		AND tr2.transaction_status='failed'
		AND tr2.amount < 0
		)
		WHERE tr1.application_id=a.application_id
		AND tr1.transaction_status='failed'
		AND tr1.amount < 0
		AND tr2.transaction_register_id IS NULL
		GROUP BY tr.date_effective
		),
		ROUND(SUM(IF(eat.name_short <> 'irrecoverable' AND tr.transaction_status='complete', ea.amount, 0)))
		) AS PastDue,

		-- (SELECT MAX(DATE(tr2.date_modified))
                (SELECT MAX(DATE(tr2.date_effective))
		FROM event_schedule AS es2
		JOIN transaction_register AS tr2 USING (application_id, event_schedule_id)
		JOIN event_amount ea2 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat2 USING (event_amount_type_id)
		WHERE ea2.application_id = a.application_id
		AND tr2.amount < 0
		AND eat2.name_short <> 'irrecoverable'
		AND tr2.transaction_status = 'failed') AS PastDate,
		
		'' AS VoidStatus,
		a.bank_account AS BankAcct,			
		a.bank_aba as BankABA,
		a.application_id AS LoanID,
		-- NULL AS change_indicator,
		-- NULL AS consumer_account_number_old,
		bi.trace_info AS AppID,
		a.date_first_payment AS FirstDate,
		IF(fpv.first_pay_status = 'failed','true','false') AS FirstPaymentBad

		FROM application AS a
		JOIN company AS c ON (c.company_id = a.company_id)
		JOIN event_schedule AS es ON (es.application_id=a.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=a.application_id
		AND tr.event_schedule_id=es.event_schedule_id)
		JOIN event_amount ea ON (ea.application_id=a.application_id
		AND ea.event_schedule_id=es.event_schedule_id
		AND ea.transaction_register_id=tr.transaction_register_id)
		JOIN event_amount_type eat ON (eat.event_amount_type_id=ea.event_amount_type_id)
		LEFT JOIN (
		SELECT bureau_inquiry.trace_info, bureau_inquiry.application_id
		FROM bureau_inquiry
		JOIN bureau USING (bureau_id)
		WHERE bureau.name_short = 'clarity'
		) AS bi ON (a.application_id=bi.application_id)
		LEFT JOIN (
		SELECT tr3.application_id, MIN(tr3.date_effective) AS first_pay_date, tr3.transaction_status AS first_pay_status
		FROM transaction_register tr3
		JOIN transaction_type tt3 USING (transaction_type_id) 
		WHERE tt3.clearing_type IN ('ach','card','external')
		AND tr3.amount < 0
		GROUP BY tr3.application_id
		ORDER BY tr3.application_id
		) fpv ON (a.application_id=fpv.application_id)

		WHERE
		a.application_status_id IN (111,130,131) -- 2nd Tier Pending, Bankruptcy Notification, Bankruptcy Verified
		AND a.date_application_status_set BETWEEN (?) AND (?)
		AND c.name_short = ?
		-- AND a.application_id=901700745
		GROUP BY a.ssn
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company);

		return $query;
	}
	
	///////////////////////////////////////////////////////DATAX CLH
	//fund_update
	public function getFundUpdateCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'fund_update' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		'INTERNET' AS CHANNEL,
		'O' AS TRADELINETYPE,
		'15' AS TRADELINETYPECODE,
		ROUND(ap.apr, 2) AS APR,
		IF(ap.income_frequency='WEEKLY','BI_WEEKLY',UPPER(ap.income_frequency)) AS PAYMENTFREQUENCY,

		(SELECT FORMAT(amount,2)
		FROM transaction_register
		WHERE application_id=ap.application_id
		AND transaction_type_id=4
		ORDER BY transaction_register_id ASC
		LIMIT 1) AS PAYMENTAMOUNT,

		ap.date_fund_actual AS fund_date,
		ROUND(ap.fund_actual,2) AS fund_amount,

		(SELECT FORMAT(amount,2)
		FROM transaction_register
		WHERE application_id=ap.application_id
		AND transaction_type_id=4
		ORDER BY transaction_register_id ASC
		LIMIT 1) AS fee_amount,

		ap.date_first_payment

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN status_history AS sh ON (sh.application_id=ap.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
		LEFT JOIN status_history sh1 ON (sh1.application_id=sh.application_id
		AND sh1.application_status_id = sh.application_status_id
		AND sh1.date_created < sh.date_created)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		AND sh.application_status_id IN (20)
		AND tr.transaction_type_id = 1
		AND sh1.status_history_id IS NULL
		AND sh.date_created BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	///////////active
	public function getActiveCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'active' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		'INTERNET' AS CHANNEL,
		'O' AS TRADELINETYPE,
		'15' AS TRADELINETYPECODE,
		ROUND(ap.apr, 2) AS APR,
		IF(ap.income_frequency='WEEKLY','BI_WEEKLY',UPPER(ap.income_frequency)) AS PAYMENTFREQUENCY,

		(SELECT FORMAT(amount,2)
		FROM transaction_register
		WHERE application_id=ap.application_id
		AND transaction_type_id=4
		ORDER BY transaction_register_id ASC
		LIMIT 1) AS PAYMENTAMOUNT,

		ap.date_fund_actual AS fund_date,
		ROUND(ap.fund_actual,2) AS fund_amount,

		(SELECT FORMAT(amount,2)
		FROM transaction_register
		WHERE application_id=ap.application_id
		AND transaction_type_id=4
		ORDER BY transaction_register_id ASC
		LIMIT 1) AS fee_amount,

		ap.date_first_payment

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
		JOIN status_history AS sh ON (sh.application_id=ap.application_id)
		LEFT JOIN status_history sh1 ON (sh1.application_id=sh.application_id
		AND sh1.application_status_id = sh.application_status_id
		AND sh1.date_created < sh.date_created)
		LEFT JOIN status_history AS sh2 ON (sh2.application_id=ap.application_id
		AND sh2.application_status_id IN (194,19)
		AND sh2.date_created > tr.date_created)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		AND sh.application_status_id IN (20)
		AND tr.transaction_type_id = 1
		AND tr.transaction_status = 'complete'
		AND sh1.status_history_id IS NULL
		AND sh2.status_history_id IS NULL
		AND tr.date_modified BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	//cancel
	public function getCancelCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'cancel' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		DATE(sh.date_created) AS CANCELLEDDATE

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)

		JOIN status_history AS sh ON (sh.application_id=ap.application_id
		AND sh.application_status_id IN (19,194)
		)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id
		AND tr.transaction_type_id = 1
		AND tr.date_created < sh.date_created
		)
		LEFT JOIN status_history AS sh1 ON (sh1.application_id=sh.application_id
		AND sh1.application_status_id IN (19,194)
		AND sh1.date_created > sh.date_created
		)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		AND sh1.status_history_id IS NULL
		AND sh.date_created BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	//paid_off
	public function getPaidOffCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'paid_off' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		DATE(sh.date_created) AS PAIDOFFDATE

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN status_history AS sh ON (sh.application_id=ap.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		AND sh.application_status_id IN (109)
		AND tr.transaction_type_id = 1
		AND sh.date_created BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	//chargeoff
	public function getChargeOffCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'chargeoff' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		DATE(sh.date_created) AS CHARGEDOFFDATE,
		
		(SELECT SUM(ea1.amount)
		FROM transaction_register AS tr1
		JOIN event_amount ea1 USING (application_id, event_schedule_id, transaction_register_id)
		JOIN event_amount_type eat1 USING (event_amount_type_id)
		WHERE ea1.application_id = ap.application_id
		AND eat1.name_short <> 'irrecoverable'
		AND tr1.transaction_status <> 'failed') AS balance

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN status_history AS sh ON (sh.application_id=ap.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		AND sh.application_status_id IN (112,131)
		AND tr.transaction_type_id = 1
		AND sh.date_created BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	//recovery
	public function getRecoveryCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT DISTINCT
		'recovery' AS TYPE,
		NULL AS INQUIRYID,
		ap.application_id,
		UPPER(ap.name_first) AS NAMEFIRST,
		NULL AS NAMEMIDDLE,
		UPPER(ap.name_last) AS NAMELAST,
		UPPER(ap.street) AS STREET1,
		ap.unit AS STREET2,
		UPPER(ap.city) AS CITY,
		ap.state AS STATE,
		ap.zip_code AS ZIP,
		ap.phone_home AS PHONEHOME,
		ap.phone_cell AS PHONECELL,
		ap.phone_work AS PHONEWORK,
		ap.phone_work_ext AS PHONEEXT,
		UPPER(ap.email) AS EMAIL,
		ap.dob AS DOB,
		ap.ssn AS SSN,
		ap.legal_id_number AS DRIVERLICENSENUMBER,
		UPPER(ap.legal_id_state) AS DRIVERLICENSESTATE,
		UPPER(ap.employer_name) AS WORKNAME,
		ap.income_monthly AS MONTHLYINCOME,
		UPPER(ap.work_address_1) AS WORKSTREET1,
		ap.work_address_2 AS WORKSTREET2,
		UPPER(ap.work_city) AS WORKCITY,
		UPPER(ap.work_state) AS WORKSTATE,
		ap.work_zip_code AS WORKZIP,
		UPPER(ap.bank_name) AS BANKNAME,
		ap.bank_aba AS BANKABA,
		ap.bank_account AS BANKACCTNUMBER,
		UPPER(ap.income_frequency) AS PAYPERIOD,
		IF(ap.income_direct_deposit = 'yes', 'Y', 'N') AS DIRECTDEPOSIT,
		ap.income_monthly AS MONTHLYINCOME,

		-- UPDATE
		DATE(tr.date_modified) AS RECOVEREDCHARGEDOFFDATE,
		ABS(SUM(tr.amount)) AS RECOVEREDCHARGEDOFFAMOUNT

		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN status_history AS sh ON (sh.application_id=ap.application_id)
		JOIN transaction_register AS tr ON (tr.application_id=ap.application_id)
		WHERE
		bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND b.name_short='datax'
		-- AND sh.application_status_id IN (112)
		AND tr.transaction_type_id IN (20,21) -- Second Tier Recovery
		AND tr.date_modified BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY tr.date_effective
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
	
	//payment
	public function getPaymentCLHQuery($date, $company, array &$args)
	{
		$query = "
		SELECT * FROM
		(
		-- ach, external
		SELECT DISTINCT
		ap.application_id AS application_id,
		tr.transaction_register_id AS transaction_register_id,
		IF(tr.amount > 0,'CREDIT','DEBIT') AS TYPE,
		(CASE
		WHEN et.name_short IN ('personal_check') THEN 'CHECK'
		WHEN tt.clearing_type = 'ach' THEN 'ACH'
		WHEN tt.clearing_type = 'card' THEN 'CARD1'
		WHEN et.name_short IN ('credit_card') THEN 'CARD2'
		WHEN et.name_short IN ('money_order') THEN 'Money Order'
		WHEN et.name_short IN ('moneygram') THEN 'Moneygram'
		WHEN et.name_short IN ('western_union') THEN 'Western Union'
		WHEN et.name_short IN ('payment_debt') THEN 'Debt Consolidation'
		WHEN et.name_short IN ('payment_manual') THEN 'Manual Payment'
		ELSE 'Manual Payment'
		END) AS METHOD,
		tr.date_effective AS PAYMENTDATE,
		ABS(SUM(tr.amount)) AS AMOUNT,
		NULL AS RETURNCODE,
		UPPER(IF(tt.clearing_type='ach',ap.bank_name,NULL)) AS BANKNAME,
		IF(tt.clearing_type='ach',ap.bank_aba,NULL) AS BANKABA,
		IF(tt.clearing_type='ach',ap.bank_account,NULL) AS BANKACCTNUMBER
		
		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN event_schedule AS es ON (es.company_id=ap.company_id AND es.application_id=ap.application_id)
		JOIN event_type AS et ON (et.company_id=es.company_id AND et.event_type_id=es.event_type_id)
		JOIN transaction_register AS tr ON (tr.company_id=es.company_id AND tr.application_id=es.application_id AND tr.event_schedule_id=es.event_schedule_id)
		JOIN transaction_type AS tt ON (tt.company_id=tr.company_id AND tt.transaction_type_id=tr.transaction_type_id)
		
		WHERE
		b.name_short='datax'
		AND bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND tt.clearing_type IN ('ach','external')
		AND tr.transaction_status IN ('pending')
		AND tr.date_modified BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY tr.application_id,tr.date_effective
		
		-- card
		UNION ALL
		
		SELECT DISTINCT
		ap.application_id AS application_id,
		tr.transaction_register_id AS transaction_register_id,
		IF(tr.amount > 0,'CREDIT','DEBIT') AS TYPE,
		(CASE
		WHEN et.name_short IN ('personal_check') THEN 'CHECK'
		WHEN tt.clearing_type = 'ach' THEN 'ACH'
		WHEN tt.clearing_type = 'card' THEN 'CARD1'
		WHEN et.name_short IN ('credit_card') THEN 'CARD2'
		WHEN et.name_short IN ('money_order') THEN 'Money Order'
		WHEN et.name_short IN ('moneygram') THEN 'Moneygram'
		WHEN et.name_short IN ('western_union') THEN 'Western Union'
		WHEN et.name_short IN ('payment_debt') THEN 'Debt Consolidation'
		WHEN et.name_short IN ('payment_manual') THEN 'Manual Payment'
		ELSE 'Manual Payment'
		END) AS METHOD,
		tr.date_effective AS PAYMENTDATE,
		ABS(SUM(tr.amount)) AS AMOUNT,
		NULL AS RETURNCODE,
		UPPER(IF(tt.clearing_type='ach',ap.bank_name,NULL)) AS BANKNAME,
		IF(tt.clearing_type='ach',ap.bank_aba,NULL) AS BANKABA,
		IF(tt.clearing_type='ach',ap.bank_account,NULL) AS BANKACCTNUMBER
		
		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN event_schedule AS es ON (es.company_id=ap.company_id AND es.application_id=ap.application_id)
		JOIN event_type AS et ON (et.company_id=es.company_id AND et.event_type_id=es.event_type_id)
		JOIN transaction_register AS tr ON (tr.company_id=es.company_id AND tr.application_id=es.application_id AND tr.event_schedule_id=es.event_schedule_id)
		JOIN transaction_type AS tt ON (tt.company_id=tr.company_id AND tt.transaction_type_id=tr.transaction_type_id)
		
		WHERE
		b.name_short='datax'
		AND bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND tt.clearing_type IN ('card')
		AND tr.date_modified BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY tr.application_id,tr.date_effective
		
		UNION ALL
		
		SELECT DISTINCT
		ap.application_id AS application_id,
		tr.transaction_register_id AS transaction_register_id,
		IF(tr.amount > 0,'CREDIT','DEBIT') AS TYPE,
		(CASE
		WHEN et.name_short IN ('personal_check') THEN 'CHECK'
		WHEN tt.clearing_type = 'ach' THEN 'ACH'
		WHEN tt.clearing_type = 'card' THEN 'CARD1'
		WHEN et.name_short IN ('credit_card') THEN 'CARD2'
		WHEN et.name_short IN ('money_order') THEN 'Money Order'
		WHEN et.name_short IN ('moneygram') THEN 'Moneygram'
		WHEN et.name_short IN ('western_union') THEN 'Western Union'
		WHEN et.name_short IN ('payment_debt') THEN 'Debt Consolidation'
		WHEN et.name_short IN ('payment_manual') THEN 'Manual Payment'
		ELSE 'OTHER'
		END) AS METHOD,
		tr.date_effective AS PAYMENTDATE,
		ABS(SUM(tr.amount)) AS AMOUNT,
		IFNULL(arc.name_short, 'R') AS RETURNCODE,
		UPPER(IF(tt.clearing_type='ach',ap.bank_name,NULL)) AS BANKNAME,
		IF(tt.clearing_type='ach',ap.bank_aba,NULL) AS BANKABA,
		IF(tt.clearing_type='ach',ap.bank_account,NULL) AS BANKACCTNUMBER
		
		FROM application AS ap
		JOIN bureau_inquiry AS bi ON (bi.application_id=ap.application_id)
		JOIN bureau AS b ON (b.bureau_id=bi.bureau_id)
		JOIN event_schedule AS es ON (es.company_id=ap.company_id AND es.application_id=ap.application_id)
		JOIN event_type AS et ON (et.company_id=es.company_id AND et.event_type_id=es.event_type_id)
		JOIN transaction_register AS tr ON (tr.company_id=es.company_id AND tr.application_id=es.application_id AND tr.event_schedule_id=es.event_schedule_id)
		JOIN transaction_type AS tt ON (tt.company_id=tr.company_id AND tt.transaction_type_id=tr.transaction_type_id)
		LEFT JOIN ach ON (ach.ach_id=tr.ach_id)
		LEFT JOIN ach_return_code AS arc ON (arc.ach_return_code_id=ach.ach_return_code_id)
		LEFT JOIN card_process AS cp ON (cp.card_process_id=tr.card_process_id)
		LEFT JOIN card_process_response AS cpr ON (cpr.reason_code=cp.reason_code)
		
		WHERE
		b.name_short='datax'
		AND bi.inquiry_type IN ('CLH-1A','CLH-1B','CLH-1C','CLH-CR')
		AND tt.clearing_type IN ('ach','card','external')
		AND tr.transaction_status='failed'
		AND tr.date_modified BETWEEN (?) AND (?)
		AND ap.company_id = (
		SELECT company_id
		FROM company
		WHERE
		name_short = ?
		)
		GROUP BY tr.application_id,tr.date_effective
		) AS result
		ORDER BY application_id,transaction_register_id,RETURNCODE
		";

		$args = array(
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company,
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company,
		$date . ' 00:00:00',
		$date . ' 23:59:59',
		$company
		);

		return $query;
	}
}
