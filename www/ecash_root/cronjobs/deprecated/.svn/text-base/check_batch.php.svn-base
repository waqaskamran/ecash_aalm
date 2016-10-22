<?php
require_once(SQL_LIB_DIR.'util.func.php');
require_once(COMMON_LIB_DIR.'pay_date_calc.3.php');
require_once(SQL_LIB_DIR."app_stat_id_from_chain.func.php");
		

function Main()
{
	global $server;
	$log = $server->log;

	try
	{
		$date = date('Y-m-d');
		
		if($argc > 4) 
		{
			$notify_list = $argv[4];
		}
		else 
		{
			$notify_list = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";
		}
		
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$forward_date = $pdc->Get_Business_Days_Forward($date, 1);
		$date = date('Y-m-d', strtotime($date));

		$db = ECash::getMasterDb();
		
		$errors   = array();
		
		$ach_event_types = implode(', ', Fetch_ACH_event_types());
		
		$batch_query = <<<END_SQL
			SELECT
				es.application_id,
				es.company_id,
				(
					CASE
					  WHEN 
					  	(es.amount_principal + es.amount_non_principal) < 0
					  THEN 
					  	'debit'
					  ELSE
					  	'credit'
					END
				) as ach_type,
				SUM((es.amount_principal + es.amount_non_principal)) AS amount
			  FROM
			  	application a,
				application_status ass,
				event_schedule es
			  WHERE 
			  	es.application_id = a.application_id
				AND  a.application_status_id = ass.application_status_id
				AND  es.event_status			= 'scheduled'
				AND  es.company_id				=  {$server->company_id}
				AND  ass.name_short	<> 'hold'
				AND  es.event_type_id IN ({$ach_event_types})
				AND ( 
					es.date_event <= '{$date}'
			    	AND  es.date_effective <= '{$forward_date}'
			    	AND es.date_created <
				      (
				    	SELECT date_started 
						  FROM process_log
						  WHERE business_day = '{$date}'
						  	AND step = 'ach_batchclose'
						  ORDER BY date_started DESC
						  LIMIT 1
					  )
				)
			  GROUP BY application_id
			  HAVING ach_type = 'debit'
			  ORDER BY application_id
END_SQL;
	$cashline_ids            = implode(', ', CB_Get_Cashline_Ids());
	$principal_type_ids      = implode(', ', CB_Get_Transaction_Type_Ids($server->company_id, 'principal'));
	$fee_type_ids            = implode(', ', CB_Get_Transaction_Type_Ids($server->company_id, 'fee'));
	$service_charge_type_ids = implode(', ', CB_Get_Transaction_Type_Ids($server->company_id, 'service_charge'));
	$payments_due_query = <<<END_SQL
			SELECT
				a.application_id 			AS application_id,
				aps.name 					AS status,
				a.application_status_id 	AS application_status_id,
				amnt.ct 					AS is_ach,
				SUM(amnt.principal) 		AS principal,
				SUM(amnt.fees) 				AS fees,
				SUM(amnt.service_charge) 	AS service_charge,
				SUM(amnt.total) 			AS amount_due
			  FROM
			  	application_status 			AS aps,
				application					AS a,
				  (
					SELECT
						es.application_id,
						(@prin  := IF(tt.transaction_type_id IN ($principal_type_ids), es.amount_principal, 0)) AS principal,
						(@fees  := IF(tt.transaction_type_id IN ($fee_type_ids), es.amount_non_principal, 0)) AS fees,
						(@svchg := IF(tt.transaction_type_id IN ($service_charge_type_ids), es.amount_non_principal, 0)) AS service_charge,
						(@prin + @fees + @svchg) AS total,
						IF('ach' = tt.clearing_type,1,0) AS ct
					  FROM
						event_schedule    AS es,
						event_type        AS et,
						transaction_type  AS tt,
						event_transaction AS evt
					  WHERE
					  	es.event_type_id = et.event_type_id
						AND et.event_type_id = evt.event_type_id
						AND tt.transaction_type_id = evt.transaction_type_id
						AND es.company_id IN ({$server->company_id})
						AND et.company_id IN ({$server->company_id})
						AND tt.company_id IN ({$server->company_id})
						AND evt.company_id IN ({$server->company_id})
						AND es.date_effective = '{$forward_date}'
						AND tt.clearing_type IN ('ach','external')
						AND es.amount_principal <= 0
						AND es.amount_non_principal <= 0
				  ) AS amnt
			  WHERE
			  	a.application_status_id   =  aps.application_status_id
				AND amnt.application_id       =  a.application_id
				AND aps.application_status_id NOT IN ($cashline_ids)
			  GROUP BY
			  	application_id, 
			  	status, 
			  	application_status_id, 
			  	is_ach
			  HAVING
			    is_ach = 1
END_SQL;
	
		$query = <<<END_SQL
	(
	SELECT 
		a.application_id as app_id,
		a.*, 
		b.* 
	  FROM 
	    (
	{$batch_query}
		) a
	  LEFT JOIN
		(
	{$payments_due_query}
		) b ON (a.application_id = b.application_id)
	  WHERE
	  	b.application_id is null OR 
		a.amount != b.amount_due
	)
	UNION
	(
	SELECT 
		b.application_id as app_id,
		a.*, 
		b.* 
	  FROM 
		(
	{$payments_due_query}
		) b
	  LEFT JOIN
		(
	{$batch_query}
		) a ON (a.application_id = b.application_id)
	  WHERE
	  	a.application_id is null
	)
END_SQL;
	
		$results = $db->query($query);
		while ($row = $results->fetch(PDO::FETCH_OBJ))
		{
			$errors[] = $row;
		}
	
		$subject = "eCash: Payments Due and Batch inconsistencies.";
		
		if (count($errors)) 
		{
			Email_Report($notify_list, $subject, $errors);
			exit(1);
		} 
		else 
		{
			exit(0);
		}
	
	} 
	catch(Exception $e) 
	{
		echo "check_batch: {$e->getMessage()}\n";
		exit(3);
	}
	
}

function Email_Report($recipients, $subject, $results)
{
	require_once(LIB_DIR . '/CsvFormat.class.php');

	$csv = CsvFormat::getFromArray(array(
		'Application ID',
		'Company',
		'ACH Type',
		'Payments Due Amount',
		'Batch Amount'));

	foreach ($results as $result)
	{
		$csv .= CsvFormat::getFromArray(array(
			$result->app_id,
			$result->company_id,
			$result->ach_type,
			$result->amount_due,
			$result->amount));
	}

	$attachments = array(
		array(
			'method' => 'ATTACH',
			'filename' => 'alert_errors.csv',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_length' => strlen($csv)));

	require_once(LIB_DIR . '/Mail.class.php');
	return eCash_Mail::sendExceptionMessage($recipients, $subject, null, array(), $attachments);
}

function Fetch_ACH_event_types()
{
	$db = ECash::getMasterDb();

		$query = "
				SELECT DISTINCT 
					et.event_type_id 
				FROM 
					event_transaction et, 
					transaction_type tt 
				WHERE
						et.transaction_type_id	= tt.transaction_type_id 
					AND tt.clearing_type		= 'ach'
					AND tt.name_short != 'loan_disbursement'
					AND et.active_status = 'active'
		";
		
		$result = $db->query($query);
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ach_event_id_ary[] = $row['event_type_id'];
		}
		
		return $ach_event_id_ary;
}
function CB_Get_Transaction_Type_Ids($company_list, $type)
{
	$db = ECash::getMasterDb();
	switch( $type )
	{
		case 'principal':
			//				$name_short = " AND	name_short NOT LIKE '%\\_fee%'";
			$name_short = "";
			$principal  = " AND	affects_principal = 'yes'";
			break;
		case 'fee':
			$name_short = " AND	name_short IN ('payment_fee_ach_fail','full_balance')";
			$principal  = "";
			//				$principal  = " AND	affects_principal = 'no'";
			break;
		case 'service_charge':
			$name_short = "AND (name_short = 'converted_sc_event' OR name_short = 'payment_service_chg')
						   OR name_short like '%Fees%'";
			$principal  = "";
			//				$principal  = " AND	affects_principal = 'no'";
			break;
		default:
			$name_short = '';
			$principal  = '';
			break;
	}
	$query = "
		SELECT
			transaction_type_id
		FROM
			transaction_type
		WHERE
			company_id    IN ({$company_list})
		 AND	clearing_type IN ('ach','external')
		{$name_short}
		{$principal}
	";
	$result = $db->query($query);
	$ids = array();
	while( $row = $result->fetch(PDO::FETCH_OBJ))
	{
		$ids[] = $row->transaction_type_id;
	}
	return $ids;
}

function CB_Get_Cashline_Ids() {
	$db = ECash::getMasterDb();

	return array(
		app_stat_id_from_chain($db, 'dequeued::cashline::*root'),
		app_stat_id_from_chain($db, 'pending_transfer::cashline::*root'),
		app_stat_id_from_chain($db, 'queued::cashline::*root'),
	);
}

?>
