Select * from 
(SELECT
es.application_id,
abs(es.amount_principal + es.amount_non_principal) as amount,
a.bank_aba,
trim(a.bank_account) as bank_account,
a.bank_account_type,
concat(upper(a.name_last), ', ', upper(a.name_first)) as name,
(
CASE
WHEN (es.amount_principal + es.amount_non_principal) < 0 THEN 'debit'
ELSE 'credit'
END
) as ach_type
FROM
event_schedule es,
application a,
application_status ass
WHERE es.application_id =   a.application_id
AND  a.application_status_id = ass.application_status_id
AND  es.event_status			= 'scheduled'
AND  es.company_id				=  3
AND ass.name_short	<> 'hold'
AND  es.event_type_id IN (1,2,3,6,11,13,21,23,24,25,26,28)
AND ( es.event_type_id IN (1) OR (es.date_event <= '$today' 
AND  es.date_effective <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
AND es.date_created < CURRENT_TIMESTAMP()))
) a
left outer join 
(SELECT
UPPER(c.name_short)     AS company_name,
a.application_id        AS application_id,
a.name_last             AS name_last,
a.name_first            AS name_first,
a.bank_aba              AS aba,
a.bank_account          AS account,
a.company_id            AS company_id,
a.application_status_id AS application_status_id,
a.date_application_status_set as 'dss',
-- Placeholder until card db is up and running
""                    AS card_number,
(es.amount_principal + es.amount_non_principal) AS amount,
a.date_first_payment AS current_due_date,
(CASE
-- Is this an adjustment instead of a loan disbursement?
WHEN (SELECT COUNT(*)
FROM  transaction_type          AS tt4,
event_schedule                  AS es4,
event_transaction               AS et4
WHERE tt4.transaction_type_id    =  et4.transaction_type_id
AND  es4.event_type_id          =  et4.event_type_id
AND  (tt4.clearing_type         =  'adjustment'
OR   tt4.name_short          LIKE 'refund%')
AND  es4.event_schedule_id      =  es.event_schedule_id
) > 0 AND ((es.amount_principal >  0) OR (es.amount_non_principal >  0))
THEN 'Refund'
-- is there a previous failure for this app?
WHEN (SELECT COUNT(*)
FROM  transaction_register    AS tr3,
transaction_type        AS tt3
WHERE tr3.transaction_type_id =  tt3.transaction_type_id
AND  tr3.application_id      =  a.application_id
AND  tt3.name_short          =  'loan_disbursement'
AND  tr3.transaction_status  =  'failed'
AND  tr3.date_effective      <  CURDATE()
) > 0
THEN 'Resend'
-- Is the react column set to yes?
WHEN a.is_react = 'yes'
THEN 'React'
-- None of the above
ELSE 'New'
END) AS loan_type
FROM
application             AS a,
company                 AS c,
loan_type               AS lt,
event_schedule          AS es,
application_status      AS aps,
event_type              AS et
WHERE
a.company_id            =  c.company_id
AND	a.loan_type_id          =  lt.loan_type_id
AND	a.application_id        =  es.application_id
AND	es.event_type_id        =  et.event_type_id
AND	a.application_status_id =  aps.application_status_id
AND	es.date_event           =  CURDATE()
AND	et.name_short           IN ('loan_disbursement','adjustment_external','refund', 'refund_3rd_party')
AND	lt.name_short           IN ('standard')
AND	c.company_id            IN (3)
ORDER BY dss) b
on (a.application_id = b.application_id)
where b.application_id is null