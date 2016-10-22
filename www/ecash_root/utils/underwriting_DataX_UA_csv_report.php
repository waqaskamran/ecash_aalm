<?php
//all unix timestamps
//'2011-11-16' //day AALM went live outside of TSS environment
//'2012-03-08' //2nd time a run was requested
ini_set('max_execution_time',300);
$ts_start = strtotime('2014-06-01'); // 12:01:00');
$ts_today = time();
$ts_end = $ts_today; // 12:00:59'); //time();
$mnth_step = 1;
$mnth_off = 3;

/**
 * Pulls applications in a date range for the purpose of sending to
 * DataX/CRA for loan status updates and/or rules enhancement.
 *
 * @author Randy Klepetko
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

$columns = array(
        'InquiryDate',
        'ID',
        'TransactionID',
        'ApplicationId',
        'PayPeriod',
        'LeadCampaignName',
        'CumulativeAmountCollected',
        'LeadPurchasedIndicator',
        'LeadProviderName',
        'LeadPrice',
        'RequestedLoanAmount',
        'FundingApprovedIndicator',
        'FundedIndicator',
	'FundedLoanAmount',
        'FirstDueDate',
        'SecondDueDate',
        'ThirdDueDate',
        'ReturningCustomerIndicator',
        'NumberOfLoansWithLenders',
        'FirstPaymentDefault',
        'FirstPaymentFatalReturnIndicator',
        'SecondPaymentDefault',
        'ThirdPaymentDefault',
        'ChargeOffIndicator',
        'ChargeOffAmount',
        'CollectionIndicator',
        'RecoveryIndicator',
        'AmountRecoved'
);

$start = $ts_start;
while ($start < $ts_end){
$date_start = date('Y-m-d', $start);
$end = strtotime($date_start.' +'.$mnth_step.' months');
$date_end = date('Y-m-d', $end);
$day_before = date('Y-m-d',strtotime($date_start.' -1 day'));
$months_before = date('Y-m-d',strtotime($date_start.' -'.$mnth_off.' months'));
$date_today = date('Y-m-d', $ts_today);

$file = "/tmp/DataX_UA_Report_{$date_start}-{$date_end}_run_{$date_today}.csv";
file_put_contents($file,'');
//$fp = fopen("/tmp/DataX_UA_Report_{$date_start}-{$date_end}_run_{$date_today}.csv", 'w');
$outstring = '"'.implode($columns,'","').'"';
file_put_contents($file,$outstring."\n",FILE_APPEND);

$query = "(
SELECT bi.sent_package AS sent,
bi.received_package AS receive,
bi.date_created AS inquiry_date,
bi.bureau_inquiry_id AS bureau_inquiry_id,
'' AS track_id,
bi.application_id AS application_id,
ap.income_frequency AS pay_period,
ci.campaign_name AS campaign,
(SELECT SUM(-tr2.amount) FROM transaction_register tr2
    WHERE tr2.application_id = ap.application_id AND tr2.transaction_status = 'complete' AND tr2.amount < 0
    AND tr2.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt2 WHERE tt2.clearing_type IN ('ach','external','card') and company_id = 1)) as amount_paid,
IF (ap.fund_qualified > 0,1,0) AS purchased,
cp.campaign_publisher_name as provider,
IF (ap.price_point IS NULL, 0, ap.price_point) AS price,
IF (ap.fund_requested IS NULL, IF (ap.fund_qualified IS NULL, 0, ap.fund_qualified), ap.fund_requested) AS request_amount,
IF ((SELECT sh1.application_id FROM status_history sh1 where sh1.application_status_id = 121 AND sh1.application_id = ap.application_id LIMIT 1) IS NOT NULL, 1 , 0) AS funding_approved,
IF ((SELECT sh2.application_id FROM status_history sh2 where sh2.application_status_id = 20 AND sh2.application_id = ap.application_id LIMIT 1) IS NOT NULL, 1 , 0) AS funded,
IF ((SELECT sh2.application_id FROM status_history sh2 where sh2.application_status_id = 20 AND sh2.application_id = ap.application_id LIMIT 1) IS NOT NULL, ap.fund_actual, 0) AS fund_amount,
(SELECT es1.date_effective FROM event_schedule es1 WHERE es1.application_id = ap.application_id AND es1.amount_non_principal < 0 ORDER BY es1.date_effective ASC LIMIT 0,1) AS first_pay_date,
(SELECT es2.date_effective FROM event_schedule es2 WHERE es2.application_id = ap.application_id AND es2.amount_non_principal < 0 ORDER BY es2.date_effective ASC LIMIT 1,1) AS second_pay_date,
(SELECT es3.date_effective FROM event_schedule es3 WHERE es3.application_id = ap.application_id AND es3.amount_non_principal < 0 ORDER BY es3.date_effective ASC LIMIT 2,1) AS third_pay_date,
IF (ap.is_react='yes',1,0) AS returning_customer,
(SELECT count(ap1.application_id) FROM application ap1 WHERE ap1.ssn = ap.ssn AND (ap1.application_status_id > 101 OR ap1.application_status_id = 20)) AS  past_loan_count,
IF ((((SELECT transaction_status FROM transaction_register tr3
    WHERE tr3.application_id = ap.application_id AND tr3.amount<0
    AND tr3.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt3 WHERE tt3.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr3.date_effective ASC LIMIT 0,1) != 'failed')
  OR
    ((SELECT transaction_status FROM transaction_register tr13
    WHERE tr13.application_id = ap.application_id AND tr13.amount<0
    AND tr13.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt13 WHERE tt13.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr13.date_effective ASC LIMIT 0,1) IS NULL)), 0 , 1) AS first_default,
IF ((((SELECT arc6.is_fatal FROM transaction_register tr6 LEFT JOIN ach USING (ach_id) LEFT JOIN ach_return_code arc6 USING (ach_return_code_id)
        WHERE tr6.application_id = ap.application_id AND tr6.amount<0
        AND tr6.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt6 WHERE tt6.clearing_type IN ('ach','external','card') and company_id = 1)
        ORDER BY tr6.date_effective ASC LIMIT 0,1) = 'yes')
    OR ((SELECT cpr7.fatal_fail FROM transaction_register tr7 LEFT JOIN card_process USING (card_process_id) LEFT JOIN card_process_response cpr7 USING (reason_code)
        WHERE tr7.application_id = ap.application_id AND tr7.amount<0
        AND tr7.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt7 WHERE tt7.clearing_type IN ('ach','external','card') and company_id = 1)
        ORDER BY tr7.date_effective ASC LIMIT 0,1) = 1))
    , 1 , 0) AS first_fatal,
IF ((((SELECT transaction_status FROM transaction_register tr4
    WHERE tr4.application_id = ap.application_id AND tr4.amount<0
    AND tr4.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt4 WHERE tt4.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr4.date_effective ASC LIMIT 1,1) != 'failed')
  OR
    ((SELECT transaction_status FROM transaction_register tr14
    WHERE tr14.application_id = ap.application_id AND tr14.amount<0
    AND tr14.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt14 WHERE tt14.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr14.date_effective ASC LIMIT 1,1) IS NULL)), 0 , 1) AS second_default,
IF ((((SELECT transaction_status FROM transaction_register tr5
    WHERE tr5.application_id = ap.application_id AND tr5.amount<0
    AND tr5.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt5 WHERE tt5.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr5.date_effective ASC LIMIT 2,1) != 'failed')
  OR
    ((SELECT transaction_status FROM transaction_register tr15
    WHERE tr15.application_id = ap.application_id AND tr15.amount<0
    AND tr15.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type tt15 WHERE tt15.clearing_type IN ('ach','external','card') and company_id = 1)
    ORDER BY tr15.date_effective ASC LIMIT 2,1) IS NULL)), 0 , 1) AS third_default,
    IF ((SELECT sh3.application_id FROM status_history sh3 WHERE sh3.application_status_id IN (112,131,160) AND sh3.application_id = ap.application_id LIMIT 1) IS NOT NULL, 1 , 0) AS charge_off,
IF ((SELECT sh4.application_id FROM status_history sh4 WHERE sh4.application_status_id IN (112,131,160) AND sh4.application_id = ap.application_id LIMIT 1) IS NOT NULL, 
  (SELECT SUM(tr1.amount) FROM transaction_register tr1 WHERE tr1.application_id = ap.application_id AND tr1.transaction_status = 'complete' AND tr1.transaction_type_id NOT IN (18,19))
, 0) AS charge_off_amount,
0 as recovery,
0 as recovery_amount
FROM (SELECT * FROM bureau_inquiry WHERE date_created >= '".$date_start."' AND date_created < '".$date_end."' and bureau_id =2) bi
LEFT JOIN (SELECT * FROM application WHERE date_created >= '".$months_before."' AND date_created < '".$date_end."') ap ON (ap.application_id = bi.application_id)
LEFT JOIN (SELECT * FROM campaign_info WHERE date_created >= '".$months_before."' AND date_created < '".$date_end."') ci on (ci.application_id = bi.application_id)
LEFT JOIN campaigns cm on (ci.campaign_name = cm.campaign_name)
LEFT JOIN campaign_publishers cp on (cm.campaign_publisher_id = cp.campaign_publisher_id)

) UNION (

SELECT bif.sent_package AS sent,
bif.received_package AS receive,
bif.date_created AS inquiry_date,
CONCAT('F',bif.bureau_inquiry_failed_id) AS ID,
'' AS track_id,
bif.application_id AS application_id,
sap.income_frequency AS pay_period,
sap.campaign_name AS campaign,
0 as amount_paid,
0 AS purchased,
cps.campaign_publisher_name as provider,
'' AS price,
sap.requested_amount AS request_amount,
0 AS funding_approved,
0 AS funded,
0 AS fund_amount,
'' AS first_pay_date,
'' AS second_pay_date,
'' AS third_pay_date,
if (sap.application_id > 0,1,0) AS returning_customer,
(SELECT count(ap2.application_id) FROM application ap2 WHERE ap2.ssn = bif.ssn AND (ap2.application_status_id > 101 OR ap2.application_status_id = 20)) AS  past_loan_count,
0 AS first_default,
0 AS first_fatal,
0 AS second_default,
0 AS third_default,
0 AS charge_off,
0 AS charge_off_amount,
0 as recovery,
0 as recovery_amount
FROM (SELECT * FROM bureau_inquiry_failed WHERE date_created >= '".$date_start."' AND date_created < '".$date_end."' AND bureau_id = 2) bif
LEFT JOIN (SELECT * FROM submitted_applications WHERE date_created >= '".$day_before."' AND date_created < '".$date_end."') sap ON (sap.ssn = bif.ssn)
LEFT JOIN campaigns cms on (sap.campaign_name = cms.campaign_name)
LEFT JOIN campaign_publishers cps on (cms.campaign_publisher_id = cps.campaign_publisher_id)
)";

echo "start = ".date('Y-m-d', $start)." \n";
echo "end = ".date('Y-m-d', $end)." \n";
echo "Querying...\n";

$factory = ECash::getFactory();
$db = $factory->getDB();

echo $query."\n";
$statement =  $db->prepare($query);
$statement->execute();

$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

echo "Writing results\n";

//file_put_contents('/tmp/DataX_UA_out.txt',"------------------------------------------------------------------------------\n");
foreach($rows as $row) {
    set_time_limit(30);
    $row['sent'] = str_replace("&","and",htmlspecialchars_decode(gzuncompress(substr($row['sent'], 4)),ENT_QUOTES));
    $row['receive'] = str_replace("&","and",htmlspecialchars_decode(gzuncompress(substr($row['receive'], 4)),ENT_QUOTES));


//    $sent_pk = simplexml_load_string($row['sent']);
//    $sent_pk = json_decode(json_encode((array)$sent_pk), TRUE);

    $recv_pk = simplexml_load_string($row['receive']);
    $recv_pk = json_decode(json_encode((array)$recv_pk), TRUE);

    if (!(is_numeric($row['amount_paid']))) $row['amount_paid'] = 0;
    $row['track_id'] = $recv_pk['TransactionId'];
    unset($row['sent']);
    unset($row['receive']);
//file_put_contents('/tmp/DataX_UA_out.txt',print_r($row,true)."\n",FILE_APPEND);
//file_put_contents('/tmp/DataX_UA_out.txt',print_r($sent_pk,true)."\n",FILE_APPEND);
//file_put_contents('/tmp/DataX_UA_out.txt',print_r($sent_pk['QUERY'],true)."\n",FILE_APPEND);
//file_put_contents('/tmp/DataX_UA_out.txt',print_r($sent_pk['QUERY']['TRACKID'],true)."\n",FILE_APPEND);

//file_put_contents('/tmp/DataX_UA_out.txt',print_r($recv_pk['TrackId'],true)." - ".print_r($row['track_id'],true)."\n",FILE_APPEND);
//file_put_contents('/tmp/DataX_UA_out.txt',print_r($recv_pk,true)."\n",FILE_APPEND);
//file_put_contents('/tmp/DataX_UA_out.txt',"------------------------------------------------------------------------------\n",FILE_APPEND);

    $out_row = array(
        $row['inquiry_date'],
        $row['bureau_inquiry_id'],
        $row['track_id'],
        $row['application_id'],
        $row['pay_period'],
        $row['campaign'],
        $row['amount_paid'],
        $row['purchased'],
        $row['provider'],
        $row['price'],
        $row['request_amount'],
        $row['funding_approved'],
        $row['funded'],
	$row['fund_amount'],
        $row['first_pay_date'],
        $row['second_pay_date'],
        $row['third_pay_date'],
        $row['returning_customer'],
        $row['past_loan_count'],
        $row['first_default'],                   //FirstPaymentDefault
        $row['first_fatal'],                   //FirstPaymentFatalReturnIndicator
        $row['second_default'],                   //SecondPaymentDefault
        $row['third_default'],                   //ThirdPaymentDefault
        $row['charge_off'],
        $row['charge_off_amount'],
        $row['recovery'],
        $row['recovery_amount']
        );

	$outstring = '"'.implode($out_row,'","').'"';
	file_put_contents($file,$outstring."\n",FILE_APPEND);
}
$start = $end;
}
echo "\nDone.";


