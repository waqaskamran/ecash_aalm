<?php

function insert_manual_ecld_return($company_id, $contents)
{
	$db = ECash::getMasterDb();

    $query = "
        INSERT INTO ecld_return
            (   date_created
            ,   company_id
            ,   return_file_content
            ,   return_status
            )
        VALUES
            (   CURRENT_TIMESTAMP
            ,   $company_id
            ,   '$contents'
            ,   'processed'
            )
        ";

    $rc = $db->exec($query);
    return(array($rc,$db->lastInsertId()));
}

function insert_manual_ecld($company_id, $app_id, $es_id, $er_id, $ar_code, $total)
{
	$total = number_format(abs($total), 2, '.', ''); // [tonyc][mantis:8959]

	$db = ECash::getMasterDb();

    $query = "
        INSERT INTO ecld
            (   date_created
            ,   company_id
            ,   application_id
            ,   event_schedule_id
            ,   ecld_return_id
            ,   return_reason_code
            ,   business_date
            ,   amount
            ,   ecld_status
            )
        VALUES
            (   CURRENT_TIMESTAMP
            ,   '$company_id'
            ,   '$app_id'
            ,   '$es_id'
            ,   '$er_id'
            ,   '$ar_code'
            ,   CURRENT_TIMESTAMP
            ,   '$total'
            ,   'returned'
            )
        ON DUPLICATE KEY
        UPDATE  ecld_return_id='$er_id'
            ,   return_reason_code='$ar_code'
            ,   ecld_status='returned'
        ";

    $rc = $db->exec($query);
    return(array($rc,$db->lastInsertId()));
}

?>
