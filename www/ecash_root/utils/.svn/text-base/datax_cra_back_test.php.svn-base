<?php
/**
 * Pulls applications in a date range for the purpose of sending to
 * DataX/CRA for loan status updates and/or rules enhancement.
 *
 * @author Justin Foell <justin@foell.org>
 */
set_time_limit(0);
putenv("ECASH_CUSTOMER=AALM");
putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");
putenv("ECASH_EXEC_MODE=Dbmysql2");
putenv("ECASH_COMMON_DIR=/virtualhosts/ecash_common.cfe/");
$y = date('Y-m-d');
putenv("YESTERDAY==$y");  // `date --date="yesterday" +%Y-%m-%d`

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once dirname(realpath(__FILE__)) . '/../server/code/bureau_query.class.php';
$server = ECash::getServer();
ini_set('memory_limit','2048M');

//all unix timestamps
//'2011-11-16' //day AALM went live outside of TSS environment
//'2012-03-08' //2nd time a run was requested
$ts_start = strtotime('2010-01-01'); //requested per Michael Fay
$ts_end = time();
$ts_today = time();

$columns = array(
        'Date Bought', //InquiryDate',
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
        'Auto Fund',
        'Promo ID',
        'Promo Sub ID',
        'IP Address',
//        'Source URL',
        '# of successful payments',
//        'COGS',
//        'net profit',
//        'Balance Paid',
        'Unique Identifier',
);

$date_start = date('Ymd', $ts_start);
$date_end = date('Ymd', $ts_end);
$date_today = date('Ymd', $ts_today);
$fp = fopen("/tmp/datax_cra_back_test_{$date_start}-{$date_end}_run_{$date_today}.csv", 'w');
fputcsv($fp, $columns);

                $query = "
select
a.date_created as date,
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
if(fund_actual, '1', '0') as funded,
fund_actual as fund_amount,
(
        select sum(abs(tl.amount))
        from transaction_ledger tl
        where tl.application_id = a.application_id
        and tl.amount < 0
) as amount_collected,
(SELECT
        if(tr.transaction_status = 'failed', 1, 0) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 0,1) as first_payment_failed,
(SELECT
                !(
                if(tr.transaction_status = 'complete', 1, 0) | if(tr_reatt1.transaction_status = 'complete', 1, 0) | if(tr_reatt2.transaction_status = 'complete', 1, 0)
        ) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
        LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
        LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
        LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 0,1) as first_payment_and_reatt_failed,
(SELECT
        if(tr.transaction_status = 'failed', es_reatt1.date_event, NULL) as payment_date
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 0,1) as first_payment_reatt1_date,
        (
                        SELECT
                        -SUM(IF(tr_reatt1.transaction_status = 'complete', ea_reatt1.amount,0)) as total

            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
                    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
                    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
                LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                                AND tr.transaction_status != 'pending'
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 0,1
        ) AS first_payment_reatt1_collected,
(SELECT
        if(tr_reatt1.transaction_status = 'failed', es_reatt2.date_event, NULL) as payment_date
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
        LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
        LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 0,1) as first_payment_reatt2_date,
        (
                        SELECT
                        -SUM(IF(tr_reatt2.transaction_status = 'complete', ea_reatt2.amount,0)) as total
            FROM event_amount ea
            JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
            JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
                    LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
                    LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
                LEFT JOIN event_amount ea_reatt1 ON tr_reatt1.event_schedule_id = ea_reatt1.event_schedule_id
                    LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
                        LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
                    LEFT JOIN event_amount ea_reatt2 ON tr_reatt2.event_schedule_id = ea_reatt2.event_schedule_id
            WHERE
                es1.application_id = a.application_id
                AND ea.amount < 0
                                AND tr.transaction_status != 'pending'
            GROUP BY es1.date_effective
            ORDER BY es1.date_effective
            LIMIT 0,1
        ) AS first_payment_reatt2_collected,
(SELECT
        if(tr.transaction_status = 'failed', 1, 0) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 1,1) as second_payment_failed,
(SELECT
                !(
                if(tr.transaction_status = 'complete', 1, 0) | if(tr_reatt1.transaction_status = 'complete', 1, 0) | if(tr_reatt2.transaction_status = 'complete', 1, 0)
        ) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
        LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
        LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
        LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 1,1) as second_payment_and_reatt_failed,
(SELECT
        if(tr.transaction_status = 'failed', 1, 0) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 2,1) as third_payment_failed,
(SELECT
                !(
                if(tr.transaction_status = 'complete', 1, 0) | if(tr_reatt1.transaction_status = 'complete', 1, 0) | if(tr_reatt2.transaction_status = 'complete', 1, 0)
        ) as payment_status
        from event_schedule es1
        join transaction_register tr ON tr.event_schedule_id = es1.event_schedule_id
        LEFT JOIN event_schedule es_reatt1 ON es_reatt1.origin_id = tr.transaction_register_id
        LEFT JOIN transaction_register tr_reatt1 ON tr_reatt1.event_schedule_id = es_reatt1.event_schedule_id
        LEFT JOIN event_schedule es_reatt2 ON es_reatt2.origin_id = tr_reatt1.transaction_register_id
        LEFT JOIN transaction_register tr_reatt2 ON tr_reatt2.event_schedule_id = es_reatt2.event_schedule_id
        WHERE
            es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status != 'pending'
        GROUP BY es1.date_effective
        ORDER BY es1.date_effective
        LIMIT 2,1) as third_payment_and_reatt_failed,
ci.campaign_name,
a.date_fund_actual,
if (a.is_react = 'yes', 'no', 'yes') as newcustomer,
'no' as autofund,
ci.promo_id,
ci.promo_sub_code,
a.ip_address,
".// a.track_id as sourceurl,
"(SELECT
        count(ea.amount)
FROM event_amount ea
        JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
        JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
WHERE
        es1.application_id = a.application_id
        and tr.amount < 0
        AND tr.transaction_status = 'complete'
) as number_of_payments,
"./* (
        select sum(abs(tl.amount))
        from transaction_ledger tl
        where tl.application_id = a.application_id
        and tl.amount < 0
) as cogs,
(
        select abs(sum(ea.amount))
FROM event_amount ea
        JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
        JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
        where es1.application_id = a.application_id
          AND tr.transaction_status != 'pending'
) as net_profit,
"(SELECT
        sum(abs(ea.amount))
FROM event_amount ea
        JOIN event_schedule es1 ON es1.event_schedule_id = ea.event_schedule_id
        JOIN transaction_register tr ON tr.transaction_register_id = ea.transaction_register_id
WHERE
        es1.application_id = a.application_id
        -- and tr.amount < 0
        AND tr.transaction_status = 'complete'
) as balance_paid,
*/
"a.application_id
from application a
left join campaign_info ci on (ci.application_id = a.application_id)
where a.date_created >= ? and a.date_created <= ?
   ";

$factory = ECash::getFactory();
$db = $factory->getDB();
$statement =  $db->prepare($query);
$values = array(
        date('Y-m-d', $ts_start),
        date('Y-m-d', $ts_end),
);

echo "start = ".date('Y-m-d', $ts_start)." \n";
echo "end = ".date('Y-m-d', $ts_end)." \n";
echo "Querying...\n";

$statement->execute($values);
echo "Fetching results...\n";
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

echo 'Writing results';
$bureau_query = new Bureau_Query($db, $server->log);

foreach($rows as $row)
{
        //pull off the appID
        $app_id = array_pop($row);
                //This may add a significant amount of time to this event [#49280]
                //stolen from loan_data.class.php
                try{
                $inquiry_packages = $bureau_query->getData($app_id, $row['company_id']);
//              unset($row['company_id']); //don't want this in the report
                if(count($inquiry_packages))
                {
                        /**
                         * We retrieve packages Newest to Oldest, so stop on the first match
                         */
                        foreach($inquiry_packages as $package)
                        {
                                if(preg_match('/perf$/', $package->inquiry_type))
                                {
                                        $dataxResponse = new ECash_DataX_Responses_Perf();
                                        $dataxResponse->parseXML($package->received_package);
                                        if($dataxResponse->getLoanAmountDecision())
                                        {
                                                $row['autofund'] = 'Y';
                                        }
                                        if($dataxResponse->getAutoFundDecision())
                                        {
                                                $row['autofund'] = 'Auto';
                                        }

                                        break;
                                }
                        }
                }
                }
                catch(Exception $e)
                {

                }
        /*
        $any_first_three_failed =
                (int)$row['first_payment_failed'] |
                (int)$row['second_payment_failed'] |
                (int)$row['third_payment_failed'];

        unset($row['second_payment_failed']);
        unset($row['third_payment_failed']);

        $row[] = $any_first_three_failed;
        */
        $row[] = $app_id;

        echo '.';
        fputcsv($fp, $row);
}
echo "\nDone.";


fclose($fp);

