<?php
/*  Usage example:  php -f ecash_engine.php ufc LOCAL batch_maintenance  */

require_once(LIB_DIR."batch_maintenance.class.php");
require_once(SQL_LIB_DIR."ownercode_company_id_map.func.php");
require_once(SQL_LIB_DIR."app_stat_id_from_chain.func.php");

function Main()
{
	global $server;
	$log = $server->log;
	
	$filename = $_SERVER['argv'][4];
	touch ($filename);
	if (!is_writable($filename)) 
	{
		echo "You must pass a filename to write the return file to as the 4th parameter.\n";
		return;
	}

	$db = ECash::getMasterDb();
	
	$qc_sent_id = app_stat_id_from_chain($db, 'sent::quickcheck::collections::customer::*root');
	
	$query_qc_count = <<<END_SQL
	SELECT
		count(*) num_qc
	  FROM application
	  WHERE application_status_id = {$qc_sent_id}
END_SQL;
	
	$query_qc = <<<END_SQL
	SELECT DISTINCT
		e.trans_ref_no as trn, 
		e.amount as amount, 
		e.business_date as business_date, 
		e.bank_aba as bank_aba, 
		e.bank_account as bank_account, 
		e.ecld_id as checkNo
	  FROM 
		application a
		JOIN event_schedule es USING (application_id)
		JOIN transaction_register tr USING (event_schedule_id)
		JOIN ecld e USING (ecld_id)
	  WHERE
	  	a.application_status_id = 136
	  	AND es.event_type_id = 14
	  	AND tr.transaction_status = 'pending'
	  ORDER BY RAND()
	  LIMIT %d
END_SQL;
	
	$result = $db->query($query_qc_count);
	$row = $result->fetch(PDO::FETCH_OBJ);
	$qc_count = $row->num_qc;
	
	$qc_count = ceil($qc_count / 100);
	
	$result = $db->query(sprintf($query_qc, $qc_count));
	$ownercodes = reverse_ownercode_company_id_map($db);
	
	$reasons = array(
		'A', 'B', 'C', 'D', 'E', 'F', 
		'G', 'H', 'I', 'J', 'K', 'L', 
		'M', 'N', 'O', 'P', 'Q', 'R', 
		'S', 'T', 'U', 'V', 'W'
	);
	
	$fp = fopen($filename, 'w');
	fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fwrite($fp, '<ReturnsFile Source="Ecash Auto-Generated" CreateDate="'.date('Y-m-d').'" CreateTime="'.date('H:i:s').'">');
	fwrite($fp, '<Member OwnerCode="'.$ownercodes[$server->company_id].'" Name="'.$server->company.'" AccountNumber="00000000" SerialNumber="">');
	$amount = 0;
	$count = 0;
	while ($row = $result->Fetch_Object_row()) 
	{
		fwrite($fp, '<Transaction TRN="'.$row->trn.'">');
		fwrite($fp, '<Check IRM="'.$row->trn.'" CheckAmount="'.number_format($row->amount, 2, '.', '').'" '.
			'CaptureDate="'.$row->business_date.'" Endpoint="RP" OriginationDate="'.$row->business_date.'" '.
			'RT="'.$row->bank_aba.'" Account="'.$row->bank_account.'" CheckNo="'.$row->checkNo.'">');
		fwrite($fp, '<Return RID="'.date('ymdHis').rand(10000, 99999).'" ReturnDate="'.$row->business_date.'" '.
			'ReturnType="RP" ReasonCode="'.$reasons[rand(0,count($reasons) - 1)].'" ReturnAmount="'.number_format($row->amount, 2, '.', '').'" '.
			'SeqNumber="'.date('His').rand(10000, 99999).'" AltChargeBank="000" AltChargeAccount="0000000000000000" MakerInfo="" RedepositFlag="false" Final="true">');
		fwrite($fp, '<Payor FirstName1="" LastName1="" FirstName2="" LastName2="" Address1="" Address2="" City="" State="" ZIP=""/>');
		fwrite($fp, '</Return>');
		fwrite($fp, '<Remit IRN="" RemitAcc="" PayDueDate="" AppliedAmt="NaN">');
		fwrite($fp, '<UserFields>');
		fwrite($fp, '<UserField ID="1" Value=""/>');
		fwrite($fp, '<UserField ID="2" Value=""/>');
		fwrite($fp, '<UserField ID="3" Value=""/>');
		fwrite($fp, '<UserField ID="4" Value=""/>');
		fwrite($fp, '<UserField ID="5" Value=""/>');
		fwrite($fp, '<UserField ID="6" Value=""/>');
		fwrite($fp, '<UserField ID="7" Value=""/>');
		fwrite($fp, '<UserField ID="8" Value=""/>');
		fwrite($fp, '<UserField ID="9" Value=""/>');
		fwrite($fp, '<UserField ID="10" Value=""/>');
		fwrite($fp, '</UserFields>');
		fwrite($fp, '</Remit>');
		fwrite($fp, '</Check>');
		fwrite($fp, '</Transaction>');
		$amount += $row->amount;
		$count++;
	}
	
	fwrite($fp, '</Member>');
	fwrite($fp, '<ReturnsFileSummaryRecord TotalCheckDollarValue="'.number_format($amount, 2, '.', '').'" TotalReturnsDollarValue="'.number_format($amount, 2, '.', '').'" TotalTransactionCount="'.$count.'"/>');
	fwrite($fp, '</ReturnsFile>');
	
	fclose($fp);
	
	echo "Returns written to ".realpath($filename)."\n";
}

?>
