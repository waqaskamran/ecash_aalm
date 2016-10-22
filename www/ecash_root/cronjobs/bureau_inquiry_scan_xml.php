<?php
    /**
     * Scans applications for records that are in the bureau_inquiry table,
     * but have had their received_package parsed into the bureau_xml_fields table yet
    
     *
     * @author Brian Gillingham <brian.gillingham@gmail.com>
     *
     * USAGE:  from command line or cronjob call, call without any params will run the scan
     * for 100 latest records on just bureau_inquiry table.
     *  ecash cronjobs# php bureau_inquiry_scan_xml.php 
     *
     * PARAM1 = "failed" will run the scan on the bureau_inquiry_failed table
     * PARAM2 = # of inquiry records to process in this run
     */
    
    
    $runfor_failed = (isset($argv[1])) ? (($argv[1] == 'failed') ? true : false) : false;
    $howmany = (isset($argv[2])) ? (is_numeric($argv[2]) ? $argv[2] : 100) : 100;
    
    $start = 0;
    
    set_time_limit(0);
    
    putenv("ECASH_CUSTOMER=AALM");
    putenv("ECASH_CUSTOMER_DIR=/virtualhosts/aalm/ecash3.0/ecash_aalm/");
    putenv("ECASH_EXEC_MODE=Live");
    putenv("ECASH_COMMON_DIR=/virtualhosts/ecash_common.cfe/");
    
    
    require_once dirname(realpath(__FILE__)) . '/../www/config.php';
    require_once dirname(realpath(__FILE__)) . '/../server/code/bureau_query.class.php';
    require_once dirname(realpath(__FILE__)) . '/../server/code/UWLookup.php';
    
    $server = ECash::getServer();
    ini_set('memory_limit','2048M');
    
    $UWlookup = new VendorAPI_Inquiry2UW_Lookup();
    
    $is_failed_str = ($runfor_failed) ? '_failed' : '';
    
    $query = "select
            b.application_id, b.bureau_inquiry".$is_failed_str."_id, inquiry_type,
            uncompress(b.received_package) received_package,
            b.date_created
        from bureau_inquiry".$is_failed_str." b 
        where not b.bureau_inquiry".$is_failed_str."_id in (select distinct bureau_inquiry".$is_failed_str."_id from bureau_xml_fields)
        order by b.date_created DESC
        limit ".$howmany;
    
    echo $query."\n----------\n";
    $factory = ECash::getFactory();
    $db = $factory->getDB();
    $statement =  $db->prepare($query);
    $values = array();
    
    echo "run = ".date('Ymd', time())." \n";
    echo "Querying...\n";
    
    $statement->execute($values);
    echo "Fetching results...\n";
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Writing results\n";
    $bureau_query = new Bureau_Query($db, $server->log);
    $x = 0;
    $id_fieldname = ($runfor_failed) ? 'bureau_inquiry_failed_id' : 'bureau_inquiry_id';
    foreach($rows as $row) {
        try{
            if ($row['received_package'] <> '') {
                $uwSource = $UWlookup->lookupUW($row['inquiry_type']);
                switch ($uwSource) {
                   case 'DATAX':
                        $uwResponse= new ECash_DataX_Responses_Perf();
                        break;
                   case 'FT':
                        $uwResponse= new ECash_FactorTrust_Responses_Perf();
                        break;
                   case 'CL':
                        $uwResponse= new ECash_Clarity_Responses_Perf();
                        break;
                }
                $uwResponse->parseXML($row['received_package']);
                $sql_del = "DELETE FROM bureau_xml_fields WHERE `application_id` = ".$row['application_id'].
                    " AND `".$id_fieldname."` = ".$row[$id_fieldname];
                $del_stm = $db->prepare($sql_del);
                $del_stm->execute();
                $bureau_inquiry_id = ($runfor_failed) ? 0 : $row['bureau_inquiry_id'];
                $bureau_inquiry_failed_id = ($runfor_failed) ? $row['bureau_inquiry_failed_id'] : 0;
                $uwResponse->update_bureau_xml_fields($db, $row['application_id'], $bureau_inquiry_id, $bureau_inquiry_failed_id);
            }
        }
        catch(Exception $e) {
            echo "Exception on application_id = ".$app_id."\n".$e->getMessage()."\n";
        }

        $x++;
        if (($x % 100) == 0) {
            echo $x." bureau_inquiry".$is_failed_str." records processed\n";
        } elseif (($x % 3) == 0) { echo "."; }
    }
    if (!(($x % 100) == 0)) {
        echo $x." bureau_inquiry records processed\n";
    }
    echo "\nParsing XMLs done.\n";

?>

