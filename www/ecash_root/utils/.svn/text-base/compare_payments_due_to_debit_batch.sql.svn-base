/*
 * The first part of the union is from the Batch Review Screen and
 * the second part is from the Payments Due Report.  This query is 
 * valid for Mon-Thursday for normal business weeks, but if the next 
 * day is a holiday or a weekend you'll need to add however many days
 * to the two DATE_ADD(CURDATE()) functions to set that date to the 
 * next business day for this to work.
 */

SELECT * FROM 
(	SELECT	es.application_id,
			(	CASE
					WHEN (es.amount_principal + es.amount_non_principal) < 0 THEN 'debit'
					ELSE 'credit'
				END
			) as ach_type,
			SUM((es.amount_principal + es.amount_non_principal)) AS amount
	FROM	application a,
			application_status ass,
			event_schedule es
	WHERE 	es.application_id =   a.application_id
	AND  a.application_status_id = ass.application_status_id
	AND  es.event_status			= 'scheduled'
	AND  es.company_id				=  3
	AND  ass.name_short	<> 'hold'
	AND  es.event_type_id IN (1,2,3,6,11,13,21,23,24,25,26,28)
	AND ( es.date_event <= CURDATE() 
		  AND  es.date_effective <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
		  AND es.date_created < ( 	SELECT date_started 
                        			FROM process_log
                        			WHERE business_day = CURDATE()
                        			AND step = 'ach_batchclose'
                        			ORDER BY date_started DESC
                        			LIMIT 1 )
		) -- Closing Timestamp
	GROUP BY application_id
	HAVING ach_type = 'debit'
	ORDER BY application_id
) a
LEFT OUTER JOIN
(	SELECT	a.application_id 			AS application_id,
			aps.name 					AS status,
			a.application_status_id 	AS application_status_id,
			amnt.ct 					AS is_ach,
			SUM(amnt.principal) 		AS principal,
			SUM(amnt.fees) 				AS fees,
			SUM(amnt.service_charge) 	AS service_charge,
			SUM(amnt.total) 			AS amount_due
	FROM	application_status 			AS aps,
			application					AS a,
			(	SELECT	es.application_id,
					(@prin  := IF(tt.transaction_type_id IN (45,37,29,16,1,25,23,35,13,43,15,39,31,33,2,27), es.amount_principal, 0)) AS principal,
					(@fees  := IF(tt.transaction_type_id IN (16,6), es.amount_non_principal, 0)) AS fees,
					(@svchg := IF(tt.transaction_type_id IN (3,10,12,14,19,21,22,24,26,28,30,32,34,36,38,40), es.amount_non_principal, 0)) AS service_charge,
					(@prin + @fees + @svchg) AS total,
					IF('ach' = tt.clearing_type,1,0) AS ct
				FROM   	event_schedule    AS es,
						event_type        AS et,
						transaction_type  AS tt,
						event_transaction AS evt
				WHERE 	es.event_type_id = et.event_type_id
				AND 	et.event_type_id = evt.event_type_id
				AND 	tt.transaction_type_id = evt.transaction_type_id
				AND 	es.company_id IN ('3')
				AND 	et.company_id IN ('3')
				AND 	tt.company_id IN ('3')
				AND 	evt.company_id IN ('3')
				AND 	es.date_effective = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
				AND tt.clearing_type IN ('ach','external')
				AND es.amount_principal <= 0
				AND es.amount_non_principal <= 0
			) AS amnt
	WHERE 	a.application_status_id   =  aps.application_status_id
	AND   	amnt.application_id       =  a.application_id
	AND   	aps.application_status_id NOT IN (115,116,114)
	GROUP BY application_id, status, application_status_id, is_ach
	HAVING is_ach = 1
) b
ON (a.application_id = b.application_id)
WHERE b.application_id is null
-- WHERE a.amount != b.amount_due