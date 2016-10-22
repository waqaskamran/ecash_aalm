<?php
//all unix timestamps
//'2011-11-16' //day AALM went live outside of TSS environment
//'2012-03-08' //2nd time a run was requested
$ts_start = strtotime('2012-11-20'); // 12:01:00');
$ts_end = strtotime('2013-01-25'); // 12:00:59'); //time();
$ts_today = time();

/**
 * Pulls applications in a date range for the purpose of sending to
 * DataX/CRA for loan status updates and/or rules enhancement.
 *
 * @author Justin Foell <justin@foell.org>
 */
set_time_limit(0);
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");
putenv("ECASH_EXEC_MODE=Live");
putenv("ECASH_COMMON_DIR=/virtualhosts/ecash_common.cfe/");
$y = date('Y-m-d');
putenv("YESTERDAY==$y");  // `date --date="yesterday" +%Y-%m-%d`

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once dirname(realpath(__FILE__)) . '/../server/code/bureau_query.class.php';
$server = ECash::getServer();
ini_set('memory_limit','2048M');

/*
        REQUIRED FIELDS
                ApplicationID       (see: "Unique Identifier")
                FirstName           (see: "NameFirst")
                LastName            (see: "NameLast")
                DateOfBirth         (see: "DOB")
                Address             (see: "Street1")
                City
                State
                Zip
                SSN
                AccountABA          (see: "BankABA")
                AccountDDA          (see: "BankAcctNumber")
                MonthlyIncome       a.income_monthly
                RequestedLoanAmount a.fund_requested
                RequestedDueDate        (see: "Fund Date") if not, then value from a.date_first_payment
                Status  (see: "application_status")
                Inquiry Type (see: "inquiry_type")

        ADDITIONAL FIELDS  (come from bureau_xml_fields table)
                Auto Fund
                BAVSegment Code
                Comprehensive Verification Index
                CreditOptics Score
                CreditOptics Designation
                Global Decision Result
                Global Decision IDV Bucket
                Global Decision BAV Bucket
                Global Decision CRA Bucket
                Global Decision IDA Bucket
                Global Decision Perf Bucket
                Global Decision Loan Amount
                Auto Fund Decision Bucket
                Auto Fund Decision Result

*/
$mapped_rowdata_novalues = array('AutoFund'=>'',
                                 'Bavsegment.Code'=>'',
                                 'ComprehensiveVerificationIndex'=>'',
                                 'creditOptics.Score'=>'',
                                 'creditOptics.Designation' => '',
                                 'GlobalDecision.Result'=>'',
                                 'GlobalDecision.IDVBucket'=>'',
                                 'GlobalDecision.BAVBucket'=>'',
                                 'GlobalDecision.CRABucket'=>'',
                                 'GlobalDecision.IDABucket'=>'',
                                 'GlobalDecision.PerfBucket'=>'',
                                 'GlobalDecision.LoanAmount'=>'',
                                 'AutoFundDecision.Bucket'=>'',
                                 'AutoFundDecision.Result'=>'');


$columns = array(
        'Date Bought', //InquiryDate',
        'Inquiry Date',
        'SSN',
        'NameFirst',
        'NameMiddle',
        'NameLast',
        'Street1',
        'Street2',
        'City',
        'State',
        'Zip',
        'PhoneHome',
        'PhoneCell',
        'PhoneWork',
        'PhoneExt',
        'Email',
        'DOB',
        'DriverLicenseNumber',
        'DriverLicenseState',
        'WorkName',
        'WorkStreet1',
        'WorkStreet2',
        'WorkCity',
        'WorkState',
        'WorkZip',
        'BankName',
        'BankABA',
        'BankAcctNumber',
        'PayPeriod',
        'Funded',
        'FundAmount',
        'TotalCollected',
        'FirstPaymentFailed',
        'FirstPaymentAndReattsFailed',
        'FirstPaymentReatt1Date',
        'FirstPaymentReatt1Collected',
        'FirstPaymentReatt2Date',
        'FirstPaymentReatt2Collected',
        'SecondPaymentFailed',
        'SecondPaymentAndReattsFailed',
        'ThirdPaymentFailed',
        'ThirdPaymentAndReattsFailed',
        'Lead Source', // CampaignName',
        'Fund Date',
        'New Lead',
        'Promo ID',
        'Promo Sub ID',
        'IP Address',
//        'Source URL',
        '# of successful payments',
//        'COGS',
//        'net profit',
//        'Balance Paid',
                'MonthlyIncome',
                'RequestedLoanAmount',
        'Income Source',
        'Status',
        'Inquiry Type',

        'Auto Fund',
        'BAVSegment Code',
        'Comprehensive Verification Index',
        'CreditOptics Score',
        'CreditOptics Designation',
        'GlobaldecisionResult',
        'IDVBucket',
        'BAVBucket',
        'CRABucket',
        'IDABucket',
        'PerfBucket',
        'Loanamount',
        'AutofunddecisionBucket',
        'AutofunddecisionResult',
        'Unique Identifier',

);

$date_start = date('Ymd', $ts_start);
$date_end = date('Ymd', $ts_end);
$date_today = date('Ymd', $ts_today);
$fp = fopen("/tmp/underwriting_data_{$date_start}-{$date_end}_run_{$date_today}.csv", 'w');
fputcsv($fp, $columns);

$apps_query = "select 
 a.date_created AS date,
 'NULL' as inquiry_date,
 a.ssn,
 a.name_first,
 a.name_middle,
 a.name_last,
 a.street,
 a.unit,
 a.city,
 a.state,
 a.zip_code,
 a.phone_home,
 a.phone_cell,
 a.phone_work,
 a.phone_work_ext,
 a.email,
 a.dob,
 a.legal_id_number,
 a.legal_id_state,
 a.employer_name,
 a.work_address_1,
 a.work_address_2,
 a.work_city,
 a.work_state,
 a.work_zip_code,
 a.bank_name,
 a.bank_aba,
 a.bank_account,
 a.income_frequency,
 IF (fund_actual, '1', '0') AS funded,
 fund_actual AS fund_amount,
 (SELECT SUM(ABS(tl.amount))
  FROM transaction_ledger tl
  WHERE tl.application_id = a.application_id
   AND tl.amount < 0) AS amount_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', es_reatt1.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_date,
 (SELECT -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_collected,
 (SELECT IF (tr_reatt1.transaction_status = 'failed', es_reatt2.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_date,
 (SELECT -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_and_reatt_failed,
 ci.campaign_name,
 a.date_fund_actual,
 IF (a.is_react = 'yes', 'no', 'yes') AS newcustomer,
 ci.promo_id,
 ci.promo_sub_code,
 a.ip_address,
 (SELECT count(ea.amount)
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  WHERE es1.application_id = a.application_id
   AND tr.amount < 0
   AND tr.transaction_status = 'complete'
 ) AS number_of_payments,
 a.income_monthly,
 a.fund_requested,
 a.income_source,
 (select a_s.name FROM application_status a_s where a_s.application_status_id = a.application_status_id) as 'application_status',
 'NULL' inquiry_type, -- b.inquiry_type
 a.application_id,
 '0' bureau_inquiry_id, -- bureau_inquiry_id
 '0' bureau_inquiry_failed_id,
 b.inquiry_type b_inquiry_type,
 bf.inquiry_type bf_inquiry_type
from application a
LEFT JOIN campaign_info ci on (ci.application_id = a.application_id)
LEFT OUTER JOIN bureau_inquiry b ON (b.application_id = a.application_id)
LEFT OUTER JOIN bureau_inquiry_failed bf ON (bf.application_id = a.application_id)

WHERE a.date_created >= ? and a.date_created <= ?
order by a.application_id desc";

$query = "(SELECT
 a.date_created AS date,
 b.date_created as inquiry_date,
 a.ssn,
 a.name_first,
 a.name_middle,
 a.name_last,
 a.street,
 a.unit,
 a.city,
 a.state,
 a.zip_code,
 a.phone_home,
 a.phone_cell,
 a.phone_work,
 a.phone_work_ext,
 a.email,
 a.dob,
 a.legal_id_number,
 a.legal_id_state,
 a.employer_name,
 a.work_address_1,
 a.work_address_2,
 a.work_city,
 a.work_state,
 a.work_zip_code,
 a.bank_name,
 a.bank_aba,
 a.bank_account,
 a.income_frequency,
 IF (fund_actual, '1', '0') AS funded,
 fund_actual AS fund_amount,
 (SELECT SUM(ABS(tl.amount))
  FROM transaction_ledger tl
  WHERE tl.application_id = b.application_id
   AND tl.amount < 0) AS amount_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', es_reatt1.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_date,
 (SELECT -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_collected,
 (SELECT IF (tr_reatt1.transaction_status = 'failed', es_reatt2.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_date,
 (SELECT -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_and_reatt_failed,
 ci.campaign_name,
 a.date_fund_actual,
 IF (a.is_react = 'yes', 'no', 'yes') AS newcustomer,
 ci.promo_id,
 ci.promo_sub_code,
 a.ip_address,
 (SELECT count(ea.amount)
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status = 'complete'
 ) AS number_of_payments,
 a.income_monthly,
 a.fund_requested,
 a.income_source,
 (select a_s.name FROM application_status a_s where a_s.application_status_id = a.application_status_id) as 'application_status',
 b.inquiry_type,
 b.application_id,
 bureau_inquiry_id,
 '0' bureau_inquiry_failed_id
FROM bureau_inquiry b
LEFT OUTER JOIN application a ON (a.application_id = b.application_id)
LEFT JOIN campaign_info ci on (ci.application_id = b.application_id)

WHERE b.date_created >= ? and b.date_created <= ? and b.inquiry_type in ('aalm-perf', 'aalm-perf-nw', 'aalm-mls-1', 'aalm-mls-2', 'aalm-mls-3', 'aalm-mls-4', 'aalm-mls-5', 'aalm-fpr-1', 'aalm-fpr-2'))

UNION
(
SELECT
 a.date_created AS date,
 b.date_created as inquiry_date,
 a.ssn,
 a.name_first,
 a.name_middle,
 a.name_last,
 a.street,
 a.unit,
 a.city,
 a.state,
 a.zip_code,
 a.phone_home,
 a.phone_cell,
 a.phone_work,
 a.phone_work_ext,
 a.email,
 a.dob,
 a.legal_id_number,
 a.legal_id_state,
 a.employer_name,
 a.work_address_1,
 a.work_address_2,
 a.work_city,
 a.work_state,
 a.work_zip_code,
 a.bank_name,
 a.bank_aba,
 a.bank_account,
 a.income_frequency,
 IF (fund_actual, '1', '0') AS funded,
 fund_actual AS fund_amount,
 (SELECT SUM(ABS(tl.amount))
  FROM transaction_ledger tl
  WHERE tl.application_id = b.application_id
   AND tl.amount < 0) AS amount_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', es_reatt1.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_date,
 (SELECT -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt1_collected,
 (SELECT IF (tr_reatt1.transaction_status = 'failed', es_reatt2.date_event, NULL) AS payment_date
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_date,
 (SELECT -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) AS total
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND ea.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 0,1) AS first_payment_reatt2_collected,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 1,1) AS second_payment_and_reatt_failed,
 (SELECT IF (tr.transaction_status = 'failed', 1, 0) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_failed,
 (SELECT !(IF (tr.transaction_status = 'complete', 1, 0) | IF (tr_reatt1.transaction_status = 'complete', 1, 0) | IF (tr_reatt2.transaction_status = 'complete', 1, 0)) AS payment_status
  FROM event_schedule es1
  JOIN transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
  LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
  LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
  LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
  LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status != 'pending'
  GROUP BY es1.date_effective
  ORDER BY es1.date_effective
  LIMIT 2,1) AS third_payment_and_reatt_failed,
 ci.campaign_name,
 a.date_fund_actual,
 IF (a.is_react = 'yes', 'no', 'yes') AS newcustomer,
 ci.promo_id,
 ci.promo_sub_code,
 a.ip_address,
 (SELECT count(ea.amount)
  FROM event_amount ea
  JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
  JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
  WHERE es1.application_id = b.application_id
   AND tr.amount < 0
   AND tr.transaction_status = 'complete'
 ) AS number_of_payments,
 a.income_monthly,
 a.fund_requested,
 a.income_source,
 (select a_s.name FROM application_status a_s where a_s.application_status_id = a.application_status_id) as 'application_status',
 b.inquiry_type,
 b.application_id,
 '0' bureau_inquiry_id,
 bureau_inquiry_failed_id
FROM bureau_inquiry_failed b
LEFT OUTER JOIN application a ON (a.application_id = b.application_id)
LEFT JOIN campaign_info ci on (ci.application_id = b.application_id)

WHERE b.date_created >= ? and b.date_created <= ? and b.inquiry_type in ('aalm-perf', 'aalm-perf-nw', 'aalm-mls-1', 'aalm-mls-2', 'aalm-mls-3', 'aalm-mls-4', 'aalm-mls-5', 'aalm-fpr-1', 'aalm-fpr-2'))";

echo "start = ".date('Y-m-d', $ts_start)." \n";
echo "end = ".date('Y-m-d', $ts_end)." \n";
echo "Querying...\n";

$factory = ECash::getFactory();
$db = $factory->getDB();

echo $apps_query."\n";
$apps_statement =  $db->prepare($apps_query);
$apps_values = array(date('Y-m-d', $ts_start),
                     date('Y-m-d', $ts_end));
$apps_statement->execute($apps_values);

echo "\n----------------------------\n";
echo $query."\n";

$statement =  $db->prepare($query);
$values = array(date('Y-m-d', $ts_start),
                date('Y-m-d', $ts_end),
                date('Y-m-d', $ts_start),
                date('Y-m-d', $ts_end));

$statement->execute($values);
echo "Fetching results...\n";
$apps_rows = $apps_statement->fetchAll(PDO::FETCH_ASSOC);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

echo "Writing results\n";

$missed_apps = array(); // to store the application_ids for sql1 query
foreach($apps_rows as $row) {
        $app_id = $row['application_id'];
        if (!$row['date'] && !$row['ssn']) {
                $missed_apps[] = $app_id;
        }
        unset($row['bureau_inquiry_id']);
        unset($row['bureau_inquiry_failed_id']);
        unset($row['application_id']);
        echo '.';
        if ($row['b_inquiry_type'] || $row['bf_inquiry_type']) {
        } else {
                // insert 12 empty "cells"
                $row[] = ''; $row[] = ''; $row[] = ''; $row[] = '';
                $row[] = ''; $row[] = ''; $row[] = ''; $row[] = '';
                $row[] = ''; $row[] = ''; $row[] = ''; $row[] = '';
                $row[] = $app_id;
                fputcsv($fp, $row);
        }
}

foreach($rows as $row) {
        //pull off the appID
        $app_id = $row['application_id'];
        if (!$row['date'] && !$row['ssn']) {
                $missed_apps[] = $app_id;
        }
        $bureau_inquiry_id = $row['bureau_inquiry_id'];
        $bureau_inquiry_failed_id = $row['bureau_inquiry_failed_id'];
        unset($row['bureau_inquiry_id']);
        unset($row['bureau_inquiry_failed_id']);
        unset($row['application_id']);
        if ($bureau_inquiry_id) {
                $bureau_sql = "SELECT `fieldname`, `value` FROM bureau_xml_fields ".
                              "WHERE application_id = ? and bureau_inquiry_id = ?";
        } else {
                $bureau_sql = "SELECT `fieldname`, `value` FROM bureau_xml_fields ".
                              "WHERE application_id = ? and bureau_inquiry_failed_id = ?";
        }
        $bureau_statement =  $db->prepare($bureau_sql);
        $use_id = ($bureau_inquiry_id) ? $bureau_inquiry_id : $bureau_inquiry_failed_id;
        $values = array($app_id, $use_id);
        $bureau_statement->execute($values);
        $bureau_rows = $bureau_statement->fetchAll(PDO::FETCH_ASSOC);
        $mapped_rowdata = $mapped_rowdata_novalues;
        foreach ($bureau_rows as $bureau_row) {
                $fieldname = $bureau_row['fieldname'];
                if ($fieldname) {
                        $value = $bureau_row['value'];
                        $mapped_rowdata[$fieldname] = $value;
                }
        }
        foreach ($mapped_rowdata as $key=>$mapped_data) {
                $row[$key] = $mapped_data;
        }
        $row[] = $app_id;
        echo '.';
        fputcsv($fp, $row);
}
// echo 'SELECT * from [aalm].[dbo].[application] WHERE authoritative_id IN ('.implode(",", $missed_apps).")\n";

echo "\nDone.";

fclose($fp);

